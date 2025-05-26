<?php
class GeneroNode {
    public $nombre;
    public $nivel;
    public $left;
    public $right;
    public $canciones;

    public function __construct($nombre, $nivel) {
        $this->nombre = $nombre;
        $this->nivel = $nivel;
        $this->left = null;
        $this->right = null;
        $this->canciones = [];
    }

    public function agregarCancion($cancion) {
        $this->canciones[] = $cancion;
    }
}

class Database {
    private $host = "localhost";
    private $db_name = "soundscouts";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
        }
        return $this->conn;
    }
}


class GenerosTree {
    private $pdo;
    private $root;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->root = null;
    }

    public function construirArbol() {
        try {
            $stmt = $this->pdo->query("SELECT nombre, padre, nivel FROM generos_musicales ORDER BY nivel, nombre");
            $generos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($generos as $genero) {
                if ($genero['nivel'] == 0) {
                    $this->root = $this->insertar($this->root, $genero['nombre'], $genero['nivel']);
                }
            }

            foreach ($generos as $genero) {
                if ($genero['nivel'] > 0) {
                    $this->insertarSubgenero($genero['nombre'], $genero['padre'], $genero['nivel']);
                }
            }

            $this->cargarCanciones();
            return true;
        } catch (PDOException $e) {
            error_log("Error al construir árbol: " . $e->getMessage());
            return false;
        }
    }

    private function insertar($node, $nombre, $nivel) {
        if ($node === null) return new GeneroNode($nombre, $nivel);
        
        if (strcmp($nombre, $node->nombre) < 0) {
            $node->left = $this->insertar($node->left, $nombre, $nivel);
        } else {
            $node->right = $this->insertar($node->right, $nombre, $nivel);
        }
        
        return $node;
    }

    private function insertarSubgenero($nombre, $padre, $nivel) {
        $parentNode = $this->buscar($this->root, $padre);
        if ($parentNode !== null) {
            if ($parentNode->left === null) {
                $parentNode->left = new GeneroNode($nombre, $nivel);
            } else {
                $parentNode->right = new GeneroNode($nombre, $nivel);
            }
        }
    }

    public function obtenerCancionesPorGenero($genero) {
        $node = $this->buscar($this->root, $genero);
        return $node !== null ? $node->canciones : [];
    }

    private function buscar($node, $nombre) {
        if ($node === null || strcasecmp($node->nombre, $nombre) === 0) return $node;
        return strcasecmp($nombre, $node->nombre) < 0 
            ? $this->buscar($node->left, $nombre) 
            : $this->buscar($node->right, $nombre);
    }

    private function cargarCanciones() {
        try {
            $stmt = $this->pdo->query("SELECT id, titulo, artista_id, genero, duracion FROM canciones");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $generoNode = $this->buscar($this->root, $row['genero']);
                if ($generoNode !== null) $generoNode->agregarCancion($row);
            }
        } catch (PDOException $e) {
            error_log("Error al cargar canciones: " . $e->getMessage());
        }
    }
}

class GestorGenerosMusicales {
    private $pdo;
    private $arbolGeneros;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->arbolGeneros = new GenerosTree($pdo);
        $this->arbolGeneros->construirArbol();
    }

    public function recomendarCanciones($usuario_id, $limit = 10) {
        // Primero obtener los géneros favoritos del usuario
        $query = "SELECT genero_id FROM favoritos_generos WHERE usuario_id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$usuario_id]);
        $generos_favoritos = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        if (empty($generos_favoritos)) {
            // Si no tiene géneros favoritos, devolver canciones populares
            $query = "SELECT c.* FROM canciones c 
                     ORDER BY c.popularidad DESC 
                     LIMIT ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Si tiene géneros favoritos, buscar canciones de esos géneros
        $placeholders = implode(',', array_fill(0, count($generos_favoritos), '?'));
        $query = "SELECT c.* FROM canciones c 
                 JOIN generos_canciones gc ON c.id = gc.cancion_id 
                 WHERE gc.genero_id IN ($placeholders)
                 ORDER BY c.popularidad DESC 
                 LIMIT ?";
        
        // Combinar parámetros (géneros + limit)
        $params = array_merge($generos_favoritos, [$limit]);
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }
}

class ColaRecomendaciones {
    private $pdo;
    private $usuario_id;

    public function __construct($pdo, $usuario_id) {
        $this->pdo = $pdo;
        $this->usuario_id = $usuario_id;
        
        // Verificar si la sesión está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar si las recomendaciones ya fueron cargadas
        if (!isset($_SESSION['recomendaciones_cargadas']) || !$_SESSION['recomendaciones_cargadas']) {
            $this->cargarRecomendacionesIniciales();
            $_SESSION['recomendaciones_cargadas'] = true;
        }
    }

