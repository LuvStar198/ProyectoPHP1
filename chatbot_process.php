<?php
session_start();
require 'db_config/db_data.php';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($_POST['action'] === 'start_conversation') {
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $stmt = $conn->prepare("INSERT INTO ChatConversacion (ID_Usuario) VALUES (?)");
            $stmt->execute([$userId]);
            $conversationId = $conn->lastInsertId();
            echo json_encode(['success' => true, 'conversationId' => $conversationId]);
            
        } elseif ($_POST['action'] === 'send_message') {
            $message = $_POST['message'];
            $conversationId = intval($_POST['conversationId']);
            
            // Save user message
            $stmt = $conn->prepare("INSERT INTO ChatMensaje (ID_Conversacion, contenido, es_bot) VALUES (?, ?, 0)");
            $stmt->execute([$conversationId, $message]);
            
            // Generate and save bot response
            $reply = getSimpleReply($message);
            $stmt = $conn->prepare("INSERT INTO ChatMensaje (ID_Conversacion, contenido, es_bot) VALUES (?, ?, 1)");
            $stmt->execute([$conversationId, $reply]);
            
            echo json_encode(['success' => true, 'reply' => $reply]);
        }
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getSimpleReply($message) {
    $lowercaseMessage = strtolower($message);
    $replies = [
        'hola' => "¡Hola! ¿En qué puedo ayudarte hoy?",
        'producto' => "Tenemos una gran variedad de productos frescos. ¿Buscas algo en particular?",
        'precio' => "Los precios varían según el producto y la temporada. ¿Hay algún producto específico por el que quieras preguntar?",
        'envío' => "No ofrecemos envío a domicilio, si deseas envio deberás costearlo por otro medio como DiDi o Uber.",
        'gracias' => "¡De nada! ¿Hay algo más en lo que pueda ayudarte?",
        'horario' => "Nuestro horario de atención es de lunes a Domingo de 8:00 AM a 8:00 PM.",
        'pago' => "Aceptamos  tarjetas de crédito/débito y transferencias bancarias.",
    ];
    
    foreach ($replies as $keyword => $reply) {
        if (strpos($lowercaseMessage, $keyword) !== false) {
            return $reply;
        }
    }
    
    return "Lo siento, no entiendo tu pregunta. ¿Podrías reformularla o ser más específico?";
}
?>