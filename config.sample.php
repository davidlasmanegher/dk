<?php
/**
 * Credenciales de conexión a MySQL.
 * Copiá este archivo a config.php y ajustá tus datos.
 * config.php NO se versiona (ver .gitignore).
 */
return [
    'db_host'     => '127.0.0.1',
    'db_port'     => '8889',       // MAMP default; cambiar a 3306 si usás MySQL nativo
    'db_name'     => 'dk_daniel',
    'db_user'     => 'root',
    'db_pass'     => 'root',
    'db_timezone' => '-06:00',     // México (UTC-6)
    'timezone'    => 'America/Mexico_City',
    'production'  => false,
];
