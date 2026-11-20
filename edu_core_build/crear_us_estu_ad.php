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
   PROCESAR EL FORMULARIO (crear estudiante)
   ========================================= */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombres    = trim($_POST["nombres"]);
    $apellidos  = trim($_POST["apellidos"]);
    $documento  = trim($_POST["documento"]);
    $contacto   = trim($_POST["contacto"]);
    $correo     = trim($_POST["correo"]);
    $direccion  = trim($_POST["direccion"]);
    $grado      = trim($_POST["grado"]);
    $contrasena = trim($_POST["contrasena"]);

    if ($nombres === "" || $apellidos === "" || $documento === "" || $correo === "" || $grado === "" || $contrasena === "") {

        $error = "Por favor completa todos los campos obligatorios.";

    } else {

        $verif = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE correo = ? OR documento = ?");
        $verif->bind_param("ss", $correo, $documento);
        $verif->execute();
        $verif->store_result();

        if ($verif->num_rows > 0) {

            $error = "Ya existe un usuario con ese correo o número de documento.";

        } else {

            $gradoStmt = $conexion->prepare("SELECT id_grado FROM grados WHERE nombre = ?");
            $gradoStmt->bind_param("s", $grado);
            $gradoStmt->execute();
            $gradoFila = $gradoStmt->get_result()->fetch_assoc();

            if (!$gradoFila) {

                $error = "El grado seleccionado no es válido.";

            } else {

                $nombreCompleto = $nombres . " " . $apellidos;

                $conexion->begin_transaction();

                $insertUsuario = $conexion->prepare(
                    "INSERT INTO usuarios (nombre, correo, contrasena, rol, telefono, direccion, documento, estado)
                     VALUES (?, ?, ?, 'estudiante', ?, ?, ?, 'Activo')"
                );
                $insertUsuario->bind_param("ssssss", $nombreCompleto, $correo, $contrasena, $contacto, $direccion, $documento);

                if ($insertUsuario->execute()) {

                    $idNuevoUsuario = $conexion->insert_id;
                    $idGrado = $gradoFila["id_grado"];

                    $insertEstudiante = $conexion->prepare(
                        "INSERT INTO estudiantes (id_usuario, nombres, apellidos, id_grado) VALUES (?, ?, ?, ?)"
                    );
                    $insertEstudiante->bind_param("issi", $idNuevoUsuario, $nombres, $apellidos, $idGrado);

                    if ($insertEstudiante->execute()) {
                        $conexion->commit();
                        $mensaje = "Estudiante creado correctamente.";
                    } else {
                        $conexion->rollback();
                        $error = "No se pudo guardar la información del estudiante.";
                    }

                } else {

                    $conexion->rollback();
                    $error = "No se pudo crear el usuario. Intenta de nuevo.";
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Crear estudiante</title>
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
                <a href="#">
                    <i class="bi bi-journal-check"></i>
                    <span>Crear usuario</span>
                </a>
            </li>

            <li>
                <a href="editar_usuario_ad.php">
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
                <a href="#">
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

    <h2 class="title">Agregar nuevo usuario</h2>

    <?php if ($mensaje): ?>
        <p style="color:#2e7d32;"><strong><?php echo h($mensaje); ?></strong></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color:#a83232;"><strong><?php echo h($error); ?></strong></p>
    <?php endif; ?>

    <!-- BOTONES -->
    <div class="tipo-usuario">
        <a href="crear_usuario_ad.php" class="btn-tipo">Docente</a>
        <a href="crear_us_estu_ad.php" class="btn-tipo">Estudiante</a>
    </div>

    <!-- FORMULARIO -->
    <form method="POST" action="crear_us_estu_ad.php">
    <div class="container">

        <!-- COLUMNA IZQUIERDA -->
           <div class="columna">

        <div class="campo">
            <h3>Nombres</h3>
            <input type="text" name="nombres" required>
        </div>

        <div class="campo">
            <h3>Apellidos</h3>
            <input type="text" name="apellidos" required>
        </div>

        <div class="campo">
            <h3>Número de documento</h3>
            <input type="text" name="documento" required>
        </div>

        <div class="campo">
            <h3>Contacto</h3>
            <input type="text" name="contacto" required>
        </div>

        <div class="campo">
            <h3>Contraseña inicial</h3>
            <input type="text" name="contrasena" required>
        </div>

    </div>


    <!-- COLUMNA DERECHA -->
    <div class="columna">

        <div class="campo">
            <h3>Correo Electrónico</h3>
            <input type="email" name="correo" required>
        </div>

        <div class="campo">
            <h3>Dirección</h3>
            <input type="text" name="direccion" required>
        </div>

        <div class="campo">
            <h3>Grado</h3>

            <select name="grado" required>
                <option value="">Seleccione un grado</option>
                <option value="Sexto">Sexto</option>
                <option value="Séptimo">Séptimo</option>
                <option value="Octavo">Octavo</option>
                <option value="Noveno">Noveno</option>
                <option value="Décimo">Décimo</option>
                <option value="Once">Once</option>
            </select>
        </div>

        <button type="submit" class="btn-guardar">
            Guardar
        </button>

    </div>


    </div>
    </form>

</div>
</body>
</html>
