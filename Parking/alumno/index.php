<?php
session_start();

// 1. Seguridad: Comprobamos que el rol sea de alumno
$rol_actual = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';

if (strpos($rol_actual, 'alumno') === false) {
    header("Location: ../login.php");
    exit;
}

require '../conexion.php';
require '../header2.php'; 

// DNI del alumno en sesión
$dni_alumno = $_SESSION['dni']; 

// Consultas filtradas por su DNI para que no vea datos de otros
$sql_user = "SELECT * FROM usuarios WHERE dni = '$dni_alumno'";
$res_user = mysqli_query($conexion, $sql_user);
$datos_alumno = mysqli_fetch_assoc($res_user);

$sql_vehi = "SELECT * FROM vehiculos WHERE dni_usuario = '$dni_alumno'";
$res_vehi = mysqli_query($conexion, $sql_vehi);
?>

<div class="container">
    <h2 style="color: var(--color-principal);">Panel del Alumno</h2>
    <p>Bienvenido/a, <strong><?php echo $datos_alumno['nombre'] . " " . $datos_alumno['apellidos']; ?></strong>.</p>
    <p style="font-style: italic; color: #7f8c8d;">Nota: Tus datos están en modo lectura. Para cambios, contacta con secretaría.</p>

    <div style="margin-top: 30px;">
        <h3><i class="fas fa-id-card"></i> Mis Datos Personales</h3>
        <div class="tabla-responsive">
            <table>
                <tr>
                    <th style="width: 30%;">DNI</th>
                    <td><?php echo $datos_alumno['dni']; ?></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><?php echo $datos_alumno['email']; ?></td>
                </tr>
                <tr>
                    <th>Teléfono</th>
                    <td><?php echo $datos_alumno['telefono']; ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div style="margin-top: 40px;">
        <h3><i class="fas fa-car"></i> Mis Vehículos Autorizados</h3>
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
                        echo "<tr><td colspan='2'>No tienes vehículos registrados.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 40px;">
        <h3><i class="fas fa-door-open"></i> Mis Accesos Recientes</h3>
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
                    $sql_acc = "SELECT a.* FROM accesos a 
                                INNER JOIN vehiculos v ON a.matricula = v.matricula 
                                WHERE v.dni_usuario = '$dni_alumno' 
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
                        echo "<tr><td colspan='3'>No hay registros de entrada/salida.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: center;">
        <a href="../logout.php"><button style="background-color: var(--color-peligro);">Cerrar Sesión</button></a>
    </div>
</div>

<?php require '../footer2.php'; ?>