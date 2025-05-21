-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3307
-- Tiempo de generación: 21-05-2025 a las 13:02:01
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `streaming_recommendation`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `amistades`
--

CREATE TABLE IF NOT EXISTS `amistades` (
  `usuario_id1` int(11) NOT NULL,
  `usuario_id2` int(11) NOT NULL,
  `fecha_amistad` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `artistas`
--

CREATE TABLE IF NOT EXISTS `artistas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `genero_principal` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `artistas`
--

INSERT INTO `artistas` (`id`, `nombre`, `genero_principal`) VALUES
(1, 'Queen', 'Rock'),
(2, 'Madonna', 'Pop'),
(3, 'Daft Punk', 'Electrónica');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `canciones`
--

CREATE TABLE IF NOT EXISTS `canciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) NOT NULL,
  `artista_id` int(11) NOT NULL,
  `genero` varchar(50) NOT NULL,
  `duracion` int(11) NOT NULL COMMENT 'Duración en segundos',
  `fecha_lanzamiento` date DEFAULT NULL,
  `reproducciones` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `artista_id` (`artista_id`),
  KEY `idx_genero` (`genero`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `canciones`
--

INSERT INTO `canciones` (`id`, `titulo`, `artista_id`, `genero`, `duracion`, `fecha_lanzamiento`, `reproducciones`) VALUES
(1, 'Bohemian Rhapsody', 1, 'Rock Clásico', 354, '1975-10-31', 0),
(2, 'Like a Prayer', 2, 'Pop', 321, '1989-03-03', 0),
(3, 'Around the World', 3, 'Electrónica', 425, '1997-01-17', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cola_reproduccion`
--

CREATE TABLE IF NOT EXISTS `cola_reproduccion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `cancion_id` int(11) NOT NULL,
  `orden` int(11) NOT NULL,
  `fecha_agregado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cancion_id` (`cancion_id`),
  KEY `idx_cola_orden` (`usuario_id`,`orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eliminaciones_programadas`
--

CREATE TABLE IF NOT EXISTS `eliminaciones_programadas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `fecha_eliminacion` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `generos_musicales`
--

CREATE TABLE IF NOT EXISTS `generos_musicales` (
  `nombre` varchar(50) NOT NULL,
  `padre` varchar(50) DEFAULT NULL,
  `nivel` int(11) NOT NULL,
  PRIMARY KEY (`nombre`),
  KEY `padre` (`padre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `generos_musicales`
--

INSERT INTO `generos_musicales` (`nombre`, `padre`, `nivel`) VALUES
('Electrónica', NULL, 0),
('Metal', 'Rock', 1),
('Pop', NULL, 0),
('Pop Latino', 'Pop', 1),
('Rock', NULL, 0),
('Rock Clásico', 'Rock', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_reproduccion`
--

CREATE TABLE IF NOT EXISTS `historial_reproduccion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `cancion_id` int(11) NOT NULL,
  `fecha_reproduccion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cancion_id` (`cancion_id`),
  KEY `idx_historial_usuario` (`usuario_id`,`fecha_reproduccion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguimiento_artistas`
--

CREATE TABLE IF NOT EXISTS `seguimiento_artistas` (
  `usuario_id` int(11) NOT NULL,
  `artista_id` int(11) NOT NULL,
  `fecha_seguimiento` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`usuario_id`,`artista_id`),
  KEY `artista_id` (`artista_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `acepta_privacidad` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_aceptacion_privacidad` datetime DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_login` datetime DEFAULT NULL,
  `token_verificacion` varchar(100) DEFAULT NULL,
  `intentos_login` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_usuario_email` (`email`),
  KEY `idx_usuario_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `historial_reproduccion`
--
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `limpiar_historial` AFTER INSERT ON `historial_reproduccion` FOR EACH ROW BEGIN
    DELETE FROM historial_reproduccion
    WHERE usuario_id = NEW.usuario_id
    AND id NOT IN (
        SELECT id FROM (
            SELECT id
            FROM historial_reproduccion
            WHERE usuario_id = NEW.usuario_id
            ORDER BY fecha_reproduccion DESC
            LIMIT 50
        ) AS temp
    );
END //
DELIMITER ;

--
-- Disparadores para la tabla `seguimiento_artistas`
--
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `evitar_auto_seguimiento`
BEFORE INSERT ON `seguimiento_artistas`
FOR EACH ROW
BEGIN
    -- Verificar si el usuario es el mismo que el artista
    -- (Esto asume que los artistas también están en la tabla de usuarios)
    IF NEW.usuario_id = NEW.artista_id THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Un usuario no puede seguirse a sí mismo como artista';
    END IF;
END //
DELIMITER ;

--
-- Procedimientos almacenados
--

-- Función para seguir a un artista
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `seguir_artista`(
    IN p_usuario_id INT,
    IN p_artista_id INT
)
BEGIN
    -- Verificar si el usuario ya sigue al artista
    IF NOT EXISTS (
        SELECT 1 FROM seguimiento_artistas 
        WHERE usuario_id = p_usuario_id AND artista_id = p_artista_id
    ) THEN
        -- Insertar nuevo seguimiento
        INSERT INTO seguimiento_artistas (usuario_id, artista_id)
        VALUES (p_usuario_id, p_artista_id);
        
        SELECT 'Ahora sigues a este artista' AS mensaje;
    ELSE
        SELECT 'Ya sigues a este artista' AS mensaje;
    END IF;
END //
DELIMITER ;

-- Función para dejar de seguir a un artista
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `dejar_seguir_artista`(
    IN p_usuario_id INT,
    IN p_artista_id INT
)
BEGIN
    -- Eliminar el seguimiento si existe
    DELETE FROM seguimiento_artistas 
    WHERE usuario_id = p_usuario_id AND artista_id = p_artista_id;
    
    IF ROW_COUNT() > 0 THEN
        SELECT 'Has dejado de seguir a este artista' AS mensaje;
    ELSE
        SELECT 'No seguías a este artista' AS mensaje;
    END IF;
END //
DELIMITER ;

-- Obtener artistas seguidos por un usuario
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `obtener_artistas_seguidos`(
    IN p_usuario_id INT
)
BEGIN
    SELECT a.id, a.nombre, a.genero_principal, sa.fecha_seguimiento
    FROM artistas a
    JOIN seguimiento_artistas sa ON a.id = sa.artista_id
    WHERE sa.usuario_id = p_usuario_id
    ORDER BY sa.fecha_seguimiento DESC;
END //
DELIMITER ;

-- Obtener seguidores de un artista
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `obtener_seguidores_artista`(
    IN p_artista_id INT
)
BEGIN
    SELECT u.id, u.nombre, u.email, sa.fecha_seguimiento
    FROM usuarios u
    JOIN seguimiento_artistas sa ON u.id = sa.usuario_id
    WHERE sa.artista_id = p_artista_id
    ORDER BY sa.fecha_seguimiento DESC;
END //
DELIMITER ;

-- Verificar si un usuario sigue a un artista específico
DELIMITER //
CREATE FUNCTION IF NOT EXISTS `sigue_artista`(
    p_usuario_id INT,
    p_artista_id INT
) RETURNS BOOLEAN
DETERMINISTIC
BEGIN
    DECLARE resultado BOOLEAN;
    
    SELECT EXISTS (
        SELECT 1 FROM seguimiento_artistas 
        WHERE usuario_id = p_usuario_id AND artista_id = p_artista_id
    ) INTO resultado;
    
    RETURN resultado;
END //
DELIMITER ;

-- Recomendar canciones basadas en seguimiento
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `recomendar_por_seguimiento`(
    IN p_usuario_id INT,
    IN p_limit INT
)
BEGIN
    -- Canciones de artistas que sigue, ordenadas por popularidad
    SELECT c.id, c.titulo, a.nombre AS artista, c.genero, c.duracion
    FROM canciones c
    JOIN artistas a ON c.artista_id = a.id
    JOIN seguimiento_artistas sa ON a.id = sa.artista_id
    WHERE sa.usuario_id = p_usuario_id
    ORDER BY c.reproducciones DESC
    LIMIT p_limit;
END //
DELIMITER ;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `amistades`
--
ALTER TABLE `amistades`
  ADD CONSTRAINT `amistades_ibfk_1` FOREIGN KEY (`usuario_id1`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `amistades_ibfk_2` FOREIGN KEY (`usuario_id2`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `canciones`
--
ALTER TABLE `canciones`
  ADD CONSTRAINT `canciones_ibfk_1` FOREIGN KEY (`artista_id`) REFERENCES `artistas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cola_reproduccion`
--
ALTER TABLE `cola_reproduccion`
  ADD CONSTRAINT `cola_reproduccion_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cola_reproduccion_ibfk_2` FOREIGN KEY (`cancion_id`) REFERENCES `canciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `eliminaciones_programadas`
--
ALTER TABLE `eliminaciones_programadas`
  ADD CONSTRAINT `eliminaciones_programadas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `generos_musicales`
--
ALTER TABLE `generos_musicales`
  ADD CONSTRAINT `generos_musicales_ibfk_1` FOREIGN KEY (`padre`) REFERENCES `generos_musicales` (`nombre`) ON DELETE CASCADE;

--
-- Filtros para la tabla `historial_reproduccion`
--
ALTER TABLE `historial_reproduccion`
  ADD CONSTRAINT `historial_reproduccion_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historial_reproduccion_ibfk_2` FOREIGN KEY (`cancion_id`) REFERENCES `canciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `seguimiento_artistas`
--
ALTER TABLE `seguimiento_artistas`
  ADD CONSTRAINT `seguimiento_artistas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seguimiento_artistas_ibfk_2` FOREIGN KEY (`artista_id`) REFERENCES `artistas` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;