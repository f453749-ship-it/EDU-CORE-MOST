<?php
/* =========================================
   CONEXIÓN CENTRAL CON LA BASE DE DATOS
   Todas las páginas incluyen este archivo en
   vez de abrir su propia conexión mysqli.
   ========================================= */

$conexion = new mysqli("localhost", "root", "", "edu_core");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");

/* =========================================
   FUNCIONES DE AYUDA REUTILIZABLES
   ========================================= */

// Escapa texto para mostrarlo seguro dentro de HTML
function h($texto) {
    return htmlspecialchars($texto ?? "", ENT_QUOTES, "UTF-8");
}

// Formatea una fecha/fecha-hora de MySQL (YYYY-MM-DD) a DD/MM/YYYY
function formatoFecha($fecha) {
    if (empty($fecha)) return "-";
    $partes = explode(" ", $fecha);
    $soloFecha = $partes[0];
    $trozos = explode("-", $soloFecha);
    if (count($trozos) != 3) return h($fecha);
    return $trozos[2] . "/" . $trozos[1] . "/" . $trozos[0];
}

// Texto de tiempo relativo ("hace 5 minutos", "ayer", etc.) para el feed de actividad
function tiempoRelativo($fechaHora) {
    $entonces = strtotime($fechaHora);
    $ahora = time();
    $diferencia = $ahora - $entonces;

    if ($diferencia < 60) return "Hace un momento";
    if ($diferencia < 3600) {
        $min = floor($diferencia / 60);
        return "Hace " . $min . " minuto" . ($min == 1 ? "" : "s");
    }
    if ($diferencia < 86400) {
        $horas = floor($diferencia / 3600);
        return "Hace " . $horas . " hora" . ($horas == 1 ? "" : "s");
    }
    $dias = floor($diferencia / 86400);
    if ($dias == 1) return "Ayer";
    return "Hace " . $dias . " días";
}
