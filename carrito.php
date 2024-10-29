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

// Procesar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_to_cart':
                if (isset($_POST['product_id'], $_POST['quantity'])) {
                    $product_id = $_POST['product_id'];
                    $quantity = $_POST['quantity'];
                    
                    $cartHandler = new CartHandler($conn, $user_id, $product_id, $quantity);
                    $result = $cartHandler->addToCart($product_id, $quantity);
                    echo json_encode($result);
                    exit;
                }
                break;
            case 'update_quantity':
                if (isset($_POST['product_id'], $_POST['quantity'])) {
                    $product_id = $_POST['product_id'];
                    $quantity = $_POST['quantity'];
                    
                    $cartHandler = new CartHandler($conn, $user_id, $product_id, $quantity);
                    $result = $cartHandler->updateProductQuantity($product_id, $quantity, $cart_id);
                    echo json_encode($result);
                    exit;
                }
                break;
            case 'remove_product':
                if (isset($_POST['product_id'])) {
                    $product_id = $_POST['product_id'];
                    
                    $delete_sql = "DELETE FROM Carrito_Producto 
                                 WHERE ID_Carrito = ? AND ID_Producto = ?";
                    $delete_stmt = $conn->prepare($delete_sql);
                    $delete_stmt->bind_param("ii", $cart_id, $product_id);
                    
                    $response = array(
                        'success' => $delete_stmt->execute(),
                        'message' => $delete_stmt->error ? $delete_stmt->error : 'Producto eliminado'
                    );
                    echo json_encode($response);
                    exit;
                }
                break;
        }
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
        $('.increase-quantity').click(function() {
    const input = $(this).siblings('.quantity-input');
    const newValue = parseInt(input.val()) + 1;
    const max = parseInt(input.attr('max'));
    if (newValue <= max) {
        updateQuantity(
            input.data('product-id'),
            input.data('cart-id'),
            newValue
        );
    }
});
    $(document).ready(function() {
        // Función para actualizar cantidad
        function updateQuantity(productId, cartId, newQuantity) {
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: {
                    action: 'update_quantity',
                    product_id: productId,
                    cart_id: cartId,
                    quantity: newQuantity
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.message || 'Error al actualizar la cantidad');
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        alert('Error al procesar la respuesta del servidor');
                    }
                },
                error: function() {
                    alert('Error de conexión al actualizar la cantidad');
                }
            });
        }
        });

        $('.decrease-quantity').click(function() {
            const input = $(this).siblings('.quantity-input');
            const newValue = parseInt(input.val()) - 1;
            if (newValue >= 1) {
                updateQuantity(
                    input.data('product-id'),
                    input.data('cart-id'),
                    newValue
                );
            }
        });

        // Manejar cambio directo en el input de cantidad
        $('.quantity-input').change(function() {
            const newValue = parseInt($(this).val());
            const max = parseInt($(this).attr('max'));
            if (newValue >= 1 && newValue <= max) {
                updateQuantity(
                    $(this).data('product-id'),
                    $(this).data('cart-id'),
                    newValue
                );
            }
        });

        // Función para eliminar producto
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
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            // Eliminar el elemento del DOM y recargar si el carrito está vacío
                            $(`#product-${productId}`).fadeOut(300, function() {
                                $(this).remove();
                                if ($('.product-item').length === 0) {
                                    location.reload();
                                }
                            });
                        }
                    }
                });
            }
        });

        function updateQuantity(productId, cartId, newQuantity) {
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: {
                action: 'update_quantity',
                product_id: productId,
                cart_id: cartId,
                quantity: newQuantity
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (!data.success) {
                        alert(data.message || 'Error al actualizar la cantidad');
                        location.reload();
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    alert('Error al procesar la respuesta del servidor');
                }
            },
            error: function() {
                alert('Error de conexión al actualizar la cantidad');
            }
        });
    }

    // Manejar click en aumentar cantidad
    $('.increase-quantity').click(function() {
        const input = $(this).siblings('.quantity-input');
        const newValue = parseInt(input.val()) + 1;
        const max = parseInt(input.attr('max'));
        
        if (newValue <= max) {
            input.val(newValue);
            updatePrices();
            updateQuantity(
                input.data('product-id'),
                input.data('cart-id'),
                newValue
            );
        } else {
            alert('No hay suficiente stock disponible');
        }
    });

    // Manejar click en disminuir cantidad
    $('.decrease-quantity').click(function() {
        const input = $(this).siblings('.quantity-input');
        const newValue = parseInt(input.val()) - 1;
        
        if (newValue >= 1) {
            input.val(newValue);
            updatePrices();
            updateQuantity(
                input.data('product-id'),
                input.data('cart-id'),
                newValue
            );
        }
    });

    // Manejar cambio directo en el input de cantidad
    $('.quantity-input').change(function() {
        const newValue = parseInt($(this).val());
        const max = parseInt($(this).attr('max'));
        
        if (newValue >= 1 && newValue <= max) {
            updatePrices();
            updateQuantity(
                $(this).data('product-id'),
                $(this).data('cart-id'),
                newValue
            );
        } else {
            alert('Cantidad no válida');
            $(this).val(1);
            updatePrices();
        }
    });

    // Función para eliminar producto
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
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        $(`#product-${productId}`).fadeOut(300, function() {
                            $(this).remove();
                            updatePrices();
                            if ($('.product-item').length === 0) {
                                location.reload();
                            }
                        });
                    }
                }
            });
        }
    });
    function actualizarPrecios() {
    let totalCarrito = 0;

    // Recorrer cada producto en el carrito
    $('.product-item').each(function() {
        const inputCantidad = $(this).find('.quantity-input');
        const cantidad = parseInt(inputCantidad.val());
        const precioUnitario = parseFloat($(this).find('.unit-price').data('price'));
        const elementoSubtotal = $(this).find('.subtotal-price');
        
        // Calcular subtotal para este producto
        const subtotal = cantidad * precioUnitario;
        totalCarrito += subtotal;

        // Actualizar visualización del subtotal con formato
        elementoSubtotal.text('$' + subtotal.toLocaleString('es-CO', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }));
    });

    // Actualizar total del carrito con formato
    $('.cart-total').text('$' + totalCarrito.toLocaleString('es-CO', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }));
}

