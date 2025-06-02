<?php
session_start();

// Database connection (same as in personalizar_perfil.php)
$host = 'localhost';
$db   = 'soundscouts';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login_soundscouts.php');
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Handle friend request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['friend_id'])) {
    $friend_id = (int)$_POST['friend_id'];
    
    try {
        // Check if friendship already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM amistades 
                              WHERE (usuario_id1 = ? AND usuario_id2 = ?) 
                              OR (usuario_id1 = ? AND usuario_id2 = ?)");
        $stmt->execute([$current_user_id, $friend_id, $friend_id, $current_user_id]);
        $exists = $stmt->fetchColumn();
        
        if (!$exists) {
            // Create friendship
            $stmt = $pdo->prepare("INSERT INTO amistades (usuario_id1, usuario_id2) VALUES (?, ?)");
            $stmt->execute([$current_user_id, $friend_id]);
            
            $_SESSION['success_message'] = "¡Ahora sois amigos!";
        } else {
            $_SESSION['error_message'] = "Ya sois amigos";
        }
        
        header("Location: amistades_recomendadas.php");
        exit();
    } catch (PDOException $e) {
        die("Error al procesar la solicitud de amistad: " . $e->getMessage());
    }
}

// Get recommended friends (users who are not already friends)
$recommended_friends = [];
try {
    $query = "
        SELECT u.id, u.nombre, u.apodo, u.foto_perfil 
        FROM usuarios u
        WHERE u.id != ? 
        AND u.id NOT IN (
            SELECT usuario_id2 FROM amistades WHERE usuario_id1 = ?
            UNION
            SELECT usuario_id1 FROM amistades WHERE usuario_id2 = ?
        )
        ORDER BY RAND() 
        LIMIT 5
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$current_user_id, $current_user_id, $current_user_id]);
    $recommended_friends = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error al obtener recomendaciones: " . $e->getMessage());
}

// Get current user's info for display
$current_user = [];
try {
    $stmt = $pdo->prepare("SELECT nombre, apodo, foto_perfil FROM usuarios WHERE id = ?");
    $stmt->execute([$current_user_id]);
    $current_user = $stmt->fetch();
} catch (PDOException $e) {
    die("Error al obtener información del usuario: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amistades recomendadas - SoundScouts</title>
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
            padding: 20px;
            color: white;
        }

        .container {
            width: 100%;
            max-width: 800px;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #08d;
            border-radius: 20px;
            padding: 30px;
            backdrop-filter: blur(8.5px);
            box-shadow: 0 0 20px rgba(0, 136, 221, 0.3);
            animation: float 6s ease-in-out infinite;
        }

        h1 {
            color: #08d;
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 0 0 10px rgba(0, 136, 221, 0.5);
        }

        .welcome-message {
            text-align: center;
            margin-bottom: 30px;
        }

        .welcome-message h2 {
            color: #08d;
            margin-bottom: 10px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #08d;
            margin-right: 20px;
        }

        .user-info {
            text-align: left;
        }

        .user-name {
            font-size: 1.5em;
            color: #08d;
        }

        .friends-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .friend-card {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(0, 136, 221, 0.5);
            border-radius: 15px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .friend-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 136, 221, 0.3);
        }

        .friend-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #08d;
            margin-bottom: 15px;
        }

        .friend-name {
            font-size: 1.1em;
            margin-bottom: 5px;
            color: #08d;
            text-align: center;
        }

        .friend-nickname {
            font-size: 0.9em;
            color: #aaa;
            margin-bottom: 15px;
            text-align: center;
        }

        .friend-button {
            background: rgba(0, 100, 200, 0.2);
            border: 2px solid #08d;
            border-radius: 20px;
            color: white;
            padding: 8px 15px;
            cursor: pointer;
            transition: 0.3s;
            font-size: 0.9em;
            width: 100%;
            text-align: center;
        }

        .friend-button:hover {
            background: rgba(0, 100, 200, 0.4);
            box-shadow: 0 0 10px #08d;
        }

        .buttons {
            display: flex;
            justify-content: center;
            margin-top: 40px;
        }

        .btn {
            padding: 10px 25px;
            border-radius: 20px;
            cursor: pointer;
            transition: 0.3s;
            font-size: 1em;
            text-decoration: none;
        }

        .btn-skip {
            background: transparent;
            border: 2px solid #666;
            color: #666;
        }

        .btn-skip:hover {
            border-color: #aaa;
            color: #aaa;
        }

        .btn-continue {
            background: rgba(0, 100, 200, 0.2);
            border: 2px solid #08d;
            color: white;
            margin-left: 15px;
        }

        .btn-continue:hover {
            background: rgba(0, 100, 200, 0.4);
            box-shadow: 0 0 15px #08d;
        }

        .message {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            border-radius: 10px;
        }

        .success {
            background-color: rgba(46, 213, 115, 0.2);
            color: #2ed573;
            border: 1px solid #2ed573;
        }

        .error {
            background-color: rgba(255, 71, 87, 0.2);
            color: #ff4757;
            border: 1px solid #ff4757;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Amistades Recomendadas</h1>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message success"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php elseif (isset($_SESSION['error_message'])): ?>
            <div class="message error"><?= htmlspecialchars($_SESSION['error_message']) ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <div class="welcome-message">
            <h2>¡Hola, <?= htmlspecialchars($current_user['apodo'] ?? $current_user['nombre']) ?>!</h2>
            <p>Conecta con otros usuarios que comparten tu amor por la música</p>
        </div>
        
        <div class="user-profile">
            <?php if (!empty($current_user['foto_perfil'])): ?>
                <img src="<?= htmlspecialchars($current_user['foto_perfil']) ?>" alt="Tu foto de perfil" class="user-avatar">
            <?php else: ?>
                <div class="user-avatar" style="display: flex; align-items: center; justify-content: center; background-color: #333; font-size: 2em;">👤</div>
            <?php endif; ?>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($current_user['nombre']) ?></div>
                <div><?= htmlspecialchars($current_user['apodo'] ?? '') ?></div>
            </div>
        </div>
        
        <?php if (!empty($recommended_friends)): ?>
            <h3 style="text-align: center; color: #08d; margin-bottom: 20px;">Personas que podrías conocer</h3>
            
            <div class="friends-list">
                <?php foreach ($recommended_friends as $friend): ?>
                    <div class="friend-card">
                        <?php if (!empty($friend['foto_perfil'])): ?>
                            <img src="<?= htmlspecialchars($friend['foto_perfil']) ?>" alt="Foto de perfil" class="friend-avatar">
                        <?php else: ?>
                            <div class="friend-avatar" style="display: flex; align-items: center; justify-content: center; background-color: #333; font-size: 2em;">👤</div>
                        <?php endif; ?>
                        
                        <div class="friend-name"><?= htmlspecialchars($friend['nombre']) ?></div>
                        
                        <?php if (!empty($friend['apodo'])): ?>
                            <div class="friend-nickname">@<?= htmlspecialchars($friend['apodo']) ?></div>
                        <?php endif; ?>
                        
                        <form method="post" style="width: 100%;">
                            <input type="hidden" name="friend_id" value="<?= htmlspecialchars($friend['id']) ?>">
                            <button type="submit" class="friend-button">Hacer amigo</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 20px; color: #aaa;">
                No hay recomendaciones de amistades disponibles en este momento.
            </div>
        <?php endif; ?>
        
        <div class="buttons">
            <a href="inicio.php" class="btn btn-skip">Saltar</a>
            <a href="inicio.php" class="btn btn-continue">Continuar a Inicio</a>
        </div>
    </div>
</body>
</html>