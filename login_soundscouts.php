<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    // Verificar si hay cookie de "recordar"
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        // Conexión a la base de datos
        $conn = new mysqli("localhost", "root", "", "soundscouts");
        
        if ($conn->connect_error) {
            die("Error de conexión: " . $conn->connect_error);
        }

        try {
            // Buscar usuario por token
            $stmt = $conn->prepare("SELECT id, nombre, email FROM usuarios WHERE token_verificacion = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Establecer sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['nombre'];
                
                // Actualizar último login
                $update = $conn->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
                $update->bind_param("i", $user['id']);
                $update->execute();
                
                // Redirigir al dashboard después de autenticar
                header('Location: inicio.php');
                exit();
            } else {
                // Token inválido, eliminar cookie
                setcookie('remember_token', '', time() - 3600, '/');
            }
        } catch (Exception $e) {
            error_log('Error al verificar token: ' . $e->getMessage());
        } finally {
            $conn->close();
        }
    }
    
    // Mostrar formulario de login si no está autenticado
    mostrarFormularioLogin();
    exit();
}

// Si llegamos aquí, el usuario está autenticado - redirigir al dashboard
header('Location: inicio.php');
exit();

function mostrarFormularioLogin() {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - SOUNDSCOUTS</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: 'Poppins', sans-serif;
            }
            
            body {
                min-height: 100vh;
                background: url('https://pbs.twimg.com/media/F4FZIZebkAULKbo.jpg:large') no-repeat;
                background-size: cover;
                background-position: center;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            
            .login-container {
                background: rgba(0, 0, 0, 0.7);
                padding: 40px;
                border-radius: 10px;
                width: 400px;
                max-width: 90%;
                backdrop-filter: blur(10px);
            }
            
            .logo {
                text-align: center;
                font-size: 2.5em;
                margin-bottom: 30px;
                color: white;
            }
            
            .input-group {
                margin-bottom: 20px;
            }
            
            .input-group input {
                width: 100%;
                padding: 15px;
                background: rgba(255, 255, 255, 0.1);
                border: none;
                border-radius: 5px;
                color: white;
                font-size: 1em;
            }
            
            .input-group input:focus {
                outline: 2px solid #1DA1F2;
            }
            
            .remember-me {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
                color: #b3b3b3;
            }
            
            .remember-me input {
                margin-right: 10px;
            }
            
            .login-btn {
                width: 100%;
                padding: 15px;
                background: #1DA1F2;
                color: white;
                border: none;
                border-radius: 5px;
                font-size: 1em;
                cursor: pointer;
                transition: background 0.3s;
            }
            
            .login-btn:hover {
                background: #1991db;
            }
            
            .links {
                margin-top: 20px;
                text-align: center;
                color: #b3b3b3;
            }
            
            .links a {
                color: #1DA1F2;
                text-decoration: none;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="logo">SOUNDSCOUTS</div>
            <form action="procesar_login.php" method="POST">
                <div class="input-group">
                    <input type="email" name="email" placeholder="Correo electrónico" required>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Contraseña" required>
                </div>
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Recordar sesión</label>
                </div>
                <button type="submit" class="login-btn">Iniciar sesión</button>
            </form>
            <div class="links">
                <a href="#">¿Olvidaste tu contraseña?</a>
                <span> • </span>
                <a href="#">Registrarse</a>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>