<?php
/**
 * Runner diario del agente DK — para ejecutarse por cron (CLI).
 * Planifica tareas y las procesa en modo híbrido (sugiere primeros contactos,
 * envía seguimientos). NO accesible por web: solo línea de comandos.
 *
 * Cron sugerido (cPanel, usuario tools) — 7:00 México (13:00 UTC):
 *   0 13 * * * /opt/cpanel/ea-php74/root/usr/bin/php /home/tools/public_html/dk/cron/agent_run.php >> /home/tools/dk_cron.log 2>&1
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("Solo CLI\n"); }

require_once __DIR__ . '/../includes/agent.php';
require_once __DIR__ . '/../includes/campaigns.php';

// 1) Campañas activas: cada una selecciona los N leads del día de su foco y deja
//    el primer contacto como borrador para aprobar (no envía nada solo).
$camp = campaign_run_all();
$campPrepared = array_sum(array_map(function ($x) { return (int)$x['prepared']; }, $camp));

// 2) Modo controlado: procesa lo ya programado (pasos de secuencias inscritas,
//    seguimientos vencidos). NO auto-planifica los 1.820 prospectos.
$res = agent_run();

fwrite(STDOUT, sprintf(
    "[%s] campañas=%d borradores=%d | procesadas=%d sugeridas=%d enviadas=%d fallidas=%d\n",
    date('c'), count($camp), $campPrepared, $res['processed'], $res['suggested'], $res['sent'], $res['failed']
));
