<?php
session_start();

// Configuración de la base de datos (ajusta según tus credenciales)
define('DB_HOST', 'localhost');
define('DB_NAME', 'streaming_recommendation');
define('DB_USER', 'root');
define('DB_PASS', '');

// Clase para manejar la conexión a la base de datos
class Database {
    private $connection;

    public function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=".DB_HOST.";dbname=".DB_NAME,
                DB_USER,
                DB_PASS
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->connection;
    }
}

// Clase principal para manejar la cola de reproducción
class ColaRecomendaciones {
    private $db;
    private $usuario_id;

    public function __construct($usuario_id) {
        $this->db = (new Database())->getConnection();
        $this->usuario_id = $usuario_id;
        
        // Cargar recomendaciones al crear la instancia (solo una vez por sesión)
        if (!isset($_SESSION['recomendaciones_cargadas'])) {
            $this->cargarRecomendacionesIniciales();
            $_SESSION['recomendaciones_cargadas'] = true;
        }
    }

    // Cargar recomendaciones basadas en diferentes criterios
    private function cargarRecomendacionesIniciales() {
        // 1. Recomendaciones basadas en amigos (Subgrupo 1 - Grafos)
        $this->cargarRecomendacionesDeAmigos();
        
        // 2. Recomendaciones basadas en géneros musicales (Subgrupo 2 - Árboles)
        $this->cargarRecomendacionesPorGenero();
        
        // 3. Recomendaciones basadas en tendencias globales
        $this->cargarTendenciasGlobales();
    }

    private function cargarRecomendacionesDeAmigos() {
        $query = "SELECT DISTINCT h.cancion_id 
                  FROM amistades a
                  JOIN historial_reproduccion h ON a.usuario_id2 = h.usuario_id
                  WHERE a.usuario_id1 = :usuario_id
                  ORDER BY h.fecha_reproduccion DESC
                  LIMIT 3";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':usuario_id', $this->usuario_id);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->agregarCancion($row['cancion_id']);
        }
    }

    private function cargarRecomendacionesPorGenero() {
        // Obtener géneros favoritos del usuario (basado en su historial)
        $query = "SELECT c.genero, COUNT(*) as reproducciones 
                  FROM historial_reproduccion h
                  JOIN canciones c ON h.cancion_id = c.id
                  WHERE h.usuario_id = :usuario_id
                  GROUP BY c.genero
                  ORDER BY reproducciones DESC
                  LIMIT 2";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':usuario_id', $this->usuario_id);
        $stmt->execute();
        
        $generos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($generos as $genero) {
            $this->cargarCancionesPorGenero($genero['genero'], 2);
        }
    }

    private function cargarCancionesPorGenero($genero, $limite) {
        $query = "SELECT c.id 
                  FROM canciones c
                  WHERE c.genero = :genero
                  AND c.id NOT IN (
                      SELECT cancion_id FROM historial_reproduccion 
                      WHERE usuario_id = :usuario_id
                  )
                  ORDER BY c.reproducciones DESC
                  LIMIT :limite";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':genero', $genero);
        $stmt->bindParam(':usuario_id', $this->usuario_id);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->agregarCancion($row['id']);
        }
    }

    private function cargarTendenciasGlobales() {
        $query = "SELECT id 
                  FROM canciones 
                  ORDER BY reproducciones DESC 
                  LIMIT 2";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->agregarCancion($row['id']);
        }
    }

    // Agregar una canción a la cola de reproducción
    public function agregarCancion($cancion_id) {
        // Verificar si la canción ya está en la cola
        $query = "SELECT COUNT(*) FROM cola_reproduccion 
                  WHERE usuario_id = :usuario_id AND cancion_id = :cancion_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':usuario_id', $this->usuario_id);
        $stmt->bindParam(':cancion_id', $cancion_id);
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $insert = "INSERT INTO cola_reproduccion (usuario_id, cancion_id) 
                       VALUES (:usuario_id, :cancion_id)";
            
            $stmt = $this->db->prepare($insert);
            $stmt->bindParam(':usuario_id', $this->usuario_id);
            $stmt->bindParam(':cancion_id', $cancion_id);
            $stmt->execute();
        }
    }

    // Obtener la próxima canción (FIFO)
    public function obtenerProximaCancion() {
        $query = "SELECT cr.id, cr.cancion_id, c.titulo, a.nombre as artista 
                  FROM cola_reproduccion cr
                  JOIN canciones c ON cr.cancion_id = c.id
                  JOIN artistas a ON c.artista_id = a.id
                  WHERE cr.usuario_id = :usuario_id
                  ORDER BY cr.fecha_agregado ASC
                  LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':usuario_id', $this->usuario_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Eliminar una canción de la cola (después de reproducirla)
    public function eliminarCancion($cola_id) {
        $query = "DELETE FROM cola_reproduccion 
                  WHERE id = :id AND usuario_id = :usuario_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $cola_id);
        $stmt->bindParam(':usuario_id', $this->usuario_id);
        return $stmt->execute();
    }

    // Obtener todas las canciones en la cola
    public function obtenerCola() {
        $query = "SELECT cr.id, c.titulo, a.nombre as artista 
                  FROM cola_reproduccion cr
                  JOIN canciones c ON cr.cancion_id = c.id
                  JOIN artistas a ON c.artista_id = a.id
                  WHERE cr.usuario_id = :usuario_id
                  ORDER BY cr.fecha_agregado ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':usuario_id', $this->usuario_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Vaciar la cola de reproducción
    public function vaciarCola() {
        $query = "DELETE FROM cola_reproduccion WHERE usuario_id = :usuario_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':usuario_id', $this->usuario_id);
        return $stmt->execute();
    }
}

