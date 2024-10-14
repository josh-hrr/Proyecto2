<?php
include 'config.php';

session_start();
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_completo = $_POST['nombre_completo'];
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];

    if ($contrasena === $confirmar_contrasena) {
        // Conectar a la base de datos  
        $mysqli = new mysqli($servername, $username, $password, $dbname);

        if ($mysqli->connect_error) {
            die("Error de conexión: " . $mysqli->connect_error);
        }

        // Llamar al procedimiento almacenado
        $stmt = $mysqli->prepare("CALL CrearCajero(?, ?, ?)");
        $stmt->bind_param("sss", $nombre_completo, $usuario, $contrasena);
        if ($stmt->execute()) {
            echo "<p>Cajero creado con éxito</p>";
        } else {
            echo "<p>Error al crear el cajero</p>";
        }

        $stmt->close();
        $mysqli->close();
    } else {
        echo "<p>Las contraseñas no coinciden</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cajero</title>
</head>
<body>
    <h1>Crear Cajero</h1>
    <form action="crear_cajero.php" method="POST">
        <label for="nombre_completo">Nombre Completo:</label>
        <input type="text" id="nombre_completo" name="nombre_completo" required>
        <br><br>
        <label for="usuario">Usuario:</label>
        <input type="text" id="usuario" name="usuario" required>
        <br><br>
        <label for="contrasena">Contraseña:</label>
        <input type="password" id="contrasena" name="contrasena" required>
        <br><br>
        <label for="confirmar_contrasena">Confirmar Contraseña:</label>
        <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" required>
        <br><br>
        <button type="submit">Crear Cajero</button>
    </form>
</body>
</html> 
 