<?php
/**
 * CLASE GeneroNode
 * Representa un nodo del árbol binario de géneros musicales
 */
class GeneroNode {
    // Propiedades públicas del nodo
    public $nombre;      // Nombre del género musical (ej: "Rock", "Pop")
    public $nivel;       // Nivel en la jerarquía (0 para raíz, 1 para subgéneros)
    public $left;        // Referencia al nodo hijo izquierdo (subárbol izquierdo)
    public $right;       // Referencia al nodo hijo derecho (subárbol derecho)
    public $canciones;   // Array que almacenará las canciones de este género
    
    /**
     * Constructor de la clase GeneroNode
     * @param string $nombre - Nombre del género musical
     * @param int $nivel - Nivel en la jerarquía
     */
    public function __construct($nombre, $nivel) {
        // Inicializa las propiedades del nodo
        $this->nombre = $nombre;
        $this->nivel = $nivel;
        $this->left = null;      // Al crear el nodo, no tiene hijos
        $this->right = null;     // Al crear el nodo, no tiene hijos
        $this->canciones = array(); // Inicializa array vacío para canciones
    }
    
    /**
     * Agrega una canción al array de canciones del género
     * @param array $cancion - Array con datos de la canción
     */
    public function agregarCancion($cancion) {
        $this->canciones[] = $cancion; // Añade la canción al final del array
    }
}

/**
 * CLASE GenerosTree
 * Representa el árbol binario completo de géneros musicales
 */
class GenerosTree {
    // Propiedades privadas de la clase
    private $root;       // Nodo raíz del árbol (punto de entrada)
    private $db;        // Conexión a la base de datos (objeto MySQLi)
    
    /**
     * Constructor de la clase GenerosTree
     * @param mysqli $dbConnection - Objeto de conexión a la base de datos
     */
    public function __construct($dbConnection) {
        $this->db = $dbConnection; // Almacena la conexión a la DB
        $this->root = null;        // Inicializa el árbol vacío
    }
    
    /**
     * Construye el árbol completo a partir de los datos en la base de datos
     * @return bool - True si se construyó correctamente, False si hubo error
     */
    public function construirArbol() {
        try {
            // Consulta SQL para obtener todos los géneros ordenados por nivel y nombre
            $query = "SELECT nombre, padre, nivel FROM generos_musicales ORDER BY nivel, nombre";
            $result = $this->db->query($query); // Ejecuta la consulta
            
            // Verifica si la consulta tuvo éxito
            if (!$result) {
                throw new Exception("Error al obtener géneros: " . $this->db->error);
            }
            
            // Almacena temporalmente los géneros en un array
            $generos = array();
            while ($row = $result->fetch_assoc()) {
                $generos[] = $row; // Añade cada fila de resultados al array
            }
            
            // Primero inserta los géneros raíz (nivel 0)
            foreach ($generos as $genero) {
                if ($genero['nivel'] == 0) {
                    $this->root = $this->insertar($this->root, $genero['nombre'], $genero['nivel']);
                }
            }
            
            // Luego inserta los subgéneros (nivel > 0)
            foreach ($generos as $genero) {
                if ($genero['nivel'] > 0) {
                    $this->insertarSubgenero($genero['nombre'], $genero['padre'], $genero['nivel']);
                }
            }
            
            // Finalmente, carga las canciones para cada género
            $this->cargarCanciones();
            return true; // Indica éxito en la construcción
            
        } catch (Exception $e) {
            // Registra el error en el log
            error_log("Error al construir árbol: " . $e->getMessage());
            return false; // Indica fallo en la construcción
        }
    }
    
    /**
     * Inserta un nuevo nodo en el árbol (método auxiliar recursivo)
     * @param GeneroNode|null $node - Nodo actual (null si es el primero)
     * @param string $nombre - Nombre del género a insertar
     * @param int $nivel - Nivel del género en la jerarquía
     * @return GeneroNode - El nodo insertado/modificado
     */
    private function insertar($node, $nombre, $nivel) {
        // Si llegamos a un nodo nulo, creamos uno nuevo
        if ($node === null) {
            return new GeneroNode($nombre, $nivel);
        }
        
        // Compara alfabéticamente para decidir posición (izquierda o derecha)
        if (strcmp($nombre, $node->nombre) < 0) {
            // Si el nombre es menor, inserta en el subárbol izquierdo
            $node->left = $this->insertar($node->left, $nombre, $nivel);
        } else {
            // Si el nombre es mayor o igual, inserta en el subárbol derecho
            $node->right = $this->insertar($node->right, $nombre, $nivel);
        }
        
        return $node; // Devuelve el nodo modificado
    }
    
