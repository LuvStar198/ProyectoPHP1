<?php
header('Content-Type: application/json');
require 'db_config/db_data.php';

if (!isset($_POST['id_pedido']) || !isset($_POST['nuevo_estado'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan parámetros']);
    exit;
}

$id_pedido = $_POST['id_pedido'];
$nuevo_estado = $_POST['nuevo_estado'];

// Validar el estado
$estados_validos = ['pendiente', 'procesando', 'completado', 'cancelado'];
if (!in_array($nuevo_estado, $estados_validos)) {
    echo json_encode(['success' => false, 'message' => 'Estado no válido']);
    exit;
}

$sql = "UPDATE Pedido SET estado = ? WHERE ID_Pedido = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $nuevo_estado, $id_pedido);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado']);
}

$conn->close();
?>