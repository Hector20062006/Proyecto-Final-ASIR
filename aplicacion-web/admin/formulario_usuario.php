<?php
session_start();

// Comprobación de seguridad actualizada
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'administrador') {
    header("Location: ../login.php");
    exit;
}
include '../header2.php'; 
include '../conexion.php';

$filtro = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
?>

<div class="container container-lg">
    <h2>Añadir Nuevo Usuario</h2>
    <p>Rellena los datos para crear un nuevo usuario. Puedes consultar abajo si ya está registrado.</p>
    
    <form action="guardar_usuario.php" method="POST" class="form-nueva-alta">
        
        <h3>Datos Personales</h3>
        <div class="form-row">
            <div class="form-group-md">
                <label class="form-label">DNI:</label><br>
                <input type="text" name="dni" required maxlength="9" placeholder="Ej: 12345678A" class="form-control">
            </div>
            <div class="form-group-md">
                <label class="form-label">Nombre:</label><br>
                <input type="text" name="nombre" placeholder="Ej: Pepito" required class="form-control">
            </div>
            <div class="form-group-md">
                <label class="form-label">Apellidos:</label><br>
                <input type="text" name="apellidos" placeholder="Ej: Fernandez Heredia" required class="form-control">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group-md">
                <label class="form-label">Email:</label><br>
                <input type="email" name="email" placeholder="Ej: usuario@iesiliberis.es" required class="form-control">
            </div>
            <div class="form-group-md">
                <label class="form-label">Teléfono:</label><br>
                <input type="text" name="telefono" placeholder="Ej: 123456789" class="form-control">
            </div>
            <div class="form-group-md">
                <label class="form-label">Contraseña:</label><br>
                <input type="password" name="password" required class="form-control">
            </div>
            <div class="form-group-md">
                <label class="form-label">Rol:</label><br>
                <select name="id_rol" required class="form-control">
                    <option value="2">Profesor</option>
                    <option value="3">Alumnos</option>
                    <option value="1">Administrador</option>
                </select>
            </div>
        </div>

        <hr class="hr-separador">

        <h3>Datos del Vehículo</h3>
        <p><i>(Dejar en blanco si el usuario no tiene vehículo)</i></p>
        <div class="form-row">
            <div class="form-group-md">
                <label class="form-label">Matrícula:</label><br>
                <input type="text" name="matricula" maxlength="10" placeholder="Ej: 1234ABC" class="form-control">
            </div>
            <div class="form-group-md">
                <label class="form-label">Marca y Modelo:</label><br>
                <input type="text" name="marca_modelo" maxlength="50" placeholder="Ej: Seat Leon" class="form-control">
            </div>
        </div>

        <br>
        <div class="btn-group">
            <button type="submit" class="btn-green btn-lg btn-grow">Guardar Usuario</button>
            <a href="index.php" class="link-grow"><button type="button" class="btn-gray btn-lg btn-w100">Cancelar</button></a>
        </div>
    </form>

    <hr class="hr-separador" style="margin: 40px 0;">

    <!-- SECCIÓN DE BÚSQUEDA AÑADIDA PARA VERIFICAR USUARIOS -->
    <h2>Usuarios ya registrados</h2>
    <p>Comprueba fácilmente si un usuario ya existe en el sistema para evitar duplicados.</p>

    <form method="GET" action="formulario_usuario.php" class="filtro-form">
        <div class="form-row" style="align-items: flex-end;">
            <div class="form-group-lg">
                <label for="buscar" class="form-label">🔍 Buscar por Nombre, Apellido o DNI:</label>
                <input type="text" name="buscar" id="buscar" class="form-control" value="<?php echo htmlspecialchars($filtro); ?>" placeholder="Ej: Juan, Fernandez, 12345678A" autocomplete="off">
            </div>
            <div class="btn-group">
                <button type="submit" class="btn-blue btn-lg">Buscar</button>
                <?php if ($filtro !== ''): ?>
                    <a href="formulario_usuario.php" class="link-grow"><button type="button" class="btn-red btn-lg">Limpiar</button></a>
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

    $sql = "SELECT u.dni, u.nombre, u.apellidos, u.email, r.nombre_rol 
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
                    <th>Rol</th>
                </tr>
            </thead>
            <tbody>";
            
        while ($registro = mysqli_fetch_row($resultado)) {
            $rol = ucfirst($registro[4]);
            echo "<tr>
                    <td><strong>{$registro[0]}</strong></td>
                    <td>{$registro[1]} {$registro[2]}</td>
                    <td>{$registro[3]}</td>
                    <td>{$rol}</td>
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

</div>

<?php include '../footer2.php'; ?>