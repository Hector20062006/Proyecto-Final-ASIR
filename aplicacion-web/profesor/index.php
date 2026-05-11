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

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- SECCIÓN: REPORTAR MAL APARCAMIENTO                                  -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div style="margin-top: 45px;">
        <h3><i class="fas fa-exclamation-triangle" style="color:#c0392b;"></i> Reportar Mal Aparcamiento</h3>
        <p style="color:#666; font-size:14px; margin-bottom:16px;">
            Introduce la matrícula del vehículo mal aparcado. Se enviará un aviso automático al canal de Telegram del parking y quedará registrado en el sistema.
        </p>

        <div id="reporte-card" style="
            background: #fff8f8;
            border: 2px solid #f0b8b8;
            border-radius: 10px;
            padding: 24px 28px;
            max-width: 520px;
        ">
            <form id="form-reporte" onsubmit="enviarReporte(event)">

                <!-- Matrícula -->
                <div style="margin-bottom: 16px;">
                    <label for="input-matricula" style="display:block; font-weight:bold; margin-bottom:6px; color:#444;">
                        <i class="fas fa-car"></i> Matrícula del vehículo:
                    </label>
                    <input
                        type="text"
                        id="input-matricula"
                        name="matricula"
                        maxlength="10"
                        placeholder="Ej: 1234ABC"
                        required
                        autocomplete="off"
                        style="
                            width: 100%;
                            padding: 10px 14px;
                            border: 1px solid #ccc;
                            border-radius: 6px;
                            font-size: 16px;
                            letter-spacing: 2px;
                            text-transform: uppercase;
                            box-sizing: border-box;
                        "
                        oninput="this.value = this.value.toUpperCase()"
                    >
                </div>

                <!-- Comentario opcional -->
                <div style="margin-bottom: 20px;">
                    <label for="input-comentario" style="display:block; font-weight:bold; margin-bottom:6px; color:#444;">
                        <i class="fas fa-comment"></i> Comentario (opcional):
                    </label>
                    <textarea
                        id="input-comentario"
                        name="comentario"
                        rows="3"
                        maxlength="300"
                        placeholder="Ej: Bloqueando la rampa de acceso..."
                        style="
                            width: 100%;
                            padding: 10px 14px;
                            border: 1px solid #ccc;
                            border-radius: 6px;
                            font-size: 14px;
                            box-sizing: border-box;
                            resize: vertical;
                        "
                    ></textarea>
                </div>

                <!-- Botón enviar -->
                <button
                    type="submit"
                    id="btn-reporte"
                    style="
                        background-color: #c0392b;
                        color: white;
                        padding: 11px 24px;
                        border: none;
                        border-radius: 6px;
                        font-size: 15px;
                        font-weight: bold;
                        cursor: pointer;
                        transition: background 0.2s;
                        width: 100%;
                    "
                    onmouseover="this.style.backgroundColor='#a93226'"
                    onmouseout="this.style.backgroundColor='#c0392b'"
                >
                    <i class="fas fa-paper-plane"></i> Enviar Aviso
                </button>
            </form>

            <!-- Feedback -->
            <div id="reporte-feedback" style="display:none; margin-top:16px; padding:12px 16px; border-radius:6px; font-size:14px;"></div>
        </div>
    </div>

    <!-- Script AJAX para el formulario de reporte -->
    <script>
    async function enviarReporte(e) {
        e.preventDefault();

        const matricula  = document.getElementById('input-matricula').value.trim();
        const comentario = document.getElementById('input-comentario').value.trim();
        const btn        = document.getElementById('btn-reporte');
        const feedback   = document.getElementById('reporte-feedback');

        if (!matricula) return;

        // Estado de carga
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        feedback.style.display = 'none';

        try {
            const formData = new FormData();
            formData.append('matricula',  matricula);
            formData.append('comentario', comentario);

            const response = await fetch('reportar_mal_aparcado.php', {
                method: 'POST',
                body:   formData
            });

            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error("Error parsing JSON:", parseError);
                console.error("Raw response:", text);
                feedback.style.background = '#f8d7da';
                feedback.style.border     = '1px solid #f5c6cb';
                feedback.style.color      = '#721c24';
                feedback.innerHTML = '❌ Error en la respuesta del servidor. Revisa la consola.';
                feedback.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Aviso';
                return;
            }

            if (data.success) {
                const isWarning = data.warning === true;
                feedback.style.background   = isWarning ? '#fff3cd' : '#d4edda';
                feedback.style.border       = isWarning ? '1px solid #ffc107' : '1px solid #c3e6cb';
                feedback.style.color        = isWarning ? '#856404'  : '#155724';
                feedback.innerHTML = (isWarning ? '⚠️ ' : '✅ ') + data.message
                    + (data.propietario !== 'Desconocido' ? '<br><strong>Propietario:</strong> ' + data.propietario : '');
                document.getElementById('form-reporte').reset();
            } else {
                feedback.style.background = '#f8d7da';
                feedback.style.border     = '1px solid #f5c6cb';
                feedback.style.color      = '#721c24';
                feedback.innerHTML = '❌ ' + data.message;
            }
        } catch (err) {
            feedback.style.background = '#f8d7da';
            feedback.style.border     = '1px solid #f5c6cb';
            feedback.style.color      = '#721c24';
            feedback.innerHTML = '❌ Error de conexión. Inténtalo de nuevo.';
        }

        feedback.style.display = 'block';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Aviso';
    }
    </script>

    <div style="margin-top: 40px; text-align: center;">
        <a href="../logout.php"><button style="background-color: var(--color-peligro);">Cerrar Sesión</button></a>
    </div>
</div>

<?php require '../footer2.php'; ?>