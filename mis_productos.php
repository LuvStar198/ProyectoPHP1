<?php
session_start();

// Verificar si el usuario ha iniciado sesión y es un vendedor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 0) {
    header("Location: IniciarSesion.php");
    exit();
}

require 'db_config/db_data.php';

$user_id = $_SESSION['user_id'];
$mensaje = '';

// Eliminar producto
if (isset($_POST['eliminar'])) {
    $id_producto = $_POST['id_producto'];
    $sql_delete = "DELETE FROM Producto WHERE ID_Producto = ? AND ID_Vendedor = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("ii", $id_producto, $user_id);
    
    if ($stmt_delete->execute()) {
        $mensaje = "Producto eliminado exitosamente";
    } else {
        $mensaje = "Error al eliminar el producto: " . $conn->error;
    }
}

// Actualizar producto
if (isset($_POST['actualizar'])) {
    $id_producto = $_POST['id_producto'];
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $cantidad = $_POST['cantidad'];
    $categoria = $_POST['categoria'];

    $sql_update = "UPDATE Producto SET 
                   nombre = ?, 
                   descripcion = ?, 
                   Precio = ?, 
                   Cantidad = ?, 
                   categoria = ?
                   WHERE ID_Producto = ? AND ID_Vendedor = ?";
    
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ssiisii", $nombre, $descripcion, $precio, $cantidad, $categoria, $id_producto, $user_id);
    
    if ($stmt_update->execute()) {
        $mensaje = "Producto actualizado exitosamente";
    } else {
        $mensaje = "Error al actualizar el producto: " . $conn->error;
    }
}

// Obtener todos los productos del vendedor
$sql = "SELECT * FROM Producto WHERE ID_Vendedor = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Mercadito</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Botón Volver -->
        <div class="flex items-center mb-6">
            <a href="perfil_vendedor.php" class="bg-green-700 hover:bg-green-800 text-white font-bold py-2 px-4 rounded flex items-center"> 
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Volver al Panel
            </a>
        </div>

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Gestión de Productos</h1>
        </div>
        
        <?php if ($mensaje): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
        <?php endif; ?>

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Imagen</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($producto = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($producto['ID_Producto']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($producto['imagen_producto']): ?>
                                <img src="<?php echo htmlspecialchars($producto['imagen_producto']); ?>" 
                                     alt="Producto" 
                                     class="h-20 w-20 object-cover rounded">
                            <?php else: ?>
                                <div class="h-20 w-20 bg-gray-200 rounded flex items-center justify-center">
                                    <span class="text-gray-500">Sin imagen</span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($producto['nombre']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($producto['descripcion']); ?></td>
                        <td class="px-6 py-4">$<?php echo number_format($producto['Precio'], 0, ',', '.'); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($producto['Cantidad']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($producto['categoria']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button onclick="abrirModalEditar(<?php echo htmlspecialchars(json_encode($producto)); ?>)"
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">
                                Editar
                            </button>
                            <button onclick="confirmarEliminar(<?php echo $producto['ID_Producto']; ?>)"
                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de Edición -->
    <div id="modalEditar" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Editar Producto</h3>
                <form id="formEditar" method="POST">
                    <input type="hidden" name="id_producto" id="edit_id_producto">
                    <input type="hidden" name="actualizar" value="1">
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="nombre">
                            Nombre
                        </label>
                        <input type="text" name="nombre" id="edit_nombre" 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="descripcion">
                            Descripción
                        </label>
                        <textarea name="descripcion" id="edit_descripcion" 
                                  class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="precio">
                            Precio
                        </label>
                        <input type="number" name="precio" id="edit_precio" 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="cantidad">
                            Stock
                        </label>
                        <input type="number" name="cantidad" id="edit_cantidad" 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="categoria">
                            Categoría
                        </label>
                        <input type="text" name="categoria" id="edit_categoria" 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="button" onclick="cerrarModalEditar()" 
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-2">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Formulario para eliminar -->
    <form id="formEliminar" method="POST" style="display: none;">
        <input type="hidden" name="id_producto" id="eliminar_id_producto">
        <input type="hidden" name="eliminar" value="1">
    </form>

    <script>
        function abrirModalEditar(producto) {
            document.getElementById('edit_id_producto').value = producto.ID_Producto;
            document.getElementById('edit_nombre').value = producto.nombre;
            document.getElementById('edit_descripcion').value = producto.descripcion;
            document.getElementById('edit_precio').value = producto.Precio;
            document.getElementById('edit_cantidad').value = producto.Cantidad;
            document.getElementById('edit_categoria').value = producto.categoria;
            document.getElementById('modalEditar').classList.remove('hidden');
        }

        function cerrarModalEditar() {
            document.getElementById('modalEditar').classList.add('hidden');
        }

        function confirmarEliminar(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este producto?')) {
                document.getElementById('eliminar_id_producto').value = id;
                document.getElementById('formEliminar').submit();
            }
        }
    </script>
</body>
</html>