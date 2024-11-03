<?php
// Iniciar sesión y conexión a la base de datos
session_start();
require_once 'db_config/db_data.php';

// Verificar si se ha proporcionado un ID de usuario
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$userId) {
    die('Usuario no especificado');
}

// Obtener información del usuario
$stmt = $conn->prepare("
    SELECT Nombre, Direccion, comuna, imagen_usuario
    FROM Usuario
    WHERE ID_Usuario = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

// Obtener los 5 productos más vendidos del usuario
$stmt = $conn->prepare("
    SELECT p.*, 
           COUNT(pp.ID_Producto) as total_vendidos
    FROM Producto p
    LEFT JOIN Pedido_Producto pp ON p.ID_Producto = pp.ID_Producto
    LEFT JOIN Pedido pe ON pp.ID_Pedido = pe.ID_Pedido
    WHERE p.ID_Vendedor = ?
    GROUP BY p.ID_Producto
    ORDER BY total_vendidos DESC
    LIMIT 5
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$productos_top = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calcular valoración promedio
$stmt = $conn->prepare("
    SELECT AVG(valoracion) as promedio_valoracion
    FROM Producto
    WHERE ID_Vendedor = ?
    AND valoracion IS NOT NULL
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$valoracion = $stmt->get_result()->fetch_assoc();

// Obtener feedback del usuario
$stmt = $conn->prepare("
    SELECT f.*, u.Nombre as nombre_cliente
    FROM Feedback f
    JOIN Usuario u ON f.ID_Usuario = u.ID_Usuario
    WHERE f.ID_Usuario = ?
    ORDER BY f.fecha DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$feedback = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?php echo htmlspecialchars($vendor_info['nombre']); ?> - Mercadito</title>
    <link href="estilos/mostrar-perfil.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Información del vendedor -->
        <div class="perfil-header">
            <div class="perfil-imagen">
                <?php if ($usuario['imagen_usuario']): ?>
                    <img src="<?php echo htmlspecialchars($usuario['imagen_usuario']); ?>" alt="Foto de perfil">
                <?php else: ?>
                    <img src="img/default-profile.png" alt="Foto de perfil por defecto">
                <?php endif; ?>
            </div>
            
            <div class="perfil-info">
                <h1><?php echo htmlspecialchars($usuario['Nombre']); ?></h1>
                <p>
                    <strong>Dirección:</strong> 
                    <?php echo htmlspecialchars($usuario['Direccion']); ?>, 
                    <?php echo htmlspecialchars($usuario['comuna']); ?>
                </p>
                <div class="valoracion">
                    <strong>Valoración promedio:</strong>
                    <?php 
                    $promedio = round($valoracion['promedio_valoracion'], 1);
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $promedio) {
                            echo '★';
                        } else {
                            echo '☆';
                        }
                    }
                    echo " ($promedio)";
                    ?>
                </div>
            </div>
        </div>

        <!-- Productos más vendidos -->
        <div class="productos-top">
            <h2>Productos más vendidos</h2>
            <div class="productos-grid">
                <?php foreach ($productos_top as $producto): ?>
                    <div class="producto-card">
                        <img src="<?php echo htmlspecialchars($producto['imagen_producto']); ?>" 
                             alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                        <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                        <p class="precio">$<?php echo number_format($producto['Precio'], 0, ',', '.'); ?></p>
                        <p class="vendidos">Vendidos: <?php echo $producto['total_vendidos']; ?></p>
                        <div class="valoracion-producto">
                            <?php 
                            $valoracion_producto = round($producto['valoracion'], 1);
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $valoracion_producto) {
                                    echo '★';
                                } else {
                                    echo '☆';
                                }
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Feedback de usuarios -->
        <div class="feedback-section">
            <h2>Opiniones de compradores</h2>
            <?php if ($feedback): ?>
                <?php foreach ($feedback as $comentario): ?>
                    <div class="feedback-card">
                        <div class="feedback-header">
                            <strong><?php echo htmlspecialchars($comentario['nombre_cliente']); ?></strong>
                            <span class="fecha">
                                <?php echo date('d/m/Y', strtotime($comentario['fecha'])); ?>
                            </span>
                        </div>
                        <p><?php echo htmlspecialchars($comentario['comentario']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-feedback">Aún no hay opiniones para este usuario.</p>
            <?php endif; ?>
        </div>
    </div>


    <!-- Botón para volver -->
    <div class="fixed bottom-8 right-8">
        <a href="productos.php" class="bg-blue-500 text-white px-6 py-3 rounded-full shadow-lg hover">Volver</a>
    </div>
</body>
</html>       