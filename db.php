<?php
// ══════════════════════════════════════════════
//  ECOMUNDO · Conexión a la base de datos
//  Archivo: db.php
//  Coloca este archivo en la misma carpeta que index.html
// ══════════════════════════════════════════════

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Usuario de XAMPP (por defecto es 'root')
define('DB_PASS', '');           // Contraseña de XAMPP (por defecto está vacía)
define('DB_NAME', 'ecomundo');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['ok' => false, 'msg' => 'Error de conexión: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// Cabeceras CORS para que el fetch() de index.html funcione
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
