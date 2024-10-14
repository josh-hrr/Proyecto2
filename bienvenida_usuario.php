<?php 
include 'config.php';

session_start();

// Verificar que el usuario haya iniciado sesión
if (isset($_SESSION['usuario'])) {
    $bienvenida = "Bienvenido al sitio: " . $_SESSION['usuario'];
    $loginButton = "";  
    $logoutButton = '
        <nav>
            <ul>
                <li><a href="?accion=agregar_cuenta_tercero">Agregar Cuentas de Terceros</a></li> 
                <li><a href="?accion=mis_cuentas_terceros">Mis Cuentas de Terceros</a></li> 
                <li><a href="?accion=transferencia_cuenta_tercero">Transferencia a Cuentas de Terceros</a></li>  
                <li><a href="?accion=estado_cuenta">Estado de cuenta</a></li>  
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

// Asegurarse de que el usuario_id esté definido en la sesión
if (!isset($_SESSION['usuario_id'])) {
    die("Error: No se ha establecido el ID del usuario.");
}

$usuario_id = $_SESSION['usuario_id'];

// Variables para los mensajes de éxito o error
$mensaje = ''; 

// Conectar a la base de datos
$mysqli = new mysqli($servername, $username, $password, $dbname);

if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

// Obtener el número de cuenta y el saldo del usuario
$stmt = $mysqli->prepare("SELECT numero_cuenta, monto_inicial FROM cuentamonetaria WHERE dpi = (SELECT dpi FROM usuario WHERE id = ?)");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$stmt->bind_result($numero_cuenta_usuario, $saldo_usuario);
$stmt->fetch();
$stmt->close();

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Agregar Cuentas de Terceros
    if (isset($_POST['formulario']) && $_POST['formulario'] === 'agregar_cuenta_tercero') {
        $numero_cuenta = $_POST['numero_cuenta'];
        $monto_maximo = $_POST['monto_maximo'];
        $transacciones_max_diarias = $_POST['transacciones_max_diarias'];
        $alias = $_POST['alias'];
    
        $stmt = $mysqli->prepare("CALL AgregarCuentaTercero(?, ?, ?, ?, ?)");
        $stmt->bind_param("isdss", $usuario_id, $numero_cuenta, $monto_maximo, $transacciones_max_diarias, $alias);
    
        if ($stmt->execute()) {
            $mensaje = "<p style='color:green;'>Cuenta de Tercero agregada con éxito.</p>";
        } else {
            $mensaje = "<p style='color:red;'>Error al agregar la Cuenta de Tercero.</p>";
        }
        $stmt->close();
    }
    

    // Transferencia a Cuenta de Tercero
    if (isset($_POST['formulario']) && $_POST['formulario'] === 'transferencia_cuenta_tercero') {
        $cuenta_tercero_id = $_POST['cuenta_tercero'];
        $monto = $_POST['monto'];

        // Obtener detalles de la cuenta de tercero
        $stmt = $mysqli->prepare("SELECT numero_cuenta, monto_maximo, transacciones_max_diarias FROM cuentatercero WHERE id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $cuenta_tercero_id, $usuario_id);
        $stmt->execute();
        $stmt->bind_result($numero_cuenta_tercero, $monto_maximo, $transacciones_max_diarias);
        $stmt->fetch();
        $stmt->close();

        // Validar si el usuario tiene saldo suficiente para la transferencia
        if ($saldo_usuario >= $monto) { 
            // Validar si el monto no excede el monto máximo permitido
            if ($monto <= $monto_maximo) {
                // Contar las transacciones realizadas hoy
                $fecha_hoy = date("Y-m-d");
                $stmt = $mysqli->prepare("SELECT COUNT(*) FROM transaccion WHERE cuenta_tercero_id = ? AND fecha = ? AND tipo_transaccion = 'transferencia'");
                $stmt->bind_param("is", $cuenta_tercero_id, $fecha_hoy);
                $stmt->execute();
                $stmt->bind_result($transacciones_hoy);
                $stmt->fetch();
                $stmt->close();

                // Validar si no se excede el máximo de transacciones diarias
                if ($transacciones_hoy < $transacciones_max_diarias) {
                    // Llamar al stored procedure para realizar la transferencia
                    $stmt = $mysqli->prepare("CALL TransferenciaCuentaTercero(?, ?, ?, ?)");
                    $stmt->bind_param("iids", $cuenta_tercero_id, $usuario_id, $monto, $numero_cuenta_usuario);
 
                    if ($stmt->execute()) {
                        $mensaje = "<p style='color:green;'>Transferencia realizada con éxito.</p>";
                    } else {
                        $mensaje = "<p style='color:red;'>Error al realizar la transferencia: " . $stmt->error . "</p>";
                    }

                    $stmt->close();
                } else {
                    $mensaje = "<p style='color:red;'>Se ha excedido el número máximo de transacciones diarias.</p>";
                }
            } else {
                $mensaje = "<p style='color:red;'>El monto ingresado supera el máximo permitido para esta cuenta.</p>";
            }
        } else {
            $mensaje = "<p style='color:red;'>No tienes saldo suficiente para realizar la transferencia.</p>";
        }
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
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;  
            margin: 20px auto;
            font-family: Arial, sans-serif;
            box-sizing: border-box;  
        }

        .form-container label {
            font-weight: bold;
            margin-bottom: 8px;
            display: block;
            color: #333;
        }

        .form-container input[type="text"],
        .form-container input[type="number"],
        .form-container select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box; 
        }

        .form-container button {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
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

        .form-container h1 {
            text-align: center;
            color: #333;
        }

        /* Estilos para tablas */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
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
        // Agregar Cuenta de Tercero
        if (isset($_GET['accion']) && $_GET['accion'] === 'agregar_cuenta_tercero'): ?>
            <div class="form-container">
                <h1>Agregar Cuenta de Tercero</h1>
                <form action="bienvenida_usuario.php?accion=agregar_cuenta_tercero" method="POST">
                    <input type="hidden" name="formulario" value="agregar_cuenta_tercero">
                    <label for="numero_cuenta">Número de Cuenta:</label>
                    <input type="text" id="numero_cuenta" name="numero_cuenta" required>

                    <label for="monto_maximo">Monto Máximo a Transferir:</label>
                    <input type="number" id="monto_maximo" name="monto_maximo" required>

                    <label for="transacciones_max_diarias">Cantidad Máxima de Transacciones Diarias:</label>
                    <input type="number" id="transacciones_max_diarias" name="transacciones_max_diarias" required>

                    <label for="alias">Alias de la Cuenta:</label>
                    <input type="text" id="alias" name="alias" required>

                    <button type="submit">Agregar Cuenta</button>
                </form>

                <div class="mensaje">
                    <?php echo $mensaje; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php
        // Transferencia a Cuenta de Tercero
        if (isset($_GET['accion']) && $_GET['accion'] === 'transferencia_cuenta_tercero'): ?>
            <div class="form-container">
                <h1>Transferencia a Cuenta de Tercero</h1>
                <form action="bienvenida_usuario.php?accion=transferencia_cuenta_tercero" method="POST">
                    <input type="hidden" name="formulario" value="transferencia_cuenta_tercero">
                    <label for="cuenta_tercero">Cuenta de Tercero:</label>
                    <select id="cuenta_tercero" name="cuenta_tercero" required>
                        <?php
                        // Obtener las cuentas de terceros del usuario
                        $stmt = $mysqli->prepare("SELECT id, alias FROM cuentatercero WHERE usuario_id = ?");
                        $stmt->bind_param("i", $usuario_id);
                        $stmt->execute();
                        $stmt->bind_result($cuenta_id, $alias);
                        while ($stmt->fetch()) {
                            echo "<option value='$cuenta_id'>$alias</option>";
                        }
                        $stmt->close();
                        ?>
                    </select>

                    <label for="monto">Monto a Transferir:</label>
                    <input type="number" id="monto" name="monto" required>

                    <button type="submit">Transferir</button>
                </form>

                <div class="mensaje">
                    <?php echo $mensaje; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php
        // Mis Cuentas de Terceros
        if (isset($_GET['accion']) && $_GET['accion'] === 'mis_cuentas_terceros'): ?>
            <div class="form-container">
                <h1>Mis Cuentas de Terceros</h1>
                <table>
                    <thead>
                        <tr>
                            <th>Número de Cuenta</th>
                            <th>Alias</th>
                            <th>Monto Máximo</th>
                            <th>Transacciones Máximas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $mysqli->prepare("SELECT numero_cuenta, alias, monto_maximo, transacciones_max_diarias FROM cuentatercero WHERE usuario_id = ?");
                        $stmt->bind_param("i", $usuario_id);
                        $stmt->execute();
                        $stmt->bind_result($numero_cuenta, $alias, $monto_maximo, $transacciones_max_diarias);
                        while ($stmt->fetch()) {
                            echo "<tr><td>$numero_cuenta</td><td>$alias</td><td>$monto_maximo</td><td>$transacciones_max_diarias</td></tr>";
                        }
                        $stmt->close();
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php
        // Estado de Cuenta
        if (isset($_GET['accion']) && $_GET['accion'] === 'estado_cuenta'): ?>
            <div class="form-container">
                <h1>Estado de Cuenta</h1>
                <table>
                    <thead>
                        <tr>
                            <th>Tipo de Transacción</th>
                            <th>Número de Cuenta</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Mostrar todas las transacciones relacionadas con el usuario (depósitos, retiros, transferencias)
                        $stmt = $mysqli->prepare("CALL EstadoCuenta(?)");
                        $stmt->bind_param("i", $usuario_id);
                        $stmt->execute();
                        $stmt->bind_result($tipo_transaccion, $numero_cuenta, $monto, $fecha);
                        while ($stmt->fetch()) {
                            echo "<tr><td>$tipo_transaccion</td><td>$numero_cuenta</td><td>$monto</td><td>$fecha</td></tr>";
                        }
                        $stmt->close();
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer>
    <p>&copy; Derechos Reservados 2024</p>
</footer>

</body>
</html>