    /**
     * Inserta un subgénero como hijo de su género padre
     * @param string $nombre - Nombre del subgénero
     * @param string $padre - Nombre del género padre
     * @param int $nivel - Nivel del subgénero
     */
    private function insertarSubgenero($nombre, $padre, $nivel) {
        // Busca el nodo padre en el árbol
        $padreNode = $this->buscar($this->root, $padre);
        
        // Si encontró el padre, inserta el subgénero como hijo izquierdo
        if ($padreNode !== null) {
            $padreNode->left = $this->insertar($padreNode->left, $nombre, $nivel);
        }
    }
    
    /**
     * Busca un género por su nombre (interfaz pública)
     * @param string $nombre - Nombre del género a buscar
     * @return GeneroNode|null - El nodo encontrado o null si no existe
     */
    public function buscarGenero($nombre) {
        return $this->buscar($this->root, $nombre);
    }
    
    /**
     * Busca recursivamente un nodo por su nombre (método privado)
     * @param GeneroNode|null $node - Nodo actual en la búsqueda
     * @param string $nombre - Nombre del género a buscar
     * @return GeneroNode|null - Nodo encontrado o null
     */
    private function buscar($node, $nombre) {
        // Caso base: nodo nulo o nombre coincide (búsqueda insensible a mayúsculas)
        if ($node === null || strcasecmp($node->nombre, $nombre) === 0) {
            return $node;
        }
        
        // Si el nombre buscado es menor, busca en el subárbol izquierdo
        if (strcasecmp($nombre, $node->nombre) < 0) {
            return $this->buscar($node->left, $nombre);
        }
        
        // Si el nombre buscado es mayor, busca en el subárbol derecho
        return $this->buscar($node->right, $nombre);
    }
    
    /**
     * Carga las canciones desde la base de datos y las asigna a sus géneros
     */
    private function cargarCanciones() {
        try {
            // Consulta para obtener todas las canciones
            $query = "SELECT id, titulo, artista_id, genero, duracion, fecha_lanzamiento 
                      FROM canciones";
            $result = $this->db->query($query);
            
            // Verifica si la consulta tuvo éxito
            if (!$result) {
                throw new Exception("Error al obtener canciones: " . $this->db->error);
            }
            
            // Procesa cada canción del resultado
            while ($row = $result->fetch_assoc()) {
                // Busca el nodo del género de esta canción
                $generoNode = $this->buscar($this->root, $row['genero']);
                // Si encontró el género, agrega la canción
                if ($generoNode !== null) {
                    $generoNode->agregarCancion($row);
                }
            }
            
        } catch (Exception $e) {
            // Registra el error en el log
            error_log("Error al cargar canciones: " . $e->getMessage());
        }
    }
    
    /**
     * Devuelve una lista ordenada de todos los géneros
     * @return array - Lista de géneros con sus propiedades
     */
    public function listarGeneros() {
        $generos = array(); // Array para almacenar el resultado
        $this->inOrder($this->root, $generos); // Recorre el árbol in-order
        return $generos;
    }
    
    /**
     * Recorrido in-order del árbol (izquierda, raíz, derecha)
     * @param GeneroNode|null $node - Nodo actual
     * @param array &$generos - Referencia al array de resultados
     */
    private function inOrder($node, &$generos) {
        if ($node !== null) {
            // Recorre el subárbol izquierdo
            $this->inOrder($node->left, $generos);
            
            // Procesa el nodo actual (añade al array de resultados)
            $generos[] = array(
                'nombre' => $node->nombre,
                'nivel' => $node->nivel,
                'num_canciones' => count($node->canciones)
            );
            
            // Recorre el subárbol derecho
            $this->inOrder($node->right, $generos);
        }
    }
    
    /**
     * Obtiene todas las canciones de un género específico
     * @param string $genero - Nombre del género
     * @return array - Lista de canciones del género
     */
    public function obtenerCancionesPorGenero($genero) {
        // Busca el nodo del género
        $node = $this->buscar($this->root, $genero);
        // Si existe, devuelve sus canciones; si no, array vacío
        if ($node !== null) {
            return $node->canciones;
        }
        return array();
    }
    
    /**
     * Obtiene la estructura jerárquica completa del árbol
     * @return array - Estructura jerárquica de géneros
     */
    public function obtenerJerarquiaCompleta() {
        $jerarquia = array(); // Array para el resultado
        $this->construirJerarquia($this->root, $jerarquia); // Construye la jerarquía
        return $jerarquia;
    }
    
