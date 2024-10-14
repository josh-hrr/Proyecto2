<?php
include 'config.php';

session_start();

// Variables para los mensajes de éxito o error
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_cuenta = $_POST['numero_cuenta'];
    $correo_electronico = $_POST['correo_electronico']; 
    $dpi = $_POST['dpi'];
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];

    if ($contrasena === $confirmar_contrasena) {
        // Conectar a la base de datos  
        $mysqli = new mysqli($servername, $username, $password, $dbname);

        if ($mysqli->connect_error) {
            die("Error de conexión: " . $mysqli->connect_error);
        }

        // Validar que la cuenta bancaria exista y que el DPI coincida
        $stmt = $mysqli->prepare("SELECT dpi FROM cuentamonetaria WHERE numero_cuenta = ?");
        $stmt->bind_param("s", $numero_cuenta);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($dpi_cuenta);
            $stmt->fetch();

            if ($dpi === $dpi_cuenta) {
                // Validar que no exista un usuario registrado con esta cuenta bancaria
                $stmt2 = $mysqli->prepare("SELECT id FROM usuario WHERE correo_electronico = ?");
                $stmt2->bind_param("s", $correo_electronico);
                $stmt2->execute();
                $stmt2->store_result();

                if ($stmt2->num_rows == 0) {
                    // Registrar el nuevo usuario usando el stored procedure
                    $stmt3 = $mysqli->prepare("CALL RegistrarUsuario(?, ?, ?)");
                    $hashed_password = password_hash($contrasena, PASSWORD_BCRYPT); // Encriptar la contraseña
                    $stmt3->bind_param("sss", $correo_electronico, $dpi, $hashed_password);

                    if ($stmt3->execute()) {
                        $mensaje = "<p class='success'>Usuario registrado con éxito</p>";
                    } else {
                        $mensaje = "<p class='error'>Error al registrar el usuario: " . $stmt3->error . "</p>";
                    }

                    $stmt3->close();
                } else {
                    $mensaje = "<p class='error'>Ya existe un usuario registrado con este correo electrónico</p>";
                }

                $stmt2->close();
            } else {
                $mensaje = "<p class='error'>El DPI no coincide con el registrado en la cuenta bancaria</p>";
            }
        } else {
            $mensaje = "<p class='error'>La cuenta bancaria no existe</p>";
        }

        $stmt->close();
        $mysqli->close();
    } else {
        $mensaje = "<p class='error'>Las contraseñas no coinciden</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Usuario</title>
    <style> 
        .form-container {
            max-width: 500px;
            margin: 50px auto;
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            font-family: Arial, sans-serif;
        }
 
        .form-container h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
 
        .form-container label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #555;
        }

        .form-container input[type="text"],
        .form-container input[type="email"],
        .form-container input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
 
        .form-container input[type="text"]:focus,
        .form-container input[type="email"]:focus,
        .form-container input[type="password"]:focus {
            border-color: #4CAF50;
            outline: none;
        }
 
        .form-container button {
            width: 100%;
            padding: 14px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
 
        .form-container button:hover {
            background-color: #45a049;
        } 

        .mensaje {
            text-align: center;
            margin-top: 20px;
            font-size: 16px;
        }

        .mensaje p {
            padding: 10px;
            border-radius: 5px;
        }

        .mensaje p.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .mensaje p.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

<main>
    <div class="form-container">
        <h1>Registrar Usuario</h1>
        <form action="Registrar_Usuario.php" method="POST">
            <label for="numero_cuenta">No. Cuenta Bancaria:</label>
            <input type="text" id="numero_cuenta" name="numero_cuenta" required>

            <label for="correo_electronico">Correo Electrónico (Nombre de Usuario):</label>
            <input type="email" id="correo_electronico" name="correo_electronico" required>

            <label for="dpi">DPI:</label>
            <input type="text" id="dpi" name="dpi" required>

            <label for="contrasena">Contraseña:</label>
            <input type="password" id="contrasena" name="contrasena" required>

            <label for="confirmar_contrasena">Confirmar Contraseña:</label>
            <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" required>

            <button type="submit">Registrar Usuario</button>
        </form> 
        <div class="mensaje">
            <?php echo $mensaje; ?>
        </div>
    </div>
</main>

</body>
</html>
