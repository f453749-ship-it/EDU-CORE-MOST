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
   PROCESAR EL FORMULARIO (crear docente)
   ========================================= */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombres    = trim($_POST["nombres"]);
    $apellidos  = trim($_POST["apellidos"]);
    $documento  = trim($_POST["documento"]);
    $contacto   = trim($_POST["contacto"]);
    $correo     = trim($_POST["correo"]);
    $direccion  = trim($_POST["direccion"]);
    $grado      = trim($_POST["grado"]);
    $materias   = isset($_POST["materia"]) ? $_POST["materia"] : [];
    $contrasena = trim($_POST["contrasena"]);

    if ($nombres === "" || $apellidos === "" || $documento === "" || $correo === "" || $contrasena === "" || $grado === "") {

        $error = "Por favor completa todos los campos obligatorios.";

    } else {

        // Verificar que el correo o el documento no existan ya
        $verif = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE correo = ? OR documento = ?");
        $verif->bind_param("ss", $correo, $documento);
        $verif->execute();
        $verif->store_result();

        if ($verif->num_rows > 0) {

            $error = "Ya existe un usuario con ese correo o número de documento.";

        } else {

            $nombreCompleto = $nombres . " " . $apellidos;

            $insertUsuario = $conexion->prepare(
                "INSERT INTO usuarios (nombre, correo, contrasena, rol, telefono, direccion, documento, estado)
                 VALUES (?, ?, ?, 'docente', ?, ?, ?, 'Activo')"
            );
            $insertUsuario->bind_param("ssssss", $nombreCompleto, $correo, $contrasena, $contacto, $direccion, $documento);

            if ($insertUsuario->execute()) {

                $idNuevoUsuario = $conexion->insert_id;

                // Buscar el id del grado seleccionado
                $gradoStmt = $conexion->prepare("SELECT id_grado FROM grados WHERE nombre = ?");
                $gradoStmt->bind_param("s", $grado);
                $gradoStmt->execute();
                $gradoRes = $gradoStmt->get_result();
                $gradoFila = $gradoRes->fetch_assoc();

                if ($gradoFila) {

                    $idGrado = $gradoFila["id_grado"];

                    $insertAsignacion = $conexion->prepare(
                        "INSERT IGNORE INTO docente_materia_grado (id_usuario, id_materia, id_grado)
                         SELECT ?, id_materia, ? FROM materias WHERE nombre = ?"
                    );

                    foreach ($materias as $nombreMateria) {
                        $nombreMateria = trim($nombreMateria);
                        if ($nombreMateria === "") continue;
                        $insertAsignacion->bind_param("iis", $idNuevoUsuario, $idGrado, $nombreMateria);
                        $insertAsignacion->execute();
                    }
                }

                $mensaje = "Docente creado correctamente.";

            } else {

                $error = "No se pudo crear el usuario. Intenta de nuevo.";
            }
        }
    }
}

// Lista de materias para pintar el catálogo en el formulario
$materiasDisponibles = [];
$resMaterias = $conexion->query("SELECT nombre FROM materias ORDER BY nombre");
while ($fila = $resMaterias->fetch_assoc()) {
    $materiasDisponibles[] = $fila["nombre"];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Crear usuario</title>
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

    <h2 class="title">Agregar nuevo usuario</h2>

    <?php if ($mensaje): ?>
        <p style="color:#2e7d32;"><strong><?php echo h($mensaje); ?></strong></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color:#a83232;"><strong><?php echo h($error); ?></strong></p>
    <?php endif; ?>

    <!-- BOTONES -->
    <div class="tipo-usuario">
        <button class="btn-tipo">Docente</button>
        <a href="crear_us_estu_ad.php" class="btn-tipo">Estudiante</a>
    </div>

    <!-- FORMULARIO -->
    <form method="POST" action="crear_usuario_ad.php">
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

            <div class="fila-doble">

                <div class="campo">
                    <h3>Grado</h3>
                    <select name="grado" required>
                        <option value="">Seleccione un grado</option>
                        <option>Sexto</option>
                        <option>Séptimo</option>
                        <option>Octavo</option>
                        <option>Noveno</option>
                        <option>Décimo</option>
                        <option>Once</option>
                    </select>
                </div>

                <div class="campo">
                    <h3>Cantidad de materias</h3>

                    <div class="cantidad">
                        <button type="button" onclick="disminuir()">−</button>

                        <input type="number" id="cantidad" value="1" min="1" readonly>

                        <button type="button" onclick="aumentar()">+</button>
                    </div>
                </div>

            </div>

            <!-- AQUÍ APARECERÁN LAS MATERIAS -->
            <div id="materias-container"></div>

            <br>

            <button type="submit" class="btn-guardar">Guardar</button>

        </div>


    </div>
    </form>

</div>
<script>

const materias = <?php echo json_encode($materiasDisponibles); ?>;


function aumentar() {

    let cantidad = document.getElementById("cantidad");

    let valor = parseInt(cantidad.value);

    cantidad.value = valor + 1;

    actualizarMaterias();
}


function disminuir() {

    let cantidad = document.getElementById("cantidad");

    let valor = parseInt(cantidad.value);

    if (valor > 1) {

        cantidad.value = valor - 1;

        actualizarMaterias();
    }
}


function actualizarMaterias() {

    let cantidad = parseInt(
        document.getElementById("cantidad").value
    );

    let contenedor = document.getElementById("materias-container");

    contenedor.innerHTML = "";


    for (let i = 1; i <= cantidad; i++) {

        let div = document.createElement("div");

        div.className = "campo";

        let titulo = document.createElement("h3");

        titulo.textContent = "Materia " + i;


        let select = document.createElement("select");

        select.name = "materia[]";


        let opcionInicial = document.createElement("option");

        opcionInicial.value = "";

        opcionInicial.textContent = "Seleccione una materia";

        select.appendChild(opcionInicial);


        materias.forEach(function(materia) {

            let opcion = document.createElement("option");

            opcion.value = materia;

            opcion.textContent = materia;

            select.appendChild(opcion);

        });


        div.appendChild(titulo);

        div.appendChild(select);

        contenedor.appendChild(div);
    }
}


/* Crear la primera materia al cargar la página */
actualizarMaterias();

</script>
</body>
</html>