    /**
     * Construye recursivamente la estructura jerárquica
     * @param GeneroNode|null $node - Nodo actual
     * @param array &$jerarquia - Referencia al array de resultados
     */
    private function construirJerarquia($node, &$jerarquia) {
        if ($node !== null) {
            // Crea la entrada para el nodo actual
            $current = array(
                'genero' => $node->nombre,
                'nivel' => $node->nivel,
                'subgeneros' => array() // Para los subgéneros
            );
            
            // Si el hijo izquierdo es un subgénero (nivel mayor), lo procesa
            if ($node->left !== null && $node->left->nivel > $node->nivel) {
                $this->construirJerarquia($node->left, $current['subgeneros']);
            }
            
            // Añade el nodo actual a la jerarquía
            $jerarquia[] = $current;
            
            // Procesa el subárbol derecho (géneros del mismo nivel)
            $this->construirJerarquia($node->right, $jerarquia);
        }
    }
    
    /**
     * Muestra el árbol en formato de texto (para depuración)
     */
    public function visualizarArbol() {
        echo "<pre>"; // Etiqueta pre para formato monoespaciado
        $this->printTree($this->root); // Imprime el árbol
        echo "</pre>";
    }
    
    /**
     * Imprime recursivamente la estructura del árbol
     * @param GeneroNode|null $node - Nodo actual
     * @param string $prefix - Prefijo para indentación
     * @param bool $isLeft - Indica si es hijo izquierdo
     */
    private function printTree($node, $prefix = "", $isLeft = true) {
        if ($node !== null) {
            // Imprime el nodo actual con su nivel
            echo $prefix . ($isLeft ? "├── " : "└── ") . $node->nombre . " (nivel: " . $node->nivel . ")\n";
            // Imprime el subárbol izquierdo con mayor indentación
            $this->printTree($node->left, $prefix . ($isLeft ? "│   " : "    "), true);
            // Imprime el subárbol derecho con mayor indentación
            $this->printTree($node->right, $prefix . ($isLeft ? "│   " : "    "), false);
        }
    }
}

// CONFIGURACIÓN Y EJEMPLO DE USO

// Configuración de conexión para XAMPP (valores por defecto)
$config = [
    'servername' => 'localhost',  // Servidor de la base de datos
    'username' => 'root',         // Usuario de MySQL (por defecto en XAMPP)
    'password' => '',             // Contraseña (vacía por defecto en XAMPP)
    'dbname' => 'streaming_recommendation' // Nombre de la base de datos
];

try {
    // 1. Establecer conexión a la base de datos
    $conn = new mysqli(
        $config['servername'], 
        $config['username'], 
        $config['password'], 
        $config['dbname']
    );
    
    // Verificar conexión
    if ($conn->connect_error) {
        throw new Exception("Conexión fallida: " . $conn->connect_error);
    }
    
    // Encabezado de la página
    echo "<h1>Sistema de Recomendación Musical</h1>";
    
    // 2. Crear el árbol de géneros musicales
    $arbolGeneros = new GenerosTree($conn);
    if ($arbolGeneros->construirArbol()) {
        // Mensaje de éxito
        echo "<p style='color:green;'>Árbol de géneros construido correctamente</p>";
        
        // 3. Ejemplos de operaciones con el árbol:
        
        // Lista todos los géneros ordenados
        echo "<h2>Lista de Géneros Musicales</h2>";
        $generos = $arbolGeneros->listarGeneros();
        foreach ($generos as $genero) {
            echo "{$genero['nombre']} (Nivel: {$genero['nivel']}, Canciones: {$genero['num_canciones']})<br>";
        }
        
        // Muestra canciones de un género específico
        echo "<h2>Canciones de Rock Clásico</h2>";
        $cancionesRock = $arbolGeneros->obtenerCancionesPorGenero("Rock Clásico");
        foreach ($cancionesRock as $cancion) {
            echo "{$cancion['titulo']} (Duración: {$cancion['duracion']}s)<br>";
        }
        
        // Muestra la estructura jerárquica del árbol
        echo "<h2>Jerarquía de Géneros Musicales</h2>";
        $arbolGeneros->visualizarArbol();
        
    } else {
        // Mensaje de error en la construcción
        echo "<p style='color:red;'>Error al construir el árbol de géneros</p>";
    }
    
    // Cerrar conexión
    $conn->close();
    
} catch (Exception $e) {
    // Manejo de errores generales
    echo "<h1>Error</h1>";
    echo "<p style='color:red;'>" . $e->getMessage() . "</p>";
    echo "<p>Verifica que:</p>";
    echo "<ul>";
    echo "<li>MySQL esté corriendo en XAMPP</li>";
    echo "<li>La base de datos 'streaming_recommendation' exista</li>";
    echo "<li>Las tablas estén creadas correctamente</li>";
    echo "<li>Las credenciales de conexión sean correctas</li>";
    echo "</ul>";
}
?>