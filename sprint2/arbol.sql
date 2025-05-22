DELIMITER //

CREATE PROCEDURE BuscarCancionesPorGenero(IN genero_busqueda VARCHAR(50))
BEGIN
    -- Verificar si la versión de MySQL/MariaDB soporta CTE recursivas
    DECLARE version_ok INT DEFAULT 0;
    
    -- Para MySQL (versión >= 8.0) o MariaDB (versión >= 10.2.2)
    SELECT CASE 
        WHEN @@version LIKE '%MariaDB%' AND 
             CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(@@version, '-', 1), '.', 1) AS UNSIGNED) * 100 +
             CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(@@version, '-', 1), '.', -1) AS UNSIGNED) >= 1002 THEN 1
        WHEN @@version LIKE '%MySQL%' AND 
             CAST(SUBSTRING_INDEX(@@version, '.', 1) AS UNSIGNED) >= 8 THEN 1
        ELSE 0
    END INTO version_ok;
    
    IF version_ok = 1 THEN
        -- Usar CTE recursiva si la versión lo soporta
        SET @sql = CONCAT('
            WITH RECURSIVE GenerosHierarchy AS (
                SELECT nombre, padre, nivel
                FROM generos_musicales
                WHERE nombre = "', genero_busqueda, '"
                
                UNION ALL
                
                SELECT g.nombre, g.padre, g.nivel
                FROM generos_musicales g
                JOIN GenerosHierarchy gh ON g.padre = gh.nombre
            )
            
            SELECT c.id, c.titulo, a.nombre AS artista, c.genero, 
                   SEC_TO_TIME(c.duracion) AS duracion_formateada,
                   c.fecha_lanzamiento, c.reproducciones
            FROM canciones c
            JOIN artistas a ON c.artista_id = a.id
            JOIN GenerosHierarchy gh ON c.genero = gh.nombre
            ORDER BY c.reproducciones DESC, c.titulo ASC');
        
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    ELSE
        -- Versión alternativa para bases de datos antiguas (menos eficiente)
        SET @sql = CONCAT('
            SELECT c.id, c.titulo, a.nombre AS artista, c.genero, 
                   SEC_TO_TIME(c.duracion) AS duracion_formateada,
                   c.fecha_lanzamiento, c.reproducciones
            FROM canciones c
            JOIN artistas a ON c.artista_id = a.id
            WHERE c.genero = "', genero_busqueda, '"
            ORDER BY c.reproducciones DESC, c.titulo ASC');
        
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Su versión de MySQL/MariaDB no soporta búsqueda jerárquica completa. Actualice a MySQL 8+ o MariaDB 10.2.2+ para obtener todos los subgéneros.';
    END IF;
END //

DELIMITER ;