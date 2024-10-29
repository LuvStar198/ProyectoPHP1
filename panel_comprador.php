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

// Verificar si el usuario ha iniciado sesión y es un comprador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: IniciarSesion.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

require 'db_config/db_data.php';

// Obtener información del usuario
$sql_user = "SELECT Nombre, imagen_usuario FROM Usuario WHERE ID_Usuario = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user_info = $result_user->fetch_assoc();

// Procesar la subida de imagen si se ha enviado un formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_image"])) {
    $target_dir = "uploads/profile_images/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
    $new_file_name = $user_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_file_name;
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Verificaciones de la imagen...
    // (Mantén el código de verificación de imagen del ejemplo original)

    if ($uploadOk == 1 && move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
        $sql_update_image = "UPDATE Usuario SET imagen_usuario = ? WHERE ID_Usuario = ?";
        $stmt_update_image = $conn->prepare($sql_update_image);
        $stmt_update_image->bind_param("si", $target_file, $user_id);
        if ($stmt_update_image->execute()) {
            $_SESSION['user_image'] = $target_file;
            $user_info['imagen_usuario'] = $target_file;
        }
        $stmt_update_image->close();
    }
}

// Obtener últimas compras del usuario
$sql_purchases = "SELECT p.ID_Pedido, p.total, p.fecha_compra, p.estado
                  FROM Pedido p
                  WHERE p.ID_Usuario = ?
                  ORDER BY p.fecha_compra DESC
                  LIMIT 5";
$stmt_purchases = $conn->prepare($sql_purchases);
$stmt_purchases->bind_param("i", $user_id);
$stmt_purchases->execute();
$result_purchases = $stmt_purchases->get_result();

// Obtener productos en el carrito
$sql_cart = "SELECT p.ID_Producto, p.nombre, p.Precio, p.imagen_producto, cp.cantidad
             FROM Carrito c
             JOIN Carrito_Producto cp ON c.ID_Carrito = cp.ID_Carrito
             JOIN Producto p ON cp.ID_Producto = p.ID_Producto
             WHERE c.ID_Usuario = ?
             LIMIT 5";
$stmt_cart = $conn->prepare($sql_cart);
$stmt_cart->bind_param("i", $user_id);
$stmt_cart->execute();
$result_cart = $stmt_cart->get_result();

// Obtener últimas conversaciones de chat
$sql_chat = "SELECT cc.ID_Conversacion, cc.fecha_inicio, 
             (SELECT contenido FROM ChatMensaje WHERE ID_Conversacion = cc.ID_Conversacion ORDER BY fecha DESC LIMIT 1) as ultimo_mensaje
             FROM ChatConversacion cc
             WHERE cc.ID_Usuario = ?
             ORDER BY cc.fecha_inicio DESC
             LIMIT 3";
$stmt_chat = $conn->prepare($sql_chat);
$stmt_chat->bind_param("i", $user_id);
$stmt_chat->execute();
$result_chat = $stmt_chat->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mercadito - Panel de Comprador</title>
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
    
    <div id="mainContent" class="main-content <?php echo $mainContentClass; ?>">
        <h1 class="text-2xl font-bold mb-4">Mi Perfil de Comprador</h1>
        <div class="container">
            <!-- Información del usuario -->
            <div class="bg-white shadow-md rounded-lg p-4 mb-4">
                <?php if (!empty($user_info['imagen_usuario'])): ?>
                    <img src="<?php echo htmlspecialchars($user_info['imagen_usuario']); ?>" alt="Foto de perfil" class="w-32 h-32 rounded-full mx-auto mb-4">
                <?php else: ?>
                    <div class="w-32 h-32 bg-gray-300 rounded-full mx-auto mb-4 flex items-center justify-center">
                        <span class="text-4xl text-gray-600"><?php echo substr($user_info['Nombre'], 0, 1); ?></span>
                    </div>
                <?php endif; ?>
                <h2 class="text-xl font-semibold text-center"><?php echo htmlspecialchars($user_info['Nombre']); ?></h2>
                
                <!-- Formulario para subir imagen de perfil -->
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data" class="mt-4">
                    <div class="flex items-center justify-center">
                        <label for="profile_image" class="cursor-pointer bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Seleccionar Imagen
                        </label>
                        <input type="file" id="profile_image" name="profile_image" class="hidden" onchange="this.form.submit()">
                    </div>
                </form>
            </div>
            
            <!-- Últimas compras -->
            <h2 class="text-xl font-semibold mb-2">Mis Últimas Compras</h2>
            <div class="bg-white shadow-md rounded-lg p-4 mb-4">
                <?php while($purchase = $result_purchases->fetch_assoc()): ?>
                <div class="mb-2 pb-2 border-b last:border-b-0">
                    <p class="font-semibold">Pedido #<?php echo $purchase['ID_Pedido']; ?></p>
                    <p>Total: $<?php echo number_format($purchase['total'], 2); ?></p>
                    <p>Fecha: <?php echo $purchase['fecha_compra']; ?></p>
                    <p>Estado: <?php echo $purchase['estado']; ?></p>
                </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Carrito actual -->
            <h2 class="text-xl font-semibold mb-2">Mi Carrito Actual</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php while($item = $result_cart->fetch_assoc()): ?>
                <div class="w-full max-w-sm bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
                    <a href="#">
                        <img class="p-8 rounded-t-lg" src="<?php echo htmlspecialchars($item['imagen_producto']); ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>" />
                    </a>
                    <div class="px-5 pb-5">
                        <a href="#">
                            <h5 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white"><?php echo htmlspecialchars($item['nombre']); ?></h5>
                        </a>
                        <div class="flex items-center mt-2.5 mb-5">
                            <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-blue-200 dark:text-blue-800 ms-3">Cantidad: <?php echo $item['cantidad']; ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-3xl font-bold text-gray-900 dark:text-white">$<?php echo number_format($item['Precio'], 2); ?></span>
                            <a href="#" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Ver Detalles</a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Últimas conversaciones de chat -->
            <h2 class="text-xl font-semibold mt-4 mb-2">Mis Últimas Conversaciones</h2>
            <div class="bg-white shadow-md rounded-lg p-4">
                <?php while($chat = $result_chat->fetch_assoc()): ?>
                <div class="mb-4 pb-4 border-b last:border-b-0">
                    <p class="font-semibold">Conversación #<?php echo $chat['ID_Conversacion']; ?></p>
                    <p class="text-sm text-gray-600">Iniciada: <?php echo $chat['fecha_inicio']; ?></p>
                    <p class="mt-2">Último mensaje: <?php echo htmlspecialchars(substr($chat['ultimo_mensaje'], 0, 50)) . '...'; ?></p>
                    <a href="chat.php?id=<?php echo $chat['ID_Conversacion']; ?>" class="text-blue-600 hover:underline">Continuar chat</a>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    
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