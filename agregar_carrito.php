
<?php
//agregar_carrito.php

session_start();
require 'db_config/db_data.php';

header('Content-Type: application/json');

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no autenticado'
    ]);
    exit;
}

// Verificar si se recibieron los datos necesarios
if (!isset($_POST['product_id']) || !isset($_POST['cantidad'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos incompletos'
    ]);
    exit;
}

$product_id = intval($_POST['product_id']);
$quantity = intval($_POST['cantidad']);
$user_id = $_SESSION['user_id'];

try {
    $cartHandler = new CartHandler($conn, $user_id);
    $result = $cartHandler->addToCart($product_id, $quantity);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
    ]);
}

class CartException extends Exception {}

class CartHandler {
    private $conn;
    private $user_id;
    private $product_id;
    private $quantity;

    public function __construct($conn, $user_id, $product_id = null, $quantity = null) {
        if (!$conn) {
            $this->logError("Database connection failed");
            throw new CartException("Error de conexión a la base de datos");
        }
        $this->conn = $conn;
        $this->user_id = $user_id;
        $this->product_id = $product_id;
        $this->quantity = $quantity;
        
        // Verificar la conexión inmediatamente
        if ($this->conn->connect_error) {
            $this->logError("Database connection error: " . $this->conn->connect_error);
            throw new CartException("Error de conexión a la base de datos");
        }
    }

    private function logError($message, $data = null) {
        $log_entry = "[ERROR] " . date('Y-m-d H:i:s') . " - " . $message . "\n";
        $log_entry .= "User ID: " . $this->user_id . "\n";
        if ($data) {
            $log_entry .= "Data: " . print_r($data, true) . "\n";
        }
        $log_entry .= "MySQL Error: " . ($this->conn ? $this->conn->error : 'No connection') . "\n";
        $log_entry .= str_repeat('-', 50) . "\n";
        
        error_log($log_entry, 3, 'cart_errors.log');
    }

    private function getOrCreateCart() {
        try {
            // Iniciar transacción
            $this->conn->begin_transaction();

            // Verificar si existe un carrito activo
            $query = "SELECT ID_Carrito FROM Carrito WHERE ID_Usuario = ? AND estado = 'pendiente' FOR UPDATE";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $this->user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $carrito = $result->fetch_assoc();
            $stmt->close();

            if ($carrito) {
                $this->conn->commit();
                return $carrito['ID_Carrito'];
            }

            // Si no existe, crear uno nuevo
            $insert_query = "INSERT INTO Carrito (ID_Usuario, fecha_modificacion, estado) VALUES (?, NOW(), 'activo')";
            $stmt = $this->conn->prepare($insert_query);
            $stmt->bind_param("i", $this->user_id);
            if (!$stmt->execute()) {
                throw new CartException("Error al crear el carrito: " . $stmt->error);
            }
            $new_cart_id = $this->conn->insert_id;
            $stmt->close();

            // Crear entrada en Carrito_Producto
            $this->addProductToCart($new_cart_id, $this->product_id, $this->quantity);

            $this->conn->commit();
            return $new_cart_id;
        } catch (Exception $e) {
            $this->conn->rollback();
            $this->logError("Exception in getOrCreateCart: " . $e->getMessage());
            throw $e;
        }
    }

    private function verifyProduct($product_id, $quantity) {
        try {
            $query = "SELECT p.ID_Producto, p.nombre, p.Cantidad, p.Precio, u.ID_Usuario as ID_Vendedor 
                     FROM Producto p 
                     JOIN Usuario u ON p.ID_Vendedor = u.ID_Usuario 
                     WHERE p.ID_Producto = ? 
                     FOR UPDATE";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $producto = $result->fetch_assoc();
            $stmt->close();
            
            if (!$producto) {
                throw new CartException("Producto no encontrado");
            }
            
            if ($producto['Cantidad'] < $quantity) {
                throw new CartException("Stock insuficiente. Solo quedan " . $producto['Cantidad'] . " unidades");
            }
            
            return $producto;
            
        } catch (Exception $e) {
            $this->logError("Exception in verifyProduct: " . $e->getMessage());
            throw $e;
        }
    }

    private function addProductToCart($cart_id, $product_id, $quantity) {
        try {
            // Verificar si el producto ya está en el carrito
            $check_query = "SELECT cantidad FROM Carrito_Producto WHERE ID_Carrito = ? AND ID_Producto = ?";
            $stmt = $this->conn->prepare($check_query);
            $stmt->bind_param("ii", $cart_id, $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing_product = $result->fetch_assoc();
            $stmt->close();

            if ($existing_product) {
                // Actualizar cantidad
                $new_quantity = $existing_product['cantidad'] + $quantity;
                $update_query = "UPDATE Carrito_Producto SET cantidad = ? WHERE ID_Carrito = ? AND ID_Producto = ?";
                $stmt = $this->conn->prepare($update_query);
                $stmt->bind_param("iii", $new_quantity, $cart_id, $product_id);
                $stmt->execute();
            } else {
                // Insertar nuevo producto
                $insert_query = "INSERT INTO Carrito_Producto (ID_Carrito, ID_Producto, cantidad) VALUES (?, ?, ?)";
                $stmt = $this->conn->prepare($insert_query);
                $stmt->bind_param("iii", $cart_id, $product_id, $quantity);
                $stmt->execute();
            }
        } catch (Exception $e) {
            $this->logError("Exception in addProductToCart: " . $e->getMessage());
            throw $e;
        }
    }

    public function addToCart($product_id, $quantity) {
        try {
            $this->conn->begin_transaction();

            // Verificar producto
            $producto = $this->verifyProduct($product_id, $quantity);

            // Obtener o crear carrito
            $cart_id = $this->getOrCreateCart();

            // Agregar producto al carrito
            $this->addProductToCart($cart_id, $product_id, $quantity);

            // Actualizar fecha de modificación del carrito
            $update_cart_query = "UPDATE Carrito SET fecha_modificacion = NOW() WHERE ID_Carrito = ?";
            $stmt = $this->conn->prepare($update_cart_query);
            $stmt->bind_param("i", $cart_id);
            $stmt->execute();

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Producto agregado al carrito correctamente',
                'data' => [
                    'product_name' => $producto['nombre'],
                    'quantity' => $quantity
                ]
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            $this->logError("Error in addToCart: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function updateProductQuantity($product_id, $quantity, $cart_id) {
        try {
            $this->conn->begin_transaction();

            // Verificar producto
            $producto = $this->verifyProduct($product_id, $quantity);

            // Actualizar cantidad en Carrito_Producto
            $update_query = "UPDATE Carrito_Producto SET cantidad = ? WHERE ID_Carrito = ? AND ID_Producto = ?";
            $stmt = $this->conn->prepare($update_query);
            $stmt->bind_param("iii", $quantity, $cart_id, $product_id);
            $stmt->execute();

            // Actualizar fecha de modificación del carrito
            $update_cart_query = "UPDATE Carrito SET fecha_modificacion = NOW() WHERE ID_Carrito = ?";
            $stmt = $this->conn->prepare($update_cart_query);
            $stmt->bind_param("i", $cart_id);
            $stmt->execute();

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Cantidad actualizada correctamente',
                'data' => [
                    'product_name' => $producto['nombre'],
                    'quantity' => $quantity
                ]
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            $this->logError("Error in updateProductQuantity: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
?>