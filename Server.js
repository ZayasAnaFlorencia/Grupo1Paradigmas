const express = require('express');
const mysql = require('mysql2/promise');
const cors = require('cors');

const app = express();
app.use(cors());
app.use(express.json());

// Conexión a tu base de datos MySQL (usa tus datos)
const pool = mysql.createPool({
  host: 'localhost',
  user: 'tu_usuario',
  password: 'tu_contraseña',
  database: 'streaming_recommendation',
  port: 3307
});

// Ruta para obtener canciones
app.get('/api/canciones', async (req, res) => {
  try {
    const [rows] = await pool.query('SELECT * FROM canciones');
    res.json(rows);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Iniciar servidor
const PORT = 3001;
app.listen(PORT, () => {
  console.log(`API corriendo en http://localhost:${PORT}`);
});