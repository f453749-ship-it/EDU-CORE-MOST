<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

// Rol que ya viene detectado desde la base de datos (login.php lo guardó en la sesión)
$rol = $_SESSION["rol"];

// A qué panel hay que continuar según el rol detectado
$destino = "login.php";
if ($rol == "docente")        $destino = "historial.php";
elseif ($rol == "estudiante")      $destino = "historial_estu.php";
elseif ($rol == "administrador")   $destino = "historial_ad.php";

// Función chiquita para no repetir el mismo if 3 veces en el HTML
function claseRol($rolActual, $rolBox) {
    return $rolActual == $rolBox ? "rol-activo" : "rol-inactivo";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Rol</title>
    <link rel="stylesheet" href="style.css">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

    <div class="decoracion">

        <div class="tarjeta">

            <h1>Gestión de rol</h1>

            <p class="descripcion">
                El sistema identificó tu rol como
                <strong><?php echo ucfirst($rol); ?></strong>.
                Te estamos redirigiendo...
            </p>

            <div class="opcion-docente <?php echo claseRol($rol, 'docente'); ?>" id="docente-box">
                <i class="fa-solid fa-user"></i>

                <div class="texto">
                    <h2>Docente</h2>
                    <p>Gestiona actividades y calificaciones</p>
                </div>
            </div>

            <div class="opcion <?php echo claseRol($rol, 'estudiante'); ?>" id="estudiante-box">
                <i class="fa-solid fa-graduation-cap"></i>

                <div class="texto">
                    <h2>Estudiante</h2>
                    <p>Consulta tus notas y actividades</p>
                </div>
            </div>

            <div class="opcion <?php echo claseRol($rol, 'administrador'); ?>" id="administrador-box">
                <i class="fa-solid fa-shield-halved"></i>

                <div class="texto">
                    <h2>Administrador</h2>
                    <p>Administrar usuarios</p>
                </div>
            </div>

        </div>

    </div>

    <script>
        // La opción "activa" (la del rol detectado) se puede tocar/clickear
        // para continuar de una vez, y de todas formas continúa sola después
        // de un momento para que el usuario alcance a ver la animación.
        const destino = <?php echo json_encode($destino); ?>;
        const activa = document.querySelector('.rol-activo');

        if (activa) {
            activa.addEventListener('click', () => {
                window.location.href = destino;
            });
        }

        setTimeout(() => {
            window.location.href = destino;
        }, 1800);
    </script>

</body>
</html>
