<?php
require_once 'usuario.php'; // Carga la clase Usuario (que se conecta a la base de datos y maneja login, registro, etc.)

// Verifica si se ha enviado el formulario por método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoge el email y la contraseña enviados por el formulario
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Crea una instancia de la clase Usuario
    // Nota: $pdo debería estar definido en 'conexion.php' incluido dentro de 'usuario.php'
    $usuario = new Usuario($pdo);

    // Llama al método login de la clase Usuario
    $mensaje = $usuario->login($email, $password);

    // Muestra el mensaje devuelto (éxito o error)
    echo $mensaje;
}
?>

<!-- Formulario HTML para iniciar sesión -->
<form method="POST"> <!-- El formulario envía datos mediante POST a esta misma página -->
    <input type="email" name="email" required placeholder="Email"> <!-- Campo de entrada para el email -->
    <input type="password" name="password" required placeholder="Contraseña"> <!-- Campo de entrada para la contraseña -->
    <button type="submit">Iniciar sesión</button> <!-- Botón para enviar el formulario -->
</form>
