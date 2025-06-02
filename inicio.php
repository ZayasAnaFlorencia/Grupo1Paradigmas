<?php
session_start();
// db.php - Database connection file
$host = 'localhost';
$dbname = 'soundscouts';
$username = 'root'; // Default XAMPP username
$password = ''; // Default XAMPP password is empty

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT nombre, foto_perfil FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$user_name = $user['nombre'] ?? 'User';
$user_photo = $user['foto_perfil'] ?? null;

$current_user_id = $_SESSION['user_id'];

$friend_recommendations = [];
try {
    $query = "
        SELECT p.*, u.nombre as usuario_nombre, u.apodo as usuario_apodo, u.foto_perfil as usuario_foto
        FROM playlists p
        JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.usuario_id IN (
            SELECT usuario_id2 FROM amistades WHERE usuario_id1 = ?
            UNION
            SELECT usuario_id1 FROM amistades WHERE usuario_id2 = ?
        )
        ORDER BY p.fecha_creacion DESC
        LIMIT 5
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$current_user_id, $current_user_id]);
    $friend_recommendations = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error al obtener recomendaciones de amigos: " . $e->getMessage());
}

// Obtener canciones para el reproductor
function obtenerCanciones($pdo) {
    $songs = [];
    try {
        $query = "SELECT c.id, c.titulo, a.nombre as artista, 
                 SEC_TO_TIME(c.duracion) as duracion, 
                 c.portada, c.audio, c.genero
                 FROM canciones c
                 JOIN artistas a ON c.artista_id = a.id";
        $stmt = $pdo->query($query);
        
        if ($stmt && $stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $song = [
                    'id' => $row['id'] ?? 0,
                    'titulo' => $row['titulo'] ?? 'Título desconocido',
                    'artista' => $row['artista'] ?? 'Artista desconocido',
                    'duracion' => $row['duracion'] ?? '0:00',
                    'portada' => $row['portada'] ?? 'https://via.placeholder.com/300',
                    'audio' => $row['audio'] ?? 'assets/audio_placeholder.mp3',
                    'genero' => $row['genero'] ?? 'Desconocido'
                ];
                $songs[] = $song;
            }
        }
    } catch (Exception $e) {
        error_log('Error al obtener canciones: ' . $e->getMessage());
    }
    
    if (empty($songs)) {
        $songs = [
            [
                'id' => 1,
                'titulo' => "Problem (feat. Iggy Azalea)",
                'artista' => "Ariana Grande",
                'duracion' => "3:55",
                'portada' => "https://cdn-images.dzcdn.net/images/cover/6706f1154083f461a348508c28030a30/1900x1900-000000-80-0-0.jpg",
                'audio' => "assets/ariana_problem.mp3",
                'genero' => "Pop"
            ],
            [
                'id' => 2,
                'titulo' => "One Last Time",
                'artista' => "Ariana Grande",
                'duracion' => "3:17",
                'portada' => "https://i.scdn.co/image/ab67616d0000b273e6f407c7f3a0ec98845e4431",
                'audio' => "assets/ariana_one_last_time.mp3",
                'genero' => "Pop"
            ]
        ];
    }
    
    return $songs;
}

