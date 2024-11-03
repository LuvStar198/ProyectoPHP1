<?php
session_start();

// Verificar si el usuario ha iniciado sesión y es un vendedor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 0) {
    header("Location: IniciarSesion.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

require 'db_config/db_data.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $categoria = $_POST['categoria'];
    $cantidad = $_POST['cantidad'];
    $precio = $_POST['precio'];

    // Procesar la imagen
    $target_dir = "uploads/";
    $imageFileType = strtolower(pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION));
    $new_file_name = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $new_file_name;

    // Permitir ciertos formatos de archivo
    $allowed_types = array('jpg', 'png', 'jpeg', 'gif');
    if(in_array($imageFileType, $allowed_types)) {
        if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_file)) {
            // Insertar en la base de datos
            $sql = "INSERT INTO Producto (nombre, descripcion, categoria, cantidad, Precio, imagen_producto, ID_Vendedor) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssiisi", $nombre, $descripcion, $categoria, $cantidad, $precio, $target_file, $user_id);

            if ($stmt->execute()) {
                $message = "Producto agregado con éxito.";
            } else {
                $message = "Error al agregar el producto: " . $conn->error;
            }
        } else {
            $message = "Hubo un error al subir la imagen.";
        }
    } else {
        $message = "Solo se permiten archivos JPG, JPEG, PNG y GIF.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Producto - Mercadito</title>
    <link href="estilos/perfil-vendedor.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
    <div class="flex items-center mb-6">
            <a href="perfil_vendedor.php" class="bg-green-700 hover:bg-green-800 text-white font-bold py-2 px-4 rounded flex items-center"> 
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Volver al Panel
            </a>
        </div>
        <h1 class="text-3xl font-bold mb-6">Agregar Nuevo Producto</h1>
        
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="nombre">
                    Nombre del Producto
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="nombre" name="nombre" type="text" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="descripcion">
                    Descripción
                </label>
                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="descripcion" name="descripcion" required></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="categoria">
                    Categoría
                </label>
                <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="categoria" name="categoria" required>
                    <option value="Fruta">Fruta</option>
                    <option value="Verdura">Verdura</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="cantidad">
                    Cantidad (kg)
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="cantidad" name="cantidad" type="number" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="precio">
                    Precio
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="precio" name="precio" type="number" step="0.01" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="imagen">
                    Imagen del Producto
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="imagen" name="imagen" type="file" required>
            </div>
            <div class="flex items-center justify-between">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                    Agregar Producto
                </button>
            </div>
        </form>
    </div>
</body>
</html>