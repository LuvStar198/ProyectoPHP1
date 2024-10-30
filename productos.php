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

// Verificar que tenemos la información del usuario
if (!$user_info) {
    die("No se pudo obtener la información del usuario");
}

// Debug: Mostrar información del usuario
error_log("Info usuario: " . print_r($user_info, true));

// Consulta de productos
$sql_products = "SELECT 
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
ORDER BY 
    p.categoria, p.nombre";

$stmt_products = $conn->prepare($sql_products);
if (!$stmt_products) {
    handleQueryError($conn, $sql_products);
}

// Debug: Mostrar el valor de la comuna
error_log("Comuna del usuario: " . $user_info['comuna']);

$stmt_products->bind_param("s", $user_info['comuna']);

if (!$stmt_products->execute()) {
    echo "Error al ejecutar la consulta: " . $stmt_products->error;
    die();
}

$result_products = $stmt_products->get_result();

// Inicializar el array de productos
$products = [];

// Debug: Contar resultados
$count = 0;

// Agrupar productos por categoría
while ($row = $result_products->fetch_assoc()) {
    $products[$row['categoria']][] = $row;
    $count++;
}

// Debug: Mostrar cantidad de productos encontrados
error_log("Cantidad de productos encontrados: " . $count);

// Función de búsqueda actualizada
function searchProducts($searchTerm, $conn, $userComuna) {
    $searchTerm = '%' . $searchTerm . '%';
    
    $sql = "SELECT 
        p.id_producto,
        p.nombre,
        p.precio,
        p.imagen_producto,
        p.categoria,
        p.valoracion,
        v.id_usuario AS id_vendedor,
        v.nombre AS nombre_vendedor
    FROM 
        producto p
        INNER JOIN usuario v ON p.id_vendedor = v.id_usuario
    WHERE 
        (p.nombre LIKE ? OR p.descripcion LIKE ? OR p.categoria LIKE ?)
        AND v.rol = 0 
        AND v.comuna = ?
    ORDER BY 
        p.categoria, p.nombre";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        handleQueryError($conn, $sql);
    }
    
    $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $userComuna);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

// Manejar solicitud de búsqueda AJAX
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $searchResults = searchProducts($searchTerm, $conn, $user_info['comuna']);
    
    header('Content-Type: application/json');
    echo json_encode($searchResults);
    exit;
}

