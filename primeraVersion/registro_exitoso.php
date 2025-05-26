<?php
// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar si se proporcionó un ID válido
$user_id = $_GET['id'] ?? 0;

// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "soundscouts";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Obtener datos del usuario recién registrado
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$user) {
    die("Usuario no encontrado");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro Exitoso</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: url('https://pbs.twimg.com/media/F4FZIZebkAULKbo.jpg:large') no-repeat;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: white;
        }
        .success-container {
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            max-width: 500px;
        }
        .success-icon {
            font-size: 80px;
            color: #2ed573;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: #1DA1F2;
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .debug-info {
            background: rgba(0,0,0,0.7);
            padding: 20px;
            margin-top: 20px;
            border-radius: 10px;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1>¡Registro Exitoso!</h1>
        <p>Bienvenido a SOUNDSCOUTS, <?php echo htmlspecialchars($user['nombre']); ?>.</p>
        <p>Tu cuenta con el email <?php echo htmlspecialchars($user['email']); ?> ha sido creada correctamente.</p>
        
        <a href="login_soundscouts.php" class="btn">Iniciar Sesión</a>
        
        <!-- Solo para depuración -->
        <div class="debug-info">
            <h3>Información de depuración:</h3>
            <p>ID de usuario: <?php echo $user['id']; ?></p>
            <p>Fecha de registro: <?php echo $user['fecha_registro']; ?></p>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
