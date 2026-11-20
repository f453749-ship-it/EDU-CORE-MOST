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
   GUARDAR CAMBIOS DE PERFIL
   ========================================= */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre   = trim($_POST["nombre"]);
    $correo   = trim($_POST["correo"]);
    $telefono = trim($_POST["telefono"]);

    if ($nombre === "" || $correo === "") {
        $error = "El nombre y el correo son obligatorios.";
    } else {
        $update = $conexion->prepare("UPDATE usuarios SET nombre = ?, correo = ?, telefono = ? WHERE id_usuario = ?");
        $update->bind_param("sssi", $nombre, $correo, $telefono, $_SESSION["id_usuario"]);

        if ($update->execute()) {
            $_SESSION["nombre"] = $nombre;
            $_SESSION["correo"] = $correo;
            $mensaje = "Perfil actualizado correctamente.";
        } else {
            $error = "No se pudo actualizar (verifica que el correo no esté en uso).";
        }
    }
}

$stmt = $conexion->prepare("SELECT nombre, correo, telefono FROM usuarios WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION["id_usuario"]);
$stmt->execute();
$miPerfil = $stmt->get_result()->fetch_assoc();

$editando = isset($_GET["editar"]);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Perfil Administrador</title>
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
                <a href="editar_usuario_ad.php">
                    <i class="bi bi-folder-fill"></i>
                    <span>Editar usuario</span>
                </a>
            </li>

            <li>
                <a href="consultar_usuario_ad.php">
                    <i class="bi bi-upload"></i>
                    <span>Consultar usuarios</span>
                </a>
            </li>

            <li>
                <a href="#">
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

                <i class="bi bi-person-fill"></i>

                <div>
                    <h6><?php echo h(strtoupper($miPerfil["nombre"])); ?></h6>
                    <p>ADMINISTRADOR</p>
                </div>

            <a href="cerrar_sesion.php" class="logout-btn">
                <i class="bi bi-box-arrow-right"></i>
            </a> 

            </div>

        </div>

        <h2 class="title">Mi perfil</h2>

        <?php if ($mensaje): ?>
            <p style="color:#2e7d32;"><strong><?php echo h($mensaje); ?></strong></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p style="color:#a83232;"><strong><?php echo h($error); ?></strong></p>
        <?php endif; ?>

        <?php if (!$editando): ?>

            <br>
            <p>Nombre Completo <br>
            <?php echo h(strtoupper($miPerfil["nombre"])); ?></p>
            <br>
            <p>Correo Electrónico <br>
            <?php echo h($miPerfil["correo"]); ?></p>
            <br>
            <br>
            <p>Número de Teléfono <br>
            <?php echo h($miPerfil["telefono"] ?: "No registrado"); ?></p>

            <a href="perfil_ad.php?editar=1"><button class="editar">Editar</button></a>

        <?php else: ?>

            <form method="POST" action="perfil_ad.php">
                <br>
                <div class="campo">
                    <h3>Nombre completo</h3>
                    <input type="text" name="nombre" value="<?php echo h($miPerfil["nombre"]); ?>" required>
                </div>
                <div class="campo">
                    <h3>Correo Electrónico</h3>
                    <input type="email" name="correo" value="<?php echo h($miPerfil["correo"]); ?>" required>
                </div>
                <div class="campo">
                    <h3>Número de Teléfono</h3>
                    <input type="text" name="telefono" value="<?php echo h($miPerfil["telefono"]); ?>">
                </div>
                <br>
                <button type="submit" class="editar">Guardar cambios</button>
                <a href="perfil_ad.php"><button type="button" class="editar">Cancelar</button></a>
            </form>

        <?php endif; ?>

        
</body>
</html>
