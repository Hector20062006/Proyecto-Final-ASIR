<?php
session_start();

// 1. Miramos si el usuario tiene una sesión iniciada
if (isset($_SESSION['role'])) {
    // Si la tiene, limpiamos su rol pasándolo a minúsculas
    $rol_actual = strtolower(trim($_SESSION['role']));
} else {
    // Si no ha pasado por el login, le dejamos el rol en blanco
    $rol_actual = '';
}

// 2. Seguridad: Si el rol NO es profesor (o profesores), lo expulsamos
if (strpos($rol_actual, 'profesor') === false) {
    header("Location: ../login.php");
    exit;
}

require '../conexion.php';
require '../header2.php'; 

// IMPORTANTE: Usamos el DNI guardado en la sesión
$dni_profesor = $_SESSION['dni'];

// 1. Consultar datos del profesor
$sql_user = "SELECT * FROM usuarios WHERE dni = '$dni_profesor'";
$res_user = mysqli_query($conexion, $sql_user);
$datos_profe = mysqli_fetch_assoc($res_user);

// 2. Consultar sus vehículos
$sql_vehi = "SELECT * FROM vehiculos WHERE dni_usuario = '$dni_profesor'";
$res_vehi = mysqli_query($conexion, $sql_vehi);
?>

<div class="container">
    <h2 style="color: var(--color-principal);">Panel del Profesor</h2>
    <p>Bienvenido/a, <strong><?php echo $datos_profe['nombre'] . " " . $datos_profe['apellidos']; ?></strong>.</p>

    <div style="margin-top: 30px;">
        <h3><i class="fas fa-user"></i> Mis Datos Personales</h3>
        <div class="tabla-responsive">
            <table>
                <tr>
                    <th>DNI</th>
                    <td><?php echo $datos_profe['dni']; ?></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><?php echo $datos_profe['email']; ?></td>
                </tr>
                <tr>
                    <th>Teléfono</th>
                    <td><?php echo $datos_profe['telefono']; ?></td>
                </tr>
            </table>
        </div>
    </div>
    <a href="editar_perfil.php"><button style="font-size: 12px; padding: 5px 10px;  background-color: #34495e;">Editar Perfil</button></a>

    <div style="margin-top: 40px;">
        <h3><i class="fas fa-car"></i> Mis Vehículos Registrados</h3>
        <div class="tabla-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Matrícula</th>
                        <th>Marca y Modelo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (mysqli_num_rows($res_vehi) > 0) {
                        while ($vehi = mysqli_fetch_assoc($res_vehi)) {
                            echo "<tr>
                                    <td><strong>{$vehi['matricula']}</strong></td>
                                    <td>{$vehi['marca_modelo']}</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2'>No tienes vehículos registrados. Contacta con el administrador.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <a href="editar_vehiculo.php"><button style="font-size: 12px; padding: 5px 10px; background-color: #34495e;">Cambiar Coche</button></a>

    <div style="margin-top: 40px;">
        <h3><i class="fas fa-history"></i> Mis Últimos Accesos al Parking</h3>
        <div class="tabla-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Movimiento</th>
                        <th>Matrícula</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Consultamos los accesos solo de las matrículas que pertenecen a este profesor
                    $sql_acc = "SELECT a.* FROM accesos a 
                                INNER JOIN vehiculos v ON a.matricula = v.matricula 
                                WHERE v.dni_usuario = '$dni_profesor' 
                                ORDER BY a.fecha_hora DESC LIMIT 5";
                    $res_acc = mysqli_query($conexion, $sql_acc);

                    if (mysqli_num_rows($res_acc) > 0) {
                        while ($acc = mysqli_fetch_assoc($res_acc)) {
                            $color = ($acc['tipo_movimiento'] == 'ENTRADA') ? 'color: #27ae60;' : 'color: #c0392b;';
                            $fecha = date("d/m/Y H:i", strtotime($acc['fecha_hora']));
                            echo "<tr>
                                    <td>{$fecha}</td>
                                    <td style='{$color} font-weight:bold;'>{$acc['tipo_movimiento']}</td>
                                    <td>{$acc['matricula']}</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>No se registran movimientos recientes.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 40px; background: #fdf2f2; padding: 20px; border-radius: 12px; border: 1px solid #f5c6cb; text-align: center;">
        <h3 style="color: #c0392b; margin-top: 0;"><i class="fas fa-exclamation-circle"></i> ¿Coche mal aparcado?</h3>
        <p>Si has detectado un vehículo que obstaculiza el parking, puedes avisar al propietario.</p>
        <a href="reportar_mal_aparcado.php"><button style="background-color: #c0392b; color: white;">Reportar Mal Aparcado</button></a>
    </div>

    <div style="margin-top: 30px; text-align: center;">
        <a href="../logout.php"><button style="background-color: var(--color-peligro);">Cerrar Sesión</button></a>
    </div>
</div>

<?php require '../footer2.php'; ?>