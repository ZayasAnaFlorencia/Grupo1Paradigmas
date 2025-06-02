<?php
// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "soundscouts";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("ERROR DE CONEXIÓN: " . $conn->connect_error);
}

echo "<pre>";
print_r($_POST);
echo "</pre>";

// Verificar si la tabla existe
$table_check = $conn->query("SHOW TABLES LIKE 'usuarios'");
if ($table_check->num_rows == 0) {
    die("ERROR: La tabla 'usuarios' no existe en la base de datos");
}

// Verificar estructura de la tabla
$structure = $conn->query("DESCRIBE usuarios");
echo "<h3>Estructura de la tabla usuarios:</h3>";
echo "<pre>";
while ($row = $structure->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

$conn->close();
?>