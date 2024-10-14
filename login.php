<?php 
include 'config.php';

session_start();    
$mysqli = new mysqli($servername, $username, $password, $dbname);

if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

if (isset($_POST['username']) && isset($_POST['password'])) {
    $nombre_usuario = $_POST['username'];
    $contrasena = $_POST['password'];

    // Llamada al procedimiento almacenado
    $stmt = $mysqli->prepare("CALL VerificarUsuario(?, ?, @usuario)");
    $stmt->bind_param("ss", $nombre_usuario, $contrasena);
    $stmt->execute(); 
    
    // Consulta para obtener el valor de la variable de salida @usuario
    $select_result = $mysqli->query("SELECT @usuario AS usuario");
    $result_row = $select_result->fetch_assoc();
    $usuario = $result_row['usuario'];

    if ($usuario) {
        // Almacenar el nombre de usuario en la variable de sesión
        $_SESSION['usuario'] = $usuario; 
        header("Location: index.php");
        exit();
    } else {
        echo "<p class='mensaje-error'>Usuario o contraseña incorrectos</p>";
    }
    $stmt->close();
}
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrador</title>
    <style>
        .login-main {
            width: 300px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
            text-align: center;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        input[type="text"], input[type="password"] {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        button {
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #218838;
        }

        a {
            text-decoration: none;
            color: #6c757d;
        }

        a:hover {
            text-decoration: underline;
        }

        .mensaje-error {
            text-align: center;
            color: red;
        }
    </style>
</head>
<body>
    <main class="login-main">
        <h1>Login Administrador</h1>
        <form action="login.php" method="POST">
            <label for="username">Usuario de Administrador:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Iniciar Sesión</button>
        </form>
        <p><a href="index.php"><- Regresar</a></p>
    </main>
</body>
</html>
