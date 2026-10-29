<?php
require __DIR__ . '/includes/bootstrap.php';
if (current_user()) {
    redirect('dashboard.php');
}

$errors = [];
$old = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $errors['form'] = t('err_csrf');
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $pw = (string) ($_POST['password'] ?? '');
        $pw2 = (string) ($_POST['password2'] ?? '');
        $old = ['name' => $name, 'email' => $email];

        $len = mb_strlen($name);
        if ($len < 2 || $len > 80) {
            $errors['name'] = t('err_name');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $errors['email'] = t('err_email');
        }
        if (strlen($pw) < 8) {
            $errors['password'] = t('err_pw_short');
        } elseif (strlen($pw) > 128) {
            $errors['password'] = t('err_pw_long');
        } elseif (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) {
            $errors['password'] = t('err_pw_weak');
        }
        if ($pw !== $pw2) {
            $errors['password2'] = t('err_pw_match');
        }

        if (!$errors) {
            $st = db()->prepare('SELECT id FROM users WHERE email = ?');
            $st->execute([$email]);
            if ($st->fetch()) {
                $errors['email'] = t('err_email_taken');
            } else {
                try {
                    db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)')
                        ->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT)]);
                    $id = (int) db()->lastInsertId();
                    login_user($id);
                    flash('success', t('welcome_new'));
                    redirect('calculator.php');
                } catch (PDOException $ex) {
                    if ((string) $ex->getCode() === '23000') {
                        $errors['email'] = t('err_email_taken');
                    } else {
                        throw $ex;
                    }
                }
            }
        }
    }
}

function auth_field(string $name, string $label, string $type, string $value, array $errors, string $autocomplete, string $hint = ''): void
{
    $err = $errors[$name] ?? null;
    echo '<div class="field' . ($err ? ' has-error' : '') . '"><label for="' . e($name) . '">' . e($label) . '</label>'
        . '<input id="' . e($name) . '" name="' . e($name) . '" type="' . e($type) . '" value="' . e($value) . '" required'
        . ' autocomplete="' . e($autocomplete) . '"' . ($err ? ' aria-invalid="true" aria-describedby="' . e($name) . '-e"' : '') . '>';
    if ($hint !== '') {
        echo '<small class="hint">' . e($hint) . '</small>';
    }
    if ($err) {
        echo '<small class="error" id="' . e($name) . '-e">' . e($err) . '</small>';
    }
    echo '</div>';
}

render_header(t('register_title'), 'register');
?>
<div class="container auth">
  <div class="card auth-card">
    <h1><?= e(t('register_title')) ?></h1>
    <p class="muted"><?= e(t('register_sub')) ?></p>
    <?php if (empty($_COOKIE['nc_seen'])): ?>
      <div class="alert alert-info"><?= e(t('first_time')) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors['form'])): ?>
      <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
    <?php endif; ?>
    <form method="post" action="register.php" novalidate>
      <?= csrf_field() ?>
      <?php auth_field('name', t('f_name'), 'text', $old['name'], $errors, 'name'); ?>
      <?php auth_field('email', t('f_email'), 'email', $old['email'], $errors, 'email'); ?>
      <?php auth_field('password', t('f_password'), 'password', '', $errors, 'new-password', t('pw_hint')); ?>
      <?php auth_field('password2', t('f_password2'), 'password', '', $errors, 'new-password'); ?>
      <button class="btn btn-primary btn-block" type="submit"><?= e(t('btn_register')) ?></button>
    </form>
    <p class="muted center-text"><?= e(t('have_account')) ?> <a href="login.php"><?= e(t('nav_login')) ?></a></p>
  </div>
</div>
<?php render_footer();
