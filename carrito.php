<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: IniciarSesion.php");
    exit();
}

require 'db_config/db_data.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Verificar la conexión
if ($conn->connect_error) {
    error_log("Error de conexión: " . $conn->connect_error);
    die("Error de conexión: " . $conn->connect_error);
}

// Obtener el carrito activo del usuario
$sql_cart = "SELECT c.ID_Carrito 
             FROM Carrito c
             WHERE c.ID_Usuario = ? 
             AND c.estado = 'pendiente'
             ORDER BY c.fecha_modificacion DESC 
             LIMIT 1";

$stmt_cart = $conn->prepare($sql_cart);
$stmt_cart->bind_param("i", $user_id);
$stmt_cart->execute();
$result_cart = $stmt_cart->get_result();

// Si no hay carrito pendiente, crear uno nuevo
if ($result_cart->num_rows == 0) {
    $sql_create = "INSERT INTO Carrito (ID_Usuario, fecha_modificacion, estado) 
                   VALUES (?, NOW(), 'pendiente')";
    $stmt_create = $conn->prepare($sql_create);
    $stmt_create->bind_param("i", $user_id);
    
    if (!$stmt_create->execute()) {
        error_log("Error creando nuevo carrito: " . $stmt_create->error);
        die("Error al crear el carrito");
    }
    $cart_id = $conn->insert_id;
} else {
    $cart = $result_cart->fetch_assoc();
    $cart_id = $cart['ID_Carrito'];
}

// Consulta modificada para obtener los productos del carrito
$sql_products = "
    SELECT 
        cp.ID_Carrito,
        cp.ID_Producto,
        p.nombre,
        p.descripcion,
        p.Precio,
        p.imagen_producto,
        cp.cantidad,
        p.Cantidad as stock,
        (p.Precio * cp.cantidad) as subtotal,
        u.Nombre as vendedor_nombre,
        p.categoria as categoria_nombre,
        cp.fecha_agregado
    FROM Carrito_Producto cp
    INNER JOIN Producto p ON cp.ID_Producto = p.ID_Producto
    INNER JOIN Usuario u ON p.ID_Vendedor = u.ID_Usuario
    WHERE cp.ID_Carrito = ?
    ORDER BY cp.fecha_agregado DESC";

// Preparar y ejecutar la consulta de productos
$stmt_products = $conn->prepare($sql_products);
if (!$stmt_products) {
    error_log("Error en la preparación de la consulta: " . $conn->error);
    die("Error al preparar la consulta de productos");
}

$stmt_products->bind_param("i", $cart_id);
if (!$stmt_products->execute()) {
    error_log("Error al ejecutar la consulta de productos: " . $stmt_products->error);
    die("Error al obtener los productos");
}

$result_products = $stmt_products->get_result();

// Agregar logs para diagnóstico
error_log("ID de usuario: " . $user_id);
error_log("ID de carrito: " . $cart_id);
error_log("Número de productos encontrados: " . $result_products->num_rows);

// Clase CartHandler para manejar las operaciones del carrito
class CartHandler {
    private $conn;
    private $user_id;
    
    public function __construct($conn, $user_id) {
        $this->conn = $conn;
        $this->user_id = $user_id;
    }

