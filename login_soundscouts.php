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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoundScouts - Login</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap');
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background: #000;
            background-image: url("https://images-wixmp-ed30a86b8c4ca887773594c2.wixmp.com/f/e570043b-1daf-4dbd-b3f7-edae04b144cd/dj0nz2t-4f7a4670-5c8c-4827-9211-769d9fb42adf.png/v1/fill/w_1197,h_668,q_70,strp/frutiger_metro__by_nezukorempadeviart07_dj0nz2t-pre.jpg?token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ1cm46YXBwOjdlMGQxODg5ODIyNjQzNzNhNWYwZDQxNWVhMGQyNmUwIiwiaXNzIjoidXJuOmFwcDo3ZTBkMTg4OTgyMjY0MzczYTVmMGQ0MTVlYTBkMjZlMCIsIm9iaiI6W1t7ImhlaWdodCI6Ijw9NzE0IiwicGF0aCI6IlwvZlwvZTU3MDA0M2ItMWRhZi00ZGJkLWIzZjctZWRhZTA0YjE0NGNkXC9kajBuejJ0LTRmN2E0NjcwLTVjOGMtNDgyNy05MjExLTc2OWQ5ZmI0MmFkZi5wbmciLCJ3aWR0aCI6Ijw9MTI4MCJ9XV0sImF1ZCI6WyJ1cm46c2VydmljZTppbWFnZS5vcGVyYXRpb25zIl19.5gierp8cQnAB_a_eTs7XM9oPerKEkQhAZL8cuPHFP1g");
            background-size: cover;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding-top: 80px;
        }

        .logo-img {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 50%;
        }

        /* Navbar extendida con logo */
        nav {
            position: fixed;
            top: 20px;
            left: 20px;
            right: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(90deg, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.8) 50%, rgba(0,0,0,0.7) 100%);
            padding: 10px 25px;
            border-radius: 30px;
            backdrop-filter: blur(5px);
            z-index: 100;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-text {
            color: white;
            font-weight: bold;
            font-size: 1.2em;
            letter-spacing: 1px;
        }

        .nav-links {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        nav a {
            position: relative;
            font-size: 0.9em;
            color: #fff;
            text-decoration: none;
            padding: 6px 15px;
            transition: all 0.3s;
            background: rgba(0, 0, 0, 0.4);
            border-radius: 15px;
        }

        nav a:hover {
            color: #08d;
            background: rgba(0, 10, 30, 0.6);
        }

        nav a span {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            border-bottom: 2px solid #08d;
            border-radius: 15px;
            transform: scale(0) translateY(50px);
            opacity: 0;
            transition: .5s;
        }

        nav a:hover span {
            transform: scale(1) translateY(0);
            opacity: 1;
        }

        nav button {
            font-size: 0.9em;
            color: #08d;
            background: rgba(0, 15, 30, 0.6);
            border: 2px solid #08d;
            border-radius: 20px;
            padding: 6px 15px;
            cursor: pointer;
            transition: all 0.3s;
        }

        nav button:hover {
            background: rgba(0, 80, 160, 0.4);
            box-shadow: 0 0 10px #08d;
        }

        /* Wrapper - Ahora más pequeño para login */
        .wrapper {
            width: 350px;
            height: 450px; /* Reducido para el formulario de login */
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #08d;
            border-radius: 20px;
            backdrop-filter: blur(8.5px);
            box-shadow: 0 0 20px rgba(0, 136, 221, 0.3);
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            z-index: 10;
            animation: float 6s ease-in-out infinite;
            margin-top: 40px;
        }

        .form-box {
            width: 100%;
            padding: 30px;
        }

        .form-box h2 {
            color: #08d;
            text-align: center;
            margin-bottom: 25px;
            font-size: 1.5em;
            text-shadow: 0 0 10px rgba(0, 136, 221, 0.5);
        }

        .input-box {
            position: relative;
            margin-bottom: 30px; /* Más espacio entre campos */
        }

        .input-box input {
            width: 100%;
            padding: 10px 0;
            background: transparent;
            border: none;
            border-bottom: 2px solid rgba(0, 136, 221, 0.5);
            color: #fff;
            font-size: 1em;
        }

.input-box input:focus ~ label,
.input-box input:not(:placeholder-shown) ~ label {
    top: -15px;
    font-size: 0.8em;
    color: #08d;
}

        .input-box label {
            position: absolute;
            top: 10px;
            left: 0;
            color: rgba(255, 255, 255, 0.7);
            transition: 0.3s;
            pointer-events: none;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 15px 0;
            font-size: 0.85em; /* Reducido ligeramente el tamaño de fuente */
            color: rgba(255, 255, 255, 0.7);
        }

        .remember-forgot label {
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap; /* Evita que el texto se divida en varias líneas */
        }

        .remember-forgot a {
            color: #08d;
            text-decoration: none;
            white-space: nowrap; /* Evita que el enlace se divida */
            margin-left: 10px; /* Espacio entre el checkbox y el enlace */
        }

        .remember-forgot a:hover {
            text-decoration: underline;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: rgba(0, 100, 200, 0.2);
            border: 2px solid #08d;
            border-radius: 20px;
            color: #fff;
            font-size: 1em;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn:hover {
            background: rgba(0, 100, 200, 0.4);
            box-shadow: 0 0 15px #08d;
        }

        .register-redirect {
            text-align: center;
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9em;
        }

        .register-redirect a {
            color: #08d;
            text-decoration: none;
        }

        .register-redirect a:hover {
            text-decoration: underline;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo-container">
             <img src="logo_sound_scouts.png" alt="SoundScouts Logo" class="logo-img">
            <div class="logo-text">SOUNDSCOUTS</div>
        </div>
        <div class="nav-links">
            <a href="#">Premium<span></span></a>
            <a href="#">Ajustes<span></span></a>
            <a href="#">Descargar<span></span></a>
            <a href="registro.php">Registrarse<span></span></a>
            <button type="button">Iniciar Sesión</button>
        </div>
    </nav>
    <div class="wrapper">
        <div class="form-box">
            <h2>Iniciar Sesión</h2>
            <form id="loginForm" action="procesar_login.php" method="POST">
                <div class="input-box">
                    <input type="email" id="email" name="email" required placeholder=" ">
                    <label>Email</label>
                </div>
                <div class="input-box">
                    <input type="password" id="password" name="password" required placeholder=" ">
                    <label>Contraseña</label>
                </div>
                <div class="remember-forgot">
                    <label><input type="checkbox"> Recordarme</label>
                    <a href="#">¿Olvidaste tu contraseña?</a>
                </div>
                <button type="submit" class="btn">Iniciar Sesión</button>
                <div class="register-redirect">
                    <p>¿No tienes una cuenta? <a href="registro.php">Regístrate</a></p>
                </div>
            </form>
        </div>
    </div>
    <?php
}
?>