// Cerrar las consultas preparadas
$stmt_user->close();
$stmt_products->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mercadito - Panel de Comprador</title>
    <link href="estilos/productos.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Botón para alternar la barra lateral -->
    <form id="sidebarToggleForm" method="post" style="display: inline;">
        <button type="submit" name="toggle_sidebar" id="sidebarToggle" class="toggle-btn">☰</button>
    </form>
    <!-- Barra lateral -->
    <aside id="sidebar" class="sidebar <?php echo $sidebarClass; ?>">
        <div class="sidebar-brand">Mercadito</div>
        <nav class="sidebar-menu">
            <a href="productos.php">Ver Productos</a>
            <a href="carrito.php">Mi Carrito</a>
            <a href="mis_pedidos.php">Mis Pedidos</a>
            <a href="panel_comprador.php">Mi Perfil</a>
            <a href="sugerencias.php">Productos Sugeridos</a> 
        </nav>
        <div style="padding: 1rem;">
            Bienvenido, <?php echo htmlspecialchars($user_name); ?><br>
            <a href="InicioSesion.php" style="color: white;">Cerrar Sesión</a>
        </div>
    </aside>
    <!-- Contenido principal -->
    <div id="mainContent" class="main-content <?php echo $mainContentClass; ?>">
         <!-- Sección de héroe -->
        <div class="container-fluid py-5 mb-5 hero-header">
            <div class="container py-5">
                <div class="row g-5 align-items-center">
                    <!-- Título y búsqueda -->
                    <div class="col-md-12 col-lg-7">
                        <h4 class="mb-3 text-secondary">100% Organico</h4>
                        <h1 class="mb-5 display-3 text-primary">Frutas y Verduras Organicas</h1>
                         <!-- Barra de búsqueda -->
                        <div class="position-relative mx-auto">
                            <input id="searchInput" class="form-control border-2 border-secondary w-75 py-3 px-4 rounded-pill" type="text" placeholder="Buscar productos">
                            <button id="searchButton" type="button" class="btn btn-primary border-2 border-secondary py-3 px-4 position-absolute rounded-pill text-white h-100" style="top: 0; right: 25%;">Buscar Ahora</button>
                        </div>
                    </div>
                     <!-- Carrusel -->
                    <div class="col-md-12 col-lg-5">
                        <div id="carouselId" class="carousel slide position-relative" data-bs-ride="carousel">
                            <!-- Contenido del carrusel -->
                            <div class="carousel-inner" role="listbox">
                                <div class="carousel-item active rounded">
                                    <img src="img/hero-img-1.png" class="img-fluid w-100 h-100 bg-secondary rounded" alt="First slide">
                                    <a href="#" class="btn px-4 py-2 text-white rounded">Frutas</a>
                                </div>
                                <div class="carousel-item rounded">
                                    <img src="img/hero-img-2.jpg" class="img-fluid w-100 h-100 rounded" alt="Second slide">
                                    <a href="#" class="btn px-4 py-2 text-white rounded">Verduras</a>
                                </div>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#carouselId" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previo</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#carouselId" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Siguiente</span>
                            </button>
                        </div>
                    </div>
                    <!-- Resultados de búsqueda -->
                    <div class="container mt-5">
                        <h2 class="text-2xl font-bold mb-4">Resultados de búsqueda</h2>
                        <div id="searchResults" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <!-- los resultados aparecerán aqui de forma dinamica -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4 py-8 mx-auto">
    <div class="max-w-7xl mx-auto">
        <h2 class="text-2xl font-bold mb-4 text-center">
            Productos locales en <?php echo htmlspecialchars($user_info['comuna']); ?>
        </h2>
        
        <?php foreach (['Fruta', 'Verdura'] as $categoria): ?>
            <div class="mb-8">
                <h3 class="text-2xl font-semibold mt-8 mb-4 text-center"><?php echo $categoria; ?>s</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <?php 
                    if (isset($products[$categoria]) && !empty($products[$categoria])):
                        foreach ($products[$categoria] as $product): 
                    ?>
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                            <div class="relative pb-48">
                                <img 
                                    src="<?php echo htmlspecialchars($product['imagen_producto']); ?>" 
                                    alt="<?php echo htmlspecialchars($product['nombre']); ?>"
                                    class="absolute h-full w-full object-cover"
                                >
                            </div>
                            <div class="p-4">
                                <h4 class="text-xl font-semibold mb-2">
                                    <?php echo htmlspecialchars($product['nombre']); ?>
                                </h4>
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
                                    <span class="ml-2 text-gray-600"><?php echo $rating; ?></span>
                                </div>

                                <div class="flex justify-between items-center mt-4">
                                    <span class="text-xl font-bold text-green-600">
                                        $<?php echo number_format($product['precio'], 0, ',', '.'); ?>
                                    </span>
                                    <button 
                                        class="add-to-cart-btn bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors duration-200"
                                        data-product-id="<?php echo $product['id_producto']; ?>"
                                        data-product-name="<?php echo htmlspecialchars($product['nombre']); ?>"
                                        data-product-price="<?php echo $product['precio']; ?>">
                                        Agregar
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php 
                        endforeach;
                    else: 
                    ?>
                        <p class="col-span-full text-center text-gray-500">
                            No hay <?php echo strtolower($categoria); ?>s disponibles en tu zona.
                        </p>
                    <?php 
                    endif; 
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Footer modificado -->
<footer id="footer" class="footer bg-[#4a7c59] text-white mt-8 py-8 <?php echo $footerClass; ?> relative">
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

    <!-- Menú flotante de contacto -->
    <div class="fixed bottom-6 right-6 z-50">
        <div id="contact-menu" class="flex flex-col-reverse gap-3 transition-all duration-300 opacity-0 scale-0 origin-bottom">
            <a href="https://wa.me/56935834815" 
               target="_blank" 
               class="flex items-center justify-center w-12 h-12 bg-green-500 hover:bg-green-600 text-white rounded-full shadow-lg transition-colors">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                </svg>
            </a>
            <a href="mailto:info@mercadito.com" 
               class="flex items-center justify-center w-12 h-12 bg-red-500 hover:bg-red-600 text-white rounded-full shadow-lg transition-colors">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
            </a>
        </div>
        <button id="toggle-contact" 
                class="flex items-center justify-center w-14 h-14 bg-yellow-300 hover:bg-yellow-400 text-gray-800 rounded-full shadow-lg transition-colors">
            <svg class="w-6 h-6 transform transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
            </svg>
        </button>
    </div>

    <style>
        #contact-menu.active {
            opacity: 1;
            transform: scale(1);
        }
        
        #toggle-contact.active svg {
            transform: rotate(180deg);
        }
    </style>

    <script>
        document.getElementById('toggle-contact').addEventListener('click', function() {
            const menu = document.getElementById('contact-menu');
            const button = document.getElementById('toggle-contact');
            menu.classList.toggle('active');
            button.classList.toggle('active');
        });
    </script>
