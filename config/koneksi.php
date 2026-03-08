<?php
/**
 * Database Connection - PDO with Prepared Statements
 * Sistem Reservasi Studio & Alat Siaran TVRI
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'reservasi_tvri');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die('<div style="text-align:center;margin-top:100px;font-family:sans-serif;">
         <h2>Koneksi Database Gagal</h2>
         <p>Pastikan MySQL aktif dan database <code>reservasi_tvri</code> sudah dibuat.</p>
         </div>');
}
