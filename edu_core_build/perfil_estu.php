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
$mensaje = "";
$error = "";

/* =========================================
   GUARDAR CAMBIOS DE PERFIL (correo y teléfono,
   el nombre y el grado los administra el colegio)
   ========================================= */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $correo   = trim($_POST["correo"]);
    $telefono = trim($_POST["telefono"]);

    if ($correo === "") {
        $error = "El correo es obligatorio.";
    } else {
        $update = $conexion->prepare("UPDATE usuarios SET correo = ?, telefono = ? WHERE id_usuario = ?");
        $update->bind_param("ssi", $correo, $telefono, $idUsuario);

        if ($update->execute()) {
            $mensaje = "Perfil actualizado correctamente.";
        } else {
            $error = "No se pudo actualizar (verifica que el correo no esté en uso).";
        }
    }
}

$stmt = $conexion->prepare(
    "SELECT u.nombre, u.correo, u.telefono, g.nombre AS grado
     FROM usuarios u
     JOIN estudiantes e ON e.id_usuario = u.id_usuario
     JOIN grados g ON g.id_grado = e.id_grado
     WHERE u.id_usuario = ?"
);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$miPerfil = $stmt->get_result()->fetch_assoc();

$editando = isset($_GET["editar"]);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Perfil Estudiante</title>
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
        

<aside>

        <ul class="menu">

            <li>
                <a href="historial_estu.php">
                    <i class="bi bi-arrow-left"></i>
                    <span>HISTORIAL</span>
                </a>
            </li>
            </ul>
    </aside>

    
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
                    <p>Estudiante</p>
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
            <p>Grado<br>
            <?php echo h($miPerfil["grado"]); ?></p>
            <br>
            <p>Número de Teléfono <br>
            <?php echo h($miPerfil["telefono"] ?: "No registrado"); ?></p>

        <?php else: ?>

       

        <?php endif; ?>

        
</body>
</html>
