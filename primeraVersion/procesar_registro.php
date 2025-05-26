<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $old_data = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'email' => trim($_POST['email'] ?? '')
    ];

    // Validaciones
    if (empty($old_data['nombre'])) {
        $errors['nombre'] = 'Nombre requerido';
    } elseif (strlen($old_data['nombre']) < 3) {
        $errors['nombre'] = 'El nombre debe tener al menos 3 caracteres';
    }

    if (empty($old_data['email'])) {
        $errors['email'] = 'Email requerido';
    } elseif (!filter_var($old_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Ingresa un email válido';
    }

    if (empty($_POST['password'])) {
        $errors['password'] = 'Contraseña requerida';
    } elseif (strlen($_POST['password']) < 8) {
        $errors['password'] = 'La contraseña debe tener al menos 8 caracteres';
    } elseif (!preg_match('/[A-Z]/', $_POST['password']) || !preg_match('/[a-z]/', $_POST['password']) || !preg_match('/[0-9]/', $_POST['password'])) {
        $errors['password'] = 'Debe contener mayúsculas, minúsculas y números';
    }

    if ($_POST['password'] !== ($_POST['confirmar'] ?? '')) {
        $errors['confirmar'] = 'Las contraseñas no coinciden';
    }

    if (!isset($_POST['terms'])) {
        $errors['terms'] = 'Debes aceptar los términos y condiciones';
    }

    if (empty($errors)) {
        // Conexión a la base de datos con MySQLi
        $conn = new mysqli("localhost", "root", "", "soundscouts");
        
        if ($conn->connect_error) {
            die("Error de conexión: " . $conn->connect_error);
        }

        try {
            // Verificar si el email ya existe
            $checkEmail = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
            $checkEmail->bind_param("s", $old_data['email']);
            $checkEmail->execute();
            $checkEmail->store_result();
            
            if ($checkEmail->num_rows > 0) {
                $errors['email'] = 'Este email ya está registrado';
                $_SESSION['errors'] = $errors;
                $_SESSION['old_data'] = $old_data;
                header('Location: registro.php');
                exit();
            }

            // Insertar nuevo usuario
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password_hash, fecha_registro, acepta_privacidad) VALUES (?, ?, ?, NOW(), 1)");
            $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $stmt->bind_param("sss", $old_data['nombre'], $old_data['email'], $password_hash);
            $stmt->execute();

            // Redirigir a página de éxito
            header('Location: registro_exitoso.php?id='.$conn->insert_id);
            exit();
        } catch (Exception $e) {
            // Registrar el error y redirigir
            error_log('Error en registro: ' . $e->getMessage());
            $errors['database'] = 'Error al registrar. Por favor intenta nuevamente.';
            $_SESSION['errors'] = $errors;
            $_SESSION['old_data'] = $old_data;
            header('Location: registro.php');
            exit();
        } finally {
            $conn->close();
        }
    } else {
        // Guardar errores y redirigir
        $_SESSION['errors'] = $errors;
        $_SESSION['old_data'] = $old_data;
        header('Location: registro.php');
        exit();
    }
} else {
    // Si no es POST, redirigir al formulario
    header('Location: registro.php');
    exit();
}
?>
