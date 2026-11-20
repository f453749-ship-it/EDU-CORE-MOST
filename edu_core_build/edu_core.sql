-- ============================================================
-- BASE DE DATOS: EDU-CORE (ajustada al código real del proyecto)
-- Compatible con login.php sin modificar ese archivo.
-- Motor: MySQL 8.x
--
-- IMPORTANTE: este script BORRA la base de datos "edu_core" si ya
-- existe y la crea desde cero. Así evitas el error de "la tabla ya
-- existe" que corta la importación a la mitad (por ejemplo si ya
-- tenías la tabla `usuarios` del script original del proyecto).
-- ============================================================

DROP DATABASE IF EXISTS edu_core;

CREATE DATABASE edu_core
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE edu_core;

-- ------------------------------------------------------------
-- USUARIOS
-- Tabla de autenticación. Mantiene EXACTAMENTE los mismos
-- nombres de columna que usa login.php (id_usuario, nombre,
-- correo, contrasena, rol) para no romper nada.
-- Sirve para docentes y administradores directamente.
-- Los estudiantes también tienen su fila aquí (para el login),
-- y sus datos adicionales van en la tabla `estudiantes`.
-- ------------------------------------------------------------
CREATE TABLE usuarios (
    id_usuario   INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(150) NOT NULL,
    correo       VARCHAR(150) NOT NULL UNIQUE,
    contrasena   VARCHAR(255) NOT NULL,
    rol          ENUM('docente', 'estudiante', 'administrador') NOT NULL,
    telefono     VARCHAR(15),
    direccion    VARCHAR(150),
    documento    VARCHAR(30) UNIQUE,
    estado       ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso  DATETIME
);

-- ------------------------------------------------------------
-- GRADOS
-- Catálogo fijo visto en los <select> de crear_us_estu_ad.php
-- y editar_usuario_ad.php.
-- ------------------------------------------------------------
CREATE TABLE grados (
    id_grado    INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(20) NOT NULL UNIQUE
);

INSERT INTO grados (nombre) VALUES
('Primero'),('Segundo'),('Tercero'),('Cuarto'),('Quinto'),
('Sexto'),('Séptimo'),('Octavo'),('Noveno'),('Décimo'),('Once');

-- ------------------------------------------------------------
-- MATERIAS
-- Catálogo fijo visto en el <script> de crear_usuario_ad.php.
-- ------------------------------------------------------------
CREATE TABLE materias (
    id_materia  INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(60) NOT NULL UNIQUE
);

INSERT INTO materias (nombre) VALUES
('Matemáticas'),('Lengua Castellana'),('Inglés'),('Ciencias Naturales'),
('Ciencias Sociales'),('Física'),('Química'),('Biología'),
('Educación Física'),('Educación Artística'),('Tecnología e Informática'),
('Ética y Valores'),('Religión'),('Filosofía');

