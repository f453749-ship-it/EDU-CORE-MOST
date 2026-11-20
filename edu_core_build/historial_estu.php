<?php

session_start();
require "db.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION["rol"] != "estudiante") {
    header("Location: acceso_denegado.php");
    exit();
}

$idUsuario = $_SESSION["id_usuario"];

$stmtEst = $conexion->prepare("SELECT id_estudiante, id_grado FROM estudiantes WHERE id_usuario = ?");
$stmtEst->bind_param("i", $idUsuario);
$stmtEst->execute();
$estudiante = $stmtEst->get_result()->fetch_assoc();

$idEstudiante = $estudiante["id_estudiante"];
$idGrado = $estudiante["id_grado"];

/* =========================================
   PROMEDIO GENERAL Y ACTIVIDADES REALIZADAS
   ========================================= */
$stmtProm = $conexion->prepare(
    "SELECT AVG(nota) AS promedio, COUNT(*) AS total
     FROM calificaciones WHERE id_estudiante = ? AND estado = 'Publicada'"
);
$stmtProm->bind_param("i", $idEstudiante);
$stmtProm->execute();
$resumen = $stmtProm->get_result()->fetch_assoc();
$promedio = $resumen["promedio"] !== null ? number_format($resumen["promedio"], 1) : "-";
$totalActividades = $resumen["total"];

/* =========================================
   PRÓXIMAS ACTIVIDADES
   (actividades de las materias de su grado que aún no tienen nota para él)
   ========================================= */
$stmtProx = $conexion->prepare(
    "SELECT a.nombre, a.fecha
     FROM actividades a
     JOIN docente_materia_grado dmg ON dmg.id_asignacion = a.id_asignacion
     WHERE dmg.id_grado = ?
       AND a.id_actividad NOT IN (
           SELECT id_actividad FROM calificaciones WHERE id_estudiante = ?
       )
     ORDER BY a.fecha ASC
     LIMIT 3"
);
$stmtProx->bind_param("ii", $idGrado, $idEstudiante);
$stmtProx->execute();
$proximas = $stmtProx->get_result();

/* =========================================
   ÚLTIMAS NOTAS PUBLICADAS
   ========================================= */
$stmtNotas = $conexion->prepare(
    "SELECT a.nombre, a.fecha, c.nota
     FROM calificaciones c
     JOIN actividades a ON a.id_actividad = c.id_actividad
     WHERE c.id_estudiante = ? AND c.estado = 'Publicada'
     ORDER BY a.fecha DESC
     LIMIT 3"
);
$stmtNotas->bind_param("i", $idEstudiante);
$stmtNotas->execute();
$ultimasNotas = $stmtNotas->get_result();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EDU-CORE Dashboard</title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Font Awesome (usado por los iconos fa-solid/fa-regular de esta página) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="style4.css">
</head>
<body>

    <div class="dashboard-container">
        
        <aside class="sidebar">
            <div class="logo-section">
                
    <a href="login.php">
        <img src="img/logo.png" alt="logo">
    </a>
            </div>
            <nav class="sidebar-menu">
                <ul>
                    
                    <li class="active"><a href="#"><i class="bi bi-clock-history"></i> Historial</a></li>
                    <li><a href="perfil_estu.php"><i class="bi bi-person-fill"></i> Perfil</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            
            <header class="main-header">
                <button class="menu-toggle"><i class="fa-solid fa-bars"></i></button>
                <div class="user-profile">
                    <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
                    <div class="user-info">
                        <span class="user-name"><?php echo h(strtoupper($_SESSION["nombre"])); ?></span>
                        <span class="user-role">ESTUDIANTE</span>
                    </div>
                    <a class="logout-btn" href="cerrar_sesion.php"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
                </div>
            </header>

            <h1 class="section-title">Historial academico</h1>
            
            <div class="dashboard-grid">
                
                <div class="left-column">
                    <div class="stats-cards-container">
                        <div class="stat-card">
                            <h3>Promedio general</h3>
                            <p class="stat-number"><?php echo h($promedio); ?></p>
                        </div>
                        <div class="stat-card">
                            <h3>Actividades realizadas</h3>
                            <p class="stat-number"><?php echo h($totalActividades); ?></p>
                        </div>
                    </div>

                    <section class="upcoming-activities">
                        <h2>Próximas actividades</h2>

                        <?php if ($proximas && $proximas->num_rows > 0): ?>
                            <?php while ($p = $proximas->fetch_assoc()): ?>
                                <div class="activity-item">
                                    <div class="activity-icon blue-icon"><i class="fa-regular fa-list-checkbox"></i></div>
                                    <div class="activity-details">
                                        <h4><?php echo h($p["nombre"]); ?></h4>
                                        <p>Fecha limite: <?php echo formatoFecha($p["fecha"]); ?></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No tienes actividades pendientes por el momento.</p>
                        <?php endif; ?>
                    </section>
                </div>

                <div class="right-column">
                    <div class="grades-card">
                        <h2>Ultimas notas</h2>

                        <?php if ($ultimasNotas && $ultimasNotas->num_rows > 0): ?>
                            <?php while ($n = $ultimasNotas->fetch_assoc()): ?>
                                <div class="grade-item">
                                    <span class="grade-name"><?php echo h($n["nombre"]); ?></span>
                                    <div class="grade-val-date">
                                        <span class="grade-value"><?php echo h($n["nota"]); ?></span>
                                        <span class="grade-date"><?php echo formatoFecha($n["fecha"]); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>Todavía no tienes notas publicadas.</p>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </main>
    </div>

</body>
</html>
