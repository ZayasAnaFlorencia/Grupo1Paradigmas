<?php
require_once 'codigos_soundscouts.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        // Configuración de conexión a la base de datos con manejo de errores
        $db_host = "127.0.0.1"; // Usar 127.0.0.1 en lugar de localhost
        $db_user = "root";
        $db_pass = ""; // Cambiar si usas contraseña
        $db_name = "soundscouts";
        
        try {
            $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
            
            if ($conn->connect_error) {
                throw new Exception("Error de conexión: " . $conn->connect_error);
            }

            $stmt = $conn->prepare("SELECT id, nombre, email FROM usuarios WHERE token_verificacion = ?");
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $conn->error);
            }
            
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['nombre'];
                
                $update = $conn->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
                $update->bind_param("i", $user['id']);
                $update->execute();
            } else {
                header('Location: login_soundscouts.php');
                exit();
            }
        } catch (Exception $e) {
            error_log('Error en la autenticación: ' . $e->getMessage());
            header('Location: login_soundscouts.php');
            exit();
        } finally {
            if (isset($conn)) {
                $conn->close();
            }
        }
    } else {
        header('Location: login_soundscouts.php');
        exit();
    }
}

// Obtener datos del usuario
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Obtener último login
try {
    $conn = new mysqli("127.0.0.1", "root", "", "soundscouts");
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }

    $ultimo_login = 'Nunca';
    $stmt = $conn->prepare("SELECT ultimo_login FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if ($row['ultimo_login']) {
            $ultimo_login = date('d/m/Y H:i', strtotime($row['ultimo_login']));
        }
    }
} catch (Exception $e) {
    error_log('Error al obtener último login: ' . $e->getMessage());
    $ultimo_login = 'No disponible';
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

// Obtener recomendaciones
try {
    $db = (new Database())->getConnection();
    $gestor = new GestorGenerosMusicales($db);
    $recommendations = $gestor->recomendarCanciones($user_id, 6);
} catch (Exception $e) {
    error_log('Error al obtener recomendaciones: ' . $e->getMessage());
    $recommendations = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - SOUNDSCOUTS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            background-attachment: fixed;
            color: white;
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 20px 100px;
            background: rgba(0, 0, 0, 0.5);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 99;
        }

        .logo {
            font-size: 2em;
            color: white;
            user-select: none;
        }

        .navigation a {
            position: relative;
            font-size: 1.1em;
            color: white;
            text-decoration: none;
            font-weight: 500;
            margin-left: 40px;
        }

        .navigation a i {
            margin-right: 5px;
        }

        .navigation a.active::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -6px;
            width: 100%;
            height: 3px;
            background: white;
            border-radius: 5px;
        }

        .user-info {
            display: flex;
            align-items: center;
            margin-left: 40px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background-color: #4b6cb7;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 10px;
            font-weight: bold;
        }

        .logout-btn {
            color: white;
            text-decoration: none;
            font-weight: 500;
        }

        .main-container {
            margin-top: 120px;
            padding: 0 100px;
        }

        .welcome-section {
            margin-bottom: 40px;
        }

        .welcome-message {
            font-size: 2.5em;
            margin-bottom: 20px;
        }

        .user-details {
            background: rgba(0, 0, 0, 0.5);
            padding: 20px;
            border-radius: 10px;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            max-width: 600px;
        }

        .user-details p {
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .user-details i {
            margin-right: 10px;
            color: #4b6cb7;
        }

        .section-title {
            font-size: 1.8em;
            margin-bottom: 20px;
        }

        .section-title i {
            margin-right: 10px;
            color: #4b6cb7;
        }

        .music-section {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 50px;
        }

        .music-card {
            background: rgba(0, 0, 0, 0.5);
            border-radius: 10px;
            padding: 15px;
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }

        .music-card:hover {
            transform: translateY(-5px);
            background: rgba(0, 0, 0, 0.7);
        }

        .music-card img {
            width: 100%;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .music-card h3 {
            font-size: 1em;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .music-card p {
            font-size: 0.8em;
            color: #aaa;
            margin-bottom: 15px;
        }

        .play-icon {
            position: absolute;
            right: 15px;
            bottom: 15px;
            background: #4b6cb7;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .music-card:hover .play-icon {
            opacity: 1;
            transform: translateY(-10px);
        }
    </style>
</head>
<body>
    <header>
        <h2 class="logo">SOUNDSCOUTS</h2>
        <nav class="navigation">
            <a href="inicio.php" class="active"><i class="fas fa-home"></i>Inicio</a>
            <a href="#"><i class="fas fa-search"></i>Buscar</a>
            <a href="#"><i class="fas fa-music"></i>Tu Biblioteca</a>
            <a href="player.php"><i class="fas fa-play-circle"></i>Player</a>
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
            </div>
        </nav>
    </header>

    <div class="main-container">
        <div class="welcome-section">
            <h1 class="welcome-message">¡Bienvenido de nuevo, <?php echo htmlspecialchars($user_name); ?>!</h1>
            <div class="user-details">
                <p><i class="fas fa-envelope"></i> Email: <span><?php echo htmlspecialchars($user_email); ?></span></p>
                <p><i class="fas fa-clock"></i> Último inicio: <span><?php echo $ultimo_login; ?></span></p>
            </div>
        </div>

        <h2 class="section-title"><i class="fas fa-headphones"></i> Recomendaciones para ti</h2>
        
        <div class="music-section">
            <?php if (!empty($recommendations)): ?>
                <?php foreach ($recommendations as $song): ?>
                    <div class="music-card">
                        <img src="https://via.placeholder.com/300" alt="Album Cover">
                        <div>
                            <h3><?= htmlspecialchars($song['titulo']) ?></h3>
                            <p><?= htmlspecialchars($song['artista']) ?></p>
                        </div>
                        <div class="play-icon">
                            <i class="fas fa-play"></i>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="music-card">
                    <img src="https://i.scdn.co/image/ab67616d0000b27396094d0c80f0f321da705f88" alt="Album Cover">
                    <div>
                        <h3>we can't be friends</h3>
                        <p>Ariana Grande</p>
                    </div>
                    <div class="play-icon">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
                
                <div class="music-card">
                    <img src="https://i.scdn.co/image/ab67616d0000b273e6f407c7f3a0ec98845e4431" alt="Album Cover">
                    <div>
                        <h3>Lose Control</h3>
                        <p>Teddy Swims</p>
                    </div>
                    <div class="play-icon">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
                
                <div class="music-card">
                    <img src="https://i.scdn.co/image/ab67616d0000b2731d7e26119d1a6648e34dc51c" alt="Album Cover">
                    <div>
                        <h3>Water</h3>
                        <p>Tyla</p>
                    </div>
                    <div class="play-icon">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
                
                <div class="music-card">
                    <img src="https://i.scdn.co/image/ab67616d0000b273f629eb64fd8ef76a97b154f5" alt="Album Cover">
                    <div>
                        <h3>Stick Season</h3>
                        <p>Noah Kahan</p>
                    </div>
                    <div class="play-icon">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
                
                <div class="music-card">
                    <img src="https://i.scdn.co/image/ab67616d0000b273c6ba98fd3f3b396a6c6f7091" alt="Album Cover">
                    <div>
                        <h3>Lovin On Me</h3>
                        <p>Jack Harlow</p>
                    </div>
                    <div class="play-icon">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
                
                <div class="music-card">
                    <img src="https://i.scdn.co/image/ab67616d0000b2739e169bac9ee9b7b5a89a0c88" alt="Album Cover">
                    <div>
                        <h3>Beautiful Things</h3>
                        <p>Benson Boone</p>
                    </div>
                    <div class="play-icon">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.querySelectorAll('.music-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.querySelector('.play-icon').style.opacity = '1';
                card.querySelector('.play-icon').style.transform = 'translateY(-10px)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.querySelector('.play-icon').style.opacity = '0';
                card.querySelector('.play-icon').style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>