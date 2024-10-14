<?php 
include 'config.php';

session_start();  

// Variables para los mensajes de éxito o error
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_GET['accion']) && $_GET['accion'] == 'crear_cajero') {
        // Lógica para Crear Cajero
        $nombre_completo = $_POST['nombre_completo'];
        $usuario = $_POST['usuario'];
        $contrasena = $_POST['contrasena'];
        $confirmar_contrasena = $_POST['confirmar_contrasena'];

        if ($contrasena === $confirmar_contrasena) { 

            $mysqli = new mysqli($servername, $username, $password, $dbname);

            if ($mysqli->connect_error) {
                die("Error de conexión: " . $mysqli->connect_error);
            }

            // Llamar al procedimiento almacenado para crear cajero
            $stmt = $mysqli->prepare("CALL CrearCajero(?, ?, ?)");
            $stmt->bind_param("sss", $nombre_completo, $usuario, $contrasena);
            if ($stmt->execute()) {
                $mensaje = "<p style='color:green;'>Cajero creado con éxito</p>";
            } else {
                $mensaje = "<p style='color:red;'>Error al crear el cajero</p>";
            }

            $stmt->close();
            $mysqli->close();
        } else {
            $mensaje = "<p style='color:red;'>Las contraseñas no coinciden</p>";
        }
    } elseif (isset($_GET['accion']) && $_GET['accion'] == 'registrar_usuario') {
        // Lógica para Registrar Usuario
        $numero_cuenta = $_POST['numero_cuenta'];
        $correo_electronico = $_POST['correo_electronico'];
        $dpi = $_POST['dpi'];
        $contrasena = $_POST['contrasena'];
        $confirmar_contrasena = $_POST['confirmar_contrasena'];

        if ($contrasena === $confirmar_contrasena) { 
            
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
                        $hashed_password = password_hash($contrasena, PASSWORD_BCRYPT);
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
}

if (isset($_SESSION['usuario'])) {
    $bienvenida = "Bienvenido al sitio: " . $_SESSION['usuario'];
    $loginButton = "";  
    $logoutButton = '
                    <nav>
                        <ul>
                            <li><a href="index.php?accion=crear_cajero">Crear Cajero</a></li> 
                            <li><a href="index.php?accion=gestionar_cajeros">Gestionar Cajeros</a></li>
                            <li><a href="index.php?accion=monitor_transferencias">Monitor de transferencias</a></li>
                        </ul>
                    </nav>  
                    <hr>
                    <form action="logout.php" method="POST"> 
                        <button type="submit">Cerrar Sesión</button>
                    </form> 
                    ';
} else {
    $bienvenida = '<div class="no-session">No sesión iniciada</div>';
    $loginButton = '
            <nav>
                <ul>
                    <li><a href="index.php">Principal</a></li>
                    <li><a href="login.php">Administrador</a></li>
                    <li><a href="loginUsuario.php">Usuario</a></li>
                    <li><a href="loginCajero.php">Cajero</a></li>
                    <li><a href="index.php?accion=registrar_usuario">Registrar usuario</a></li>
                </ul>
            </nav>';
    $logoutButton = "";  
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="styles.css"> 
    <style>
        nav ul {
            list-style-type: none; 
            padding: 0; 
            margin: 0; 
            display: flex; 
            gap: 10px; 
        }
        
        nav ul li {
            display: inline; 
        }
        
        nav a {
            text-decoration: none; 
            color: black;
        }

        nav {
            display: flex;
            justify-content: space-between; 
        }

        .no-session {
            border: 2px solid black; 
            padding: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;  
        }

        /* Estilos para el formulario */
        .form-container {
            max-width: 400px;
            margin: 20px auto;
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

        /* Estilos para el mensaje de éxito o error */
        .mensaje {
            text-align: center;
            margin-top: 20px;
            font-size: 16px;
        }

        .mensaje p.success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
        }

        .mensaje p.error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>

    <main>
        <header>
            <img width="150px" src="images/bank_logo.png" alt="logo del banco">
            <h1>Banco Nuevo</h1>
            <hr> 
            <div>
                <?php echo $loginButton; ?> 
            </div>
            <div>  
                <?php echo $logoutButton; ?>    
            </div>
        </header>

        <section>
            <h2 class="bienvenida">
                <?php echo $bienvenida; ?> 
            </h2>

            <!-- Mostrar el formulario de Crear Cajero -->
            <?php if (isset($_GET['accion']) && $_GET['accion'] == 'crear_cajero'): ?>
                <div class="form-container">
                    <h1>Crear Cajero</h1>
                    <form action="index.php?accion=crear_cajero" method="POST">
                        <label for="nombre_completo">Nombre Completo:</label>
                        <input type="text" id="nombre_completo" name="nombre_completo" required>

                        <label for="usuario">Usuario:</label>
                        <input type="text" id="usuario" name="usuario" required>

                        <label for="contrasena">Contraseña:</label>
                        <input type="password" id="contrasena" name="contrasena" required>

                        <label for="confirmar_contrasena">Confirmar Contraseña:</label>
                        <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" required>

                        <button type="submit">Crear Cajero</button>
                    </form>

                    <!-- Mostrar mensaje debajo del formulario -->
                    <div class="mensaje">
                        <?php echo $mensaje; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['accion']) && $_GET['accion'] == 'monitor_transferencias'): ?>
    <div class="form-container">
        <h1>Monitor de Transferencias</h1>

        <h2>Sección 1: Datos Estadísticos</h2>
        <ul>
        <?php
                // Conexión a la base de datos
                $mysqli = new mysqli($servername, $username, $password, $dbname); 
                if ($mysqli->connect_error) {
                    die("Error de conexión: " . $mysqli->connect_error);
                }

                // Fecha de hoy
                $hoy = date("Y-m-d");
 
                // Conexión a la base de datos
                $mysqli = new mysqli($servername, $username, $password, $dbname);
                if ($mysqli->connect_error) {
                    die("Error de conexión: " . $mysqli->connect_error);
                }

                // Fecha de hoy
                $hoy = date("Y-m-d");

                // Llamar al procedimiento almacenado para obtener las estadísticas del día
                $stmt = $mysqli->prepare("CALL ObtenerEstadisticasDelDia(?)");
                $stmt->bind_param("s", $hoy);
                $stmt->execute();  
                $stmt->bind_result($cuentas_creadas_hoy, $usuarios_registrados_hoy, $transacciones_hoy, $depositos_hoy, $retiros_hoy);
 
                $stmt->fetch(); 
                $stmt->close();

                ?>

                <li>Cantidad de cuentas creadas hoy: <?php echo $cuentas_creadas_hoy; ?></li>
                <li>Cantidad de usuarios cliente registrados hoy: <?php echo $usuarios_registrados_hoy; ?></li>
                <li>Cantidad de transacciones hoy: <?php echo $transacciones_hoy; ?></li>
                <li>Cantidad de depósitos hoy: <?php echo $depositos_hoy; ?></li>
                <li>Cantidad de retiros hoy: <?php echo $retiros_hoy; ?></li>
            </ul>

            <h2>Sección 2: Gráfica - Depósitos vs Retiros</h2>
            <canvas id="graficoTransferencias" width="400" height="200"></canvas>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                var ctx = document.getElementById('graficoTransferencias').getContext('2d');
                var graficoTransferencias = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Depósitos', 'Retiros'],
                        datasets: [{
                            label: 'Depósitos',
                            data: [<?php
                                // Obtener la cantidad total monetaria de depósitos
                                $stmt = $mysqli->prepare("SELECT SUM(monto) FROM transaccion WHERE tipo_transaccion = 'deposito' AND DATE(fecha) = ?");
                                $stmt->bind_param("s", $hoy);
                                $stmt->execute();
                                $stmt->bind_result($total_depositos);
                                $stmt->fetch();
                                $stmt->close();

                                // Obtener la cantidad total monetaria de retiros
                                $stmt = $mysqli->prepare("SELECT SUM(monto) FROM transaccion WHERE tipo_transaccion = 'retiro' AND DATE(fecha) = ?");
                                $stmt->bind_param("s", $hoy);
                                $stmt->execute();
                                $stmt->bind_result($total_retiros);
                                $stmt->fetch();
                                $stmt->close();

                                echo $total_depositos ? $total_depositos : 0;
                                echo ', ';
                                echo $total_retiros ? $total_retiros : 0;
                            ?>],
                            backgroundColor: ['#4CAF50', '#FF5733'],
                            borderColor: ['#4CAF50', '#FF5733'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            </script>
        </div>
    <?php endif; ?>


            <!-- Mostrar el formulario de Registrar Usuario -->
            <?php if (isset($_GET['accion']) && $_GET['accion'] == 'registrar_usuario'): ?>
                <div class="form-container">
                    <h1>Registrar Usuario</h1>
                    <form action="index.php?accion=registrar_usuario" method="POST">
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
            <?php endif; ?>

            <?php if (isset($_GET['accion']) && $_GET['accion'] == 'gestionar_cajeros'): ?>
    <div class="form-container">
        <h1>Gestión de Cajeros</h1>
            <?php
                // Conexión a la base de datos

                $mysqli = new mysqli($servername, $username, $password, $dbname);

                if ($mysqli->connect_error) {
                    die("Error de conexión: " . $mysqli->connect_error);
                }

                // Llamar al procedimiento almacenado para obtener la lista de cajeros
                $stmt = $mysqli->prepare("CALL ObtenerCajeros()");
                $stmt->execute();

                // Obtener el resultado
                $result = $stmt->get_result();

                // Verificar si se encontraron cajeros
                if ($result->num_rows > 0) {
                    echo '<table border="1">';
                    echo '<tr>';
                    echo '<th>Nombre Completo</th>';
                    echo '<th>Usuario</th>';
                    echo '<th>Estado</th>';
                    echo '<th>Acciones</th>';
                    echo '</tr>';

                    // Mostrar los cajeros en la tabla
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['nombre_completo'] . "</td>";
                        echo "<td>" . $row['usuario'] . "</td>";
                        echo "<td>" . $row['estado'] . "</td>";
                        echo "<td>";
                        if ($row['estado'] == 'activo') {
                            echo "<a href='bloquear_cajero.php?id=" . $row['id'] . "'>Bloquear</a>";
                        } else {
                            echo "<a href='desbloquear_cajero.php?id=" . $row['id'] . "'>Desbloquear</a>";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }

                    echo '</table>';
                } else {
                    echo "No se encontraron cajeros.";
                }
 
                $stmt->close();
                $mysqli->close();
            ?>
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
