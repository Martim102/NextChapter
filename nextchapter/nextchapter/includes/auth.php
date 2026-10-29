<?php
/** Autenticação. */

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $id = (int) ($_SESSION['uid'] ?? 0);
    if ($id <= 0) {
        return $user = null;
    }
    $st = db()->prepare('SELECT id, name, email FROM users WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) {
        unset($_SESSION['uid']);
        return $user = null;
    }
    return $user = $row;
}

function login_user(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $id;
    nc_cookie('nc_seen', '1', 1825); // já usou o site: da próxima vez vai para "Entrar"
}

function logout_user(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

/**
 * Exige sessão iniciada. Na PRIMEIRA vez (sem cookie nc_seen) envia para criar conta;
 * quem já tem conta vai para o login.
 */
function require_login(): array
{
    $u = current_user();
    if ($u) {
        return $u;
    }
    flash('info', t('login_required'));
    redirect(empty($_COOKIE['nc_seen']) ? 'register.php' : 'login.php');
}
