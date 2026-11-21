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


$sqlAsig = "SELECT dmg.id_asignacion, dmg.id_materia, m.nombre AS materia, g.nombre AS grado, dmg.id_grado
            FROM docente_materia_grado dmg
            JOIN materias m ON m.id_materia = dmg.id_materia
            JOIN grados g ON g.id_grado = dmg.id_grado
            WHERE dmg.id_usuario = ?
            ORDER BY m.nombre, g.id_grado";
$stmtAsig = $conexion->prepare($sqlAsig);
$stmtAsig->bind_param("i", $idDocente);
$stmtAsig->execute();
$asignaciones = $stmtAsig->get_result();

$asignacionesArr = [];
$estudiantesPorGrado = [];
$materiasUnicas = [];

while ($a = $asignaciones->fetch_assoc()) {
    $asignacionesArr[] = $a;

    if (!isset($materiasUnicas[$a["id_materia"]])) {
        $materiasUnicas[$a["id_materia"]] = $a["materia"];
    }

    if (!isset($estudiantesPorGrado[$a["id_grado"]])) {
        $stmtEst = $conexion->prepare(
            "SELECT id_estudiante, nombres, apellidos FROM estudiantes WHERE id_grado = ? ORDER BY apellidos, nombres"
        );
        $stmtEst->bind_param("i", $a["id_grado"]);
        $stmtEst->execute();
        $resEst = $stmtEst->get_result();
        $lista = [];
        while ($e = $resEst->fetch_assoc()) $lista[] = $e;
        $estudiantesPorGrado[$a["id_grado"]] = $lista;
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $idAsignacion = intval($_POST["id_asignacion"]);
    $idEstudiante = intval($_POST["estudiante"]);
    $actividad    = trim($_POST["actividad"]);
    $nota         = floatval($_POST["nota"]);
    $fecha        = $_POST["fecha"] !== "" ? $_POST["fecha"] : date("Y-m-d");

    
    $verifAsig = $conexion->prepare("SELECT id_grado FROM docente_materia_grado WHERE id_asignacion = ? AND id_usuario = ?");
    $verifAsig->bind_param("ii", $idAsignacion, $idDocente);
    $verifAsig->execute();
    $filaAsig = $verifAsig->get_result()->fetch_assoc();

    if (!$filaAsig) {

        $error = "La materia/grado seleccionada no es válida.";

    } elseif ($actividad === "" || $nota < 1 || $nota > 5) {

        $error = "Verifica el nombre de la actividad y que la nota esté entre 1.0 y 5.0.";

    } else {


        $buscarAct = $conexion->prepare(
            "SELECT id_actividad FROM actividades WHERE id_asignacion = ? AND nombre = ? AND fecha = ?"
        );
        $buscarAct->bind_param("iss", $idAsignacion, $actividad, $fecha);
        $buscarAct->execute();
        $filaAct = $buscarAct->get_result()->fetch_assoc();

        if ($filaAct) {
            $idActividad = $filaAct["id_actividad"];
        } else {
            $crearAct = $conexion->prepare(
                "INSERT INTO actividades (id_asignacion, nombre, fecha) VALUES (?, ?, ?)"
            );
            $crearAct->bind_param("iss", $idAsignacion, $actividad, $fecha);
            $crearAct->execute();
            $idActividad = $conexion->insert_id;
        }

        // Guardar o actualizar la calificación (si ya existía para ese estudiante/actividad)
        $upsert = $conexion->prepare(
            "INSERT INTO calificaciones (id_actividad, id_estudiante, nota, estado)
             VALUES (?, ?, ?, 'Pendiente')
             ON DUPLICATE KEY UPDATE nota = VALUES(nota), estado = 'Pendiente'"
        );
        $upsert->bind_param("iid", $idActividad, $idEstudiante, $nota);

        if ($upsert->execute()) {
            $mensaje = "Calificación guardada correctamente.";
        } else {
            $error = "No se pudo guardar la calificación.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EDU-CORE Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style2.css">
</head>
<body>

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
                <a href="#">
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

    <div class="main">

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

        <div class="content">

            <div class="container">
                <h2 class="form-title">Asignar nueva calificación</h2>

                <?php if ($mensaje): ?>
                    <p style="color:#2e7d32;"><strong><?php echo h($mensaje); ?></strong></p>
                <?php endif; ?>
                <?php if ($error): ?>
                    <p style="color:#a83232;"><strong><?php echo h($error); ?></strong></p>
                <?php endif; ?>

                <?php if (empty($asignacionesArr)): ?>
                    <p>Todavía no tienes materias ni grados asignados. Pide a un administrador que te asigne una materia primero.</p>
                <?php else: ?>

                <form id="assignForm" method="POST" action="asignar_nota.php">
                    <div class="form-body">

                        <div class="campo">
                            <label for="materia_ui">Materia</label>
                            <select id="materia_ui" required onchange="actualizarGrados()">
                                <option value="">Seleccione una materia</option>
                                <?php foreach ($materiasUnicas as $idMateria => $nombreMateria): ?>
                                    <option value="<?php echo $idMateria; ?>"><?php echo h($nombreMateria); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="campo">
                            <label for="grado_ui">Grado</label>
                            <select id="grado_ui" required onchange="actualizarEstudiantes()">
                                <option value="">Primero seleccione una materia</option>
                            </select>
                        </div>

                        <input type="hidden" id="id_asignacion" name="id_asignacion">

                        <div class="campo">
                            <label for="estudiante">Seleccionar estudiante</label>
                            <select id="estudiante" name="estudiante" required></select>
                        </div>

                        <div class="campo">
                            <label for="actividad">Nombre de la actividad</label>
                            <input id="actividad" name="actividad" type="text" placeholder="Ej: Tarea 2, Examen Final" required>
                        </div>

                        <div class="campo">
                            <label for="fecha">Fecha</label>
                            <input id="fecha" name="fecha" type="date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="campo">
                            <label for="nota">Calificación (1.0 - 5.0)</label>
                            <input id="nota" name="nota" type="number" step="0.1" min="1" max="5" value="4.0" required>
                        </div>

                        <button type="submit" class="btn-guardar">Guardar calificación</button>

                    </div>
                </form>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
       
        const asignaciones = <?php echo json_encode($asignacionesArr); ?>;
        const estudiantesPorGrado = <?php echo json_encode($estudiantesPorGrado); ?>;

        // 1) Al elegir una MATERIA, se llenan los GRADOS donde el docente dicta esa materia
        function actualizarGrados() {
            const idMateria = document.getElementById('materia_ui').value;
            const selectGrado = document.getElementById('grado_ui');
            selectGrado.innerHTML = '';

            const opciones = asignaciones.filter(function(a) {
                return String(a.id_materia) === idMateria;
            });

            if (idMateria === '' || opciones.length === 0) {
                const op = document.createElement('option');
                op.value = '';
                op.textContent = 'Primero seleccione una materia';
                selectGrado.appendChild(op);
            } else {
                opciones.forEach(function(a) {
                    const op = document.createElement('option');
                    op.value = a.id_grado;
                    op.textContent = a.grado;
                    op.setAttribute('data-asignacion', a.id_asignacion);
                    selectGrado.appendChild(op);
                });
            }

            actualizarEstudiantes();
        }

        // 2) Al elegir (o recalcular) el GRADO, se fija el id_asignacion real y se cargan los ESTUDIANTES
        function actualizarEstudiantes() {
            const selectGrado = document.getElementById('grado_ui');
            const opcion = selectGrado.options[selectGrado.selectedIndex];
            const idAsignacion = opcion ? opcion.getAttribute('data-asignacion') : null;
            const idGrado = opcion ? opcion.value : '';

            document.getElementById('id_asignacion').value = idAsignacion || '';

            const selectEst = document.getElementById('estudiante');
            selectEst.innerHTML = '';

            const lista = estudiantesPorGrado[idGrado] || [];
            lista.forEach(function(est) {
                const op = document.createElement('option');
                op.value = est.id_estudiante;
                op.textContent = est.nombres + ' ' + est.apellidos;
                selectEst.appendChild(op);
            });

            if (lista.length === 0) {
                const op = document.createElement('option');
                op.value = '';
                op.textContent = 'No hay estudiantes en este grado';
                selectEst.appendChild(op);
            }
        }

        document.addEventListener('DOMContentLoaded', actualizarGrados);
    </script>
</body>
</html>