$canciones = obtenerCanciones($pdo);
$canciones_json = json_encode($canciones);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoundScouts</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }
        
        body {
            background-color: #121212;
            color: #fff;
        }
        
        /* Modern Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #333;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .logo {
            width: 45px;
            height: 45px;
            border-radius: 50%; 
            overflow: hidden; 
        }
        
        .logo img {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 50%;
        }
        
        .nav-center {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .nav-icon {
            color: #b3b3b3;
            font-size: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .nav-icon.active {
            color: #0d6efd;
        }
        
        .nav-icon:hover {
            color: #fff;
            transform: scale(1.1);
        }
        
        .search-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .search-bar {
            position: relative;
            width: 300px;
        }
        
        .search-bar input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border-radius: 20px;
            border: none;
            background-color: #333;
            color: #fff;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .search-bar input:focus {
            background-color: #444;
            box-shadow: 0 0 0 2px #0d6efd;
        }
        
        .search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #b3b3b3;
        }
        
        .explore-btn {
            background: transparent;
            border: none;
            color: #b3b3b3;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 8px 12px;
            border-radius: 20px;
        }
        
        .explore-btn:hover {
            background-color: #333;
            color: #fff;
        }
        
        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .notification-bell {
            position: relative;
            color: #b3b3b3;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .notification-bell:hover {
            color: #fff;
            transform: scale(1.1);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #0d6efd;
            color: white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .friends-icon {
            color: #b3b3b3;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .friends-icon:hover {
            color: #fff;
            transform: scale(1.1);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #0d6efd;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .user-avatar:hover {
            transform: scale(1.1);
            border-color: #fff;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-avatar .initials {
            width: 100%;
            height: 100%;
            background-color: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .explore-btn span {
                display: none;
            }
            
            .explore-btn {
                padding: 8px;
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
            }
            
            .search-bar {
                width: 200px;
            }
            
            .nav-center {
                gap: 15px;
            }
            
            .nav-right {
                gap: 15px;
            }
        }
        
        @media (max-width: 576px) {
            .search-bar {
                width: 150px;
            }
            
            .search-bar input {
                padding: 8px 15px 8px 35px;
                font-size: 12px;
            }
            
            .friends-icon {
                display: none;
            }
        }

        .user-menu {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #282828;
            min-width: 220px;
            border-radius: 4px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            z-index: 1001;
            padding: 10px 0;
            margin-top: 10px;
        }
        
        .dropdown-content.show {
            display: block;
        }
        
        .dropdown-content a {
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            display: block;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        
        .dropdown-content a:hover {
            background-color: #333;
        }
        
        .dropdown-divider {
            height: 1px;
            background-color: #444;
            margin: 8px 0;
        }
        
        .dropdown-header {
            padding: 8px 20px;
            color: #aaa;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .dropdown-section {
            padding: 8px 0;
        }
        /* Friends Dropdown Styles */
        .friends-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 60px;
            background: #1a1a2e; /* Cambio a fondo sólido */
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 12px;
            width: 380px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 1001;
            padding: 1rem;
            animation: fadeIn 0.2s ease-out;
        }

        .friends-dropdown.show {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .friends-dropdown-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .friends-dropdown-header h3 {
            font-size: 1.2rem;
            color: white;
        }

        .friends-dropdown-header .close-btn {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .friends-recommendations {
            display: grid;
            gap: 1rem;
        }

        .friends-recommendation {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 0.8rem;
            transition: all 0.2s;
        }

        .friends-recommendation:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .friend-info {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .friend-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 0.8rem;
            object-fit: cover;
        }

        .friend-name {
            font-size: 0.95rem;
            font-weight: 500;
        }

        .recommendation-item {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border-radius: 4px;
            background: rgba(0, 0, 0, 0.2);
        }

        .recommendation-cover {
            width: 40px;
            height: 40px;
            margin-right: 0.8rem;
            border-radius: 4px;
        }

        .recommendation-details {
            flex: 1;
        }

        .recommendation-title {
            font-size: 0.9rem;
            margin-bottom: 0.1rem;
        }

        .recommendation-artist {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .recommendation-play {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .recommendation-play:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        .recommendation-play i {
            font-size: 0.7rem;
            margin-left: 1px;
        }

        .recommendation-comment {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 0.5rem;
            padding-left: 0.5rem;
            font-style: italic;
        }

        .friends-dropdown-footer {
            text-align: center;
            margin-top: 1rem;
            padding-top: 0.8rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .view-all-btn {
            display: inline-block;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 20px;
            padding: 0.5rem 1rem;
            color: white;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.2s;
            text-decoration: none;
            text-align: center;
        }

        .view-all-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Reproductor de audio */
        #audioPlayer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #181818;
            padding: 15px;
            border-top: 1px solid #333;
            display: none;
            z-index: 1000;
        }
        
        .player-content {
            display: flex;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .player-album-art {
            width: 60px;
            height: 60px;
            margin-right: 15px;
        }
        
        .player-album-art img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .player-info {
            flex: 1;
            min-width: 0;
        }
        
        .player-song-title {
            font-size: 14px;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .player-artist-name {
            font-size: 12px;
            color: #b3b3b3;
        }
        
        .player-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 0 20px;
        }
        
        .player-btn {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
        }
        
        .player-progress {
            flex: 2;
            min-width: 0;
        }
        
        .progress-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .time-display {
            font-size: 12px;
            color: #b3b3b3;
            width: 40px;
        }
        
        .progress-bar-container {
            flex: 1;
            height: 4px;
            background: #333;
            border-radius: 2px;
            cursor: pointer;
        }
        
        .progress-bar {
            height: 100%;
            background: #0d6efd;
            border-radius: 2px;
            width: 0%;
        }
        
        .player-volume {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: 20px;
        }
        
        /* Contenedor centrado para todo el contenido */
        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 20px 20px;
        }
        
        /* Album grid */
        .albums-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .album-row {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 8px;
            background: #181818;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .album-row:hover {
            background-color: #282828;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .album-row img {
            width: 50px;
            height: 50px;
            border-radius: 4px;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .album-info {
            flex: 1;
        }
        
        .album-title {
            font-size: 14px;
            color: white;
            margin-bottom: 2px;
        }
        
        .album-year {
            color: #989898;
            font-size: 12px;
        }
        
        .play-button {
            background-color: #0d6efd;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: translateY(5px);
            transition: all 0.3s ease;
            position: absolute;
            right: 15px;
        }
        
        .album-row:hover .play-button {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Secciones de recomendaciones verticales */
        .section-title {
            font-size: 24px;
            font-weight: 700;
            margin: 30px 0 20px;
            color: #fff;
        }
        
        .playlists-section {
            margin-bottom: 40px;
        }
        
        .playlists-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
        }
        
        .playlist-card {
            background: #181818;
            border-radius: 8px;
            padding: 16px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            height: 260px;
        }
        
        .playlist-card:hover {
            background: #282828;
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        
        .playlist-card img {
            width: 100%;
            aspect-ratio: 1;
            border-radius: 4px;
            object-fit: cover;
            margin-bottom: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        
        .playlist-card h4 {
            color: #fff;
            font-size: 16px;
            margin-bottom: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .playlist-card p {
            color: #b3b3b3;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .playlist-play-button {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background-color: #0d6efd;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
        }
        
        .playlist-card:hover .playlist-play-button {
            opacity: 1;
            transform: translateY(0);
        }
        
        .playlist-play-button i {
            color: white;
            font-size: 14px;
            margin-left: 2px;
        }
    </style>
</head>
<body>
    <!-- Modern Navbar -->
    <nav class="navbar">
        <!-- Logo on the left -->
        <div class="logo">
            <img src="logo_sound_scouts.png" alt="SoundScouts Logo">
        </div>
        
        <!-- Center section with home icon and search -->
        <div class="nav-center">
            <a href="inicio.php" class="nav-icon active">
                <i class="fas fa-home"></i>
            </a>
            
            <div class="search-container">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Buscar artistas, canciones...">
                </div>
                <button class="explore-btn">
                    <i class="fas fa-compass"></i>
                    <span>Explorar</span>
                </button>
            </div>
        </div>
        
        <!-- Right section with notifications, friends and user -->
        <div class="nav-right">
            <div class="notification-bell">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">3</span>
            </div>
            
            <div class="friends-icon">
                <i class="fas fa-user-friends"></i>
            </div>
            
            <div class="user-menu">
                <div class="user-avatar" id="userAvatar">
                    <?php if (!empty($user_photo)): ?>
                        <img src="<?= htmlspecialchars($user_photo) ?>" alt="User profile">
                    <?php else: ?>
                        <div class="initials"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="dropdown-content" id="userDropdown">
                    <div class="dropdown-section">
                        <div class="dropdown-header">Cuenta</div>
                        <a href="profile.php">Perfil</a>
                        <a href="preferences.php">Preferencias</a>
                    </div>
                    
                    <div class="dropdown-divider"></div>
                    
                    <div class="dropdown-section">
                        <div class="dropdown-header">2025</div>
                        <a href="#">Central</a>
                        <a href="#">Ayuda</a>
                        <a href="#">Descargar</a>
                    </div>
                    
                    <div class="dropdown-divider"></div>
                    
                    <div class="dropdown-section">
                        <a href="logout.php">Cerrar sesión</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Friends Dropdown -->
        <div class="friends-dropdown" id="friendsDropdown">
            <div class="friends-dropdown-header">
                <h3>Recomendaciones de amigos</h3>
                <button class="close-btn" id="closeFriendsDropdown">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="friends-recommendations">
                <!-- Friend Recommendation 1 -->
                <div class="friends-recommendation">
                    <div class="friend-info">
                        <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="María López" class="friend-avatar">
                        <span class="friend-name">María López</span>
                    </div>
                    
                    <div class="recommendation-item">
                        <img src="https://i.scdn.co/image/ab67616d00001e02ff9ca10b55ce82ae553c8228" alt="Blinding Lights" class="recommendation-cover">
                        <div class="recommendation-details">
                            <div class="recommendation-title">Blinding Lights</div>
                            <div class="recommendation-artist">The Weeknd</div>
                        </div>
                        <button class="recommendation-play">
                            <i class="fas fa-play"></i>
                        </button>
                    </div>
                    
                    <div class="recommendation-comment">
                        "Esta canción me recuerda a nuestros viajes nocturnos, ¡te va a encantar!"
                    </div>
                </div>
                
                <!-- Friend Recommendation 2 -->
                <div class="friends-recommendation">
                    <div class="friend-info">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Carlos Ruiz" class="friend-avatar">
                        <span class="friend-name">Carlos Ruiz</span>
                    </div>
                    
                    <div class="recommendation-item">
                        <img src="https://i.scdn.co/image/ab67706f00000002e4e2b5a3a0d6a1d4863c4c35" alt="Rock Clásico" class="recommendation-cover">
                        <div class="recommendation-details">
                            <div class="recommendation-title">Rock Clásico Essentials</div>
                            <div class="recommendation-artist">125 canciones</div>
                        </div>
                        <button class="recommendation-play">
                            <i class="fas fa-play"></i>
                        </button>
                    </div>
                    
                    <div class="recommendation-comment">
                        "Hice esta playlist para nuestro viaje de fin de semana, ¡échale un oído!"
                    </div>
                </div>
            </div>
            <?php if (!empty($friend_recommendations)): ?>
            <h3 style="text-align: center; color: #08d; margin-top: 40px; margin-bottom: 20px;">Recomendaciones de tus amigos</h3>
            
            <div class="friends-list">
                <?php foreach ($friend_recommendations as $playlist): ?>
                    <div class="friend-card">
                        <?php if (!empty($playlist['usuario_foto'])): ?>
                            <img src="<?= htmlspecialchars($playlist['usuario_foto']) ?>" alt="Foto de perfil" class="friend-avatar">
                        <?php else: ?>
                            <div class="friend-avatar" style="display: flex; align-items: center; justify-content: center; background-color: #333; font-size: 2em;">👤</div>
                        <?php endif; ?>
                        
                        <div class="friend-name"><?= htmlspecialchars($playlist['nombre']) ?></div>
                        <div class="friend-nickname">De @<?= htmlspecialchars($playlist['usuario_apodo'] ?? $playlist['usuario_nombre']) ?></div>
                        <div style="font-size: 0.8em; color: #aaa; margin-bottom: 10px; text-align: center;">
                            <?= htmlspecialchars($playlist['descripcion'] ?? 'Sin descripción') ?>
                        </div>
                        
                        <a href="#" class="friend-button" style="text-decoration: none;">Escuchar playlist</a>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="friends-dropdown-footer">
                <a href="amistades_recomendadas.php" class="view-all-btn">Ver nuevas amistades recomendadas</a>
            </div>
        </div>
    </nav>
    
    <!-- Contenedor principal centrado -->
    <div class="content-container">
        <!-- Álbumes de Ariana Grande -->
        <h2 class="section-title">Álbumes destacados</h2>
        <div class="albums-grid">
            <!-- Album 1 -->
            <div class="album-row" data-album-id="1">
                <img src="https://upload.wikimedia.org/wikipedia/en/d/dd/Thank_U%2C_Next_album_cover.png" alt="Thank U, Next">
                <div class="album-info">
                    <h3 class="album-title">Thank U, Next</h3>
                    <p class="album-year">2019 • Album</p>
                </div>
                <div class="play-button">
                    <i class="fas fa-play" style="color: white; font-size: 12px;"></i>
                </div>
            </div>
            
            <!-- Album 2 -->
            <div class="album-row" data-album-id="2">
                <img src="https://umusicstore.com.ar/cdn/shop/files/D_NQ_NP_745426-MLA40025118593_122019-O.jpg?v=1684182107" alt="Sweetener">
                <div class="album-info">
                    <h3 class="album-title">Sweetener</h3>
                    <p class="album-year">2018 • Album</p>
                </div>
                <div class="play-button">
                    <i class="fas fa-play" style="color: white; font-size: 12px;"></i>
                </div>
            </div>
            
            <!-- Album 3 -->
            <div class="album-row" data-album-id="3">
                <img src="https://umusicstore.com.ar/cdn/shop/files/D_NQ_NP_689894-MLA32091768152_092019-O.jpg?v=1684181989" alt="Dangerous Woman">
                <div class="album-info">
                    <h3 class="album-title">Dangerous Woman</h3>
                    <p class="album-year">2016 • Album</p>
                </div>
                <div class="play-button">
                    <i class="fas fa-play" style="color: white; font-size: 12px;"></i>
                </div>
            </div>
            
            <!-- Album 4 -->
            <div class="album-row" data-album-id="4">
                <img src="https://i.discogs.com/c9E4xV1FLWiccOemNVrVI61TIIStFffBvrodxXf0t-U/rs:fit/g:sm/q:40/h:300/w:300/czM6Ly9kaXNjb2dz/LWRhdGFiYXNlLWlt/YWdlcy9SLTcxMzg1/NjQtMTQzNDU3MTU5/Ni00OTEwLnBuZw.jpeg" alt="My Everything">
                <div class="album-info">
                    <h3 class="album-title">My Everything</h3>
                    <p class="album-year">2014 • Album</p>
                </div>
                <div class="play-button">
                    <i class="fas fa-play" style="color: white; font-size: 12px;"></i>
                </div>
            </div>
            
            <!-- Album 5 -->
            <div class="album-row" data-album-id="5">
                <img src="https://upload.wikimedia.org/wikipedia/en/a/a0/Ariana_Grande_-_Positions.png" alt="Positions">
                <div class="album-info">
                    <h3 class="album-title">Positions</h3>
                    <p class="album-year">2020 • Album</p>
                </div>
                <div class="play-button">
                    <i class="fas fa-play" style="color: white; font-size: 12px;"></i>
                </div>
            </div>
        </div>
        
        <!-- Sección de recomendaciones verticales -->
        <div class="playlists-section">
            <h2 class="section-title"> ¡Novedades! </h2>
            <div class="playlists-container">
                <!-- Recomendación 1 -->
                <div class="playlist-card">
                    <img src="https://i.scdn.co/image/ab67706f000000027217b1b10b1d8a4eb3e334d3" alt="All New Pop">
                    <h4>All New Pop</h4>
                    <div class="playlist-play-button">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
                
                <!-- Recomendación 2 -->
                <div class="playlist-card">
                    <img src="https://i.scdn.co/image/ab67706f0000000212aa78dcfc7aee9b6e154237" alt="Chill Vibes">
                    <h4>The New Alt</h4>
                    <div class="playlist-play-button">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
                
                <!-- Recomendación 3 -->
                <div class="playlist-card">
                    <img src="https://i.scdn.co/image/ab67706f00000002c39ba20d5629a8a8c849474e" alt="All New Kpop">
                    <h4>All New Kpop</h4>
                    <div class="playlist-play-button">
                        <i class="fas fa-play"></i>
                    </div>
                </div>

                <!-- Recomendación 4 -->
                <div class="playlist-card">
                    <img src="https://i.scdn.co/image/ab67706f0000000244cb6e4e307b59711872aa7d" alt="All New All Now">
                    <h4>All New, All Now</h4>
                    <div class="playlist-play-button">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
                
                <!-- Recomendación 5 -->
                <div class="playlist-card">
                    <img src="https://i.scdn.co/image/ab67706f00000002e1db6313bda81429208c24b8" alt="Singled Out">
                    <h4>Singled Out</h4>
                    <div class="playlist-play-button">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Segunda sección de recomendaciones -->
        <div class="playlists-section">
            <h2 class="section-title">Playlists populares</h2>
            <div class="playlists-container">
                <!-- Recomendación 6 -->
                <div class="playlist-card">
                    <img src="https://i1.sndcdn.com/artworks-ZxS4BvXk75MjVIpr-2Krlnw-t240x240.jpg" alt="Latin Hits">
                    <h4>All Out 2010s</h4>
                    <div class="playlist-play-button">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
                
                <!-- Recomendación 7 -->
                <div class="playlist-card">
                    <img src="https://i.scdn.co/image/ab67706f00000002583117b5f326c5759bcd4628" alt="Electronic">
                    <h4>Sad Songs</h4>
                    <div class="playlist-play-button">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
                
                <!-- Recomendación 8 -->
                <div class="playlist-card">
                    <img src="https://i.scdn.co/image/ab67706f000000029249b35f23fb596b6f006a15" alt="Jazz">
                    <h4>Modo Bestia</h4>
                    <div class="playlist-play-button">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
                
                <!-- Recomendación 9 -->
                <div class="playlist-card">
                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSBxty2Ht8yqMxROHXUVmSADaSIaj4ybdFFdQ&s" alt="Tay-Disco">
                    <h4>Taylor Discography</h4>
                    <div class="playlist-play-button">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
                
                <!-- Recomendación 10 -->
                <div class="playlist-card">
                    <img src="https://image-cdn-ak.spotifycdn.com/image/ab67706c0000da848ddcc161106fccc47354c829" alt="Indie">
                    <h4>Indie Rising</h4>
                    <div class="playlist-play-button">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reproductor de audio -->
    <div id="audioPlayer">
        <div class="player-content">
            <!-- Album Art -->
            <div class="player-album-art">
                <img id="playerAlbumArt" src="">
            </div>
            
            <!-- Song Info -->
            <div class="player-info">
                <div id="playerSongTitle" class="player-song-title"></div>
                <div id="playerArtistName" class="player-artist-name"></div>
            </div>
            
            <!-- Controls -->
            <div class="player-controls">
                <button id="prevBtn" class="player-btn">
                    <i class="fas fa-step-backward" style="font-size: 16px;"></i>
                </button>
                <button id="playPauseBtn" class="player-btn">
                    <i class="fas fa-play" style="font-size: 24px;"></i>
                </button>
                <button id="nextBtn" class="player-btn">
                    <i class="fas fa-step-forward" style="font-size: 16px;"></i>
                </button>
            </div>
            
            <!-- Progress Bar -->
            <div class="player-progress">
                <div class="progress-container">
                    <span id="currentTime" class="time-display">0:00</span>
                    <div class="progress-bar-container" id="progressBar">
                        <div class="progress-bar" id="progress"></div>
                    </div>
                    <span id="duration" class="time-display">0:00</span>
                </div>
            </div>
            
            <!-- Volume Control -->
            <div class="player-volume">
                <i class="fas fa-volume-up" style="color: #b3b3b3;"></i>
                <input type="range" id="volumeControl" min="0" max="1" step="0.01" value="0.7" style="width: 100px;">
            </div>
        </div>
        
        <!-- Hidden Audio Element -->
        <audio id="audioElement"></audio>
    </div>

    <script>
        // Simple interaction for demonstration
        document.querySelectorAll('.nav-icon, .explore-btn, .notification-bell, .friends-icon').forEach(el => {
            el.addEventListener('click', function() {
                // Remove active class from all icons
                document.querySelectorAll('.nav-icon').forEach(icon => {
                    icon.classList.remove('active');
                });
                
                // Add active class to clicked icon if it's a nav-icon
                if (this.classList.contains('nav-icon')) {
                    this.classList.add('active');
                }
                
                // In a real app, you would navigate to the appropriate page
                console.log('Navigating to:', this.getAttribute('href') || this.innerText);
            });
        });
        
        // Toggle dropdown when clicking user avatar
        document.getElementById('userAvatar').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('userDropdown').classList.toggle('show');
        });

        // Close dropdown when clicking outside
        window.addEventListener('click', function() {
            document.getElementById('userDropdown').classList.remove('show');
        });

        // Prevent dropdown from closing when clicking inside it
        document.getElementById('userDropdown').addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Friends dropdown functionality
        const friendsIcon = document.querySelector('.friends-icon');
        const friendsDropdown = document.getElementById('friendsDropdown');
        const closeFriendsDropdown = document.getElementById('closeFriendsDropdown');

        friendsIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            // Close user dropdown if open
            document.getElementById('userDropdown').classList.remove('show');
            // Toggle friends dropdown
            friendsDropdown.classList.toggle('show');
        });

        closeFriendsDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            friendsDropdown.classList.remove('show');
        });

        // Close friends dropdown when clicking outside
        window.addEventListener('click', function() {
            if (friendsDropdown.classList.contains('show')) {
                friendsDropdown.classList.remove('show');
            }
        });

        // Prevent friends dropdown from closing when clicking inside it
        friendsDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Player functionality
        const playerState = {
            currentTrackIndex: 0,
            isPlaying: false,
            currentPlaylist: [],
            currentAlbum: null
        };

        // DOM elements
        const audioPlayer = document.getElementById('audioPlayer');
        const audioElement = document.getElementById('audioElement');
        const playerAlbumArt = document.getElementById('playerAlbumArt');
        const playerSongTitle = document.getElementById('playerSongTitle');
        const playerArtistName = document.getElementById('playerArtistName');
        const playPauseBtn = document.getElementById('playPauseBtn');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const progressBar = document.getElementById('progressBar');
        const progress = document.getElementById('progress');
        const currentTimeEl = document.getElementById('currentTime');
        const durationEl = document.getElementById('duration');
        const volumeControl = document.getElementById('volumeControl');

        // Format time (seconds to MM:SS)
        function formatTime(seconds) {
            if (isNaN(seconds)) return "0:00";
            
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes}:${secs < 10 ? '0' : ''}${secs}`;
        }

        // Update progress bar
        function updateProgress() {
            const { currentTime, duration } = audioElement;
            const progressPercent = (currentTime / duration) * 100;
            progress.style.width = `${progressPercent}%`;
            currentTimeEl.textContent = formatTime(currentTime);
        }

        // Set song in player
        function loadSong(track) {
            playerSongTitle.textContent = track.titulo;
            playerArtistName.textContent = track.artista || 'Unknown Artist';
            playerAlbumArt.src = track.portada || 'https://via.placeholder.com/300';
            audioElement.src = track.audio;
            
            // Update duration when metadata is loaded
            audioElement.addEventListener('loadedmetadata', () => {
                durationEl.textContent = formatTime(audioElement.duration);
            });
        }

        // Play song
        function playSong() {
            audioPlayer.style.display = 'flex';
            audioElement.play()
                .then(() => {
                    playerState.isPlaying = true;
                    playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
                })
                .catch(error => {
                    console.error('Playback failed:', error);
                });
        }

        // Pause song
        function pauseSong() {
            audioElement.pause();
            playerState.isPlaying = false;
            playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
        }

        // Play next song
        function nextSong() {
            playerState.currentTrackIndex = (playerState.currentTrackIndex + 1) % playerState.currentPlaylist.length;
            loadSong(playerState.currentPlaylist[playerState.currentTrackIndex]);
            if (playerState.isPlaying) playSong();
        }

        // Play previous song
        function prevSong() {
            playerState.currentTrackIndex = (playerState.currentTrackIndex - 1 + playerState.currentPlaylist.length) % playerState.currentPlaylist.length;
            loadSong(playerState.currentPlaylist[playerState.currentTrackIndex]);
            if (playerState.isPlaying) playSong();
        }

        // Event listeners
        playPauseBtn.addEventListener('click', () => {
            if (playerState.isPlaying) {
                pauseSong();
            } else {
                playSong();
            }
        });

        nextBtn.addEventListener('click', nextSong);
        prevBtn.addEventListener('click', prevSong);

        // Progress bar click
        progressBar.addEventListener('click', (e) => {
            const width = progressBar.clientWidth;
            const clickX = e.offsetX;
            const duration = audioElement.duration;
            audioElement.currentTime = (clickX / width) * duration;
        });

        // Volume control
        volumeControl.addEventListener('input', () => {
            audioElement.volume = volumeControl.value;
        });

        // Time update
        audioElement.addEventListener('timeupdate', updateProgress);

        // Song ended
        audioElement.addEventListener('ended', nextSong);

        // Function to play an album
        function playAlbum(albumId) {
            // Simulamos los datos del álbum
            const albums = {
                1: [ // Thank U, Next
                    {
                        id: 1,
                        titulo: "imagine",
                        artista: "Ariana Grande",
                        duracion: "3:32",
                        portada: "https://upload.wikimedia.org/wikipedia/en/d/dd/Thank_U%2C_Next_album_cover.png",
                        audio: "assets/ariana_imagine.mpeg",
                        genero: "Pop"
                    },
                    {
                        id: 2,
                        titulo: "needy",
                        artista: "Ariana Grande",
                        duracion: "2:51",
                        portada: "https://upload.wikimedia.org/wikipedia/en/d/dd/Thank_U%2C_Next_album_cover.png",
                        audio: "assets/ariana_needy.mpeg",
                        genero: "Pop"
                    },
                     {
                        id: 3,
                        titulo: "NASA",
                        artista: "Ariana Grande",
                        duracion: "3:02",
                        portada: "https://upload.wikimedia.org/wikipedia/en/d/dd/Thank_U%2C_Next_album_cover.png",
                        audio: "assets/ariana_nasa.mpeg",
                        genero: "Pop"
                    }
                ],
                5: [ // Positions
                    {
                        id: 4,
                        titulo: "shut up",
                        artista: "Ariana Grande",
                        duracion: "2:38",
                        portada: "https://upload.wikimedia.org/wikipedia/en/a/a0/Ariana_Grande_-_Positions.png",
                        audio: "assets/ariana_shut_up.mp3",
                        genero: "Pop"
                    },
                    {
                        id: 5,
                        titulo: "34+35",
                        artista: "Ariana Grande",
                        duracion: "2:54",
                        portada: "https://upload.wikimedia.org/wikipedia/en/a/a0/Ariana_Grande_-_Positions.png",
                        audio: "assets/ariana_34_35.mpeg",
                        genero: "Pop"
                    },
                    {
                        id: 6,
                        titulo: "motive (with Doja Cat)",
                        artista: "Ariana Grande",
                        duracion: "2:54",
                        portada: "https://upload.wikimedia.org/wikipedia/en/a/a0/Ariana_Grande_-_Positions.png",
                        audio: "assets/ariana_motive.mpeg",
                        genero: "Pop"
                    }

                ],
                4: [ // My Everything
                    {
                        id: 7,
                        titulo: "Intro",
                        artista: "Ariana Grande",
                        duracion: "3:25",
                        portada: "https://i.discogs.com/c9E4xV1FLWiccOemNVrVI61TIIStFffBvrodxXf0t-U/rs:fit/g:sm/q:40/h:300/w:300/czM6Ly9kaXNjb2dz/LWRhdGFiYXNlLWlt/YWdlcy9SLTcxMzg1/NjQtMTQzNDU3MTU5/Ni00OTEwLnBuZw.jpeg",
                        audio: "assets/ariana_intro.mp3",
                        genero: "Pop"
                    },
                    {
                        id: 8,
                        titulo: "Problem (feat. Iggy Azalea)",
                        artista: "Ariana Grande",
                        duracion: "3:17",
                        portada: "https://i.discogs.com/c9E4xV1FLWiccOemNVrVI61TIIStFffBvrodxXf0t-U/rs:fit/g:sm/q:40/h:300/w:300/czM6Ly9kaXNjb2dz/LWRhdGFiYXNlLWlt/YWdlcy9SLTcxMzg1/NjQtMTQzNDU3MTU5/Ni00OTEwLnBuZw.jpeg",
                        audio: "assets/ariana_problem.mp3",
                        genero: "Pop"
                    }
                ],
                // ... otros álbumes
            };

            if (albums[albumId]) {
                playerState.currentPlaylist = albums[albumId];
                playerState.currentTrackIndex = 0;
                playerState.currentAlbum = albumId;
                loadSong(albums[albumId][0]);
                playSong();
            }
        }

        // Add click handler to album rows
        document.querySelectorAll('.album-row').forEach(row => {
            const albumId = row.getAttribute('data-album-id');
            row.addEventListener('click', function(e) {
                // Solo si no se hizo clic en el botón de play
                if (!e.target.classList.contains('play-button')) {
                    playAlbum(albumId);
                }
            });
            
            // Handler para el botón de play
            const playBtn = row.querySelector('.play-button');
            playBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                playAlbum(albumId);
            });
        });
        
        // Add click handler to playlist cards
        document.querySelectorAll('.playlist-card').forEach(card => {
            card.addEventListener('click', function() {
                // Simular reproducción de playlist
                const playlistTitle = this.querySelector('h4').textContent;
                alert(`Reproduciendo: ${playlistTitle}`);
            });
        });
    </script>
</body>
</html>