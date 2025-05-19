<?php
require_once 'usuario.php'; // Carga la clase Usuario que contiene la lógica para verificar cuentas

// Verifica si en la URL se recibió un parámetro llamado 'token'
if (isset($_GET['token'])) {
    // Crea una instancia de la clase Usuario
    // Nota: $pdo viene de 'conexion.php', incluido en 'usuario.php'
    $usuario = new Usuario($pdo);

    // Llama al método verificar() con el token recibido
    $mensaje = $usuario->verificar($_GET['token']);

    // Muestra el resultado de la verificación
    echo $mensaje;
} else {
    // Si no se recibió el token en la URL, muestra un mensaje de error
    echo "Token no recibido.";
}
?>
