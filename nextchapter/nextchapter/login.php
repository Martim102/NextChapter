<?php
require __DIR__ . '/includes/bootstrap.php';
if (current_user()) {
    redirect('dashboard.php');
}

// Hash falso para manter o tempo de resposta igual quando o email não existe.
const NC_DUMMY_HASH = '$2y$10$RvQxWs6roxEmf/x0lfBWoe.c2tD79LTGm7kf0ymev3oe5OJSHDsBu';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = t('err_csrf');
    } else {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $pw = (string) ($_POST['password'] ?? '');
        $st = db()->prepare('SELECT id, password_hash, failed_logins, locked_until FROM users WHERE email = ?');
        $st->execute([$email]);
        $u = $st->fetch();

        if ($u && (int) $u['locked_until'] > time()) {
            $error = t('err_locked');
        } elseif ($u && password_verify($pw, $u['password_hash'])) {
            db()->prepare('UPDATE users SET failed_logins = 0, locked_until = 0 WHERE id = ?')->execute([$u['id']]);
            if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash($pw, PASSWORD_DEFAULT), $u['id']]);
            }
            login_user((int) $u['id']);
            flash('success', t('welcome_back'));
            redirect('dashboard.php');
        } else {
            if ($u) {
                $f = (int) $u['failed_logins'] + 1;
                $lock = $f >= 5 ? time() + 900 : 0;
                db()->prepare('UPDATE users SET failed_logins = ?, locked_until = ? WHERE id = ?')
                    ->execute([$lock ? 0 : $f, $lock, $u['id']]);
            } else {
                password_verify($pw, NC_DUMMY_HASH);
            }
            $error = t('err_login');
        }
    }
}

render_header(t('login_title'), 'login');
?>
<div class="container auth">
  <div class="card auth-card">
    <h1><?= e(t('login_title')) ?></h1>
    <p class="muted"><?= e(t('login_sub')) ?></p>
    <?php if ($error !== ''): ?>
      <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="login.php" novalidate>
      <?= csrf_field() ?>
      <div class="field">
        <label for="email"><?= e(t('f_email')) ?></label>
        <input id="email" name="email" type="email" value="<?= e($email) ?>" required autocomplete="email">
      </div>
      <div class="field">
        <label for="password"><?= e(t('f_password')) ?></label>
        <input id="password" name="password" type="password" required autocomplete="current-password">
      </div>
      <button class="btn btn-primary btn-block" type="submit"><?= e(t('btn_login')) ?></button>
    </form>
    <p class="muted center-text"><?= e(t('no_account')) ?> <a href="register.php"><?= e(t('nav_register')) ?></a></p>
  </div>
</div>
<?php render_footer();
