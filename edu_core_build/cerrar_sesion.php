<?php

session_start();

// Si el usuario confirma, se destruye la sesión de verdad y se manda a login.
if (isset($_GET["confirmar"]) && $_GET["confirmar"] == "1") {

    $_SESSION = [];
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();

}

// Si el usuario cancela, lo devolvemos a SU panel según su rol
// (antes siempre volvía a historial.php, aunque fuera estudiante o administrador).
$volver = "login.php";

if (isset($_SESSION["rol"])) {

    if ($_SESSION["rol"] == "docente") {
        $volver = "historial.php";
    } elseif ($_SESSION["rol"] == "estudiante") {
        $volver = "historial_estu.php";
    } elseif ($_SESSION["rol"] == "administrador") {
        $volver = "historial_ad.php";
    }

}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EDU-CORE</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="close">

    <div class="container">
    <div class="tarjeta">
        <h1>Cerrar Sesión</h1>
        <br>
        <div class="texto">
            <h2>¿Estas seguro que deseas cerrar la Sesión?</h2>
        </div>
        <br>
        <div class="opciones">
            <a href="<?php echo $volver; ?>">
            <button>Cancelar</button>
            </a>
            <a href="cerrar_sesion.php?confirmar=1">
            <button class="cerrar">cerrar sesión</button>
            </a>
        </div>
        </div>
    </div>
    
</body>
</html>
