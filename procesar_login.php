<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Verificar si el usuario ya está logueado
if (isset($_SESSION['user_id'])) {
    header('Location: inicio.php');
    exit();
}

// Procesar el formulario de login si se envió
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Validaciones básicas
    if (empty($email)) {
        $errors['email'] = 'Email requerido';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Ingresa un email válido';
    }

    if (empty($password)) {
        $errors['password'] = 'Contraseña requerida';
    }

    if (empty($errors)) {
        // Conexión a la base de datos
        $conn = new mysqli("localhost", "root", "", "soundscouts");
        
        if ($conn->connect_error) {
            die("Error de conexión: " . $conn->connect_error);
        }

        try {
            // Buscar usuario por email
            $stmt = $conn->prepare("SELECT id, nombre, email, password_hash FROM usuarios WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verificar contraseña
                if (password_verify($password, $user['password_hash'])) {
                    // Actualizar último login
                    $update = $conn->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
                    $update->bind_param("i", $user['id']);
                    $update->execute();
                    
                    // Establecer sesión
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['nombre'];
                    
                    // Recordar usuario si marcó la opción
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + 86400 * 30, "/"); // 30 días
                        
                        // Guardar token en la base de datos
                        $updateToken = $conn->prepare("UPDATE usuarios SET token_verificacion = ? WHERE id = ?");
                        $updateToken->bind_param("si", $token, $user['id']);
                        $updateToken->execute();
                    }
                    
                    // Redirigir a la página de inicio
                    header('Location: inicio.php');
                    exit();
                } else {
                    $errors['password'] = 'Contraseña incorrecta';
                }
            } else {
                $errors['email'] = 'Email no registrado';
            }
        } catch (Exception $e) {
            error_log('Error en login: ' . $e->getMessage());
            $errors['database'] = 'Error al iniciar sesión. Por favor intenta nuevamente.';
        } finally {
            $conn->close();
        }
    }
    
    // Si hay errores, volver al formulario
    $_SESSION['login_errors'] = $errors;
    $_SESSION['old_login_data'] = ['email' => $email];
    header('Location: login_soundscouts.php');
    exit();
} else {
    // Si no es POST, redirigir al login
    header('Location: login_soundscouts.php');
    exit();
}