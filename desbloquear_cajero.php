<?php 
include 'config.php';

// Conexión a la base de datos
$mysqli = new mysqli($servername, $username, $password, $dbname);

if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

$id = $_GET['id'];

// Llamar al procedimiento almacenado para desbloquear el cajero
$stmt = $mysqli->prepare("CALL DesbloquearCajero(?)");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: index.php?accion=gestionar_cajeros");
} else {
    echo "Error al desbloquear cajero.";
}

$stmt->close();
$mysqli->close();
?>
