<?php
session_start();

// Configuración de la base de datos
require 'db_config/db_data.php';

// Habilitar errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Procesar el formulario al enviarlo
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo_electronico = isset($_POST['username']) ? $_POST['username'] : null;
    $contrasena = isset($_POST['password']) ? $_POST['password'] : null;

    if ($correo_electronico && $contrasena) {
        try {
            $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_username, $db_password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Consulta para obtener el usuario
            $sql = "SELECT ID_Usuario, Nombre, contrasena, rol FROM Usuario WHERE Correo_electronico = :correo_electronico";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':correo_electronico', $correo_electronico);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificación de la contraseña
            if ($user && password_verify($contrasena, $user['contrasena'])) {
                $_SESSION['user_id'] = $user['ID_Usuario'];
                $_SESSION['user_name'] = $user['Nombre'];
                $_SESSION['user_role'] = $user['rol'];

                // Convertir el rol de binary a entero
                $rol_usuario = (int)$user['rol']; // Obtiene el valor del rol como entero

                // Verificar el rol y redirigir según corresponda
                echo "<script>console.log('Rol: " . $rol_usuario . "');</script>";
                echo "<p>Rol del usuario: " . htmlspecialchars($rol_usuario) . "</p>"; // Mostrar el rol en la página
                
                if ($rol_usuario == 1) { // Comprador
                    header('Location: panel_comprador.php');
                } elseif ($rol_usuario == 0) { // Vendedor
                    header('Location: perfil_vendedor.php');
                } else {
                    echo "<script>alert('Rol no reconocido.');</script>";
                }
                exit();
            } else {
                echo "<script>alert('Correo electrónico o contraseña incorrectos.');</script>";
            }
        } catch (PDOException $e) {
            echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
        }
    } else {
        echo "<script>alert('Por favor, complete todos los campos.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión - Mercadito</title>
    <link href="estilos/inicio-sesion.css" rel="stylesheet">
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">Mercadito</div>
    </div>
    <div class="main-content">
        <div class="container">
            <h1>Iniciar Sesión</h1>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <label for="username">
                <i class="fas fa-envelope"></i>
                    Correo Electrónico:
                </label>
                <input type="text" id="username" name="username" required>
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
                <button type="submit">Entrar</button>
            </form>
            <div class="forgot-password"><a href="recuperar_password.php">¿Olvidaste tu contraseña?</a></div>
            <div class="create-account"><a href="crear_cuenta.php">Crear una cuenta</a></div>
        </div>
    </div>

    <div id="chatbot-icon" onclick="toggleChatbot()">💬</div>
    <div id="chatbot-window">
        <div id="chatbot-header">
            Chatbot
            <span class="close-btn" onclick="toggleChatbot()">×</span>
        </div>
        <div id="chatbot-messages"></div>
        <div id="chatbot-input">
            <input type="text" id="chat-input" placeholder="Escribe tu mensaje...">
            <button onclick="sendMessage()">Enviar</button>
        </div>
    </div>

    <script>
        let conversationId = null;

function toggleChatbot() {
    const chatbotWindow = document.getElementById('chatbot-window');
    if (chatbotWindow.style.display === 'none' || chatbotWindow.style.display === '') {
        chatbotWindow.style.display = 'block';
        if (!conversationId) {
            startConversation();
        }
    } else {
        chatbotWindow.style.display = 'none';
    }
}

function startConversation() {
    fetch('chatbot_process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=start_conversation'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            conversationId = data.conversationId;
            appendMessage("¡Hola! ¿En qué puedo ayudarte?", true);
        }
    })
    .catch(error => console.error('Error:', error));
}

function sendMessage() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    
    if (message && conversationId) {
        appendMessage(message, false);
        input.value = '';
        
        fetch('chatbot_process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=send_message&message=${encodeURIComponent(message)}&conversationId=${conversationId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                appendMessage(data.reply, true);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

function appendMessage(message, isBot) {
    const messagesDiv = document.getElementById('chatbot-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = isBot ? 'bot-message' : 'user-message';
    messageDiv.textContent = message;
    messagesDiv.appendChild(messageDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

// Allow sending messages with Enter key
document.getElementById('chat-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        sendMessage();
    }
});
    </script>
</body>
</html>

