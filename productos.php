<?php
// Iniciar la sesión
session_start();


// Manejo de la barra lateral
if (isset($_POST['toggle_sidebar'])) {
// Alternar el estado de la barra lateral
    $_SESSION['sidebar_collapsed'] = !isset($_SESSION['sidebar_collapsed']) || !$_SESSION['sidebar_collapsed'];
    // Manejar solicitudes AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true]);
        exit;
    }
// Redireccionar para solicitudes no-AJAX
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Determinar el estado actual de la barra lateral
$sidebarClass = isset($_SESSION['sidebar_collapsed']) && $_SESSION['sidebar_collapsed'] ? 'collapsed' : '';
$mainContentClass = isset($_SESSION['sidebar_collapsed']) && $_SESSION['sidebar_collapsed'] ? 'expanded' : '';
$footerClass = isset($_SESSION['sidebar_collapsed']) && $_SESSION['sidebar_collapsed'] ? 'expanded' : '';

// funcionalidad de busqueda
function searchProducts($searchTerm, $conn) {
    $searchTerm = '%' . $searchTerm . '%';

// Obtener productos de vendedores
    $sql = "SELECT p.ID_Producto, p.nombre, p.Precio, p.imagen_producto, p.categoria, p.valoracion,
                   v.ID_Usuario AS ID_Vendedor, v.Nombre AS NombreVendedor, c.Nombre AS NombreComuna
            FROM Producto p
            JOIN Usuario v ON p.ID_Vendedor = v.ID_Usuario
            JOIN Comuna c ON v.ID_Comuna = c.ID_Comuna
            WHERE (p.nombre LIKE ? OR p.descripcion LIKE ? OR p.categoria LIKE ?)
            AND v.rol = 0  
            ORDER BY p.categoria, p.nombre";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
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
    $searchResults = searchProducts($searchTerm, $conn);
    
    header('Content-Type: application/json');
    echo json_encode($searchResults);
    exit;
}

// Verificar si el usuario ha iniciado sesión y es un comprador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: IniciarSesion.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Conexión a la base de datos

require 'db_config/db_data.php';

// Obtener información del usuario
$sql_user = "SELECT Nombre, imagen_usuario, Comuna FROM Usuario WHERE ID_Usuario = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user_info = $result_user->fetch_assoc();

// Obtener productos de vendedores en la misma comuna
$sql_products = "SELECT p.ID_Producto, p.nombre, p.Precio, p.imagen_producto, p.categoria, p.valoracion,
                        v.ID_Usuario AS ID_Vendedor, v.Nombre AS NombreVendedor
                 FROM Producto p
                 JOIN Usuario v ON p.ID_Vendedor = v.ID_Usuario
                 WHERE v.rol = 0  
                 ORDER BY p.categoria, p.nombre";

$stmt_products = $conn->prepare($sql_products);

if ($stmt_products === false) {
die("Error en la preparación de la consulta: " . $conn->error);
}

if (!$stmt_products->execute()) {
die("Error al ejecutar la consulta: " . $stmt_products->error);
}

$result_products = $stmt_products->get_result();

