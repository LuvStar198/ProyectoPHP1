<?php
session_start();

// Check if the toggle button was clicked
if (isset($_POST['toggle_sidebar'])) {
    $_SESSION['sidebar_collapsed'] = !isset($_SESSION['sidebar_collapsed']) || !$_SESSION['sidebar_collapsed'];
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true]);
        exit;
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Determine the current state of the sidebar
$sidebarClass = isset($_SESSION['sidebar_collapsed']) && $_SESSION['sidebar_collapsed'] ? 'collapsed' : '';
$mainContentClass = isset($_SESSION['sidebar_collapsed']) && $_SESSION['sidebar_collapsed'] ? 'expanded' : '';
$footerClass = isset($_SESSION['sidebar_collapsed']) && $_SESSION['sidebar_collapsed'] ? 'expanded' : '';

// Verificar si el usuario ha iniciado sesión y es un vendedor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 0) {
    header("Location: IniciarSesion.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

require 'db_config/db_data.php';

// Obtener feedback del usuario
$sql_feedback = "SELECT f.comentario, f.fecha, u.Nombre as NombreCliente 
                 FROM Feedback f 
                 JOIN Usuario u ON f.ID_Usuario = u.ID_Usuario 
                 WHERE f.ID_Usuario = ? 
                 ORDER BY f.fecha DESC 
                 LIMIT 5";
$stmt_feedback = $conn->prepare($sql_feedback);
if ($stmt_feedback === false) {
    die("Error en la preparación de la consulta de feedback: " . $conn->error);
}
$stmt_feedback->bind_param("i", $user_id);
$stmt_feedback->execute();
$result_feedback = $stmt_feedback->get_result();

// Obtener historial de ventas del vendedor
$sql_ventas = "SELECT p.nombre AS producto_nombre, pp.cantidad, pe.fecha_compra, pe.total, u.Nombre AS nombre_comprador
               FROM Pedido pe
               JOIN Pedido_Producto pp ON pe.ID_Pedido = pp.ID_Pedido
               JOIN Producto p ON pp.ID_Producto = p.ID_Producto
               JOIN Usuario u ON pe.ID_Usuario = u.ID_Usuario
               WHERE p.ID_Vendedor = ?
               ORDER BY pe.fecha_compra DESC
               LIMIT 10";
$stmt_ventas = $conn->prepare($sql_ventas);
if ($stmt_ventas === false) {
    die("Error en la preparación de la consulta de ventas: " . $conn->error);
}
$stmt_ventas->bind_param("i", $user_id);
$stmt_ventas->execute();
$result_ventas = $stmt_ventas->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mercadito - Panel de Vendedor</title>
    <link href="estilos/perfil-vendedor.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
    <form id="sidebarToggleForm" method="post" style="display: inline;">
        <button type="submit" name="toggle_sidebar" id="sidebarToggle" class="toggle-btn">☰</button>
    </form>
    <aside id="sidebar" class="sidebar <?php echo $sidebarClass; ?>">
        <div class="sidebar-brand">Mercadito</div>
        <nav class="sidebar-menu">
            <a href="agregar_producto.php">Agregar Producto</a>
            <a href="mis_productos.php">Mis Productos</a>
            <a href="ventas.php">Ventas</a>
            <a href="perfil_vendedor.php">Mi Perfil</a>
        </nav>
        <div style="padding: 1rem;">
            Bienvenido, <?php echo htmlspecialchars($user_name); ?><br>
            <a href="InicioSesion.php" style="color: white;">Cerrar Sesión</a>
        </div>
    </aside>
    
    <div id="mainContent" class="main-content <?php echo $mainContentClass; ?>">
        <h1 class="text-2xl font-bold mb-4">Panel de Vendedor</h1>
        <div class="container">
            <!-- Feedback -->
            <h2 class="text-xl font-semibold mt-4 mb-2">Feedback Reciente</h2>
            <div class="bg-white shadow-md rounded-lg p-4">
                <?php if ($result_feedback->num_rows > 0): ?>
                    <?php while($feedback = $result_feedback->fetch_assoc()): ?>
                    <div class="mb-4 pb-4 border-b last:border-b-0">
                        <p class="font-semibold"><?php echo htmlspecialchars($feedback['NombreCliente']); ?></p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($feedback['fecha']); ?></p>
                        <p class="mt-2"><?php echo htmlspecialchars($feedback['comentario']); ?></p>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-gray-600">Aún no hay feedback. ¡Sigue trabajando duro!</p>
                <?php endif; ?>
            </div>

            <!-- Historial de Ventas -->
            <h2 class="text-xl font-semibold mt-4 mb-2">Historial de Ventas Recientes</h2>
            <div class="bg-white shadow-md rounded-lg p-4 overflow-x-auto">
                <?php if ($result_ventas->num_rows > 0): ?>
                    <table class="min-w-full leading-normal">
                        <thead>
                            <tr>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Producto
                                </th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Cantidad
                                </th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Fecha de Compra
                                </th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Total
                                </th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Comprador
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($venta = $result_ventas->fetch_assoc()): ?>
                            <tr>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($venta['producto_nombre']); ?></p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap"><?php echo $venta['cantidad']; ?></p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap"><?php echo date('d/m/Y H:i', strtotime($venta['fecha_compra'])); ?></p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap">$<?php echo number_format($venta['total'], 2); ?></p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($venta['nombre_comprador']); ?></p>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-gray-600">Aún no tienes ventas. ¡Suerte con tu emprendimiento!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <footer id="footer" class="footer bg-[#4a7c59] text-white mt-8 py-8 <?php echo $footerClass; ?>">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h5 class="text-xl font-bold text-yellow-300 mb-4">Mercadito</h5>
                <p>Tu mercado local en línea</p>
            </div>
            <div>
                <h5 class="text-xl font-bold text-yellow-300 mb-4">Enlaces rápidos</h5>
                <ul class="space-y-2">
                    <li><a href="productos.php" class="hover:underline">Productos</a></li>
                    <li><a href="carrito.php" class="hover:underline">Carrito</a></li>
                    <li><a href="mis_pedidos.php" class="hover:underline">Mis Pedidos</a></li>
                </ul>
            </div>
            <div>
                <h5 class="text-xl font-bold text-yellow-300 mb-4">Contacto</h5>
                <p>Email: info@mercadito.com</p>
                <p>Teléfono: (+569) 3583 4815</p>
            </div>
        </div>
        <hr class="my-6 border-white/10">
        <div class="text-center">
            <p>&copy; 2024 Mercadito - Todos los derechos reservados</p>
        </div>
    </div>
</footer>

    <script>
    $(document).ready(function() {
        $('#sidebarToggleForm').on('submit', function(e) {
            e.preventDefault();
            $.post($(this).attr('action'), $(this).serialize(), function(response) {
                if (response.success) {
                    $('#sidebar').toggleClass('collapsed');
                    $('#mainContent').toggleClass('expanded');
                    $('#footer').toggleClass('expanded');
                }
            }, 'json');
        });
    });
    </script>
</body>
</html>