// --- Lógica de la aplicación ---

// Simulación de usuario logueado (en un sistema real esto vendría de la autenticación)
$usuario_id = 1;  // Cambiar según el usuario que esté logueado

// Inicializar la cola de recomendaciones
$cola = new ColaRecomendaciones($usuario_id);

// Agregar canciones específicas manualmente (IDs de las canciones que insertamos en la BD) <--- esto es para hacer las pruebas nomas
/* $cola->agregarCancion(1);  
$cola->agregarCancion(2);  
$cola->agregarCancion(3);   */

// Procesar acciones del usuario
if (isset($_POST['reproducir'])) {
    $proximaCancion = $cola->obtenerProximaCancion();
    if ($proximaCancion) {
        $db = (new Database())->getConnection();
        
        try {
            // Iniciar transacción
            $db->beginTransaction();
            
            // 1. Insertar en el historial
            $query = "INSERT INTO historial_reproduccion (usuario_id, cancion_id) 
                      VALUES (:usuario_id, :cancion_id)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':usuario_id', $usuario_id);
            $stmt->bindParam(':cancion_id', $proximaCancion['cancion_id']);
            $stmt->execute();
            
            // 2. Limpiar historial antiguo (mantener solo las 50 más recientes)
            $query = "DELETE FROM historial_reproduccion
                      WHERE usuario_id = :usuario_id
                      AND id NOT IN (
                          SELECT id FROM (
                              SELECT id
                              FROM historial_reproduccion
                              WHERE usuario_id = :usuario_id
                              ORDER BY fecha_reproduccion DESC
                              LIMIT 50
                          ) AS temp
                      )";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':usuario_id', $usuario_id);
            $stmt->execute();
            
            // 3. Eliminar de la cola
            $cola->eliminarCancion($proximaCancion['id']);
            
            // Confirmar transacción
            $db->commit();
            
            $_SESSION['mensaje'] = "Reproduciendo: " . $proximaCancion['titulo'];
        } catch (Exception $e) {
            // Revertir en caso de error
            $db->rollBack();
            $_SESSION['error'] = "Error al reproducir: " . $e->getMessage();
        }
    } else {
        $_SESSION['mensaje'] = "No hay canciones en la cola";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Vaciar la cola
if (isset($_POST['vaciar'])) {
    if ($cola->vaciarCola()) {
        $_SESSION['mensaje'] = "Cola vaciada correctamente";
    } else {
        $_SESSION['error'] = "Error al vaciar la cola";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Obtener la cola actual
$cancionesEnCola = $cola->obtenerCola();
$proximaCancion = $cola->obtenerProximaCancion();

// Mostrar mensajes si existen
$mensaje = $_SESSION['mensaje'] ?? null;
unset($_SESSION['mensaje']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cola de Recomendaciones</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .cancion-actual {
            background-color: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .lista-canciones {
            list-style-type: none;
            padding: 0;
        }
        .lista-canciones li {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .acciones {
            margin: 20px 0;
        }
        button {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        button:hover {
            background-color: #45a049;
        }
        .mensaje {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
        }
    </style>
</head>
<body>
    <h1>Cola de Recomendaciones</h1>
    
    <?php if ($mensaje): ?>
        <div class="mensaje success"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    
    <?php if ($proximaCancion): ?>
        <div class="cancion-actual">
            <h2>Próxima canción:</h2>
            <p><strong><?= htmlspecialchars($proximaCancion['titulo']) ?></strong> - <?= htmlspecialchars($proximaCancion['artista']) ?></p>
        </div>
    <?php endif; ?>
    
    <div class="acciones">
        <form method="post">
            <button type="submit" name="reproducir">Reproducir Siguiente</button>
            <button type="submit" name="vaciar" onclick="return confirm('¿Estás seguro de vaciar la cola?')">Vaciar Cola</button>
        </form>
    </div>
    
    <h2>Canciones en cola:</h2>
    <?php if (!empty($cancionesEnCola)): ?>
        <ul class="lista-canciones">
            <?php foreach ($cancionesEnCola as $cancion): ?>
                <li>
                    <strong><?= htmlspecialchars($cancion['titulo']) ?></strong> - 
                    <?= htmlspecialchars($cancion['artista']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No hay canciones en la cola de reproducción.</p>
    <?php endif; ?>
</body>
</html>