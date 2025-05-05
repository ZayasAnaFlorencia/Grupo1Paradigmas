<?php
//  Inicia la sesión para poder usar $_SESSION.
//  Esto permite guardar datos entre peticiones HTTP. 
session_start();

//  Verifica si la clave 'cola' no está definida en la sesión.
//  Si no está definida, la inicializa como un arreglo vacío.
//  Esto se hace para crear la estructura donde se guardarán las canciones. 
if (!isset($_SESSION['cola'])) {
    $_SESSION['cola'] = [];
}

// Inicializa una variable para mostrar la canción actual reproducida.
$cancionReproducida = null;

// Si el método de la petición es POST (es decir, el usuario envió un formulario)...
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verifica si se envió una canción a través del formulario.
    if (isset($_POST['cancion'])) {
        // Elimina espacios al inicio y al final del nombre de la canción.
        $cancion = trim($_POST['cancion']);

        // Si el texto ingresado no está vacío, lo agrega a la cola.
        if (!empty($cancion)) {
            // Añade la canción al final del arreglo 'cola' dentro de la sesión.
            $_SESSION['cola'][] = $cancion;
        }

        // Redirige al mismo archivo para evitar reenvío del formulario al refrescar.
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Si se presionó el botón de "Reproducir siguiente canción".
    if (isset($_POST['reproducir'])) {
        // Si la cola no está vacía...
        if (!empty($_SESSION['cola'])) {
            // Extrae la primera canción.
            // array_shift remueve y devuelve el primer elemento del array.
            $_SESSION['reproducida'] = array_shift($_SESSION['cola']);
        }

        // Redirige para evitar reenvío del formulario.
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Si se presionó el botón para vaciar la cola.
    if (isset($_POST['vaciar'])) {
        // Vacia la cola actual (elimina todas las canciones).
        $_SESSION['cola'] = [];

        // Elimina también la canción que estaba siendo reproducida, si existía.
        unset($_SESSION['reproducida']);

        // Redirige al mismo archivo.
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Si hay una canción reproducida almacenada en la sesión, la recupera.
if (isset($_SESSION['reproducida'])) {
    $cancionReproducida = $_SESSION['reproducida'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cola de Reproducción</title>
</head>
<body>
    <h1>Cola de Próximas Canciones</h1>

    <!-- Si hay una canción actualmente reproducida, la muestra en pantalla. -->
    <?php if ($cancionReproducida): ?>
        <p><strong>🎶 Reproduciendo ahora:</strong> <?= htmlspecialchars($cancionReproducida) ?></p>
    <?php endif; ?>

    <!-- Formulario para agregar una nueva canción a la cola -->
    <form method="post">
        <!-- Campo de entrada para escribir el nombre de la canción -->
        <input type="text" name="cancion" placeholder="Nombre de la canción" required>
        <!-- Botón para enviar la canción -->
        <button type="submit">Agregar a la cola</button>
    </form>

    <!-- Formulario con botón para reproducir la siguiente canción -->
    <form method="post" style="margin-top: 10px;">
        <button type="submit" name="reproducir">Reproducir siguiente canción</button>
    </form>

    <!-- Formulario con botón para vaciar la cola actual -->
    <form method="post" style="margin-top: 10px;">
        <!-- Al hacer clic, aparece una alerta de confirmación -->
        <button type="submit" name="vaciar" onclick="return confirm('¿Seguro que querés vaciar la cola?')">Vaciar cola</button>
    </form>

    <!-- Sección para mostrar todas las canciones que están en la cola -->
    <h2>Canciones en espera:</h2>
    <?php if (!empty($_SESSION['cola'])): ?>
        <ol>
            <!-- Recorre y muestra cada canción en la cola en una lista ordenada -->
            <?php foreach ($_SESSION['cola'] as $c): ?>
                <li><?= htmlspecialchars($c) ?></li>
            <?php endforeach; ?>
        </ol>
    <?php else: ?>
        <!-- Si la cola está vacía -->
        <p>No hay canciones en la cola.</p>
    <?php endif; ?>
</body>
</html>