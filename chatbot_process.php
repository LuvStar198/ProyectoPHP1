<?php
session_start();
require 'db_config/db_data.php';

if ($_POST['action'] == 'start_conversation') {
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $sql = "INSERT INTO ChatConversacion (ID_Usuario) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $conversationId = $stmt->insert_id;
    echo json_encode(['success' => true, 'conversationId' => $conversationId]);
} elseif ($_POST['action'] == 'send_message') {
    $message = $conn->real_escape_string($_POST['message']);
    $conversationId = intval($_POST['conversationId']);
    
    // Guardar mensaje del usuario
    $sql = "INSERT INTO ChatMensaje (ID_Conversacion, contenido, es_bot) VALUES (?, ?, 0)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $conversationId, $message);
    $stmt->execute();
    
    // Procesar la respuesta del chatbot (ejemplo simple)
    $reply = getSimpleReply($message);
    
    // Guardar respuesta del chatbot
    $sql = "INSERT INTO ChatMensaje (ID_Conversacion, contenido, es_bot) VALUES (?, ?, 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $conversationId, $reply);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'reply' => $reply]);
}

$conn->close();

function getSimpleReply($message) {
    $lowercaseMessage = strtolower($message);
    if (strpos($lowercaseMessage, 'hola') !== false) {
        return "¡Hola! ¿En qué puedo ayudarte hoy?";
    } elseif (strpos($lowercaseMessage, 'producto') !== false) {
        return "Tenemos una gran variedad de productos frescos. ¿Buscas algo en particular?";
    } elseif (strpos($lowercaseMessage, 'precio') !== false) {
        return "Los precios varían según el producto y la temporada. ¿Hay algún producto específico por el que quieras preguntar?";
    } elseif (strpos($lowercaseMessage, 'envío') !== false) {
        return "Ofrecemos envío a domicilio. El costo depende de tu ubicación. ¿Quieres más información sobre nuestras opciones de envío?";
    } else {
        return "Lo siento, no entiendo tu pregunta. ¿Podrías reformularla o ser más específico?";
    }
}
?>