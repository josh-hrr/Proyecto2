<?php 
include 'config.php';

session_start();

if (isset($_SESSION['usuario'])) {
    $bienvenida = "Bienvenido al sitio: " . $_SESSION['usuario'];
    $loginButton = "";  
    $logoutButton = '
        <nav>
            <ul>
                <li><a href="?accion=crear_cuenta_monetaria">Crear Cuenta Monetaria</a></li> 
                <li><a href="?accion=deposito_monetario">Depósito Monetario</a></li> 
                <li><a href="?accion=retiro_monetario">Retiro Monetario</a></li>
            </ul>
        </nav>
        <hr>
        <form action="logout.php" method="POST"> 
            <button type="submit">Cerrar Sesión</button>
        </form>';
} else {
    $bienvenida = '<div class="no-session">No sesión iniciada</div>';
    $loginButton = '
        <nav>
            <ul>
                <li><a href="#">Principal</a></li>
                <li><a href="login.php">Administrador</a></li>
                <li><a href="#">Usuario</a></li>
                <li><a href="loginCajero.php">Cajero</a></li>
                <li><a href="">Registrar usuario</a></li>
            </ul>
        </nav>';
    $logoutButton = "";  
}

// Variables para los mensajes de éxito o error
$mensaje = '';
$nombre_cuenta = '';
$dpi = '';
$monto_inicial = ''; 

// Procesar formularios según la acción enviada
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mysqli = new mysqli($servername, $username, $password, $dbname);

    if ($mysqli->connect_error) {
        die("Error de conexión: " . $mysqli->connect_error);
    }

    // Procesar formulario de Crear Cuenta Monetaria
    if (isset($_POST['formulario']) && $_POST['formulario'] === 'crear_cuenta_monetaria') {
        $nombre_cuenta = $_POST['nombre_cuenta'];
        $dpi = $_POST['dpi'];
        $monto_inicial = $_POST['monto_inicial'];

        // Generar un número de cuenta único
        $numero_cuenta = rand(1000000000, 9999999999); 

        // Llamar al procedimiento almacenado para crear la cuenta monetaria
        $stmt = $mysqli->prepare("CALL CrearCuentaMonetaria(?, ?, ?, ?)");
        $stmt->bind_param("sssi", $numero_cuenta, $nombre_cuenta, $dpi, $monto_inicial);

        if ($stmt->execute()) {
            $mensaje = "<p style='color:green;'>Cuenta Monetaria creada con éxito. Número de cuenta: <strong>" . $numero_cuenta . "</strong></p>";
            $nombre_cuenta = '';
            $dpi = '';
            $monto_inicial = '';
        } else {
            $mensaje = "<p style='color:red;'>Error al crear la Cuenta Monetaria</p>";
        }
        $stmt->close();
    }

    if (isset($_POST['formulario']) && $_POST['formulario'] === 'deposito_monetario') {
        $numero_cuenta = $_POST['numero_cuenta'];
        $cantidad = $_POST['cantidad'];
    
        // Llamar al procedimiento almacenado para depósito monetario
        $stmt = $mysqli->prepare("CALL DepositoMonetario(?, ?)");
        $stmt->bind_param("sd", $numero_cuenta, $cantidad);
    
        if ($stmt->execute()) {
            $mensaje = "<p style='color:green;'>Depósito realizado con éxito.</p>";
        } else {
            $mensaje = "<p style='color:red;'>Error al realizar el depósito.</p>";
        }
        $stmt->close();
    }
    
    if (isset($_POST['formulario']) && $_POST['formulario'] === 'retiro_monetario') {
        $numero_cuenta = $_POST['numero_cuenta'];
        $cantidad = $_POST['cantidad'];
    
        // Llamar al procedimiento almacenado para retiro monetario
        $stmt = $mysqli->prepare("CALL RetiroMonetario(?, ?)");
        $stmt->bind_param("sd", $numero_cuenta, $cantidad);
    
        if ($stmt->execute()) {
            $mensaje = "<p style='color:green;'>Retiro realizado con éxito.</p>";
        } else {
            $mensaje = "<p style='color:red;'>Error al realizar el retiro.</p>";
        }
        $stmt->close();
    } 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banco</title>
    <link rel="stylesheet" href="styles.css"> 

    <style>
        .form-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
        }

        .form-container label {
            font-weight: bold;
            margin-bottom: 8px;
            display: block;
            color: #333;
        }

        .form-container input[type="text"],
        .form-container input[type="number"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 14px;
        }

        .form-container button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
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

