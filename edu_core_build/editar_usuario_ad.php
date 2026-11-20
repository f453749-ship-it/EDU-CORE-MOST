<?php

session_start();
require "db.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION["rol"] != "administrador") {
    header("Location: acceso_denegado.php");
    exit();
}

$mensaje = "";
$error = "";

/* =========================================
   ELIMINAR UNA ASIGNACIÓN MATERIA/GRADO (docente)
   ========================================= */
if (isset($_GET["eliminar_asignacion"]) && isset($_GET["id"])) {

    $idAsignacion = intval($_GET["eliminar_asignacion"]);
    $del = $conexion->prepare("DELETE FROM docente_materia_grado WHERE id_asignacion = ?");
    $del->bind_param("i", $idAsignacion);
    $del->execute();

    header("Location: editar_usuario_ad.php?id=" . intval($_GET["id"]) . "&ok=asignacion_eliminada");
    exit();
}

/* =========================================
   GUARDAR CAMBIOS DEL USUARIO (POST)
   ========================================= */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $idUsuario = intval($_POST["id_usuario"]);
    $nombre    = trim($_POST["nombre"]);
    $correo    = trim($_POST["correo"]);
    $telefono  = trim($_POST["telefono"]);
    $estado    = $_POST["estado"];
    $rolActual = $_POST["rol_actual"];

    if ($nombre === "" || $correo === "") {

        $error = "El nombre y el correo son obligatorios.";

    } else {

        $update = $conexion->prepare(
            "UPDATE usuarios SET nombre = ?, correo = ?, telefono = ?, estado = ? WHERE id_usuario = ?"
        );
        $update->bind_param("ssssi", $nombre, $correo, $telefono, $estado, $idUsuario);

        if ($update->execute()) {

            // Si es estudiante, también actualizamos su grado
            if ($rolActual == "estudiante" && isset($_POST["grado"]) && $_POST["grado"] !== "") {

                $gradoStmt = $conexion->prepare("SELECT id_grado FROM grados WHERE nombre = ?");
                $gradoStmt->bind_param("s", $_POST["grado"]);
                $gradoStmt->execute();
                $gradoFila = $gradoStmt->get_result()->fetch_assoc();

                if ($gradoFila) {
                    $updEst = $conexion->prepare("UPDATE estudiantes SET id_grado = ? WHERE id_usuario = ?");
                    $updEst->bind_param("ii", $gradoFila["id_grado"], $idUsuario);
                    $updEst->execute();
                }
            }

            // Si es docente y se agregó una nueva asignación materia/grado
            if ($rolActual == "docente" && !empty($_POST["nueva_materia"]) && !empty($_POST["nuevo_grado"])) {

                $insertAsig = $conexion->prepare(
                    "INSERT IGNORE INTO docente_materia_grado (id_usuario, id_materia, id_grado)
                     SELECT ?, m.id_materia, g.id_grado
                     FROM materias m, grados g
                     WHERE m.nombre = ? AND g.nombre = ?"
                );
                $insertAsig->bind_param("iss", $idUsuario, $_POST["nueva_materia"], $_POST["nuevo_grado"]);
                $insertAsig->execute();
            }

            header("Location: editar_usuario_ad.php?id=" . $idUsuario . "&ok=guardado");
            exit();

        } else {

            $error = "No se pudo actualizar el usuario (verifica que el correo no esté repetido).";
        }
    }
}

if (isset($_GET["ok"]) && $_GET["ok"] == "guardado") $mensaje = "Cambios guardados correctamente.";
if (isset($_GET["ok"]) && $_GET["ok"] == "asignacion_eliminada") $mensaje = "Asignación eliminada.";

/* =========================================
   BÚSQUEDA DE USUARIOS
   ========================================= */
$busqueda = isset($_GET["buscar"]) ? trim($_GET["buscar"]) : "";
$resultados = [];

if ($busqueda !== "") {
    $like = "%" . $busqueda . "%";
    $buscarStmt = $conexion->prepare(
        "SELECT id_usuario, nombre, correo, rol FROM usuarios
         WHERE nombre LIKE ? OR correo LIKE ?
         ORDER BY nombre LIMIT 20"
    );
    $buscarStmt->bind_param("ss", $like, $like);
    $buscarStmt->execute();
    $resultados = $buscarStmt->get_result();
}

