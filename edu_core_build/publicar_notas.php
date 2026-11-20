<?php

session_start();
require "db.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION["rol"] != "docente") {
    header("Location: acceso_denegado.php");
    exit();
}

$idDocente = $_SESSION["id_usuario"];
$mensaje = "";

/* =========================================
   PUBLICAR UNA NOTA (o todas)
   ========================================= */
if (isset($_GET["publicar"])) {

    $idCalificacion = intval($_GET["publicar"]);

    // Verificar que la calificación pertenezca a una actividad de este docente
    $verif = $conexion->prepare(
        "SELECT c.id_calificacion FROM calificaciones c
         JOIN actividades a ON a.id_actividad = c.id_actividad
         JOIN docente_materia_grado dmg ON dmg.id_asignacion = a.id_asignacion
         WHERE c.id_calificacion = ? AND dmg.id_usuario = ?"
    );
    $verif->bind_param("ii", $idCalificacion, $idDocente);
    $verif->execute();

    if ($verif->get_result()->num_rows > 0) {
        $upd = $conexion->prepare("UPDATE calificaciones SET estado = 'Publicada' WHERE id_calificacion = ?");
        $upd->bind_param("i", $idCalificacion);
        $upd->execute();
        $mensaje = "Nota publicada correctamente.";
    }
}

if (isset($_GET["publicar_todas"])) {

    $updTodas = $conexion->prepare(
        "UPDATE calificaciones c
         JOIN actividades a ON a.id_actividad = c.id_actividad
         JOIN docente_materia_grado dmg ON dmg.id_asignacion = a.id_asignacion
         SET c.estado = 'Publicada'
         WHERE dmg.id_usuario = ? AND c.estado = 'Pendiente'"
    );
    $updTodas->bind_param("i", $idDocente);
    $updTodas->execute();
    $mensaje = "Todas las notas pendientes fueron publicadas.";
}

/* =========================================
   NOTAS PENDIENTES DE ESTE DOCENTE
   ========================================= */
$sql = "SELECT c.id_calificacion, est.nombres, est.apellidos, a.nombre AS actividad, c.nota, a.fecha, g.nombre AS grado
        FROM calificaciones c
        JOIN actividades a ON a.id_actividad = c.id_actividad
        JOIN docente_materia_grado dmg ON dmg.id_asignacion = a.id_asignacion
        JOIN estudiantes est ON est.id_estudiante = c.id_estudiante
        JOIN grados g ON g.id_grado = dmg.id_grado
        WHERE dmg.id_usuario = ? AND c.estado = 'Pendiente'
        ORDER BY a.fecha DESC";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $idDocente);
$stmt->execute();
$pendientes = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Publicar Notas</title>
    <link rel="stylesheet" href="style2.css">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- CSS -->
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">

        <div class="logo">
            
    <a href="login.php">
        <img src="img/logo.png" alt="logo">
    </a>
        </div>

        <ul class="menu">

            <li>
                <a href="historial.php">
                    <i class="bi bi-clock-history"></i>
                    <span>HISTORIAL</span>
                </a>
            </li>

            <li>
                <a href="asignar_nota.php">
                    <i class="bi bi-journal-check"></i>
                    <span>ASIGNAR NOTA</span>
                </a>
            </li>
            
            <li>
                <a href="notas_estudiantes.php">
                    <i class="bi bi-folder-fill"></i>
                    <span>VER NOTAS DE ESTUDIANTES</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <i class="bi bi-upload"></i>
                    <span>PUBLICAR NOTAS</span>
                </a>
            </li>

            <li>
                <a href="editar_notas.php">
                    <i class="bi bi-pencil-square"></i>
                    <span>EDITAR NOTAS</span>
                </a>
            </li>

            <li>
                <a href="perfil_docente.php">
                    <i class="bi bi-person-fill"></i>
                    <span>PERFIL</span>
                </a>
            </li>

        </ul>
        
    </div>

    <!-- MAIN -->
    <div class="main">

        <!-- NAVBAR -->
        <div class="navbar-custom">

            <div class="menu-icon">
                <i class="bi bi-list"></i>
            </div>

            <div class="user-info">
                <a href="perfil_docente.php">
                    <i class="bi bi-person-fill"></i>
                </a>

                <div>
                    <h6><?php echo h(strtoupper($_SESSION["nombre"])); ?></h6>
                    <p>DOCENTE</p>
                </div>

            <a href="cerrar_sesion.php" class="logout-btn">
                <i class="bi bi-box-arrow-right"></i>
            </a> 

            </div>

        </div>

        <!-- CONTENT -->
        <div class="content">

            <h2 class="title">Notas pendientes por publicar</h2>

            <?php if ($mensaje): ?>
                <p style="color:#2e7d32;"><strong><?php echo h($mensaje); ?></strong></p>
            <?php endif; ?>

            <?php if ($pendientes && $pendientes->num_rows > 0): ?>
                <p><a href="publicar_notas.php?publicar_todas=1" class="btn-tipo" onclick="return confirm('¿Publicar todas las notas pendientes?');">Publicar todas</a></p>
            <?php endif; ?>

            <table class="table">
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Actividad</th>
                        <th>Nota</th>
                        <th>Fecha</th>
                        <th>Grado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pendientes && $pendientes->num_rows > 0): ?>
                        <?php while ($p = $pendientes->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo h($p["nombres"] . " " . $p["apellidos"]); ?></td>
                                <td><?php echo h($p["actividad"]); ?></td>
                                <td><?php echo h($p["nota"]); ?></td>
                                <td><?php echo formatoFecha($p["fecha"]); ?></td>
                                <td><?php echo h($p["grado"]); ?></td>
                                <td><a href="publicar_notas.php?publicar=<?php echo $p["id_calificacion"]; ?>">Publicar</a></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No tienes notas pendientes por publicar.</td></tr>
                    <?php endif; ?>
                </tbody>
    </table>
    </div>
    
</body>
</html>
