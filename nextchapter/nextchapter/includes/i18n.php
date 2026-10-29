<?php
/** Idiomas: pt, en, es. */

const NC_LANGS = ['pt' => 'Português', 'en' => 'English', 'es' => 'Español'];

function nc_lang(): string
{
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }
    $c = $_COOKIE['nc_lang'] ?? '';
    if (is_string($c) && isset(NC_LANGS[$c])) {
        return $lang = $c;
    }
    $h = strtolower((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
    if (preg_match_all('/(?:^|,)\s*([a-z]{2})/', $h, $m)) {
        foreach ($m[1] as $code) {
            if (isset(NC_LANGS[$code])) {
                return $lang = $code;
            }
        }
    }
    return $lang = 'pt';
}

function nc_dict(string $lang): array
{
    static $cache = [];
    if (!isset($cache[$lang])) {
        $cache[$lang] = require NC_ROOT . '/lang/' . $lang . '.php';
    }
    return $cache[$lang];
}

function t(string $key, ...$args): string
{
    $d = nc_dict(nc_lang());
    $s = $d[$key] ?? (nc_dict('pt')[$key] ?? $key);
    return $args ? vsprintf($s, $args) : $s;
}

function nc_theme(): string
{
    $c = $_COOKIE['nc_theme'] ?? '';
    return $c === 'dark' ? 'dark' : 'light';
}
