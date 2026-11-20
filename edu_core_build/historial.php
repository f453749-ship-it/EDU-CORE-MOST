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

/* =========================================
   CONTADORES
   ========================================= */
$sqlCont = "SELECT
                COUNT(DISTINCT c.id_estudiante) AS estudiantes,
                COUNT(DISTINCT a.id_actividad) AS actividades,
                COUNT(c.id_calificacion) AS notas
            FROM docente_materia_grado dmg
            LEFT JOIN actividades a ON a.id_asignacion = dmg.id_asignacion
            LEFT JOIN calificaciones c ON c.id_actividad = a.id_actividad
            WHERE dmg.id_usuario = ?";
$stmt = $conexion->prepare($sqlCont);
$stmt->bind_param("i", $idDocente);
$stmt->execute();
$contadores = $stmt->get_result()->fetch_assoc();

/* =========================================
   ÚLTIMAS CALIFICACIONES ASIGNADAS
   ========================================= */
$sqlUltimas = "SELECT est.nombres, est.apellidos, a.nombre AS actividad, c.nota, g.nombre AS grado, c.fecha_registro
               FROM calificaciones c
               JOIN actividades a ON a.id_actividad = c.id_actividad
               JOIN docente_materia_grado dmg ON dmg.id_asignacion = a.id_asignacion
               JOIN estudiantes est ON est.id_estudiante = c.id_estudiante
               JOIN grados g ON g.id_grado = dmg.id_grado
               WHERE dmg.id_usuario = ?
               ORDER BY c.fecha_registro DESC
               LIMIT 5";
$stmt2 = $conexion->prepare($sqlUltimas);
$stmt2->bind_param("i", $idDocente);
$stmt2->execute();
$ultimasNotas = $stmt2->get_result();

/* =========================================
   ACTIVIDAD RECIENTE (feed)
   ========================================= */
$sqlFeed = "SELECT est.nombres, est.apellidos, a.nombre AS actividad, c.estado, c.fecha_registro
            FROM calificaciones c
            JOIN actividades a ON a.id_actividad = c.id_actividad
            JOIN docente_materia_grado dmg ON dmg.id_asignacion = a.id_asignacion
            JOIN estudiantes est ON est.id_estudiante = c.id_estudiante
            WHERE dmg.id_usuario = ?
            ORDER BY c.fecha_registro DESC
            LIMIT 5";
$stmt3 = $conexion->prepare($sqlFeed);
$stmt3->bind_param("i", $idDocente);
$stmt3->execute();
$feed = $stmt3->get_result();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EDU-CORE Dashboard</title>
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
                <a href="#">
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
                <a href="publicar_notas.php">
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

            <h2 class="title">HISTORIAL</h2>

            <!-- CARDS -->
            <div class="cards">

                <div class="card-box">
                    <h3>ESTUDIANTES</h3>
                    <span><?php echo $contadores["estudiantes"] ?? 0; ?></span>
                </div>

                <div class="card-box">
                    <h3>ACTIVIDADES</h3>
                    <span><?php echo $contadores["actividades"] ?? 0; ?></span>
                </div>

                <div class="card-box">
                    <h3>NOTAS ASIGNADAS</h3>
                    <span><?php echo $contadores["notas"] ?? 0; ?></span>
                </div>

            </div>

            <!-- TABLE -->
            <div class="table-section">

                <div class="table-container">

                    <h3>Ultimas calificaciones asignadas</h3>

                    <table class="table">

                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Actividad</th>
                                <th>Nota</th>
                                <th>Grado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($ultimasNotas && $ultimasNotas->num_rows > 0): ?>
                                <?php while ($n = $ultimasNotas->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo h($n["nombres"] . " " . $n["apellidos"]); ?></td>
                                        <td><?php echo h($n["actividad"]); ?></td>
                                        <td><?php echo h($n["nota"]); ?></td>
                                        <td><?php echo h($n["grado"]); ?></td>
                                        <td><?php echo formatoFecha($n["fecha_registro"]); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5">Aún no has asignado calificaciones.</td></tr>
                            <?php endif; ?>
                        </tbody>

                    </table>

                </div>

                <!-- ACTIVIDAD -->
                <div class="activity-box">

                    <h3>Actividad reciente</h3>

                    <?php if ($feed && $feed->num_rows > 0): ?>
                        <?php while ($f = $feed->fetch_assoc()): ?>
                            <div class="activity-item">
                                <p>
                                    <?php echo $f["estado"] == "Publicada" ? "Publicaste" : "Asignaste"; ?> nota a
                                    <?php echo h($f["nombres"] . " " . $f["apellidos"]); ?> en <?php echo h($f["actividad"]); ?>
                                </p>
                                <span><?php echo tiempoRelativo($f["fecha_registro"]); ?></span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="activity-item"><p>Sin actividad reciente.</p></div>
                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</body>
</html>
