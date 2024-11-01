<?php
session_start();

// Verificar si el usuario ha iniciado sesión como vendedor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 0) {
    header("Location: IniciarSesion.php");
    exit();
}

$user_id = $_SESSION['user_id'];

require 'db_config/db_data.php';

// Obtener los pedidos para los productos del vendedor
$sql_pedidos = "SELECT DISTINCT
                p.ID_Pedido,
                p.total,
                p.fecha_compra,
                p.estado,
                p.metodo_pago,
                p.direccion_envio,
                u.Nombre as nombre_cliente,
                u.Contacto as telefono_cliente
                FROM Pedido p
                JOIN Pedido_Producto pp ON p.ID_Pedido = pp.ID_Pedido
                JOIN Producto pr ON pp.ID_Producto = pr.ID_Producto
                JOIN Usuario u ON p.ID_Usuario = u.ID_Usuario
                WHERE pr.ID_Vendedor = ?
                ORDER BY p.fecha_compra DESC";

$stmt = $conn->prepare($sql_pedidos);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_pedidos = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mercadito - Panel de Pedidos</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Modal necesita jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Panel de Pedidos</h1>
        <div class="flex items-center mb-6">
            <a href="perfil_vendedor.php" class="bg-green-700 hover:bg-green-800 text-white font-bold py-2 px-4 rounded flex items-center"> 
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Volver al Panel
            </a>
        </div>

        <?php if ($result_pedidos->num_rows > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php while($pedido = $result_pedidos->fetch_assoc()): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-semibold">Pedido #<?php echo htmlspecialchars($pedido['ID_Pedido']); ?></h2>
                            <span class="px-3 py-1 rounded-full text-sm 
                                <?php echo $pedido['estado'] == 'pendiente' ? 'bg-yellow-200 text-yellow-800' : 
                                     ($pedido['estado'] == 'procesando' ? 'bg-blue-200 text-blue-800' : 
                                     ($pedido['estado'] == 'completado' ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800')); ?>">
                                <?php echo ucfirst(htmlspecialchars($pedido['estado'])); ?>
                            </span>
                        </div>
                        
                        <div class="mb-4">
                            <p class="text-gray-600"><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['nombre_cliente']); ?></p>
                            <p class="text-gray-600"><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_compra'])); ?></p>
                            <p class="text-gray-600"><strong>Total:</strong> $<?php echo number_format($pedido['total'], 2); ?></p>
                        </div>

                        <div class="flex justify-between items-center">
                            <button onclick="verDetallesPedido(<?php echo $pedido['ID_Pedido']; ?>)" 
                                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors">
                                Ver más
                            </button>
                            
                            <select onchange="actualizarEstado(this, <?php echo $pedido['ID_Pedido']; ?>)" 
                                    class="border rounded px-3 py-1">
                                <option value="pendiente" <?php echo $pedido['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="procesando" <?php echo $pedido['estado'] == 'procesando' ? 'selected' : ''; ?>>Procesando</option>
                                <option value="completado" <?php echo $pedido['estado'] == 'completado' ? 'selected' : ''; ?>>Completado</option>
                                <option value="cancelado" <?php echo $pedido['estado'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <p class="text-gray-600 text-center">No hay pedidos pendientes.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal -->
    <div id="detalleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Detalles del Pedido</h2>
                <button onclick="cerrarModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div id="modalContent" class="space-y-4">
                <!-- El contenido se cargará dinámicamente -->
            </div>
        </div>
    </div>

    <script>
    function verDetallesPedido(idPedido) {
        $.ajax({
            url: 'obtener_detalles_pedido.php',
            method: 'POST',
            data: { id_pedido: idPedido },
            success: function(response) {
                $('#modalContent').html(response);
                $('#detalleModal').removeClass('hidden').addClass('flex');
            },
            error: function() {
                alert('Error al cargar los detalles del pedido');
            }
        });
    }

    function cerrarModal() {
        $('#detalleModal').removeClass('flex').addClass('hidden');
    }

    function actualizarEstado(select, idPedido) {
        $.ajax({
            url: 'actualizar_estado_pedido.php',
            method: 'POST',
            data: {
                id_pedido: idPedido,
                nuevo_estado: select.value
            },
            success: function(response) {
                if (response.success) {
                    // Actualizar la UI si es necesario
                    location.reload();
                } else {
                    alert('Error al actualizar el estado');
                }
            },
            error: function() {
                alert('Error en la conexión');
            }
        });
    }

    // Cerrar modal al hacer clic fuera de él
    document.getElementById('detalleModal').addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarModal();
        }
    });
    </script>
</body>
</html>