<?php
require_once 'codigos_soundscouts.php';
header('Content-Type: application/json');

$album_id = $_GET['album_id'] ?? '';

try {
    $db = new mysqli('localhost', 'root', '', 'soundscouts');
    
    $query = "SELECT c.id, c.titulo, a.nombre as artista, c.duracion, c.portada, c.audio 
              FROM canciones c 
              JOIN artistas a ON c.artista_id = a.id 
              WHERE c.album_id = ? 
              ORDER BY c.track_number";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $album_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $songs = [];
    while ($row = $result->fetch_assoc()) {
        $songs[] = $row;
    }
    
    echo json_encode($songs);
} catch (Exception $e) {
    echo json_encode([]);
}