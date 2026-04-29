<?php
session_start();
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'administrador') {
    header("Location: ../login.php");
    exit;
}
include '../header2.php';
include '../conexion.php';

$filtro = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
?>

<div class="container container-lg">
    <h2>Actualizar Usuarios</h2>
    <p>Busca un usuario para modificar sus datos personales, contraseña o información de su vehículo.</p>

    <!-- Filtro de Búsqueda -->
    <form method="GET" action="actualizar_usuario.php" class="filtro-form">
        <div class="form-row" style="align-items: flex-end;">
            <div class="form-group-lg">
                <label for="buscar" class="form-label">🔍 Buscar por Nombre, Apellido o DNI:</label>
                <input type="text" name="buscar" id="buscar" class="form-control" value="<?php echo htmlspecialchars($filtro); ?>" placeholder="Ej: Juan, Fernandez, 12345678A" autocomplete="off">
            </div>
            <div class="btn-group">
                <button type="submit" class="btn-blue btn-lg">Buscar</button>
                <?php if ($filtro !== ''): ?>
                    <a href="actualizar_usuario.php" class="link-grow"><button type="button" class="btn-red btn-lg">Limpiar</button></a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <?php
    $where_clause = "";
    if ($filtro !== '') {
        $busqueda = mysqli_real_escape_string($conexion, $filtro);
        $where_clause = " WHERE u.dni LIKE '%$busqueda%' OR u.nombre LIKE '%$busqueda%' OR u.apellidos LIKE '%$busqueda%'";
    }

    $sql = "SELECT u.dni, u.nombre, u.apellidos, u.email, u.telefono, r.nombre_rol 
            FROM usuarios u 
            INNER JOIN roles r ON u.id_rol = r.id_rol
            $where_clause
            ORDER BY u.nombre ASC";
            
    $resultado = mysqli_query($conexion, $sql);

    if (mysqli_num_rows($resultado) > 0) {
        echo "<div class='tabla-responsive'>
            <table class='table-full'>
            <thead>
                <tr>
                    <th>DNI</th>
                    <th>Nombre y Apellidos</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Rol</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>";
            
        while ($registro = mysqli_fetch_row($resultado)) {
            $rol = ucfirst($registro[5]);
            echo "<tr>
                    <td><strong>{$registro[0]}</strong></td>
                    <td>{$registro[1]} {$registro[2]}</td>
                    <td>{$registro[3]}</td>
                    <td>{$registro[4]}</td>
                    <td>{$rol}</td>
                    <td>
                        <a href='actualizar2_usuario.php?dni={$registro[0]}'>
                           <button type='button' class='btn-green'>✏️ Editar</button>
                        </a>
                    </td>
                  </tr>";
        }
        echo "</tbody></table></div><br>";
    } else {
        echo "<div class='alerta-vacia'>
                <p class='alerta-vacia-texto'>No se encontraron usuarios que coincidan con la búsqueda.</p>
              </div>";
    }

    mysqli_close($conexion);
    ?>

    <a href="index.php"><button type="button" class="btn-gray btn-gray-mt10">Volver al panel</button></a>
</div>

<?php include '../footer2.php'; ?>