    public function updateProductQuantity($product_id, $quantity, $cart_id) {
        try {
            // Verificar que el carrito pertenece al usuario
            $check_cart = "SELECT ID_Carrito FROM Carrito WHERE ID_Carrito = ? AND ID_Usuario = ?";
            $stmt_cart = $this->conn->prepare($check_cart);
            $stmt_cart->bind_param("ii", $cart_id, $this->user_id);
            $stmt_cart->execute();
            $result_cart = $stmt_cart->get_result();
            
            if ($result_cart->num_rows === 0) {
                throw new Exception("Carrito no válido");
            }

            // Verificar el stock disponible
            $check_stock = "SELECT Cantidad FROM Producto WHERE ID_Producto = ?";
            $stmt_stock = $this->conn->prepare($check_stock);
            $stmt_stock->bind_param("i", $product_id);
            $stmt_stock->execute();
            $result_stock = $stmt_stock->get_result();
            $stock = $result_stock->fetch_assoc();

            if (!$stock || $quantity > $stock['Cantidad']) {
                throw new Exception("No hay suficiente stock disponible");
            }

            // Verificar si el producto está en el carrito
            $check_product = "SELECT ID_Producto FROM Carrito_Producto WHERE ID_Carrito = ? AND ID_Producto = ?";
            $stmt_check = $this->conn->prepare($check_product);
            $stmt_check->bind_param("ii", $cart_id, $product_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows === 0) {
                throw new Exception("Producto no encontrado en el carrito");
            }

            // Iniciar transacción
            $this->conn->begin_transaction();

            try {
                // Actualizar la cantidad en Carrito_Producto
                $update_product = "UPDATE Carrito_Producto 
                                 SET cantidad = ? 
                                 WHERE ID_Carrito = ? 
                                 AND ID_Producto = ?";
                
                $stmt_product = $this->conn->prepare($update_product);
                if (!$stmt_product) {
                    throw new Exception("Error en la preparación de la consulta: " . $this->conn->error);
                }

                $stmt_product->bind_param("iii", $quantity, $cart_id, $product_id);
                
                if (!$stmt_product->execute()) {
                    throw new Exception("Error al actualizar la cantidad del producto");
                }

                // Actualizar fecha_modificacion en la tabla Carrito
                $update_cart = "UPDATE Carrito 
                              SET fecha_modificacion = NOW() 
                              WHERE ID_Carrito = ?";
                
                $stmt_cart = $this->conn->prepare($update_cart);
                if (!$stmt_cart) {
                    throw new Exception("Error en la preparación de la consulta del carrito");
                }

                $stmt_cart->bind_param("i", $cart_id);
                
                if (!$stmt_cart->execute()) {
                    throw new Exception("Error al actualizar la fecha del carrito");
                }

                // Confirmar transacción
                $this->conn->commit();

                return [
                    'success' => true,
                    'message' => 'Cantidad actualizada correctamente',
                    'new_quantity' => $quantity
                ];

            } catch (Exception $e) {
                // Revertir transacción en caso de error
                $this->conn->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Error en CartHandler::updateProductQuantity: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function removeProduct($product_id, $cart_id) {
        try {
            // Verificar que el carrito pertenece al usuario
            $check_cart = "SELECT ID_Carrito FROM Carrito WHERE ID_Carrito = ? AND ID_Usuario = ?";
            $stmt_cart = $this->conn->prepare($check_cart);
            $stmt_cart->bind_param("ii", $cart_id, $this->user_id);
            $stmt_cart->execute();
            $result_cart = $stmt_cart->get_result();
            
            if ($result_cart->num_rows === 0) {
                throw new Exception("Carrito no válido");
            }

            // Iniciar transacción
            $this->conn->begin_transaction();

            try {
                // Eliminar el producto del carrito
                $delete_product = "DELETE FROM Carrito_Producto 
                                 WHERE ID_Carrito = ? AND ID_Producto = ?";
                
                $stmt_delete = $this->conn->prepare($delete_product);
                if (!$stmt_delete) {
                    throw new Exception("Error en la preparación de la consulta: " . $this->conn->error);
                }

                $stmt_delete->bind_param("ii", $cart_id, $product_id);
                
                if (!$stmt_delete->execute()) {
                    throw new Exception("Error al eliminar el producto del carrito");
                }

                // Actualizar fecha_modificacion en la tabla Carrito
                $update_cart = "UPDATE Carrito 
                              SET fecha_modificacion = NOW() 
                              WHERE ID_Carrito = ?";
                
                $stmt_cart = $this->conn->prepare($update_cart);
                if (!$stmt_cart) {
                    throw new Exception("Error en la preparación de la consulta del carrito");
                }

                $stmt_cart->bind_param("i", $cart_id);
                
                if (!$stmt_cart->execute()) {
                    throw new Exception("Error al actualizar la fecha del carrito");
                }

                // Confirmar transacción
                $this->conn->commit();

                return [
                    'success' => true,
                    'message' => 'Producto eliminado correctamente'
                ];

            } catch (Exception $e) {
                // Revertir transacción en caso de error
                $this->conn->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Error en CartHandler::removeProduct: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}



// Procesar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        $cartHandler = new CartHandler($conn, $user_id);

        switch ($_POST['action']) {
            case 'update_quantity':
                if (!isset($_POST['product_id'], $_POST['quantity'], $_POST['cart_id'])) {
                    throw new Exception('Faltan parámetros necesarios');
                }
                
                $product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
                $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
                $cart_id = filter_var($_POST['cart_id'], FILTER_VALIDATE_INT);
                
                if ($product_id === false || $quantity === false || $cart_id === false) {
                    throw new Exception('Parámetros inválidos');
                }
                
                if ($quantity < 1) {
                    throw new Exception('La cantidad debe ser mayor a 0');
                }
                
                $result = $cartHandler->updateProductQuantity($product_id, $quantity, $cart_id);
                break;

            case 'remove_product':
                if (!isset($_POST['product_id'], $_POST['cart_id'])) {
                    throw new Exception('Faltan parámetros necesarios');
                }
                
                $product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
                $cart_id = filter_var($_POST['cart_id'], FILTER_VALIDATE_INT);
                
                if ($product_id === false || $cart_id === false) {
                    throw new Exception('Parámetros inválidos');
                }
                
                $result = $cartHandler->removeProduct($product_id, $cart_id);
                break;

            default:
                throw new Exception('Acción no válida');
        }
        
        echo json_encode($result);
        exit;

    } catch (Exception $e) {
        error_log("Error en el procesamiento AJAX: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mercadito - Carrito de Compras</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Mi Carrito de Compras</h1>
                <p class="text-gray-600 mt-2">Bienvenido, <?php echo htmlspecialchars($user_name); ?></p>
            </div>
            <a href="productos.php" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-2"></i>Seguir Comprando
            </a>
        </div>

        <?php if ($result_products && $result_products->num_rows > 0): ?>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="divide-y divide-gray-200">
                    <?php 
                    $total = 0;
                    while($product = $result_products->fetch_assoc()): 
                        $total += $product['subtotal'];
                    ?>
                    <div class="p-6 hover:bg-gray-50 transition-colors product-item" 
                         id="product-<?php echo $product['ID_Producto']; ?>">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 w-32 h-32 bg-gray-200 rounded-lg overflow-hidden">
                                <?php if (!empty($product['imagen_producto'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['imagen_producto']); ?>"
                                         alt="<?php echo htmlspecialchars($product['nombre']); ?>"
                                         class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400 text-3xl"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="ml-6 flex-1">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-xl font-medium text-gray-900">
                                            <?php echo htmlspecialchars($product['nombre']); ?>
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Categoría: <?php echo htmlspecialchars($product['categoria_nombre']); ?>
                                        </p>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Vendedor: <?php echo htmlspecialchars($product['vendedor_nombre']); ?>
                                        </p>
                                        <p class="mt-2 text-sm text-gray-700">
                                            <?php echo htmlspecialchars($product['descripcion']); ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-medium text-gray-900 subtotal-price">
                                            $<?php echo number_format($product['subtotal'], 0, '', ','); ?>
                                        </p>
                                        <p class="mt-1 text-sm text-gray-500 unit-price" data-price="<?php echo $product['Precio']; ?>">
                                            $<?php echo number_format($product['Precio'], 0, '', ','); ?> c/u
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="mt-4 flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex items-center border rounded-lg">
                                            <button class="px-3 py-1 text-gray-600 hover:bg-gray-100 decrease-quantity"
                                                    data-product-id="<?php echo $product['ID_Producto']; ?>"
                                                    data-cart-id="<?php echo $cart_id; ?>">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" 
                                                   value="<?php echo $product['cantidad']; ?>"
                                                   class="w-16 text-center border-x quantity-input"
                                                   min="1"
                                                   max="<?php echo $product['stock']; ?>"
                                                   data-product-id="<?php echo $product['ID_Producto']; ?>"
                                                   data-cart-id="<?php echo $cart_id; ?>">
                                            <button class="px-3 py-1 text-gray-600 hover:bg-gray-100 increase-quantity"
                                                    data-product-id="<?php echo $product['ID_Producto']; ?>"
                                                    data-cart-id="<?php echo $cart_id; ?>">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        
                                        <button class="text-red-500 hover:text-red-700 remove-product"
                                                data-product-id="<?php echo $product['ID_Producto']; ?>"
                                                data-cart-id="<?php echo $cart_id; ?>">
                                            <i class="fas fa-trash-alt mr-1"></i>
                                            Eliminar
                                        </button>
                                    </div>
                                    
                                    <?php if ($product['stock'] < 5): ?>
                                    <p class="text-sm text-orange-500">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        ¡Solo quedan <?php echo $product['stock']; ?> unidades!
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="bg-gray-50 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">
                                Total (<?php echo $result_products->num_rows; ?> productos)
                            </p>
                            <p class="text-2xl font-bold text-gray-900 cart-total">
                                $<?php echo number_format($total, 0, '', ','); ?>
                            </p>
                        </div>
                        <a href="checkout.php" 
                           class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 
                                  transition-colors flex items-center">
                            Proceder al Pago
                            <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <div class="text-gray-400 mb-4">
                    <i class="fas fa-shopping-cart text-6xl"></i>
                </div>
                <h2 class="text-2xl font-medium text-gray-900 mb-4">
                    Tu carrito está vacío
                </h2>
                <p class="text-gray-500 mb-8">
                    ¡Agrega algunos productos para comenzar tu compra!
                </p>
                <a href="productos.php" 
                   class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 
                          transition-colors inline-flex items-center">
                    <i class="fas fa-store mr-2"></i>
                    Ir a la Tienda
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        $(document).ready(function() {
    // Función única para actualizar precios
    function actualizarPrecios() {
        let totalCarrito = 0;
        
        $('.product-item').each(function() {
            const cantidad = parseInt($(this).find('.quantity-input').val());
            const precioUnitario = parseFloat($(this).find('.unit-price').data('price'));
            const subtotal = cantidad * precioUnitario;
            totalCarrito += subtotal;
            
            $(this).find('.subtotal-price').text('$' + subtotal.toLocaleString('es-CO', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }));
        });

        $('.cart-total').text('$' + totalCarrito.toLocaleString('es-CO', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }));
    }

    // Función única para actualizar cantidad en el servidor
    function actualizarCantidad(idProducto, idCarrito, nuevaCantidad) {
        return $.ajax({
            url: window.location.href,
            method: 'POST',
            data: {
                action: 'update_quantity',
                product_id: idProducto,
                cart_id: idCarrito,
                quantity: nuevaCantidad
            },
            dataType: 'json'  // Especificamos que esperamos JSON como respuesta
        })
        .done(function(response) {
            if (!response.success) {
                alert(response.message || 'Error al actualizar la cantidad');
                location.reload();
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error('Error en la solicitud:', textStatus, errorThrown);
            alert('Error de conexión al actualizar la cantidad');
            location.reload();
        });
    }

    // Manejador para aumentar cantidad
    $('.increase-quantity').click(function() {
        const input = $(this).siblings('.quantity-input');
        const newValue = parseInt(input.val()) + 1;
        const max = parseInt(input.attr('max'));
        
        if (newValue <= max) {
            input.val(newValue);
            actualizarPrecios();
            actualizarCantidad(
                input.data('product-id'),
                input.data('cart-id'),
                newValue
            );
        } else {
            alert('No hay suficiente stock disponible');
        }
    });

    // Manejador para disminuir cantidad
    $('.decrease-quantity').click(function() {
        const input = $(this).siblings('.quantity-input');
        const newValue = parseInt(input.val()) - 1;
        
        if (newValue >= 1) {
            input.val(newValue);
            actualizarPrecios();
            actualizarCantidad(
                input.data('product-id'),
                input.data('cart-id'),
                newValue
            );
        }
    });

    // Manejador para cambio directo en el input
    $('.quantity-input').change(function() {
        const newValue = parseInt($(this).val());
        const max = parseInt($(this).attr('max'));
        
        if (newValue >= 1 && newValue <= max) {
            actualizarPrecios();
            actualizarCantidad(
                $(this).data('product-id'),
                $(this).data('cart-id'),
                newValue
            );
        } else {
            alert('Cantidad no válida');
            $(this).val(1);
            actualizarPrecios();
        }
    });

    // Manejador para eliminar producto
    $('.remove-product').click(function() {
        const productId = $(this).data('product-id');
        const cartId = $(this).data('cart-id');
        
        if (confirm('¿Estás seguro de que deseas eliminar este producto del carrito?')) {
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: {
                    action: 'remove_product',
                    product_id: productId,
                    cart_id: cartId
                },
                dataType: 'json'
            })
            .done(function(response) {
                if (response.success) {
                    $(`#product-${productId}`).fadeOut(300, function() {
                        $(this).remove();
                        actualizarPrecios();
                        if ($('.product-item').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    alert(response.message || 'Error al eliminar el producto');
                }
            })
            .fail(function() {
                alert('Error de conexión al eliminar el producto');
            });
        }
    });

    // Inicializar precios al cargar
    actualizarPrecios();
});
    </script>
</body>
</html>