<main>
    <header>
        <img width="150px" src="images/bank_logo.png" alt="logo del banco">
        <h1>Banco Nuevo</h1>
        <hr> 
        <div><?php echo $loginButton; ?></div>
        <div><?php echo $logoutButton; ?></div>
    </header>

    <section>
        <h2 class="bienvenida"><?php echo $bienvenida; ?></h2>
    </section>

    <section>
        <?php
        // Mostrar formulario de Crear Cuenta Monetaria
        if ((isset($_GET['accion']) && $_GET['accion'] === 'crear_cuenta_monetaria') || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['formulario']) && $_POST['formulario'] === 'crear_cuenta_monetaria')): ?>
            <div class="form-container">
                <h1>Crear Cuenta Monetaria</h1>
                <form action="bienvenida_cajero.php?accion=crear_cuenta_monetaria" method="POST">
                    <input type="hidden" name="formulario" value="crear_cuenta_monetaria">
                    <label for="nombre_cuenta">Nombre Completo del Titular:</label>
                    <input type="text" id="nombre_cuenta" name="nombre_cuenta" required value="<?php echo isset($nombre_cuenta) ? $nombre_cuenta : ''; ?>">

                    <label for="dpi">DPI:</label>
                    <input type="text" id="dpi" name="dpi" required value="<?php echo isset($dpi) ? $dpi : ''; ?>">

                    <label for="monto_inicial">Monto Inicial:</label>
                    <input type="number" id="monto_inicial" name="monto_inicial" required value="<?php echo isset($monto_inicial) ? $monto_inicial : ''; ?>">

                    <button type="submit">Crear Cuenta Monetaria</button>
                </form>

                <div class="mensaje">
                    <?php echo $mensaje; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php
        // Mostrar formulario de Depósito Monetario
        if (isset($_GET['accion']) && $_GET['accion'] === 'deposito_monetario'): ?>
            <div class="form-container">
                <h1>Depósito Monetario</h1>
                <form action="bienvenida_cajero.php?accion=deposito_monetario" method="POST">
                    <input type="hidden" name="formulario" value="deposito_monetario">
                    <label for="numero_cuenta">Número de Cuenta:</label>
                    <input type="text" id="numero_cuenta" name="numero_cuenta" required>

                    <label for="cantidad">Cantidad a Depositar:</label>
                    <input type="number" id="cantidad" name="cantidad" required>

                    <button type="submit">Depositar</button>
                </form>

                <div class="mensaje">
                    <?php echo $mensaje; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php
        // Mostrar formulario de Retiro Monetario
        if (isset($_GET['accion']) && $_GET['accion'] === 'retiro_monetario'): ?>
            <div class="form-container">
                <h1>Retiro Monetario</h1>
                <form action="bienvenida_cajero.php?accion=retiro_monetario" method="POST">
                    <input type="hidden" name="formulario" value="retiro_monetario">
                    <label for="numero_cuenta">Número de Cuenta:</label>
                    <input type="text" id="numero_cuenta" name="numero_cuenta" required>

                    <label for="cantidad">Cantidad a Retirar:</label>
                    <input type="number" id="cantidad" name="cantidad" required>

                    <button type="submit">Retirar</button>
                </form>

                <div class="mensaje">
                    <?php echo $mensaje; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer>
    <p>&copy; Derechos Reservados 2024</p>
</footer>

</body>
</html>