/* =========================================
   CARGAR EL USUARIO SELECCIONADO
   ========================================= */
$usuarioEditar = null;
$estudianteInfo = null;
$asignacionesDocente = [];

if (isset($_GET["id"])) {

    $idSeleccionado = intval($_GET["id"]);
    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $idSeleccionado);
    $stmt->execute();
    $usuarioEditar = $stmt->get_result()->fetch_assoc();

    if ($usuarioEditar) {

        if ($usuarioEditar["rol"] == "estudiante") {

            $estStmt = $conexion->prepare(
                "SELECT e.*, g.nombre AS nombre_grado FROM estudiantes e
                 JOIN grados g ON g.id_grado = e.id_grado
                 WHERE e.id_usuario = ?"
            );
            $estStmt->bind_param("i", $idSeleccionado);
            $estStmt->execute();
            $estudianteInfo = $estStmt->get_result()->fetch_assoc();

        } elseif ($usuarioEditar["rol"] == "docente") {

            $asigStmt = $conexion->prepare(
                "SELECT dmg.id_asignacion, m.nombre AS materia, g.nombre AS grado
                 FROM docente_materia_grado dmg
                 JOIN materias m ON m.id_materia = dmg.id_materia
                 JOIN grados g ON g.id_grado = dmg.id_grado
                 WHERE dmg.id_usuario = ?
                 ORDER BY g.id_grado, m.nombre"
            );
            $asigStmt->bind_param("i", $idSeleccionado);
            $asigStmt->execute();
            $asignacionesDocente = $asigStmt->get_result();
        }
    }
}

