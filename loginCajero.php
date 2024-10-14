<?php
include 'config.php';

session_start(); 
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];

    // Conectar a la base de datos  
    $mysqli = new mysqli($servername, $username, $password, $dbname);

    if ($mysqli->connect_error) {
        die("Error de conexión: " . $mysqli->connect_error);
    }

    // Llamar al procedimiento almacenado para verificar las credenciales del cajero
    $stmt = $mysqli->prepare("CALL LoginCajero(?, ?)");
    $stmt->bind_param("ss", $usuario, $contrasena);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Si el cajero existe, iniciar sesión
        $stmt->bind_result($id, $nombre_completo);
        $stmt->fetch();
        $_SESSION['usuario'] = $nombre_completo;   
        $_SESSION['id'] = $id;   
        header("Location: bienvenida_cajero.php"); 
        exit;
    } else { 
        $mensaje = "<p style='color:red;'>Usuario o contraseña incorrectos</p>";
    }

    $stmt->close();
    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Cajero</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .form-container {
            max-width: 400px;
            margin: 0 auto;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-container h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .form-container input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .form-container button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .form-container button:hover {
            background-color: #45a049;
        }

        .mensaje {
            text-align: center;
            margin-top: 20px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Login Cajero</h1>
        <form action="loginCajero.php" method="POST">
            <label for="usuario">Usuario de Cajero:</label>
            <input type="text" id="usuario" name="usuario" required>

            <label for="contrasena">Contraseña:</label>
            <input type="password" id="contrasena" name="contrasena" required>

            <button type="submit">Iniciar Sesión</button>
        </form> 
        <div class="mensaje">
            <?php echo $mensaje; ?>
        </div>
        <p style="text-align: center;"><a href="index.php"><- Regresar</a></p>   
    </div>
</body>
</html>
