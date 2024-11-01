<?php
// Iniciar la sesión
session_start();

// Verificar si el usuario ha iniciado sesión y es un comprador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: IniciarSesion.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Conexión a la base de datos
require 'db_config/db_data.php';

// Función para manejar errores de consulta
function handleQueryError($conn, $query) {
    echo "Error en la consulta: " . $conn->error;
    echo "<br>Consulta: " . $query;
    die();
}

// Obtener información del usuario y su comuna
$sql_user = "SELECT nombre, imagen_usuario, comuna 
             FROM usuario 
             WHERE id_usuario = ?";

$stmt_user = $conn->prepare($sql_user);
if (!$stmt_user) {
    handleQueryError($conn, $sql_user);
}

$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user_info = $result_user->fetch_assoc();

// Consulta de productos sugeridos (valoración >= 4)
$sql_suggestions = "SELECT 
    p.id_producto,
    p.nombre,
    p.precio,
    p.imagen_producto,
    p.categoria,
    p.valoracion,
    v.id_usuario AS id_vendedor,
    v.nombre AS nombre_vendedor,
    v.comuna
FROM 
    producto p
    INNER JOIN usuario v ON p.id_vendedor = v.id_usuario
WHERE 
    v.rol = 0 
    AND v.comuna = ?
    AND p.valoracion >= 4
ORDER BY 
    p.valoracion DESC, p.nombre";

$stmt_suggestions = $conn->prepare($sql_suggestions);
if (!$stmt_suggestions) {
    handleQueryError($conn, $sql_suggestions);
}

$stmt_suggestions->bind_param("s", $user_info['comuna']);
$stmt_suggestions->execute();
$result_suggestions = $stmt_suggestions->get_result();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mercadito - Sugerencias</title>
    <link href="estilos/productos.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Botón para alternar la barra lateral -->
    <form id="sidebarToggleForm" method="post" style="display: inline;">
        <button type="submit" name="toggle_sidebar" id="sidebarToggle" class="toggle-btn">☰</button>
    </form>

    <!-- Barra lateral -->
    <aside id="sidebar" class="sidebar">
        <div class="sidebar-brand">Mercadito</div>
        <nav class="sidebar-menu">
            <a href="productos.php">Ver Productos</a>
            <a href="carrito.php">Mi Carrito</a>
            <a href="mis_pedidos.php">Mis Pedidos</a>
            <a href="panel_comprador.php">Mi Perfil</a>
            <a href="sugerencias.php" class="active">Productos Sugeridos</a>
        </nav>
        <div style="padding: 1rem;">
            Bienvenido, <?php echo htmlspecialchars($user_name); ?><br>
            <a href="InicioSesion.php" style="color: white;">Cerrar Sesión</a>
        </div>
    </aside>

    <!-- Contenido principal -->
    <div id="mainContent" class="main-content">
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-7xl mx-auto">
                <h1 class="text-3xl font-bold mb-8 text-center text-green-600">
                    Productos Sugeridos en <?php echo htmlspecialchars($user_info['comuna']); ?>
                </h1>
                
                <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                    <p class="text-gray-600 text-center mb-4">
                        Estos son los productos mejor valorados por otros compradores en tu zona.
                        Todos tienen una calificación de 4 estrellas o más.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php while ($product = $result_suggestions->fetch_assoc()): ?>
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden transform transition-transform duration-300 hover:scale-105">
                            <div class="relative pb-48">
                                <img 
                                    src="<?php echo htmlspecialchars($product['imagen_producto']); ?>" 
                                    alt="<?php echo htmlspecialchars($product['nombre']); ?>"
                                    class="absolute h-full w-full object-cover"
                                >
                            </div>
                            <div class="p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="text-xl font-semibold">
                                        <?php echo htmlspecialchars($product['nombre']); ?>
                                    </h4>
                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">
                                        <?php echo ucfirst($product['categoria']); ?>
                                    </span>
                                </div>
                                
                                <p class="text-gray-600 mb-2">
                                    Vendedor: 
                                    <a href="perfil_vendedor_mostrar.php?id=<?php echo $product['id_vendedor']; ?>" 
                                       class="text-blue-500 hover:underline">
                                        <?php echo htmlspecialchars($product['nombre_vendedor']); ?>
                                    </a>
                                </p>
                                
                                <!-- Sistema de valoración -->
                                <div class="flex items-center mb-2">
                                    <?php
                                    $rating = round($product['valoracion'], 1);
                                    for ($i = 1; $i <= 5; $i++):
                                        if ($i <= $rating): ?>
                                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        <?php else: ?>
                                            <svg class="w-5 h-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        <?php endif;
                                    endfor; ?>
                                    <span class="ml-2 text-gray-600 text-sm"><?php echo $rating; ?></span>
                                </div>

                                <div class="flex justify-between items-center mt-4">
                                    <span class="text-xl font-bold text-green-600">
                                        $<?php echo number_format($product['precio'], 0, ',', '.'); ?>
                                    </span>
                                    <button 
                                        class="add-to-cart-btn bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors duration-200"
                                        data-product-id="<?php echo $product['id_producto']; ?>">
                                        Agregar al carrito
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <?php if ($result_suggestions->num_rows === 0): ?>
                        <div class="col-span-full text-center py-8">
                            <p class="text-gray-500 text-lg">
                                No hay productos sugeridos disponibles en tu zona en este momento.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div id="cartModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="modal-content bg-white p-6 rounded-lg shadow-lg max-w-sm w-full mx-4">
            <h3 class="text-lg font-bold mb-4">Producto agregado al carrito</h3>
            <p id="modalMessage" class="mb-4"></p>
            <div class="flex justify-end space-x-4">
                <button id="continueShoppingBtn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition-colors duration-200">
                    Seguir comprando
                </button>
                <a href="carrito.php" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors duration-200">
                    Ver carrito
                </a>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configuración del sidebar
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebarToggleForm = document.getElementById('sidebarToggleForm');

        sidebarToggleForm.addEventListener('submit', function(e) {
            e.preventDefault();
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            const isSidebarCollapsed = sidebar.classList.contains('collapsed');
            toggleBtn.style.left = isSidebarCollapsed ? '1rem' : 'calc(var(--sidebar-width) + 1rem)';
        });

        // Configuración de botones "Agregar al carrito"
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', async function() {
                const productId = this.dataset.productId;
                button.disabled = true;

                try {
                    const response = await fetch('agregar_carrito.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `product_id=${productId}&cantidad=1`
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        showModal(data.message || 'Producto agregado al carrito correctamente');
                    } else {
                        showModal(data.message || 'Error al agregar al carrito', true);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showModal('Error al procesar la solicitud', true);
                } finally {
                    button.disabled = false;
                }
            });
        });

        // Funciones del modal
        function showModal(message, isError = false) {
            const modal = document.getElementById('cartModal');
            const modalMessage = document.getElementById('modalMessage');
            
            modalMessage.textContent = message;
            modalMessage.classList.toggle('text-red-600', isError);
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            // Configurar cierre del modal
            const closeModal = () => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            };

            document.getElementById('continueShoppingBtn').onclick = closeModal;
            modal.onclick = (e) => {
                if (e.target === modal) closeModal();
            };
        }
    });
    </script>
</body>
</html>