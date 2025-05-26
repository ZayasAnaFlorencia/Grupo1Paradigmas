<?php
require_once 'codigos_soundscouts.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login_soundscouts.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reproductor - SOUNDSCOUTS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: url('https://pbs.twimg.com/media/F4FZIZebkAULKbo.jpg:large') no-repeat;
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .player-container {
            max-width: 800px;
            margin: 120px auto 50px;
            padding: 30px;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .now-playing {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .album-cover {
            width: 200px;
            height: 200px;
            border-radius: 10px;
            object-fit: cover;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .song-info h2 {
            font-size: 1.8em;
            margin-bottom: 10px;
        }

        .song-info p {
            font-size: 1.2em;
            opacity: 0.8;
            margin-bottom: 20px;
        }

        .controls {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 30px 0;
        }

        .control-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            font-size: 1.5em;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .control-btn:hover {
            background: rgba(29, 161, 242, 0.5);
            transform: scale(1.1);
        }

        .play-btn {
            width: 80px;
            height: 80px;
            background: #1DA1F2;
            font-size: 2em;
        }

        .queue {
            margin-top: 40px;
        }

        .queue h3 {
            font-size: 1.5em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .queue-list {
            list-style: none;
            padding: 0;
        }

        .queue-item {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }

        .queue-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .queue-item .song {
            flex-grow: 1;
        }

        .queue-item .song h4 {
            margin-bottom: 5px;
        }

        .queue-item .actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 1.2em;
            opacity: 0.7;
            transition: all 0.3s;
        }

        .action-btn:hover {
            opacity: 1;
            color: #1DA1F2;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background: rgba(29, 161, 242, 0.2);
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="player-container">
        <?php if (isset($mensaje)): ?>
            <div class="message"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="now-playing">
            <?php if ($proximaCancion): ?>
                <img src="https://via.placeholder.com/300" alt="Album Cover" class="album-cover">
                <div class="song-info">
                    <h2><?= htmlspecialchars($proximaCancion['titulo']) ?></h2>
                    <p><?= htmlspecialchars($proximaCancion['artista']) ?></p>
                    <form method="post">
                        <button type="submit" name="reproducir" class="control-btn play-btn">
                            <i class="fas fa-play"></i>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <p>No hay canciones en reproducción</p>
            <?php endif; ?>
        </div>

        <div class="controls">
            <button class="control-btn"><i class="fas fa-step-backward"></i></button>
            <button class="control-btn"><i class="fas fa-pause"></i></button>
            <button class="control-btn"><i class="fas fa-step-forward"></i></button>
            <button class="control-btn"><i class="fas fa-random"></i></button>
            <button class="control-btn"><i class="fas fa-redo"></i></button>
        </div>

        <div class="queue">
            <h3><i class="fas fa-list"></i> Cola de reproducción</h3>
            
            <?php if (!empty($cancionesEnCola)): ?>
                <ul class="queue-list">
                    <?php foreach ($cancionesEnCola as $cancion): ?>
                        <li class="queue-item">
                            <div class="song">
                                <h4><?= htmlspecialchars($cancion['titulo']) ?></h4>
                                <p><?= htmlspecialchars($cancion['artista']) ?></p>
                            </div>
                            <div class="actions">
                                <button class="action-btn"><i class="fas fa-play"></i></button>
                                <button class="action-btn"><i class="fas fa-times"></i></button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <form method="post" style="margin-top: 20px;">
                    <button type="submit" name="vaciar" class="control-btn" style="background: rgba(255, 71, 87, 0.2);">
                        <i class="fas fa-trash"></i> Vaciar cola
                    </button>
                </form>
            <?php else: ?>
                <p>No hay canciones en la cola</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>