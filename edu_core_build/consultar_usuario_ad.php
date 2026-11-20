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

/* =========================================
   ELIMINAR USUARIO
   ========================================= */
if (isset($_GET["eliminar"])) {

    $idEliminar = intval($_GET["eliminar"]);

    if ($idEliminar == $_SESSION["id_usuario"]) {

        $mensaje = "No puedes eliminar tu propio usuario mientras tienes la sesión iniciada.";

    } else {

        $del = $conexion->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        $del->bind_param("i", $idEliminar);
        $del->execute();
        $mensaje = "Usuario eliminado correctamente.";
    }
}

/* =========================================
   BÚSQUEDA / LISTADO
   ========================================= */
$busqueda = isset($_GET["buscar"]) ? trim($_GET["buscar"]) : "";

if ($busqueda !== "") {
    $like = "%" . $busqueda . "%";
    $sql = "SELECT u.id_usuario, u.nombre, u.correo, u.rol, u.estado, g.nombre AS grado
            FROM usuarios u
            LEFT JOIN estudiantes e ON e.id_usuario = u.id_usuario
            LEFT JOIN grados g ON g.id_grado = e.id_grado
            WHERE u.nombre LIKE ? OR u.correo LIKE ? OR u.rol LIKE ?
            ORDER BY u.nombre";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $resultados = $stmt->get_result();
} else {
    $sql = "SELECT u.id_usuario, u.nombre, u.correo, u.rol, u.estado, g.nombre AS grado
            FROM usuarios u
            LEFT JOIN estudiantes e ON e.id_usuario = u.id_usuario
            LEFT JOIN grados g ON g.id_grado = e.id_grado
            ORDER BY u.fecha_creacion DESC
            LIMIT 50";
    $resultados = $conexion->query($sql);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Consultar usuarios</title>
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
                <a href="#">
                    <i class="bi bi-upload"></i>
                    <span>Consultar usuarios</span>
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

            <h2 class="title">Consultar usuarios</h2>

            <?php if ($mensaje): ?>
                <p><strong><?php echo h($mensaje); ?></strong></p>
            <?php endif; ?>

            <div class="table-container">

                <form method="GET" action="consultar_usuario_ad.php">
                    <input type="text" name="buscar" placeholder="Buscar por nombre, correo o rol.." value="<?php echo h($busqueda); ?>">
                    <button type="submit" class="btn-tipo">Buscar</button>
                </form>

                <br><br>

                <table class="table">

                    <thead>

                        <tr>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Grado</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if ($resultados && $resultados->num_rows > 0): ?>
                            <?php while ($fila = $resultados->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo h($fila["nombre"]); ?></td>
                                    <td><?php echo h($fila["correo"]); ?></td>
                                    <td><?php echo h(ucfirst($fila["rol"])); ?></td>
                                    <td><?php echo $fila["grado"] ? h($fila["grado"]) : "-"; ?></td>
                                    <td><?php echo h($fila["estado"]); ?></td>
                                    <td>
                                        <a href="editar_usuario_ad.php?id=<?php echo $fila["id_usuario"]; ?>">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="consultar_usuario_ad.php?eliminar=<?php echo $fila["id_usuario"]; ?>"
                                           onclick="return confirm('¿Eliminar a <?php echo h(addslashes($fila["nombre"])); ?>? Esta acción no se puede deshacer.');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6">No se encontraron usuarios.</td></tr>
                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</body>
</html>
