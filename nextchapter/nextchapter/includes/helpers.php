<?php
/** Funções auxiliares. */

function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function nc_cookie(string $name, string $value, int $days = 365): void
{
    setcookie($name, $value, [
        'expires'  => time() + $days * 86400,
        'path'     => '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[$name] = $value;
}

/* ---------- Mensagens flash ---------- */

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_take(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): bool
{
    $sent = $_POST['csrf'] ?? '';
    return is_string($sent) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $sent);
}

/* ---------- Formatação ---------- */

function nf(float $v, int $dec = 2): string
{
    $en = nc_lang() === 'en';
    $dp = $en ? '.' : ',';
    $s = number_format($v, $dec, $dp, $en ? ',' : '.');
    if ($dec > 0) {
        $s = rtrim(rtrim($s, '0'), $dp);
    }
    return $s;
}

function money(float $v, string $cur = 'EUR'): string
{
    if (!is_finite($v)) {
        return '—';
    }
    $sym = NC_CURRENCIES[$cur] ?? '€';
    $v = round($v);
    $neg = $v < 0;
    $abs = abs($v);
    if (nc_lang() === 'en') {
        $s = $sym . number_format($abs, 0, '.', ',');
    } else {
        $s = number_format($abs, 0, ',', '.') . "\u{00A0}" . $sym;
    }
    return ($neg ? '−' : '') . $s;
}

function money_short(float $v): string
{
    $a = abs($v);
    if ($a >= 1e9) {
        return nf($v / 1e9, 1) . 'B';
    }
    if ($a >= 1e6) {
        return nf($v / 1e6, 1) . 'M';
    }
    if ($a >= 1e3) {
        return nf($v / 1e3, 0) . 'k';
    }
    return nf($v, 0);
}

function fmt_date(string $dt): string
{
    $ts = strtotime($dt);
    if (!$ts) {
        return '';
    }
    return date(nc_lang() === 'en' ? 'M j, Y' : 'd/m/Y', $ts);
}
