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
$error = "";
$resultado = null;

/* =========================================
   GUARDAR NUEVA NOTA
   ========================================= */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $idCalificacion = intval($_POST["id_calificacion"]);
    $nuevaNota = floatval($_POST["nuevaNota"]);

    if ($nuevaNota < 1 || $nuevaNota > 5) {

        $error = "La nota debe estar entre 1.0 y 5.0";

    } else {

        $verif = $conexion->prepare(
            "SELECT c.id_calificacion FROM calificaciones c
             JOIN actividades a ON a.id_actividad = c.id_actividad
             JOIN docente_materia_grado dmg ON dmg.id_asignacion = a.id_asignacion
             WHERE c.id_calificacion = ? AND dmg.id_usuario = ?"
        );
        $verif->bind_param("ii", $idCalificacion, $idDocente);
        $verif->execute();

        if ($verif->get_result()->num_rows > 0) {
            $upd = $conexion->prepare("UPDATE calificaciones SET nota = ? WHERE id_calificacion = ?");
            $upd->bind_param("di", $nuevaNota, $idCalificacion);
            $upd->execute();
            $mensaje = "Cambios guardados correctamente.";
        } else {
            $error = "Esa calificación no pertenece a una de tus materias.";
        }
    }
}

/* =========================================
   BUSCAR CALIFICACIÓN (Estudiante-Actividad)
   ========================================= */
$buscarInput = isset($_GET["buscarInput"]) ? trim($_GET["buscarInput"]) : "";

if ($buscarInput !== "") {

    // Se espera el formato "Nombre Apellido-Actividad", pero también funciona buscando solo por nombre
    $partes = explode("-", $buscarInput, 2);
    $nombreBuscado = trim($partes[0]);
    $actividadBuscada = isset($partes[1]) ? trim($partes[1]) : "";

    $like = "%" . $nombreBuscado . "%";

    if ($actividadBuscada !== "") {
        $likeAct = "%" . $actividadBuscada . "%";
        $sql = "SELECT c.id_calificacion, est.nombres, est.apellidos, a.nombre AS actividad, c.nota
                FROM calificaciones c
                JOIN actividades a ON a.id_actividad = c.id_actividad
                JOIN docente_materia_grado dmg ON dmg.id_asignacion = a.id_asignacion
                JOIN estudiantes est ON est.id_estudiante = c.id_estudiante
                WHERE dmg.id_usuario = ?
                  AND CONCAT(est.nombres, ' ', est.apellidos) LIKE ?
                  AND a.nombre LIKE ?
                ORDER BY c.fecha_registro DESC
                LIMIT 1";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iss", $idDocente, $like, $likeAct);
    } else {
        $sql = "SELECT c.id_calificacion, est.nombres, est.apellidos, a.nombre AS actividad, c.nota
                FROM calificaciones c
                JOIN actividades a ON a.id_actividad = c.id_actividad
                JOIN docente_materia_grado dmg ON dmg.id_asignacion = a.id_asignacion
                JOIN estudiantes est ON est.id_estudiante = c.id_estudiante
                WHERE dmg.id_usuario = ?
                  AND CONCAT(est.nombres, ' ', est.apellidos) LIKE ?
                ORDER BY c.fecha_registro DESC
                LIMIT 1";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("is", $idDocente, $like);
    }

    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();
}

?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar Notas</title>

    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ICONOS -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- CSS -->
     <link rel="stylesheet" href="style2.css?v=6">

</head>

<body>

    <!-- SIDEBAR -->

    <div class="sidebar" id="sidebar">

        <!-- LOGO -->

        <div class="logo">
            
            
    <a href="login.php">
        <img src="img/logo.png" alt="logo">
    </a>

        </div>

        <!-- MENU -->

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
                <a href="publicar_notas.php">
                    <i class="bi bi-upload"></i>
                    <span>PUBLICAR NOTAS</span>
                </a>
            </li>

            <li class="active">
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

            <!-- MENU -->

            <div class="menu-icon" id="menu-btn">

                <i class="bi bi-list"></i>

            </div>

            <!-- USER -->

            <div class="user-info">
                <a href="perfil_docente.php">
                    <i class="bi bi-person-fill"></i>
                </a>

                <div>
                    <h6><?php echo h(strtoupper($_SESSION["nombre"])); ?></h6>
                    <p>DOCENTE</p>
                </div>

                <!-- SALIR -->

                <a href="cerrar_sesion.php" class="logout-btn">
                    <i class="bi bi-box-arrow-right"></i>
                </a>

            </div>

        </div>

        <!-- CONTENT -->

        <div class="content">

            <?php if ($mensaje): ?>
                <p style="color:#2e7d32;"><strong><?php echo h($mensaje); ?></strong></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p style="color:#a83232;"><strong><?php echo h($error); ?></strong></p>
            <?php endif; ?>

            <!-- BUSCADOR -->

            <form method="GET" action="editar_notas.php" class="search-container">

                <label>Buscar Calificación</label>

                <div class="search-box">

                    <input type="text" name="buscarInput"
                        placeholder="Carlos Ramirez-Examen 1" value="<?php echo h($buscarInput); ?>">

                    <button type="submit">
                        <i class="bi bi-search"></i>
                    </button>

                </div>

            </form>

            <?php if ($buscarInput !== "" && !$resultado): ?>
                <p>No se encontró ninguna calificación con esa búsqueda entre tus materias.</p>
            <?php endif; ?>

            <?php if ($resultado): ?>
            <!-- CARD -->

            <div class="card-edit">

                <h3>Información actual</h3>

                <div class="info">

                    <p><strong>Estudiante:</strong> <span>
                            <?php echo h($resultado["nombres"] . " " . $resultado["apellidos"]); ?>
                        </span></p>

                    <p><strong>Actividad:</strong> <span>
                            <?php echo h($resultado["actividad"]); ?>
                        </span></p>

                    <p><strong>Nota actual:</strong> <span>
                            <?php echo h($resultado["nota"]); ?>
                        </span></p>

                </div>

                <!-- NUEVA NOTA -->
                <form method="POST" action="editar_notas.php?buscarInput=<?php echo urlencode($buscarInput); ?>">
                <input type="hidden" name="id_calificacion" value="<?php echo $resultado["id_calificacion"]; ?>">

                <div class="new-grade">

                    <label>Nueva calificación (1.0 - 5.0)</label>

                    <input type="number" step="0.1" min="1" max="5"
                        name="nuevaNota" value="<?php echo h($resultado["nota"]); ?>">

                </div>

                <!-- BOTON --> 
                 <br>
                 <br>

                <button type="submit" class="btn-login">

                    Guardar cambios

                </button>
                </form>

            </div>
            <?php endif; ?>

        </div>

    </div>

    <!-- JAVASCRIPT -->

    <script>

        // MENU

        const menuBtn = document.getElementById("menu-btn");
        const sidebar = document.getElementById("sidebar");

        menuBtn.addEventListener("click", () => {

            sidebar.classList.toggle("close");

        });

    </script>

</body>

</html>