$products = [];
while ($row = $result_products->fetch_assoc()) {
$products[$row['categoria']][] = $row;
}

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
            <a href="sugerencias.php">Perfiles Sugeridos</a> 
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

    <div class="container-fluid px-4 py-8 mx-auto transition-all duration-300 ease-in-out">
    <div class="max-w-7xl mx-auto">
        <h2 class="text-2xl font-bold mb-4 text-center">
            Productos locales
            <?php
            if (isset($user_info['Comuna']) && !empty($user_info['Comuna'])) {
                echo " en " . htmlspecialchars($user_info['Comuna']);
            }
            ?>
        </h2>
        
        <?php foreach (['Fruta', 'Verdura'] as $categoria): ?>
        <h3 class="text-2xl font-semibold mt-8 mb-4 text-center"><?php echo $categoria; ?>s</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 max-w-7xl mx-auto px-4">
            <?php foreach ($products[$categoria] ?? [] as $product): ?>
                <div class="bg-white border border-gray-200 rounded-lg shadow hover:shadow-lg transition-shadow duration-300">
                    <a href="#" class="block">
                        <img class="w-full h-48 object-cover rounded-t-lg" src="<?php echo htmlspecialchars($product['imagen_producto']); ?>" alt="<?php echo htmlspecialchars($product['nombre']); ?>" />
                    </a>
                    <div class="p-4">
                        <a href="#" class="block">
                            <h5 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($product['nombre']); ?></h5>
                        </a>
                        <p class="text-sm text-gray-600 mb-2">
                            Vendedor: 
                            <a href="perfil_vendedor_mostrar.php?id=<?php echo $product['ID_Vendedor']; ?>" class="text-blue-500 hover:underline">
                                <?php echo htmlspecialchars($product['NombreVendedor']); ?>
                            </a>
                        </p>
                        <div class="flex items-center mb-3">
                            <div class="flex items-center space-x-1">
                                <?php
                                $rating = round($product['valoracion'], 1);
                                $fullStars = floor($rating);
                                $emptyStars = 5 - $fullStars;
                                for ($i = 0; $i < $fullStars; $i++): ?>
                                    <svg class="w-4 h-4 text-yellow-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 20">
                                        <path d="M20.924 7.625a1.523 1.523 0 0 0-1.238-1.044l-5.051-.734-2.259-4.577a1.534 1.534 0 0 0-2.752 0L7.365 5.847l-5.051.734A1.535 1.535 0 0 0 1.463 9.2l3.656 3.563-.863 5.031a1.532 1.532 0 0 0 2.226 1.616L11 17.033l4.518 2.375a1.534 1.534 0 0 0 2.226-1.617l-.863-5.03L20.537 9.2a1.523 1.523 0 0 0 .387-1.575Z"/>
                                    </svg>
                                <?php endfor; 
                                for ($i = 0; $i < $emptyStars; $i++): ?>
                                    <svg class="w-4 h-4 text-gray-200" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 20">
                                        <path d="M20.924 7.625a1.523 1.523 0 0 0-1.238-1.044l-5.051-.734-2.259-4.577a1.534 1.534 0 0 0-2.752 0L7.365 5.847l-5.051.734A1.535 1.535 0 0 0 1.463 9.2l3.656 3.563-.863 5.031a1.532 1.532 0 0 0 2.226 1.616L11 17.033l4.518 2.375a1.534 1.534 0 0 0 2.226-1.617l-.863-5.03L20.537 9.2a1.523 1.523 0 0 0 .387-1.575Z"/>
                                    </svg>
                                <?php endfor; ?>
                            </div>
                            <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-0.5 rounded ms-2"><?php echo $rating; ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-2xl font-bold text-gray-900">$<?php echo number_format($product['Precio'], 0, ',', '.'); ?></span>
                            <button class="add-to-cart-btn bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors duration-200" 
                                    data-product-id="<?php echo $product['ID_Producto']; ?>"
                                    data-product-name="<?php echo htmlspecialchars($product['nombre']); ?>"
                                    data-product-price="<?php echo $product['Precio']; ?>">
                                Agregar
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (empty($products[$categoria])): ?>
            <p class="text-gray-600 text-center">No hay <?php echo strtolower($categoria); ?>s disponibles en tu zona.</p>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<!-- Footer modificado -->
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

    
    // Manejo del modal y sus botones
$(document).ready(function() {
    // Función para mostrar el modal con mensaje personalizado
    function showModal(message, isError = false) {
        $('#modalMessage').text(message);
        if (isError) {
            $('#modalMessage').addClass('text-red-600');
        } else {
            $('#modalMessage').removeClass('text-red-600');
        }
        $('#cartModal').removeClass('hidden').addClass('flex');
    }

    // Función para cerrar el modal
    function closeModal() {
        $('#cartModal').removeClass('flex').addClass('hidden');
    }

    // Manejador para el botón "Seguir comprando"
    $('#continueShoppingBtn').click(function(e) {
        e.preventDefault();
        closeModal();
    });

    // Cerrar modal al hacer clic fuera de él
    $('#cartModal').click(function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Manejador para el botón de agregar al carrito
    $('.add-to-cart-btn').click(function() {
        const button = $(this);
        const productId = button.data('product-id');
        
        // Debug log
        console.log('Intentando agregar producto:', productId);

        // Deshabilitar el botón
        button.prop('disabled', true);

        // Realizar la petición AJAX
        $.ajax({
            url: 'agregar_carrito.php',
            method: 'POST',
            data: {
                product_id: productId,
                cantidad: 1
            },
            success: function(response) {
                console.log('Respuesta recibida:', response);
                
                if (response.success) {
                    showModal(response.message || 'Producto agregado al carrito correctamente');
                } else {
                    showModal(response.message || 'Error al agregar al carrito', true);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error Details:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText
                });
                
                showModal('Error al procesar la solicitud. Por favor, intente nuevamente.', true);
            },
            complete: function() {
                button.prop('disabled', false);
                console.log('Solicitud completada');
            }
        });
    });

    // Prevenir que el modal se cierre al hacer clic dentro del contenido del modal
    $('.modal-content').click(function(e) {
        e.stopPropagation();
    });
});

