<?php
// conecta con la base
$conn = new mysqli("localhost", "root", "", "formulario_db");

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// agarra la info 
$nombre = trim($_POST['nombre']);
$email = trim($_POST['email']);
$password = $_POST['password'];

// valida la info
if (empty($nombre) || empty($email) || empty($password)) {
    die("Todos los campos son obligatorios.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Email no válido.");
}

if (strlen($password) < 6) {
    die("La contraseña debe tener al menos 6 caracteres.");
}

// Verificar si el email ya existe
$sql = "SELECT id FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    die("Este email ya está registrado.");
}

// Encripta la contraseña
$hash = password_hash($password, PASSWORD_DEFAULT);

// mete en la base de datos
$sql = "INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $nombre, $email, $hash);

if ($stmt->execute()) {
    echo "Usuario registrado con éxito.";
} else {
    echo "Error al registrar: " . $stmt->error;
}

$conn->close();
?>
