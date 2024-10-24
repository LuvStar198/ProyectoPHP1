<?php
// Procesar el formulario al enviarlo
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Configuración de la base de datos
    $host = 'localhost';
    $dbname = 'ecommerce';
    $db_username = 'root';
    $db_password = '';

    // Verificar si los campos del formulario están definidos y no vacíos
    $nombre = $_POST['nombre'] ?? null;
    $contrasena = $_POST['contrasena'] ?? null;
    $correo_electronico = $_POST['correo_electronico'] ?? null;
    $contacto = $_POST['contacto'] ?? null;
    $direccion = $_POST['direccion'] ?? null;
    $comuna = $_POST['comuna'] ?? null;

    // Verificar que los campos obligatorios no sean nulos o vacíos
    if ($nombre && $contrasena && $correo_electronico && $contacto && $direccion) {
        try {
            // Conectar a la base de datos
            $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_username, $db_password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Iniciar una transacción
            $conn->beginTransaction();

            // Verificar si el correo electrónico ya está registrado
            $sql_check = "SELECT COUNT(*) FROM Usuario WHERE Correo_electronico = :correo_electronico";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bindParam(':correo_electronico', $correo_electronico);
            $stmt_check->execute();
            $email_exists = $stmt_check->fetchColumn();

            if ($email_exists) {
                // Si el correo ya está registrado, mostrar un mensaje de error
                $conn->rollBack();
                $error_message = "Error: El correo electrónico ya está registrado. Por favor, usa otro correo.";
            } else {
                // Encriptar la contraseña antes de almacenarla
                $hashed_password = password_hash($contrasena, PASSWORD_DEFAULT);

                // Insertar los datos en la tabla Usuario
                $sql = "INSERT INTO Usuario (Nombre, contrasena, Correo_electronico, Contacto, Direccion, comuna, rol)
                        VALUES (:nombre, :contrasena, :correo_electronico, :contacto, :direccion, :comuna, :rol)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':contrasena', $hashed_password);
                $stmt->bindParam(':correo_electronico', $correo_electronico);
                $stmt->bindParam(':contacto', $contacto);
                $stmt->bindParam(':direccion', $direccion);
                $stmt->bindParam(':comuna', $comuna);
                $rol_comprador = 1; // 1 para Comprador
                $stmt->bindParam(':rol', $rol_comprador, PDO::PARAM_INT);

                // Ejecutar la consulta
                if ($stmt->execute()) {
                    // Confirmar la transacción
                    $conn->commit();
                    // Redirigir a IniciarSesion.php
                    header("Location: InicioSesion.php");
                    exit();
                } else {
                    // Revertir la transacción si hay un error
                    $conn->rollBack();
                    $error_message = "Error al crear la cuenta.";
                }
            }
        } catch (PDOException $e) {
            // Asegurarse de revertir la transacción en caso de excepción
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error_message = "Error: " . $e->getMessage();
        }
    } else {
        $error_message = "Todos los campos son obligatorios.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Crear una nueva cuenta en Mercadito - Tu mercado de frutas y verduras">
    <title>Crear Cuenta - Mercadito</title>
    <link href="estilos/crear-cuenta.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-store mr-2"></i>
            Mercadito
        </div>
        <a href="InicioSesion.php" class="nav-link">
            <i class="fas fa-sign-in-alt"></i>
            Iniciar Sesión
        </a>
    </nav>

    <main class="main-content">
        <div class="container create-account-form">
            <h1>
                <i class="fas fa-user-plus"></i>
                Crear Cuenta
            </h1>
            
            <?php if (isset($error_message)): ?>
                <div class="error-message" role="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="registration-form">
                <div class="form-group">
                    <label for="nombre">
                        <i class="fas fa-user"></i>
                        Nombre:
                    </label>
                    <input type="text" 
                           id="nombre" 
                           name="nombre" 
                           required 
                           placeholder="Ingresa tu nombre completo"
                           autocomplete="name">
                </div>

                <div class="form-group">
                    <label for="contrasena">
                        <i class="fas fa-lock"></i>
                        Contraseña:
                    </label>
                    <div class="password-input-group">
                        <input type="password" 
                               id="contrasena" 
                               name="contrasena" 
                               required 
                               placeholder="Ingresa una contraseña segura"
                               minlength="8">
                        <button type="button" class="toggle-password" aria-label="Mostrar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="correo_electronico">
                        <i class="fas fa-envelope"></i>
                        Correo Electrónico:
                    </label>
                    <input type="email" 
                           id="correo_electronico" 
                           name="correo_electronico" 
                           required 
                           placeholder="ejemplo@correo.com"
                           autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="contacto">
                        <i class="fas fa-phone"></i>
                        Número de Contacto:
                    </label>
                    <input type="tel" 
                           id="contacto" 
                           name="contacto" 
                           required 
                           placeholder="+56 9 XXXX XXXX"
                           pattern="[0-9+\s]{9,}"
                           autocomplete="tel">
                </div>

                <div class="form-group">
                    <label for="direccion">
                        <i class="fas fa-map-marker-alt"></i>
                        Dirección:
                    </label>
                    <input type="text" 
                           id="direccion" 
                           name="direccion" 
                           required 
                           placeholder="Ingresa tu dirección completa"
                           autocomplete="street-address">
                </div>

                <div class="form-group">
                    <label for="comuna">
                        <i class="fas fa-city"></i>
                        Comuna:
                    </label>
                    <select name="comuna" id="comuna" required>
                        <option value="">Selecciona tu comuna</option>
                        <optgroup label="Santiago">
                            <option value="Cerro Navia">Cerro Navia</option>
                            <option value="El Bosque">El Bosque</option>
                            <option value="Estacion Central">Estación Central</option>
                            <option value="Huechuraba">Huechuraba</option>
                            <option value="Independencia">Independencia</option>
                            <option value="La Cisterna">La Cisterna</option>
                            <option value="La Florida">La Florida</option>
                            <option value="La Reina">La Reina</option>
                            <option value="Las Condes">Las Condes</option>
                            <option value="Lo Barnechea">Lo Barnechea</option>
                            <option value="Maipú">Maipú</option>
                            <option value="Ñuñoa">Ñuñoa</option>
                            <option value="Peñalolen">Peñalolén</option>
                            <option value="Providencia">Providencia</option>
                            <option value="Pudahuel">Pudahuel</option>
                            <option value="Quilicura">Quilicura</option>
                            <option value="Recoleta">Recoleta</option>
                            <option value="Renca">Renca</option>
                            <option value="San Miguel">San Miguel</option>
                            <option value="Vitacura">Vitacura</option>
                        </optgroup>
                        <optgroup label="Otras comunas">
                            <option value="Alhué">Alhué</option>
                            <option value="Buin">Buin</option>
                            <option value="Colina">Colina</option>
                            <option value="Melipilla">Melipilla</option>
                            <option value="Peñaflor">Peñaflor</option>
                            <option value="Puente Alto">Puente Alto</option>
                            <option value="San Bernardo">San Bernardo</option>
                        </optgroup>
                    </select>
                </div>

                <button type="submit" class="submit-button">
                    <i class="fas fa-user-plus"></i>
                    Crear Cuenta
                </button>

                <div class="login-link">
                    ¿Ya tienes una cuenta? 
                    <a href="InicioSesion.php">Inicia Sesión</a>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Toggle password visibility
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const passwordInput = document.querySelector('#contrasena');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>