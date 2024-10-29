<?php
// reset_password.php
session_start();
require 'db_config/db_data.php';

if (!isset($_GET['token'])) {
    die('Token no proporcionado');
}

$token = $_GET['token'];

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verificar si el token es válido y no ha expirado
    $stmt = $conn->prepare("SELECT email, expiracion FROM password_reset_tokens 
                           WHERE token = ? AND usado = FALSE AND expiracion > NOW()");
    $stmt->execute([$token]);
    $tokenInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenInfo) {
        die('Token inválido o expirado');
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        // Actualizar la contraseña
        $stmt = $conn->prepare("UPDATE Usuario SET contraseña = ? WHERE Correo_electronico = ?");
        $stmt->execute([$password, $tokenInfo['email']]);
        
        // Marcar el token como usado
        $stmt = $conn->prepare("UPDATE password_reset_tokens SET usado = TRUE WHERE token = ?");
        $stmt->execute([$token]);
        
        header('Location: index.php?password_reset=success');
        exit;
    }
} catch(PDOException $e) {
    die('Error en el servidor');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Mercadito</title>
    <link href="estilos/inicio-sesion.css" rel="stylesheet">
    <style>
        .container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .error-message {
            color: red;
            display: none;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Restablecer Contraseña</h2>
        <form id="resetForm" method="POST">
            <div class="form-group">
                <label for="password">Nueva contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmar contraseña:</label>
                <input type="password" id="confirm_password" required>
                <span class="error-message">Las contraseñas no coinciden</span>
            </div>
            <button type="submit">Cambiar contraseña</button>
        </form>
    </div>

    <script>
        document.getElementById('resetForm').onsubmit = function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const errorMessage = document.querySelector('.error-message');
            
            if (password !== confirmPassword) {
                e.preventDefault();
                errorMessage.style.display = 'block';
                return false;
            }
            errorMessage.style.display = 'none';
            return true;
        };
    </script>
</body>
</html>