// Actualizar precios cuando cambie la cantidad
$('.quantity-input').change(function() {
    actualizarPrecios();
});

// Botón de aumentar cantidad
$('.increase-quantity').click(function() {
    const input = $(this).siblings('.quantity-input');
    const nuevoValor = parseInt(input.val()) + 1;
    const maximo = parseInt(input.attr('max'));
    
    if (nuevoValor <= maximo) {
        input.val(nuevoValor);
        actualizarPrecios();
        actualizarCantidad(
            input.data('product-id'),
            input.data('cart-id'),
            nuevoValor
        );
    } else {
        alert('No hay suficiente stock disponible');
    }
});

// Botón de disminuir cantidad
$('.decrease-quantity').click(function() {
    const input = $(this).siblings('.quantity-input');
    const nuevoValor = parseInt(input.val()) - 1;
    
    if (nuevoValor >= 1) {
        input.val(nuevoValor);
        actualizarPrecios();
        actualizarCantidad(
            input.data('product-id'),
            input.data('cart-id'),
            nuevoValor
        );
    }
});

// Función para actualizar cantidad en la base de datos
function actualizarCantidad(idProducto, idCarrito, nuevaCantidad) {
    $.ajax({
        url: window.location.href,
        method: 'POST',
        data: {
            action: 'update_quantity',
            product_id: idProducto,
            cart_id: idCarrito,
            quantity: nuevaCantidad
        },
        success: function(response) {
            try {
                const datos = JSON.parse(response);
                if (!datos.success) {
                    alert(datos.message || 'Error al actualizar la cantidad');
                    location.reload();
                }
            } catch (e) {
                console.error('Error al procesar la respuesta:', e);
                alert('Error al procesar la respuesta del servidor');
            }
        },
        error: function() {
            alert('Error de conexión al actualizar la cantidad');
        }
    });
}

// Botón de eliminar producto
$('.remove-product').click(function() {
    const idProducto = $(this).data('product-id');
    const idCarrito = $(this).data('cart-id');
    
    if (confirm('¿Estás seguro de que deseas eliminar este producto del carrito?')) {
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: {
                action: 'remove_product',
                product_id: idProducto,
                cart_id: idCarrito
            },
            success: function(response) {
                const datos = JSON.parse(response);
                if (datos.success) {
                    $(`#product-${idProducto}`).fadeOut(300, function() {
                        $(this).remove();
                        actualizarPrecios();
                        if ($('.product-item').length === 0) {
                            location.reload();
                        }
                    });
                }
            }
        });
    }
});

// Actualizar precios al cargar la página
$(document).ready(function() {
    actualizarPrecios();
});
    </script>
</body>
</html>