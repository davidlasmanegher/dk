<?php
/**
 * Lectura de correo entrante por IMAP (CLI, para cron). Ingesta los no leídos a la bandeja.
 * Cron sugerido: cada 10 minutos. Comando del crontab:
 *   /opt/cpanel/ea-php74/root/usr/bin/php /home/tools/public_html/dk/cron/fetch_email.php >> /home/tools/dk_email.log 2>&1
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("Solo CLI\n"); }
require_once __DIR__ . '/../includes/imap_inbox.php';

$r = imap_inbox_fetch(30);
fwrite(STDOUT, sprintf("[%s] %s\n", date('c'), json_encode($r, JSON_UNESCAPED_UNICODE)));
