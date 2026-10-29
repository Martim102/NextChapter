<?php
/** Muda idioma ou tema (guardado em cookie) e volta à página anterior. */
require __DIR__ . '/includes/bootstrap.php';

$lang = $_GET['lang'] ?? '';
if (is_string($lang) && isset(NC_LANGS[$lang])) {
    nc_cookie('nc_lang', $lang);
}
$theme = $_GET['theme'] ?? '';
if ($theme === 'light' || $theme === 'dark') {
    nc_cookie('nc_theme', $theme);
}

$back = $_GET['back'] ?? 'index.php';
if (!is_string($back) || !preg_match('/^[a-z]+\.php(\?[A-Za-z0-9_=&%.\-]*)?$/', $back)) {
    $back = 'index.php';
}
redirect($back);