$listaGrados = $conexion->query("SELECT nombre FROM grados ORDER BY id_grado");
$listaMaterias = $conexion->query("SELECT nombre FROM materias ORDER BY nombre");
$materiasArr = [];
while ($m = $listaMaterias->fetch_assoc()) $materiasArr[] = $m["nombre"];
$gradosArr = [];
while ($g = $listaGrados->fetch_assoc()) $gradosArr[] = $g["nombre"];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar usuario</title>
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
                <a href="historial_ad.php">
                    <i class="bi bi-clock-history"></i>
                    <span>Historial</span>
                </a>
            </li>

            <li>
                <a href="crear_usuario_ad.php">
                    <i class="bi bi-journal-check"></i>
                    <span>Crear usuario</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <i class="bi bi-folder-fill"></i>
                    <span>Editar Usuario</span>
                </a>
            </li>

            <li>
                <a href="consultar_usuario_ad.php">
                    <i class="bi bi-upload"></i>
                    <span>Consultar usuario</span>
                </a>
            </li>

            <li>
                <a href="perfil_ad.php">
                    <i class="bi bi-person-fill"></i>
                    <span>Perfil</span>
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
                <a href="perfil_ad.php">
                    <i class="bi bi-person-fill"></i>
                </a>
                

                <div>
                    <h6><?php echo h(strtoupper($_SESSION["nombre"])); ?></h6>
                    <p>ADMINISTRADOR</p>
                </div>

            <a href="cerrar_sesion.php" class="logout-btn">
                <i class="bi bi-box-arrow-right"></i>
            </a> 

            </div>

        </div>

        <div class="content">

    <h2 class="title">Editar usuario</h2>

    <?php if ($mensaje): ?>
        <p style="color:#2e7d32;"><strong><?php echo h($mensaje); ?></strong></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p style="color:#a83232;"><strong><?php echo h($error); ?></strong></p>
    <?php endif; ?>

    <!-- BUSCADOR -->
    <form method="GET" action="editar_usuario_ad.php" style="margin-bottom:20px;">
        <input type="text" name="buscar" placeholder="Buscar por nombre o correo..." value="<?php echo h($busqueda); ?>">
        <button type="submit" class="btn-tipo">Buscar</button>
    </form>

    <?php if ($busqueda !== ""): ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Acción</th></tr>
                </thead>
                <tbody>
                <?php if ($resultados && $resultados->num_rows > 0): ?>
                    <?php while ($fila = $resultados->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo h($fila["nombre"]); ?></td>
                            <td><?php echo h($fila["correo"]); ?></td>
                            <td><?php echo h(ucfirst($fila["rol"])); ?></td>
                            <td><a href="editar_usuario_ad.php?id=<?php echo $fila["id_usuario"]; ?>">Editar</a></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4">No se encontraron usuarios.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($usuarioEditar): ?>

    <!-- FORMULARIO -->
    <form method="POST" action="editar_usuario_ad.php">
    <input type="hidden" name="id_usuario" value="<?php echo $usuarioEditar["id_usuario"]; ?>">
    <input type="hidden" name="rol_actual" value="<?php echo h($usuarioEditar["rol"]); ?>">
    <div class="container">

        <!-- COLUMNA IZQUIERDA -->
        <div class="columna">
            <h2>Informacion personal</h2>
            <br>
            <div class="campo">
                <h3>Nombre completo</h3>
                <input type="text" name="nombre" value="<?php echo h($usuarioEditar["nombre"]); ?>" required>
            </div>

            <div class="campo">
                <h3>Correo Electrónico</h3>
                <input type="email" name="correo" value="<?php echo h($usuarioEditar["correo"]); ?>" required>
            </div>


            <div class="campo">
                <h3>Teléfono</h3>
                <input type="text" name="telefono" value="<?php echo h($usuarioEditar["telefono"]); ?>">
            </div>

            <div class="campo">
                <br>
                Fecha de registro
                <input type="text" value="<?php echo formatoFecha($usuarioEditar["fecha_creacion"]); ?>" disabled>
            </div>

        </div>

        <!-- COLUMNA DERECHA -->
        <div class="columna">

    <h2>Informacion academica</h2>

    <!-- ROL (solo lectura: el rol no se cambia aquí) -->
    <div class="grupo">
        <h3>Rol</h3>
        <p><strong><?php echo h(ucfirst($usuarioEditar["rol"])); ?></strong></p>
    </div>

    <!-- ESTADO -->
    <div class="grupo">
        <h3>Estado</h3>

        <div class="opciones">
            <label><input type="radio" name="estado" value="Activo" <?php echo $usuarioEditar["estado"] == "Activo" ? "checked" : ""; ?>> Activo</label>
            <label><input type="radio" name="estado" value="Inactivo" <?php echo $usuarioEditar["estado"] == "Inactivo" ? "checked" : ""; ?>> Inactivo</label>
        </div>
    </div>

    <?php if ($usuarioEditar["rol"] == "estudiante" && $estudianteInfo): ?>
    <!-- GRADO -->
    <div class="fila-doble">
        <div class="campo">
            <h3>Grado</h3>
            <select name="grado">
                <?php foreach ($gradosArr as $g): ?>
                    <option value="<?php echo h($g); ?>" <?php echo $g == $estudianteInfo["nombre_grado"] ? "selected" : ""; ?>><?php echo h($g); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($usuarioEditar["rol"] == "docente"): ?>
    <!-- MATERIAS ASIGNADAS -->
    <div class="grupo">
        <h3>Materias asignadas</h3>
        <ul>
            <?php if ($asignacionesDocente && $asignacionesDocente->num_rows > 0): ?>
                <?php while ($a = $asignacionesDocente->fetch_assoc()): ?>
                    <li>
                        <?php echo h($a["materia"]) . " - " . h($a["grado"]); ?>
                        <a href="editar_usuario_ad.php?id=<?php echo $usuarioEditar["id_usuario"]; ?>&eliminar_asignacion=<?php echo $a["id_asignacion"]; ?>">
                            <i class="bi bi-trash"></i>
                        </a>
                    </li>
                <?php endwhile; ?>
            <?php else: ?>
                <li>Sin materias asignadas.</li>
            <?php endif; ?>
        </ul>

        <h3>Agregar asignación</h3>
        <div class="fila-doble">
            <div class="campo">
                <select name="nuevo_grado">
                    <option value="">Grado</option>
                    <?php foreach ($gradosArr as $g): ?>
                        <option value="<?php echo h($g); ?>"><?php echo h($g); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <select name="nueva_materia">
                    <option value="">Materia</option>
                    <?php foreach ($materiasArr as $m): ?>
                        <option value="<?php echo h($m); ?>"><?php echo h($m); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <br>
    <button type="submit" class="btn-guardar">Guardar cambios</button>

</div>
</div>
</form>
<?php endif; ?>

</div>
</body>
</html>
