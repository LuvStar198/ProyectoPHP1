<?php
require 'db_config/db_data.php';

if (!isset($_POST['id_pedido'])) {
    echo "Error: No se especificó el ID del pedido";
    exit;
}

$id_pedido = $_POST['id_pedido'];

// Obtener detalles del pedido
$sql = "SELECT 
        p.ID_Pedido,
        p.fecha_compra,
        p.estado,
        p.metodo_pago,
        p.direccion_envio,
        u.Nombre as nombre_cliente,
        u.Contacto as telefono_cliente,
        pr.nombre as nombre_producto,
        pr.imagen_producto,
        pp.cantidad,
        (pr.Precio * pp.cantidad) as subtotal
        FROM Pedido p
        JOIN Usuario u ON p.ID_Usuario = u.ID_Usuario
        JOIN Pedido_Producto pp ON p.ID_Pedido = pp.ID_Pedido
        JOIN Producto pr ON pp.ID_Producto = pr.ID_Producto
        WHERE p.ID_Pedido = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $pedido = $result->fetch_assoc();
    ?>
    <div class="space-y-4">
        <div class="border-b pb-4">
            <p class="font-semibold">Cliente: <?php echo htmlspecialchars($pedido['nombre_cliente']); ?></p>
            <p>Teléfono: <?php echo htmlspecialchars($pedido['telefono_cliente']); ?></p>
            <p>Dirección de envío: <?php echo htmlspecialchars($pedido['direccion_envio']); ?></p>
            <p>Método de pago: <?php echo htmlspecialchars($pedido['metodo_pago']); ?></p>
        </div>

        <div class="space-y-4">
            <h3 class="font-semibold">Productos:</h3>
            <?php
            // Reiniciar el puntero del resultado
            $result->data_seek(0);
            $total = 0;
            while ($item = $result->fetch_assoc()) {
                $total += $item['subtotal'];
                ?>
                <div class="flex items-center space-x-4 border-b pb-4">
                    <?php if ($item['imagen_producto']): ?>
                        <img src="<?php echo htmlspecialchars($item['imagen_producto']); ?>" 
                             alt="<?php echo htmlspecialchars($item['nombre_producto']); ?>"
                             class="w-20 h-20 object-cover rounded">
                    <?php endif; ?>
                    <div class="flex-1">
                        <p class="font-medium"><?php echo htmlspecialchars($item['nombre_producto']); ?></p>
                        <p class="text-gray-600">Cantidad: <?php echo $item['cantidad']; ?></p>
                        <p class="text-gray-600">Subtotal: $<?php echo number_format($item['subtotal'], 2); ?></p>
                    </div>
                </div>
                <?php
            }
            ?>
            <div class="pt-4">
                <p class="text-xl font-bold">Total: $<?php echo number_format($total, 2); ?></p>
            </div>
        </div>
    </div>
    <?php
} else {
    echo "<p class='text-red-500'>No se encontraron detalles para este pedido.</p>";
}

$conn->close();
?>