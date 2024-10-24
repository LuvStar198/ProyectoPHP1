<?php
session_start();

// Verificar si el toggle button fue clickeado
if (isset($_POST['toggle_sidebar'])) {
    $_SESSION['sidebar_collapsed'] = !isset($_SESSION['sidebar_collapsed']) || !$_SESSION['sidebar_collapsed'];
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true]);
        exit;
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Determinar el estado actual del sidebar
$sidebarClass = isset($_SESSION['sidebar_collapsed']) && $_SESSION['sidebar_collapsed'] ? 'collapsed' : '';
$mainContentClass = isset($_SESSION['sidebar_collapsed']) && $_SESSION['sidebar_collapsed'] ? 'expanded' : '';
$footerClass = isset($_SESSION['sidebar_collapsed']) && $_SESSION['sidebar_collapsed'] ? 'expanded' : '';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: IniciarSesion.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

require 'db_config/db_data.php';

// Actualizar estado del pedido si se ha enviado el formulario
if (isset($_POST['actualizar_estado']) && isset($_POST['id_pedido']) && isset($_POST['nuevo_estado'])) {
    $id_pedido = $_POST['id_pedido'];
    $nuevo_estado = $_POST['nuevo_estado'];
    
    $sql_update = "UPDATE Pedido SET estado = ? WHERE ID_Pedido = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $nuevo_estado, $id_pedido);
    $stmt_update->execute();
}

// Obtener pedidos pendientes
$sql_pedidos = "SELECT 
                p.ID_Pedido,
                p.total,
                p.fecha_compra,
                p.estado,
                p.metodo_pago,
                p.direccion_envio,
                u.Nombre as nombre_cliente,
                u.Contacto as telefono_cliente,
                GROUP_CONCAT(CONCAT(pr.nombre, ' (', pp.cantidad, ')') SEPARATOR ', ') as productos
                FROM Pedido p
                JOIN Usuario u ON p.ID_Usuario = u.ID_Usuario
                JOIN Pedido_Producto pp ON p.ID_Pedido = pp.ID_Pedido
                JOIN Producto pr ON pp.ID_Producto = pr.ID_Producto
                WHERE p.estado IN ('pendiente', 'procesando')
                GROUP BY p.ID_Pedido
                ORDER BY p.fecha_compra DESC";

$result_pedidos = $conn->query($sql_pedidos);

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mercadito - Portal de Pedidos</title>
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
            <a href="ventas.php">Ventas</a>
            <a href="productos.php">Ver Productos</a>
            <a href="perfil_vendedor.php">Mi Perfil</a>
            <a href="mis_pedidos.php">Pedidos</a>
        </nav>
        <div style="padding: 1rem;">
            Bienvenido, <?php echo htmlspecialchars($user_name); ?><br>
            <a href="IniciarSesion.php" style="color: white;">Cerrar Sesión</a>
        </div>
    </aside>

    <div id="mainContent" class="main-content <?php echo $mainContentClass; ?>">
        <h1 class="text-2xl font-bold mb-4">Portal de Pedidos</h1>
        
        <!-- Resumen de Pedidos -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-100 p-4 rounded-lg">
                <h3 class="font-semibold">Pedidos Pendientes</h3>
                <p class="text-2xl"><?php echo $result_pedidos->num_rows; ?></p>
            </div>
        </div>

        <!-- Lista de Pedidos -->
        <div class="bg-white shadow-md rounded-lg p-4 overflow-x-auto">
            <h2 class="text-xl font-semibold mb-4">Pedidos Activos</h2>
            
            <?php if ($result_pedidos->num_rows > 0): ?>
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                ID Pedido
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Cliente
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Productos
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Total
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($pedido = $result_pedidos->fetch_assoc()): ?>
                        <tr>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                #<?php echo htmlspecialchars($pedido['ID_Pedido']); ?>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <p class="font-semibold"><?php echo htmlspecialchars($pedido['nombre_cliente']); ?></p>
                                <p class="text-gray-600"><?php echo htmlspecialchars($pedido['telefono_cliente']); ?></p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <p class="text-gray-900"><?php echo htmlspecialchars($pedido['productos']); ?></p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <p class="text-gray-900">$<?php echo number_format($pedido['total'], 2); ?></p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <span class="px-3 py-1 rounded-full text-xs 
                                    <?php echo $pedido['estado'] == 'pendiente' ? 'bg-yellow-200 text-yellow-800' : 'bg-blue-200 text-blue-800'; ?>">
                                    <?php echo ucfirst(htmlspecialchars($pedido['estado'])); ?>
                                </span>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <form method="post" class="inline-block">
                                    <input type="hidden" name="id_pedido" value="<?php echo $pedido['ID_Pedido']; ?>">
                                    <select name="nuevo_estado" class="mr-2 rounded border border-gray-300 p-1">
                                        <option value="pendiente" <?php echo $pedido['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="procesando" <?php echo $pedido['estado'] == 'procesando' ? 'selected' : ''; ?>>Procesando</option>
                                        <option value="completado">Completado</option>
                                        <option value="cancelado">Cancelado</option>
                                    </select>
                                    <button type="submit" name="actualizar_estado" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">
                                        Actualizar
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-gray-600 text-center py-4">No hay pedidos pendientes en este momento.</p>
            <?php endif; ?>
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
                        <li><a href="portal_pedidos.php" class="hover:underline">Portal de Pedidos</a></li>
                        <li><a href="historial_pedidos.php" class="hover:underline">Historial</a></li>
                        <li><a href="estadisticas.php" class="hover:underline">Estadísticas</a></li>
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