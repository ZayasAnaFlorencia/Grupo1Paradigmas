<?php
require_once 'usuario.php'; // Incluye la clase Usuario (que gestiona el registro y conexión a base de datos)

// Verifica si se ha enviado el formulario mediante el método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoge los datos enviados desde el formulario
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Crea una instancia de la clase Usuario
    // Nota: $pdo viene de 'conexion.php', que está incluido dentro de 'usuario.php'
    $usuario = new Usuario($pdo);

    // Llama al método registrar para crear un nuevo usuario
    $mensaje = $usuario->registrar($nombre, $email, $password);

    // Muestra el resultado (mensaje de éxito o error)
    echo $mensaje;
}
?>

<!-- Formulario HTML para registrar un nuevo usuario -->
<form method="POST"> <!-- Envia los datos a esta misma página usando el método POST -->
    <input type="text" name="nombre" required placeholder="Nombre"> <!-- Campo para el nombre -->
    <input type="email" name="email" required placeholder="Email"> <!-- Campo para el correo electrónico -->
    <input type="password" name="password" required placeholder="Contraseña"> <!-- Campo para la contraseña -->
    <button type="submit">Registrarse</button> <!-- Botón para enviar el formulario -->
</form>