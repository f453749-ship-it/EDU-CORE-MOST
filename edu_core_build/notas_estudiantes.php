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
$busqueda = isset($_GET["buscar"]) ? trim($_GET["buscar"]) : "";
$notas = null;
$nombreEncontrado = "";

if ($busqueda !== "") {

    $like = "%" . $busqueda . "%";

    $sql = "SELECT est.nombres, est.apellidos, a.nombre AS actividad, c.nota, g.nombre AS grado, c.estado, c.fecha_registro
            FROM calificaciones c
            JOIN actividades a ON a.id_actividad = c.id_actividad
            JOIN docente_materia_grado dmg ON dmg.id_asignacion = a.id_asignacion
            JOIN estudiantes est ON est.id_estudiante = c.id_estudiante
            JOIN grados g ON g.id_grado = dmg.id_grado
            WHERE dmg.id_usuario = ?
              AND CONCAT(est.nombres, ' ', est.apellidos) LIKE ?
            ORDER BY c.fecha_registro DESC";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("is", $idDocente, $like);
    $stmt->execute();
    $notas = $stmt->get_result();

    if ($notas->num_rows > 0) {
        $notas->data_seek(0);
        $primera = $notas->fetch_assoc();
        $nombreEncontrado = $primera["nombres"] . " " . $primera["apellidos"];
        $notas->data_seek(0);
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notas de Estudiantes</title>
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
                <a href="#">
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

            <h2 class="title">Consultar notas de estudiantes</h2>

            <div class="table-container">

                <form method="GET" action="notas_estudiantes.php">
                    <p>Buscar estudiante</p>
                    <input type="text" name="buscar" placeholder="Maria Lopez" value="<?php echo h($busqueda); ?>">
                    <button type="submit" class="btn-tipo">Buscar</button>
                </form>

                <br><br>

                <?php if ($busqueda === ""): ?>

                    <p>Escribe el nombre de un estudiante para ver sus notas.</p>

                <?php elseif (!$notas || $notas->num_rows == 0): ?>

                    <p>No se encontraron notas para "<?php echo h($busqueda); ?>" en tus materias.</p>

                <?php else: ?>

                    <h3>Notas de <?php echo h($nombreEncontrado); ?></h3>

                    <br>

                    <table class="table">

                        <thead>

                            <tr>
                                <th>Actividad</th>
                                <th>Nota</th>
                                <th>Fecha</th>
                                <th>Grado</th>
                                <th>Estado</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php while ($n = $notas->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo h($n["actividad"]); ?></td>
                                    <td><?php echo h($n["nota"]); ?></td>
                                    <td><?php echo formatoFecha($n["fecha_registro"]); ?></td>
                                    <td><?php echo h($n["grado"]); ?></td>
                                    <td><?php echo h($n["estado"]); ?></td>
                                </tr>
                            <?php endwhile; ?>

                        </tbody>

                    </table>

                <?php endif; ?>

            </div>

        </div>

    </div>

</body>
</html>
