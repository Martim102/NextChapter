<?php
/**
 * Entrada única para a Vercel (runtime vercel-php).
 * Encaminha os pedidos para as páginas PHP da raiz e serve os ficheiros de /assets.
 * Em XAMPP este ficheiro não é usado.
 */
$root = dirname(__DIR__);
$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$path = '/' . trim($path, '/');

if (strpos($path, '/assets/') === 0 && strpos($path, '..') === false) {
    $file = $root . $path;
    $types = ['css' => 'text/css; charset=utf-8', 'svg' => 'image/svg+xml', 'png' => 'image/png', 'ico' => 'image/x-icon', 'jpg' => 'image/jpeg'];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (is_file($file) && isset($types[$ext])) {
        header('Content-Type: ' . $types[$ext]);
        header('Cache-Control: public, max-age=86400');
        readfile($file);
        exit;
    }
}

$name = $path === '/' ? 'index' : basename($path, '.php');
$pages = ['index', 'login', 'register', 'logout', 'dashboard', 'calculator', 'view', 'set'];
if (in_array($name, $pages, true)) {
    chdir($root);
    require $root . '/' . $name . '.php';
    exit;
}

http_response_code(404);
echo '404';
