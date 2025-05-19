<?php
require_once 'conexion.php'; // Incluye el archivo con la conexión a la base de datos

class Usuario {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo; // Guarda la conexión para usarla en otros métodos
    }

    // Método para registrar un nuevo usuario
    public function registrar($nombre, $email, $password) {
        // Verifica si el email ya está registrado
        $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return "Email ya registrado."; // Si el email existe, devuelve mensaje de error
        }

        // Hashea (protege) la contraseña usando Argon2ID
        $hash = password_hash($password, PASSWORD_ARGON2ID);

        // Genera un token aleatorio para verificación
        $token = bin2hex(random_bytes(32));

        // Inserta el nuevo usuario en la base de datos
        $stmt = $this->pdo->prepare(
            "INSERT INTO usuarios (nombre, email, password_hash, token_verificacion, acepta_privacidad, fecha_aceptacion_privacidad) 
            VALUES (?, ?, ?, ?, 1, NOW())"
        );
        $stmt->execute([$nombre, $email, $hash, $token]);

        // Devuelve mensaje de éxito con el token generado
        return "Registrado correctamente. Token de verificación: $token";
    }

    // Método para iniciar sesión
    public function login($email, $password) {
        // Busca al usuario por su email
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        // Si se encuentra el usuario y la contraseña es válida
        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            // Verifica si la cuenta está activa
            if (!$usuario['activo']) return "Cuenta desactivada.";
            
            // Actualiza la fecha de último login y reinicia los intentos fallidos
            $this->actualizarLogin($usuario['id']);
            return "Login exitoso.";
        }

        // Si no es válido, incrementa el contador de intentos fallidos
        $this->registrarIntentoFallido($email);
        return "Credenciales inválidas.";
    }

    // Método para verificar la cuenta con un token
    public function verificar($token) {
        // Busca al usuario con ese token
        $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE token_verificacion = ?");
        $stmt->execute([$token]);
        $usuario = $stmt->fetch();

        // Si el token es válido
        if ($usuario) {
            // Activa la cuenta y elimina el token
            $stmt = $this->pdo->prepare("UPDATE usuarios SET activo = 1, token_verificacion = NULL WHERE id = ?");
            $stmt->execute([$usuario['id']]);
            return "Cuenta verificada correctamente.";
        }

        // Si el token no es válido
        return "Token inválido.";
    }

    // Método privado para actualizar el login
    private function actualizarLogin($id) {
        // Actualiza la fecha del último login y reinicia intentos fallidos
        $stmt = $this->pdo->prepare("UPDATE usuarios SET ultimo_login = NOW(), intentos_login = 0 WHERE id = ?");
        $stmt->execute([$id]);
    }

    // Método privado para registrar un intento fallido de login
    private function registrarIntentoFallido($email) {
        // Incrementa el número de intentos fallidos para ese email
        $stmt = $this->pdo->prepare("UPDATE usuarios SET intentos_login = intentos_login + 1 WHERE email = ?");
        $stmt->execute([$email]);
    }
}
?>