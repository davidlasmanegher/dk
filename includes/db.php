<?php
/**
 * Conexión PDO a MySQL y acceso a configuración.
 */

function cfg(): array {
    static $config = null;
    if ($config === null) {
        $path = __DIR__ . '/../config.php';
        if (!file_exists($path)) {
            http_response_code(500);
            die('Falta config.php. Copia config.sample.php a config.php y ajusta tus datos.');
        }
        $config = require $path;
    }
    return $config;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $c   = cfg();
        $dsn = "mysql:host={$c['db_host']};port={$c['db_port']};dbname={$c['db_name']};charset=utf8mb4";
        $tz  = $c['db_timezone'] ?? '-06:00';
        $pdo = new PDO($dsn, $c['db_user'], $c['db_pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '{$tz}'",
        ]);
    }
    return $pdo;
}

/** Conexión sin base de datos seleccionada (para el instalador). */
function db_server(): PDO {
    $c   = cfg();
    $dsn = "mysql:host={$c['db_host']};port={$c['db_port']};charset=utf8mb4";
    return new PDO($dsn, $c['db_user'], $c['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
}

/** Lee un valor de settings. */
function setting(string $key, $default = null) {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (db()->query("SELECT skey, svalue FROM settings") as $row) {
                $cache[$row['skey']] = $row['svalue'];
            }
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return array_key_exists($key, $cache) ? $cache[$key] : $default;
}

/** Guarda/actualiza un valor de settings. */
function set_setting(string $key, string $value): void {
    $st = db()->prepare("INSERT INTO settings (skey, svalue) VALUES (?, ?)
                         ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)");
    $st->execute([$key, $value]);
    // Limpiar caché estático para que la próxima llamada a setting() lo relea.
    static $cache;
    $cache = null;
}

/** Arranca la aplicación: zona horaria, encoding, sesión. */
function boot(): void {
    require_once __DIR__ . '/helpers.php';
    $c = cfg();
    if (!empty($c['timezone'])) {
        date_default_timezone_set($c['timezone']);
    }
    mb_internal_encoding('UTF-8');
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
