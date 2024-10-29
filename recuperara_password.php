<?php
// recuperar_password.php
session_start();
require 'db_config/db_data.php';

// Función para generar token seguro
function generarToken() {
    return bin2hex(random_bytes(32));
}

// Función para enviar correo
function enviarCorreoRecuperacion($correo, $token) {
    $to = $correo;
    $subject = "Recuperación de contraseña - Mercadito";
    $linkRecuperacion = "http://tudominio.com/reset_password.php?token=" . $token;
    
    $message = "
    <html>
    <head>
        <title>Recuperar contraseña</title>
    </head>
    <body>
        <h2>Recuperación de contraseña - Mercadito</h2>
        <p>Has solicitado restablecer tu contraseña. Haz clic en el siguiente enlace para crear una nueva contraseña:</p>
        <p><a href='{$linkRecuperacion}'>Restablecer contraseña</a></p>
        <p>Si no solicitaste este cambio, puedes ignorar este correo.</p>
        <p>El enlace expirará en 1 hora.</p>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@mercadito.com" . "\r\n";

    return mail($to, $subject, $message, $headers);
}

// Procesar solicitud de recuperación
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $correo = $_POST['email'];
        $token = generarToken();
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Verificar si el correo existe
        $stmt = $conn->prepare("SELECT ID_Usuario FROM Usuario WHERE Correo_electronico = ?");
        $stmt->execute([$correo]);
        
        if ($stmt->rowCount() > 0) {
            // Guardar token en la base de datos
            $stmt = $conn->prepare("INSERT INTO password_reset_tokens (email, token, expiracion) VALUES (?, ?, ?)");
            $stmt->execute([$correo, $token, $expiracion]);

            if (enviarCorreoRecuperacion($correo, $token)) {
                echo json_encode(['success' => true, 'message' => 'Se ha enviado un correo con las instrucciones']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al enviar el correo']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No existe una cuenta con ese correo']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en el servidor']);
    }
    exit;
}
?>

<!-- Modificación del HTML en InicioSesion.php -->
<script>
function mostrarFormularioRecuperacion() {
    const modalHTML = `
        <div id="modalRecuperacion" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Recuperar contraseña</h2>
                <p>Ingresa tu correo electrónico y te enviaremos las instrucciones para recuperar tu contraseña.</p>
                <form id="formRecuperacion">
                    <input type="email" id="emailRecuperacion" required placeholder="Correo electrónico">
                    <button type="submit">Enviar instrucciones</button>
                </form>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    const modal = document.getElementById('modalRecuperacion');
    const span = document.getElementsByClassName("close")[0];
    
    modal.style.display = "block";
    
    span.onclick = function() {
        modal.remove();
    }
    
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.remove();
        }
    }
    
    document.getElementById('formRecuperacion').onsubmit = function(e) {
        e.preventDefault();
        const email = document.getElementById('emailRecuperacion').value;
        
        fetch('recuperar_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'email=' + encodeURIComponent(email)
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                modal.remove();
            }
        })
        .catch(error => {
            alert('Error al procesar la solicitud');
        });
    };
}

// Modificar el enlace de olvidaste contraseña en el HTML
document.querySelector('.forgot-password a').onclick = function(e) {
    e.preventDefault();
    mostrarFormularioRecuperacion();
};
</script>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 500px;
    border-radius: 8px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: black;
}

#formRecuperacion {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-top: 20px;
}

#formRecuperacion input {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

#formRecuperacion button {
    padding: 10px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

#formRecuperacion button:hover {
    background-color: #45a049;
}
</style>