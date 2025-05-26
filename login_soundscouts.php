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
            } else {
                // Token inválido, redirigir al login
                header('Location: login_soundscouts.php');
                exit();
            }
        } catch (Exception $e) {
            error_log('Error al verificar token: ' . $e->getMessage());
            header('Location: login_soundscouts.php');
            exit();
        } finally {
            $conn->close();
        }
    } else {
        // No hay sesión ni cookie, redirigir al login
        header('Location: login_soundscouts.php');
        exit();
    }
}

// Obtener información del usuario para mostrar
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - SOUNDSCOUTS</title>
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
            color: white;
            display: flex;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 240px;
            height: 100vh;
            padding: 20px 10px;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            position: fixed;
            display: flex;
            flex-direction: column;
            z-index: 10;
        }

        .logo {
            font-size: 1.8em;
            color: #fff;
            padding: 0 15px 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .navigation {
            flex-grow: 1;
        }

        .navigation ul {
            list-style: none;
        }

        .navigation li {
            margin-bottom: 5px;
        }

        .navigation a {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            color: #b3b3b3;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .navigation a:hover, .navigation a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .navigation a i {
            margin-right: 15px;
            font-size: 1.2em;
        }

        .navigation a.active i {
            color: #1DA1F2;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 240px;
            width: calc(100% - 240px);
            padding: 100px 40px 40px;
        }

        /* Navbar Styles */
        .navbar {
            position: fixed;
            top: 0;
            left: 240px;
            width: calc(100% - 240px);
            padding: 15px 40px;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 9;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .navbar-nav {
            display: flex;
            align-items: center;
        }

        .navbar-nav a {
            color: white;
            text-decoration: none;
            margin-left: 30px;
            font-size: 0.9em;
            font-weight: 500;
            transition: color 0.3s;
        }

        .navbar-nav a:hover {
            color: #1DA1F2;
        }

        .navbar-nav .divider {
            color: rgba(255, 255, 255, 0.5);
            margin: 0 20px;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #1DA1F2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }

        .logout-btn {
            background: transparent;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 6px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.8em;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
        }

        /* Welcome Section */
        .welcome-section {
            margin-bottom: 40px;
        }

        .welcome-message {
            font-size: 2em;
            margin-bottom: 20px;
        }

        /* Music Section */
        .music-section {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
        }

        .music-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .music-card:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-5px);
        }

        .music-card img {
            width: 100%;
            border-radius: 4px;
            margin-bottom: 15px;
            aspect-ratio: 1/1;
            object-fit: cover;
        }

        .music-card h3 {
            font-size: 1em;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .music-card p {
            color: #b3b3b3;
            font-size: 0.8em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .play-icon {
            position: absolute;
            right: 15px;
            bottom: 60px;
            background: #1DA1F2;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        .music-card:hover .play-icon {
            opacity: 1;
            transform: translateY(0);
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
                padding: 20px 5px;
            }
            
            .sidebar .logo {
                font-size: 1.2em;
                padding: 0 5px 20px;
                text-align: center;
            }
            
            .navigation a span {
                display: none;
            }
            
            .navigation a i {
                margin-right: 0;
                font-size: 1.4em;
            }
            
            .main-content {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
            
            .navbar {
                left: 70px;
                width: calc(100% - 70px);
            }
        }

        @media (max-width: 768px) {
            .music-section {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .navbar {
                padding: 15px 20px;
            }
            
            .navbar-nav a {
                margin-left: 15px;
            }
            
            .main-content {
                padding: 100px 20px 40px;
            }
        }

        @media (max-width: 576px) {
            .sidebar {
                width: 0;
                overflow: hidden;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .navbar {
                left: 0;
                width: 100%;
            }
            
            .music-section {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 15px;
            }
            
            .music-card h3 {
                font-size: 0.9em;
            }
            
            .music-card p {
                font-size: 0.7em;
            }
            
            .play-icon {
                width: 30px;
                height: 30px;
                right: 10px;
                bottom: 50px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">SOUNDSCOUTS</div>
        <div class="navigation">
            <ul>
                <li>
                    <a href="inicio.php" class="active">
                        <i class="fas fa-home"></i>
                        <span>Inicio</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fas fa-search"></i>
                        <span>Buscar</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fas fa-book"></i>
                        <span>Tu Biblioteca</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="navigation">
            <ul>
                <li>
                    <a href="#">
                        <i class="fas fa-plus-square"></i>
                        <span>Crear Playlist</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fas fa-heart"></i>
                        <span>Canciones Gustadas</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Navbar -->
        <div class="navbar">
            <div class="navbar-nav">
                <a href="#">Premium</a>
                <a href="#">Ajustes</a>
                <a href="#">Descargar</a>
                <span class="divider">|</span>
                <a href="#">Registrarse</a>
            </div>
            <div class="user-menu">
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
            </div>
        </div>

        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1 class="welcome-message">¡Bienvenido de nuevo, <?php echo htmlspecialchars($user_name); ?>!</h1>
        </div>

        <!-- Music Section -->
        <div class="music-section">
            <div class="music-card">
                <img src="https://i.scdn.co/image/ab67616d0000b27396094d0c80f0f321da705f88" alt="Album Cover">
                <h3>we can't be friends</h3>
                <p>Ariana Grande</p>
                <div class="play-icon">
                    <i class="fas fa-play"></i>
                </div>
            </div>
            
            <div class="music-card">
                <img src="https://i.scdn.co/image/ab67616d0000b27307e2cf9023db855b41f3d26e" alt="Album Cover">
                <h3>Chill Kill</h3>
                <p>Red Velvet</p>
                <div class="play-icon">
                    <i class="fas fa-play"></i>
                </div>
            </div>
            
            <div class="music-card">
                <img src="https://i.scdn.co/image/ab67616d0000b273d8041a531487d0e0e4cfb41f" alt="Album Cover">
                <h3>Laurel Hell</h3>
                <p>Mitski</p>
                <div class="play-icon">
                    <i class="fas fa-play"></i>
                </div>
            </div>
            
            <div class="music-card">
                <img src="https://i.scdn.co/image/ab67616d0000b273c5649add07ed3720be9d5526" alt="Album Cover">
                <h3>Blonde</h3>
                <p>Frank Ocean</p>
                <div class="play-icon">
                    <i class="fas fa-play"></i>
                </div>
            </div>
            
            <div class="music-card">
                <img src="https://i.scdn.co/image/ab67706f00000002d72ef75e14ca6f60ea2364c2" alt="Album Cover">
                <h3>Daily Mix 1</h3>
                <p>Arctic Monkeys, The Strokes</p>
                <div class="play-icon">
                    <i class="fas fa-play"></i>
                </div>
            </div>
            
            <div class="music-card">
                <img src="https://i.scdn.co/image/ab67706f00000002d5f3f0331b4b0e0f7a7f9a1f" alt="Album Cover">
                <h3>Discover Weekly</h3>
                <p>Nuevos lanzamientos</p>
                <div class="play-icon">
                    <i class="fas fa-play"></i>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Efecto de hover en las tarjetas de música
        document.querySelectorAll('.music-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.querySelector('.play-icon').style.opacity = '1';
                card.querySelector('.play-icon').style.transform = 'translateY(0)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.querySelector('.play-icon').style.opacity = '0';
                card.querySelector('.play-icon').style.transform = 'translateY(10px)';
            });
        });
    </script>
</body>
</html>