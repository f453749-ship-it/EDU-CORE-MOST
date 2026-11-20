<?php

session_start();
require "db.php";


/* =========================================
   COMPROBAR SI SE ENVIÓ EL FORMULARIO
   ========================================= */

$mensaje = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usuario = trim($_POST["usuario"]);
    $contrasena = $_POST["contrasena"];


    /* =====================================
       BUSCAR EL USUARIO EN LA BASE DE DATOS
       ===================================== */

    $sql = "SELECT * FROM usuarios WHERE correo = ?";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param("s", $usuario);

    $stmt->execute();

    $resultado = $stmt->get_result();


    /* =====================================
       COMPROBAR SI EL USUARIO EXISTE
       ===================================== */

    if ($resultado->num_rows == 1) {

        $datos = $resultado->fetch_assoc();


        /* =================================
           COMPROBAR CONTRASEÑA
           ================================= */

        if ($contrasena == $datos["contrasena"]) {


            /* =============================
               GUARDAR DATOS EN LA SESIÓN
               ============================= */

            $_SESSION["id_usuario"] = $datos["id_usuario"];
            $_SESSION["nombre"] = $datos["nombre"];
            $_SESSION["correo"] = $datos["correo"];
            $_SESSION["rol"] = $datos["rol"];


            /* =============================
               MOSTRAR PANTALLA DE ROL
               ============================= */

            // En vez de mandar directo al panel, primero pasamos por
            // rol_usuario.php, que muestra el rol detectado y luego
            // continúa automáticamente al panel correcto.
            header("Location: rol_usuario.php");
            exit();

        }

        else {

            $mensaje = "Correo o contraseña incorrecta.";

        }

    }

    else {

        $mensaje = "Correo o contraseña incorrecta.";

    }

}

?>


<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login EDU-CORE</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <link rel="stylesheet" href="style3.css">

</head>


<body>


    <div class="login-container">


        <!-- PANEL IZQUIERDO -->

        <div class="left-panel">

            <a href="bienvenida.html">
        <img src="img/logo.png" alt="logo">
    </a>

            <h1>EDU-CORE</h1>

            <p>
                Núcleo de la educación
            </p>


            <div class="network">

                <img src="img/figura1.png" alt="">

            </div>

        </div>


        <!-- PANEL DERECHO -->

        <div class="right-panel">

            <h2>Iniciar sesión</h2>

            <p>Bienvenido de nuevo</p>


            <!-- MENSAJE DE ERROR -->

            <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($mensaje)): ?>

    <div class="alert alert-danger">
        <?php echo $mensaje; ?>
    </div>

<?php endif; ?>


            <!-- FORMULARIO -->

            <form method="POST" action="login.php">


                <!-- USUARIO -->

                <div class="input-group">

                    <i class="bi bi-person"></i>

                    <input
                        type="text"
                        name="usuario"
                        class="form-control"
                        placeholder="Usuario o correo electrónico"
                        required
                    >

                </div>


                <!-- CONTRASEÑA -->

                <div class="input-group">

                    <i class="bi bi-lock"></i>

                    <input
                        type="password"
                        name="contrasena"
                        class="form-control"
                        placeholder="Contraseña"
                        required
                    >

                </div>


                <!-- RECORDARME -->

                <div class="remember">

                    <div>

                        <input type="checkbox">

                        Recordarme

                    </div>

                </div>


                <!-- BOTÓN -->

                <button
                    type="submit"
                    class="btn-login"
                >

                    Iniciar sesión

                </button>


            </form>

        </div>

    </div>


</body>

</html>
