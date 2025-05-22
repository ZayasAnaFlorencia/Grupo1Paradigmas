function buscarCancionesPorGenero($db, $genero) {
    $stmt = $db->prepare("CALL BuscarCancionesPorGenero(:genero)");
    $stmt->bindParam(':genero', $genero, PDO::PARAM_STR);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Ejemplo de uso
$db = new PDO('mysql:host=localhost;port=3307;dbname=streaming_recommendation', 'usuario', 'contraseña');
$canciones = buscarCancionesPorGenero($db, 'Rock');

foreach ($canciones as $cancion) {
    echo "{$cancion['titulo']} - {$cancion['artista']} ({$cancion['genero']})\n";
}