</footer>

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
    // Elementos del DOM
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const footer = document.getElementById('footer');
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebarToggleForm = document.getElementById('sidebarToggleForm');
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');

    // Configuración inicial
    initializePage();
    setupEventListeners();

    // Función de inicialización
    function initializePage() {
        const isMobile = window.innerWidth <= 768;
        if (!isMobile) {
            adjustSidebarPosition();
        }
    }

    // Configurar event listeners
    function setupEventListeners() {
        // Sidebar toggle
        sidebarToggleForm.addEventListener('submit', handleSidebarToggle);
        
        // Click fuera del sidebar (móvil)
        document.addEventListener('click', handleOutsideClick);
        
        // Resize window
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(handleWindowResize, 250);
        });

        // Búsqueda
        searchInput.addEventListener('input', debounce(handleSearch, 300));

        // Botones de agregar al carrito
        setupAddToCartButtons();
    }

    // Manejadores de eventos
    function handleSidebarToggle(event) {
        event.preventDefault();
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
            sidebar.classList.toggle('active');
        } else {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            footer.classList.toggle('expanded');
            adjustSidebarPosition();
        }
    }

    function handleOutsideClick(event) {
        const isMobile = window.innerWidth <= 768;
        if (isMobile && 
            !sidebar.contains(event.target) && 
            !toggleBtn.contains(event.target)) {
            sidebar.classList.remove('active');
        }
    }

    function handleWindowResize() {
        const isMobile = window.innerWidth <= 768;
        
        if (!isMobile) {
            sidebar.classList.remove('active');
            adjustSidebarPosition();
        } else {
            toggleBtn.style.left = '1rem';
        }
    }

    async function handleSearch() {
        const searchTerm = searchInput.value.trim();
        if (searchTerm.length < 2) {
            searchResults.innerHTML = '';
            return;
        }

        try {
            const response = await fetch(`${window.location.pathname}?search=${encodeURIComponent(searchTerm)}`);
            if (!response.ok) throw new Error('Error en la búsqueda');
            
            const results = await response.json();
            displaySearchResults(results);
        } catch (error) {
            console.error('Error en la búsqueda:', error);
            searchResults.innerHTML = '<p class="text-red-500">Error al realizar la búsqueda</p>';
        }
    }

    // Funciones auxiliares
    function adjustSidebarPosition() {
        toggleBtn.style.left = sidebar.classList.contains('collapsed') 
            ? '1rem' 
            : 'calc(var(--sidebar-width) + 1rem)';
    }

    function displaySearchResults(results) {
        if (!Array.isArray(results) || results.length === 0) {
            searchResults.innerHTML = '<p class="text-gray-600 text-center">No se encontraron resultados.</p>';
            return;
        }

        const resultsHTML = results.map(product => {
            const rating = Math.round(product.valoracion * 10) / 10;
            const fullStars = Math.floor(rating);
            const emptyStars = 5 - fullStars;

            const starsHTML = Array(fullStars).fill('★').concat(Array(emptyStars).fill('☆')).join('');

            return `
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <img src="${escapeHtml(product.imagen_producto)}" 
                         alt="${escapeHtml(product.nombre)}" 
                         class="w-full h-48 object-cover">
                    <div class="p-4">
                        <h4 class="font-semibold">${escapeHtml(product.nombre)}</h4>
                        <p class="text-gray-600">
                            Vendedor: 
                            <a href="perfil_vendedor_mostrar.php?id=${escapeHtml(product.id_vendedor)}" 
                               class="text-blue-500 hover:underline">
                                ${escapeHtml(product.NombreVendedor)}
                            </a>
                        </p>
                        <div class="flex items-center mb-2">
                            <span class="text-yellow-500">${starsHTML}</span>
                            <span class="ml-1">${rating}</span>
                        </div>
                        <p class="text-green-600 font-bold mt-2">
                            $${parseFloat(product.Precio).toLocaleString('es-CL')}
                        </p>
                        <button class="add-to-cart-btn mt-2 w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                                data-product-id="${escapeHtml(product.id_producto)}">
                            Agregar al carrito
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        searchResults.innerHTML = resultsHTML;
        setupAddToCartButtons();
    }

    function setupAddToCartButtons() {
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', handleAddToCart);
        });
    }

    async function handleAddToCart(event) {
        const button = event.currentTarget;
        const productId = button.dataset.productId;
        
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
            console.error('Error al agregar al carrito:', error);
            showModal('Error al procesar la solicitud. Por favor, intente nuevamente.', true);
        } finally {
            button.disabled = false;
        }
    }

    // Utilidades
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

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