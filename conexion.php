<?php
// Parámetros para conectar a la base de datos
$host = 'localhost';
$dbname = 'streaming_recommendation';
$usuario = 'root';      // Cambiar si tenés otro usuario
$contrasena = '';       // Cambiar si tu MySQL tiene contraseña

try {
    // Se crea un objeto PDO para conectarse a la base de datos MySQL
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $usuario, $contrasena);
    
    // Configura PDO para que lance excepciones si ocurre un error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // Si hay un error al conectar, se detiene el script y muestra el mensaje
    die("Error de conexión: " . $e->getMessage());
}
?>

























?>