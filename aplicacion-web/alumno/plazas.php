<?php
session_start();

$rol_actual = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if (strpos($rol_actual, 'alumno') === false) {
    header("Location: ../login.php");
    exit;
}

require '../conexion.php';
require '../header2.php';

$resultado = mysqli_query($conexion, "SELECT * FROM plazas ORDER BY id_plaza ASC");
?>

<div class="container">
    <meta http-equiv="refresh" content="5">
    <h2 style="color: var(--color-principal);">Estado de las Plazas</h2>
    <p>Consulta en tiempo real el estado de las plazas del parking.</p>

    <div style="margin-top: 30px; display: flex; gap: 20px; flex-wrap: wrap;">
        <?php while ($plaza = mysqli_fetch_assoc($resultado)): ?>
            <div style="
            padding: 20px 30px;
            border-radius: 10px;
            text-align: center;
            color: white;
            background-color: <?= $plaza['estado'] == 'ocupada' ? '#c0392b' : '#27ae60' ?>;
            min-width: 150px;">
                <h3>Plaza <?= $plaza['id_plaza'] ?></h3>
                <p style="font-size: 18px; font-weight: bold;"><?= ucfirst($plaza['estado']) ?></p>
                <small><?= date("d/m/Y H:i", strtotime($plaza['ultima_actualizacion'])) ?></small>
            </div>
        <?php endwhile; ?>
    </div>

    <div style="margin-top: 30px;">
        <a href="index.php"><button style="background-color: #34495e;">Volver al Panel</button></a>
    </div>
</div>

<?php require '../footer2.php'; ?>