<?php
/**
 * SISTEMA COMPLETO DE GENERACIÓN DE PLAYLISTS AUTOMÁTICAS
 * Versión mejorada con integración de árbol de géneros musicales
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'soundscouts'); // Usando la base de datos soundscouts

class ConexionDB {
    private static $instancia;
    
    public static function obtener() {
        if (!self::$instancia) {
            self::$instancia = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if (self::$instancia->connect_error) {
                die("Error de conexión: " . self::$instancia->connect_error);
            }
            
            self::$instancia->set_charset("utf8");
        }
        return self::$instancia;
    }
}

class GestorGenerosMusicales {
    private $conexion;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
    }
    
    // Obtiene los géneros favoritos de un usuario basado en su historial
    public function obtenerGenerosFavoritos($usuarioId) {
        // Consulta SQL para obtener géneros más escuchados
        $query = "SELECT c.genero, COUNT(*) as reproducciones
                  FROM historial_reproduccion hr
                  JOIN canciones c ON hr.cancion_id = c.id
                  WHERE hr.usuario_id = ?
                  GROUP BY c.genero
                  ORDER BY reproducciones DESC
                  LIMIT 5";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Obtiene géneros favoritos explícitos del usuario (de la tabla favoritos_generos)
    public function obtenerGenerosFavoritosExplicitos($usuarioId) {
        $query = "SELECT g.nombre, g.padre, g.nivel 
                  FROM favoritos_generos fg
                  JOIN generos_musicales g ON fg.genero_nombre = g.nombre
                  WHERE fg.usuario_id = ?
                  ORDER BY fg.fecha_agregado DESC";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Obtiene la jerarquía completa de un género (padres e hijos)
    public function obtenerJerarquiaGenero($genero) {
        // Primero obtenemos el género padre
        $queryPadre = "SELECT padre FROM generos_musicales WHERE nombre = ?";
        $stmtPadre = $this->conexion->prepare($queryPadre);
        $stmtPadre->bind_param("s", $genero);
        $stmtPadre->execute();
        $padre = $stmtPadre->get_result()->fetch_assoc();
        
        $jerarquia = [];
        
        // Si tiene padre, lo agregamos a la jerarquía
        if ($padre && $padre['padre']) {
            $jerarquia[] = $padre['padre'];
            
            // Buscamos más ancestros recursivamente
            $abuelo = $this->obtenerJerarquiaGenero($padre['padre']);
            if ($abuelo) {
                $jerarquia = array_merge($jerarquia, $abuelo);
            }
        }
        
        return $jerarquia;
    }
    
    // Obtiene todos los géneros hijos de un género dado
    public function obtenerSubGeneros($generoPadre) {
        $query = "SELECT nombre FROM generos_musicales WHERE padre = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("s", $generoPadre);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Recomienda géneros similares basados en el árbol de géneros
    public function recomendarPorGenerosSimilares($genero, $limite = 5) {
        // Obtenemos la jerarquía del género
        $jerarquia = $this->obtenerJerarquiaGenero($genero);
        
        // Incluimos el género actual en la jerarquía
        array_unshift($jerarquia, $genero);
        
        $generosSimilares = [];
        
        // Para cada nivel en la jerarquía, buscamos géneros relacionados
        foreach ($jerarquia as $g) {
            // Obtenemos el género padre
            $queryPadre = "SELECT padre FROM generos_musicales WHERE nombre = ?";
            $stmtPadre = $this->conexion->prepare($queryPadre);
            $stmtPadre->bind_param("s", $g);
            $stmtPadre->execute();
            $padre = $stmtPadre->get_result()->fetch_assoc();
            
            // Si tiene padre, buscamos hermanos (géneros con el mismo padre)
            if ($padre && $padre['padre']) {
                $queryHermanos = "SELECT nombre FROM generos_musicales 
                                WHERE padre = ? AND nombre != ?
                                LIMIT ?";
                $stmtHermanos = $this->conexion->prepare($queryHermanos);
                $limiteHermanos = ceil($limite / count($jerarquia));
                $stmtHermanos->bind_param("ssi", $padre['padre'], $g, $limiteHermanos);
                $stmtHermanos->execute();
                $hermanos = $stmtHermanos->get_result()->fetch_all(MYSQLI_ASSOC);
                
                $generosSimilares = array_merge($generosSimilares, $hermanos);
            }
            
            // También buscamos subgéneros (géneros hijos)
            $queryHijos = "SELECT nombre FROM generos_musicales 
                          WHERE padre = ?
                          LIMIT ?";
            $stmtHijos = $this->conexion->prepare($queryHijos);
            $limiteHijos = ceil($limite / count($jerarquia));
            $stmtHijos->bind_param("si", $g, $limiteHijos);
            $stmtHijos->execute();
            $hijos = $stmtHijos->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $generosSimilares = array_merge($generosSimilares, $hijos);
            
            // Si ya tenemos suficientes, salimos del bucle
            if (count($generosSimilares) >= $limite) {
                break;
            }
        }
        
        // Eliminamos duplicados
        $generosUnicos = [];
        foreach ($generosSimilares as $genero) {
            if (!in_array($genero, $generosUnicos)) {
                $generosUnicos[] = $genero;
            }
        }
        
        return array_slice($generosUnicos, 0, $limite);
    }
}

class GeneradorPlaylists {
    private $conexion;
    private $gestorGeneros;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
        $this->gestorGeneros = new GestorGenerosMusicales($conexion);
    }

    // Método principal para generar playlists automáticas
    public function generarPlaylistAutomatica($usuarioId, $nombrePlaylist, $numCanciones = 15) {
        try {
            // Primero intentamos con géneros favoritos explícitos
            $generosFavoritos = $this->gestorGeneros->obtenerGenerosFavoritosExplicitos($usuarioId);
            
            // Si no hay géneros favoritos explícitos, usamos el historial
            if (empty($generosFavoritos)) {
                $generosFavoritos = $this->gestorGeneros->obtenerGenerosFavoritos($usuarioId);
                
                if (empty($generosFavoritos)) {
                    throw new Exception("No hay suficiente información para determinar géneros favoritos");
                }
            }

            // Selecciona canciones basadas en los géneros
            $cancionesSeleccionadas = $this->seleccionarCanciones($usuarioId, $generosFavoritos, $numCanciones);
            
            if (empty($cancionesSeleccionadas)) {
                throw new Exception("No se encontraron canciones adecuadas");
            }

            // Crea la playlist en la base de datos
            $playlistId = $this->crearPlaylistEnBD($usuarioId, $nombrePlaylist);
            // Agrega las canciones a la playlist
            $this->agregarCancionesAPlaylist($playlistId, $cancionesSeleccionadas);

            return [
                'id' => $playlistId,
                'nombre' => $nombrePlaylist,
                'num_canciones' => count($cancionesSeleccionadas),
                'canciones' => $cancionesSeleccionadas
            ];

        } catch (Exception $e) {
            error_log("Error generando playlist: " . $e->getMessage());
            return false;
        }
    }

    // Selecciona canciones para la playlist
    private function seleccionarCanciones($usuarioId, $generosFavoritos, $numCanciones) {
        $cancionesSeleccionadas = [];
        $generosProcesados = [];
        
        // Ordena géneros por preferencia (explícitos primero, luego por reproducciones)
        usort($generosFavoritos, function($a, $b) {
            $prioridadA = isset($a['nivel']) ? $a['nivel'] : $a['reproducciones'];
            $prioridadB = isset($b['nivel']) ? $b['nivel'] : $b['reproducciones'];
            return $prioridadA - $prioridadB;
        });

        // Toma los 3 géneros principales
        $topGeneros = array_slice($generosFavoritos, 0, 3);
        
        foreach ($topGeneros as $genero) {
            $nombreGenero = $genero['genero'] ?? $genero['nombre'];
            
            if (in_array($nombreGenero, $generosProcesados)) continue;
            
            $generosProcesados[] = $nombreGenero;
            
            // Obtenemos canciones para este género principal
            $cancionesGenero = $this->obtenerCancionesParaGenero(
                $usuarioId, 
                $nombreGenero, 
                ceil($numCanciones / count($topGeneros))
            );
            
            shuffle($cancionesGenero);
            $cancionesSeleccionadas = array_merge($cancionesSeleccionadas, $cancionesGenero);
            
            // Si ya tenemos suficientes canciones, terminamos
            if (count($cancionesSeleccionadas) >= $numCanciones) break;
            
            // Buscamos géneros similares basados en el árbol de géneros
            $generosSimilares = $this->gestorGeneros->recomendarPorGenerosSimilares($nombreGenero);
            
            foreach ($generosSimilares as $generoSimilar) {
                if (count($cancionesSeleccionadas) >= $numCanciones) break;
                
                $cancionesSimilar = $this->obtenerCancionesParaGenero(
                    $usuarioId, 
                    $generoSimilar['nombre'], 
                    max(1, floor($numCanciones / (count($topGeneros) * 3)))
                );
                
                $cancionesSeleccionadas = array_merge($cancionesSeleccionadas, $cancionesSimilar);
            }
        }
        
        return array_slice($cancionesSeleccionadas, 0, $numCanciones);
    }

    // Obtiene canciones para un género específico
    private function obtenerCancionesParaGenero($usuarioId, $genero, $limite) {
        // Primero busca canciones no escuchadas del género
        $query = "SELECT c.id, c.titulo, a.nombre as artista, c.genero 
                  FROM canciones c
                  JOIN artistas a ON c.artista_id = a.id
                  WHERE c.genero = ?
                  AND c.id NOT IN (
                      SELECT cancion_id FROM historial_reproduccion 
                      WHERE usuario_id = ?
                  )
                  ORDER BY c.reproducciones DESC
                  LIMIT ?";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("sii", $genero, $usuarioId, $limite);
        $stmt->execute();
        $result = $stmt->get_result();
        $canciones = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        
        // Si no encontró suficientes canciones no escuchadas, busca cualquier canción del género
        if (count($canciones) < $limite) {
            $query = "SELECT c.id, c.titulo, a.nombre as artista, c.genero 
                      FROM canciones c
                      JOIN artistas a ON c.artista_id = a.id
                      WHERE c.genero = ?
                      ORDER BY c.reproducciones DESC
                      LIMIT ?";
            
            $stmt = $this->conexion->prepare($query);
            $stmt->bind_param("si", $genero, $limite);
            $stmt->execute();
            $result = $stmt->get_result();
            $masCanciones = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
            
            $canciones = array_merge($canciones, $masCanciones);
        }
        
        return array_slice($canciones, 0, $limite);
    }

    // Crea una nueva playlist en la base de datos
    private function crearPlaylistEnBD($usuarioId, $nombrePlaylist) {
        // Primero verificamos si la tabla playlists existe
        $queryCheck = "SHOW TABLES LIKE 'playlists'";
        $result = $this->conexion->query($queryCheck);
        
        if ($result->num_rows == 0) {
            // Si no existe, la creamos
            $queryCreate = "CREATE TABLE playlists (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                nombre VARCHAR(255) NOT NULL,
                fecha_creacion DATETIME NOT NULL,
                es_automatica BOOLEAN DEFAULT 1,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
            )";
            $this->conexion->query($queryCreate);
            
            // Creamos también la tabla de relación playlist_canciones
            $queryCreateRel = "CREATE TABLE playlist_canciones (
                id INT AUTO_INCREMENT PRIMARY KEY,
                playlist_id INT NOT NULL,
                cancion_id INT NOT NULL,
                orden INT NOT NULL,
                fecha_agregado DATETIME NOT NULL,
                FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
                FOREIGN KEY (cancion_id) REFERENCES canciones(id) ON DELETE CASCADE
            )";
            $this->conexion->query($queryCreateRel);
        }
        
        $query = "INSERT INTO playlists (usuario_id, nombre, fecha_creacion, es_automatica) 
                  VALUES (?, ?, NOW(), 1)";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("is", $usuarioId, $nombrePlaylist);
        $stmt->execute();
        return $stmt->insert_id;
    }

    // Agrega canciones a una playlist existente
    private function agregarCancionesAPlaylist($playlistId, $canciones) {
        $query = "INSERT INTO playlist_canciones (playlist_id, cancion_id, orden, fecha_agregado) 
                  VALUES (?, ?, ?, NOW())";
        $stmt = $this->conexion->prepare($query);
        $orden = 1;
        
        foreach ($canciones as $cancion) {
            $stmt->bind_param("iii", $playlistId, $cancion['id'], $orden);
            $stmt->execute();
            $orden++;
        }
    }

    // Genera un nombre automático para la playlist
    public function generarNombrePlaylist($generosFavoritos) {
        $generoPrincipal = $generosFavoritos[0]['genero'] ?? $generosFavoritos[0]['nombre'];
        $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $mesActual = $meses[date('n') - 1];
        return "Mi {$generoPrincipal} - {$mesActual} " . date('Y');
    }
}


$conexion = ConexionDB::obtener();
$usuarioId = $_SESSION['user_id']; // ID del usuario logueado

try {
    $generador = new GeneradorPlaylists($conexion);
    $gestorGeneros = new GestorGenerosMusicales($conexion);
    
    // Obtener géneros favoritos
    $generosFavoritos = $gestorGeneros->obtenerGenerosFavoritosExplicitos($usuarioId);
    if (empty($generosFavoritos)) {
        $generosFavoritos = $gestorGeneros->obtenerGenerosFavoritos($usuarioId);
    }
    
    // Generar nombre de playlist
    $nombrePlaylist = $generador->generarNombrePlaylist($generosFavoritos);
    
    // Generar playlist automática (15 canciones)
    $playlist = $generador->generarPlaylistAutomatica($usuarioId, $nombrePlaylist, 15);
    
    if ($playlist) {
        // Devolver resultado en JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'playlist' => [
                'id' => $playlist['id'],
                'nombre' => $playlist['nombre'],
                'num_canciones' => $playlist['num_canciones'],
                'canciones' => $playlist['canciones']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al generar la playlist']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}