    private function cargarRecomendacionesIniciales() {
        $gestorGeneros = new GestorGenerosMusicales($this->pdo);
        $this->cargarRecomendacionesDeAmigos();
        $this->cargarRecomendacionesPorGenero($gestorGeneros);
        $this->cargarTendenciasGlobales();
    }

    private function cargarRecomendacionesDeAmigos() {
        $query = "SELECT DISTINCT h.cancion_id 
                FROM amistades a
                JOIN historial_reproduccion h ON a.usuario_id2 = h.usuario_id
                WHERE a.usuario_id1 = :usuario_id
                LIMIT 3";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':usuario_id', $this->usuario_id);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->agregarCancion($row['cancion_id']);
        }
    }

    private function cargarRecomendacionesPorGenero($gestorGeneros) {
        $query = "SELECT c.genero, COUNT(*) as reproducciones 
                FROM historial_reproduccion h
                JOIN canciones c ON h.cancion_id = c.id
                WHERE h.usuario_id = :usuario_id
                GROUP BY c.genero
                ORDER BY reproducciones DESC
                LIMIT 2";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':usuario_id', $this->usuario_id);
        $stmt->execute();
        
        $generos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Si no hay historial, usar géneros favoritos
        if (empty($generos)) {
            $query = "SELECT genero_id FROM favoritos_generos WHERE usuario_id = :usuario_id LIMIT 2";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':usuario_id', $this->usuario_id);
            $stmt->execute();
            $generos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        foreach ($generos as $genero) {
            $canciones = $gestorGeneros->recomendarCanciones($this->usuario_id, 2);
            foreach ($canciones as $cancion) {
                $this->agregarCancion($cancion['id']);
            }
        }
    }

    private function cargarTendenciasGlobales() {
        $query = "SELECT id FROM canciones ORDER BY reproducciones DESC LIMIT 2";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->agregarCancion($row['id']);
        }
    }

    public function agregarCancion($cancion_id) {
        $query = "INSERT INTO cola_reproduccion (usuario_id, cancion_id) 
                VALUES (:usuario_id, :cancion_id)";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':usuario_id', $this->usuario_id);
        $stmt->bindParam(':cancion_id', $cancion_id);
        $stmt->execute();
    }

    public function obtenerProximaCancion() {
        $query = "SELECT cr.id, cr.cancion_id, c.titulo, a.nombre as artista 
                FROM cola_reproduccion cr
                JOIN canciones c ON cr.cancion_id = c.id
                JOIN artistas a ON c.artista_id = a.id
                WHERE cr.usuario_id = :usuario_id
                ORDER BY cr.fecha_agregado ASC
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':usuario_id', $this->usuario_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerCola() {
        $query = "SELECT cr.id, c.titulo, a.nombre as artista 
                FROM cola_reproduccion cr
                JOIN canciones c ON cr.cancion_id = c.id
                JOIN artistas a ON c.artista_id = a.id
                WHERE cr.usuario_id = :usuario_id
                ORDER BY cr.fecha_agregado ASC";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':usuario_id', $this->usuario_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarCancion($cola_id) {
        $query = "DELETE FROM cola_reproduccion WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':id', $cola_id);
        return $stmt->execute();
    }

    public function vaciarCola() {
        $query = "DELETE FROM cola_reproduccion WHERE usuario_id = :usuario_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':usuario_id', $this->usuario_id);
        return $stmt->execute();
    }
}

// Controlador principal para el player
if (basename($_SERVER['PHP_SELF']) === 'player.php') {
    // Iniciar sesión si no está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $db = (new Database())->getConnection();
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: login_soundscouts.php");
        exit();
    }

    $usuario_id = $_SESSION['user_id'];
    $cola = new ColaRecomendaciones($db, $usuario_id);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            if (isset($_POST['reproducir'])) {
                $proxima = $cola->obtenerProximaCancion();
                if ($proxima) {
                    $cola->eliminarCancion($proxima['id']);
                    $_SESSION['mensaje'] = "Reproduciendo: " . htmlspecialchars($proxima['titulo']);
                } else {
                    $_SESSION['mensaje'] = "No hay canciones en la cola";
                }
            } elseif (isset($_POST['vaciar'])) {
                if ($cola->vaciarCola()) {
                    $_SESSION['mensaje'] = "Cola vaciada";
                } else {
                    $_SESSION['mensaje'] = "Error al vaciar la cola";
                }
            }
            header("Location: player.php");
            exit();
        } catch (Exception $e) {
            error_log("Error en player.php: " . $e->getMessage());
            $_SESSION['error'] = "Ocurrió un error al procesar la solicitud";
            header("Location: player.php");
            exit();
        }
    }

    $cancionesEnCola = $cola->obtenerCola();
    $proximaCancion = $cola->obtenerProximaCancion();
    $mensaje = $_SESSION['mensaje'] ?? null;
    $error = $_SESSION['error'] ?? null;
    
    // Limpiar mensajes después de mostrarlos
    unset($_SESSION['mensaje'], $_SESSION['error']);
}
?>