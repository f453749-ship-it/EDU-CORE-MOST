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

/* =========================================
   CONTADORES
   ========================================= */
$totalUsuarios = $conexion->query("SELECT COUNT(*) AS c FROM usuarios")->fetch_assoc()["c"];
$totalDocentes = $conexion->query("SELECT COUNT(*) AS c FROM usuarios WHERE rol = 'docente'")->fetch_assoc()["c"];
$totalEstudiantes = $conexion->query("SELECT COUNT(*) AS c FROM usuarios WHERE rol = 'estudiante'")->fetch_assoc()["c"];

/* =========================================
   USUARIOS RECIENTES
   ========================================= */
$sql = "SELECT u.nombre, u.correo, u.rol, u.estado, g.nombre AS grado
        FROM usuarios u
        LEFT JOIN estudiantes e ON e.id_usuario = u.id_usuario
        LEFT JOIN grados g ON g.id_grado = e.id_grado
        ORDER BY u.fecha_creacion DESC
        LIMIT 8";
$recientes = $conexion->query($sql);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Historial Administradores</title>
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

        <!-- CONTENT -->
        <div class="content">

            <h2 class="title">Resumen del sistema</h2>

            <!-- CARDS -->
            <div class="cards">

                <div class="card-box">
                    <h3>Total de usuarios</h3>
                    <span><?php echo $totalUsuarios; ?></span>
                </div>

                <div class="card-box">
                    <h3>Docentes</h3>
                    <span><?php echo $totalDocentes; ?></span>
                </div>

                <div class="card-box">
                    <h3>Estudiantes</h3>
                    <span><?php echo $totalEstudiantes; ?></span>
                </div>
            </div>
            <!-- TABLE -->
            <div class="table-section">
                <div class="table-container">
                    <h3>Usuarios recientes</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Grado</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recientes && $recientes->num_rows > 0): ?>
                                <?php while ($fila = $recientes->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo h($fila["nombre"]); ?></td>
                                        <td><?php echo h($fila["correo"]); ?></td>
                                        <td><?php echo h(ucfirst($fila["rol"])); ?></td>
                                        <td><?php echo $fila["grado"] ? h($fila["grado"]) : "-"; ?></td>
                                        <td><?php echo h($fila["estado"]); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5">Todavía no hay usuarios registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