// Función para mostrar el modal
function showModal(message, isError = false) {
    const modal = $('#cartModal');
    const modalMessage = $('#modalMessage');
    
    modalMessage.text(message);
    modalMessage.toggleClass('text-red-600', isError);
    
    modal.removeClass('hidden').addClass('flex');
}
ddocument.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const footer = document.getElementById('footer');
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebarToggleForm = document.getElementById('sidebarToggleForm');
    
    // Función para manejar el toggle del sidebar
    function toggleSidebar(event) {
        if (event) {
            event.preventDefault();
        }
        
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
            sidebar.classList.toggle('active');
        } else {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            footer.classList.toggle('expanded');
        }
        
        // Actualizar la posición del botón toggle en desktop
        if (!isMobile) {
            toggleBtn.style.left = sidebar.classList.contains('collapsed') ? '1rem' : 'calc(var(--sidebar-width) + 1rem)';
        }
    }
    
    // Event listeners
    sidebarToggleForm.addEventListener('submit', toggleSidebar);
    
    // Cerrar sidebar al hacer click fuera en móvil
    document.addEventListener('click', function(event) {
        const isMobile = window.innerWidth <= 768;
        if (isMobile && !sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
            sidebar.classList.remove('active');
        }
    });
    
    // Manejar cambios de tamaño de ventana
    let timeoutId;
    window.addEventListener('resize', function() {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(function() {
            const isMobile = window.innerWidth <= 768;
            
            if (!isMobile) {
                sidebar.classList.remove('active');
                toggleBtn.style.left = sidebar.classList.contains('collapsed') ? '1rem' : 'calc(var(--sidebar-width) + 1rem)';
            } else {
                toggleBtn.style.left = '1rem';
            }
        }, 250);
    });
});
        //scrip para manejar las interacciones en la pagina
        $(document).ready(function() {
    function toggleSidebar() {
        $('#sidebar').toggleClass('collapsed');
        $('#mainContent, #footer').toggleClass('expanded');
        $('body').toggleClass('sidebar-open');
        
        // Ajustar el contenedor de productos
        $('.container').css({
            'margin-left': $('#sidebar').hasClass('collapsed') ? '0' : '250px',
            'width': $('#sidebar').hasClass('collapsed') ? '100%' : 'calc(100% - 250px)'
        });
    }

    $('#sidebarToggleForm').on('submit', function(e) {
        e.preventDefault();
        toggleSidebar();
    });
        
        function performSearch() {
            var searchTerm = $('#searchInput').val();
            
            $.ajax({
                url: '<?php echo $_SERVER['PHP_SELF']; ?>',
                method: 'GET',
                data: { search: searchTerm },
                dataType: 'json',
                success: function(results) {
                    displaySearchResults(results);
                },
                error: function(xhr, status, error) {
                    console.error("Error in search:", error);
                    alert("An error occurred while searching. Please try again.");
                }
            });
        }
        
        function displaySearchResults(results) {
            var resultsContainer = $('#searchResults');
            resultsContainer.empty();
            
            if (results.length === 0) {
                resultsContainer.append('<p class="text-gray-600">No se encontraron resultados.</p>');
                return;
            }
            
            results.forEach(function(product) {
                var rating = Math.round(product.valoracion * 10) / 10;
                var stars = '★'.repeat(Math.floor(rating)) + '☆'.repeat(5 - Math.floor(rating));
                
                var productCard = `
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <img src="${product.imagen_producto}" alt="${product.nombre}" class="w-full h-48 object-cover">
                        <div class="p-4">
                            <h4 class="font-semibold">${product.nombre}</h4>
                            <p class="text-gray-600">
                                Vendedor: 
                                <a href="perfil_vendedor.php?id=${product.ID_Vendedor}" class="text-blue-500 hover:underline">
                                    ${product.NombreVendedor}
                                </a>
                            </p>
                            <p class="text-gray-600">Comuna: ${product.NombreComuna}</p>
                            <p class="text-yellow-500">${stars} ${rating}</p>
                            <p class="text-green-600 font-bold mt-2">$${parseFloat(product.Precio).toLocaleString('es-CL')}</p>
                            <button class="mt-2 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Agregar al carrito</button>
                        </div>
                    </div>
                `;
                resultsContainer.append(productCard);
            });
        }    
        $(window).resize(function() {
        if ($(window).width() > 768) {
            $('#sidebar').removeClass('collapsed');
            $('#mainContent, #footer').removeClass('expanded');
            $('body').removeClass('sidebar-open');
            
            // Restablecer el contenedor de productos
            $('.container').css({
                'margin-left': '250px',
                'width': 'calc(100% - 250px)'
            });
        }
    });
});
    </script>
</body>
</html>