-- ------------------------------------------------------------
-- ESTUDIANTES
-- Datos específicos que solo pide el formulario de estudiante
-- (crear_us_estu_ad.php): nombres/apellidos por separado y grado.
-- Se enlaza 1 a 1 con usuarios (rol = 'estudiante').
-- ------------------------------------------------------------
CREATE TABLE estudiantes (
    id_estudiante   INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario      INT NOT NULL UNIQUE,
    nombres         VARCHAR(60) NOT NULL,
    apellidos       VARCHAR(60) NOT NULL,
    id_grado        INT NOT NULL,
    CONSTRAINT fk_estudiante_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_estudiante_grado FOREIGN KEY (id_grado)
        REFERENCES grados(id_grado)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ------------------------------------------------------------
-- DOCENTE_MATERIA_GRADO
-- Qué materia(s) y en qué grado dicta cada docente. Refleja el
-- selector "Cantidad de materias" de crear_usuario_ad.php y los
-- selects de Grado/Materia de editar_usuario_ad.php.
-- ------------------------------------------------------------
CREATE TABLE docente_materia_grado (
    id_asignacion   INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario      INT NOT NULL,
    id_materia      INT NOT NULL,
    id_grado        INT NOT NULL,
    CONSTRAINT fk_dmg_docente FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_dmg_materia FOREIGN KEY (id_materia)
        REFERENCES materias(id_materia)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_dmg_grado FOREIGN KEY (id_grado)
        REFERENCES grados(id_grado)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_dmg UNIQUE (id_usuario, id_materia, id_grado)
);

-- ------------------------------------------------------------
-- ACTIVIDADES
-- Cada "Tarea 1", "Tarea 2"... que asigna un docente, visto en
-- asignar_nota.php, consultar_usuario_ad.php y publicar_notas.php.
-- ------------------------------------------------------------
CREATE TABLE actividades (
    id_actividad    INT AUTO_INCREMENT PRIMARY KEY,
    id_asignacion   INT NOT NULL,
    nombre          VARCHAR(100) NOT NULL,
    fecha           DATE NOT NULL,
    CONSTRAINT fk_actividad_asignacion FOREIGN KEY (id_asignacion)
        REFERENCES docente_materia_grado(id_asignacion)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT uq_actividad UNIQUE (id_asignacion, nombre, fecha)
);

-- ------------------------------------------------------------
-- CALIFICACIONES
-- Nota de cada estudiante por actividad, con estado
-- Pendiente/Publicada tal como aparece en consultar_usuario_ad.php,
-- notas_estudiantes.php y publicar_notas.php.
-- ------------------------------------------------------------
CREATE TABLE calificaciones (
    id_calificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_actividad    INT NOT NULL,
    id_estudiante   INT NOT NULL,
    nota            DECIMAL(3,2) NOT NULL CHECK (nota BETWEEN 1.00 AND 5.00),
    estado          ENUM('Pendiente', 'Publicada') NOT NULL DEFAULT 'Pendiente',
    fecha_registro  DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_calif_actividad FOREIGN KEY (id_actividad)
        REFERENCES actividades(id_actividad)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_calif_estudiante FOREIGN KEY (id_estudiante)
        REFERENCES estudiantes(id_estudiante)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT uq_calif UNIQUE (id_actividad, id_estudiante)
);

-- ------------------------------------------------------------
-- DATOS DE PRUEBA
-- Mismos tres usuarios del script original para que login.php
-- siga funcionando igual que antes, más los datos mínimos para
-- que el docente y el estudiante de ejemplo ya tengan algo que ver.
-- ------------------------------------------------------------
INSERT INTO usuarios (id_usuario, nombre, correo, contrasena, rol, documento) VALUES
(1, 'Samuel Popayan', 'docente@institucion.edu.co',        '12345_l', 'docente', 'DOC-0001'),
(2, 'Samuel Popayan', 'estudiante@institucion.edu.co',      '12345_l', 'estudiante', 'EST-0001'),
(3, 'Samuel Popayan', 'administrador@institucion.edu.co',   '12345_l', 'administrador', 'ADM-0001');

-- El estudiante de ejemplo queda matriculado en Décimo
INSERT INTO estudiantes (id_usuario, nombres, apellidos, id_grado)
SELECT 2, 'Samuel', 'Popayan', id_grado FROM grados WHERE nombre = 'Décimo';

-- El docente de ejemplo queda a cargo de Matemáticas en Décimo
INSERT INTO docente_materia_grado (id_usuario, id_materia, id_grado)
SELECT 1, m.id_materia, g.id_grado
FROM materias m, grados g
WHERE m.nombre = 'Matemáticas' AND g.nombre = 'Décimo';

-- =========================================
-- NOTA DE SEGURIDAD (igual que en el script original)
-- =========================================
-- Guardar contraseñas en texto plano no es seguro para producción.
-- Cuando quieras endurecerlo:
--   1) Genera el hash en PHP con password_hash($contrasena, PASSWORD_DEFAULT)
--      y guarda ese valor en la columna `contrasena`.
--   2) En login.php cambia:
--        if ($contrasena == $datos["contrasena"])
--      por:
--        if (password_verify($contrasena, $datos["contrasena"]))
