<?php
/** Arranque comum a todas as páginas. */

define('NC_ROOT', dirname(__DIR__));
$GLOBALS['nc_config'] = require __DIR__ . '/config.php';
define('NC_DEBUG', getenv('APP_DEBUG') === '1');

error_reporting(E_ALL);
ini_set('display_errors', NC_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
date_default_timezone_set('Europe/Lisbon');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/calc.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/results.php';
require_once __DIR__ . '/layout.php';

header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'none'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");

function nc_fatal(Throwable $e): void
{
    error_log('NextChapter: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    $isDb = $e instanceof PDOException;
    $msg = $isDb ? t('err_db') : t('err_server');
    echo '<!doctype html><html lang="' . e(nc_lang()) . '"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1"><title>NextChapter</title>'
        . '<style>body{font-family:system-ui,sans-serif;background:#05080c;color:#eaf4fb;display:grid;place-items:center;min-height:100vh;margin:0;padding:16px}'
        . '.b{max-width:480px;border:1px solid #1c2a36;border-radius:16px;padding:24px;background:#0d1319}h1{color:#4cc2ff;margin-top:0}'
        . 'a{color:#4cc2ff}code{font-size:.85em;color:#93a4b3}</style></head><body><div class="b"><h1>NextChapter</h1><p>'
        . e($msg) . '</p>';
    if (NC_DEBUG) {
        echo '<p><code>' . e($e->getMessage()) . '</code></p>';
    }
    echo '<p><a href="index.php">' . e(t('back_home')) . '</a></p></div></body></html>';
}

set_exception_handler(function (Throwable $e) {
    nc_fatal($e);
});

// Sessões em MySQL (funciona em XAMPP e em serverless).
if (PHP_SAPI !== 'cli') {
    $life = 60 * 60 * 24 * 30;
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) $life);
    session_name('ncsid');
    session_set_cookie_params([
        'lifetime' => $life,
        'path'     => '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_set_save_handler(new NcSessionHandler(), true);
    session_start();
}
