<?php
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$type = (string) ($_POST['type'] ?? $_GET['type'] ?? 'retirement');
if (!in_array($type, NC_TYPES, true)) {
    $type = 'retirement';
}

$form = nc_to_form(nc_defaults($type));
$errors = [];
$res = null;
$vals = null;

// "Editar como nova": pré-preenche com uma simulação guardada.
if (!$isPost && isset($_GET['copy'])) {
    $st = db()->prepare('SELECT type, inputs FROM simulations WHERE id = ? AND user_id = ?');
    $st->execute([(int) $_GET['copy'], $user['id']]);
    if ($row = $st->fetch()) {
        if (in_array($row['type'], NC_TYPES, true)) {
            $type = $row['type'];
            [$vv, $ee] = nc_parse($type, json_decode((string) $row['inputs'], true) ?: [], true);
            if (!$ee) {
                $form = nc_to_form($vv);
            }
        }
    }
}

if ($isPost) {
    if (!csrf_check()) {
        $errors['_form'] = t('err_csrf');
    } else {
        foreach (array_keys(nc_defaults($type)) as $k) {
            $form[$k] = $k === 'indexed' ? (empty($_POST['indexed']) ? '0' : '1') : trim((string) ($_POST[$k] ?? ''));
        }
        [$vals, $errors] = nc_parse($type, $_POST);
        if (!$errors) {
            $res = nc_compute($type, $vals);
            if (($_POST['action'] ?? '') === 'save') {
                $title = $vals['title'] !== '' ? $vals['title'] : t('type_' . $type) . ' · ' . fmt_date(date('Y-m-d'));
                db()->prepare('INSERT INTO simulations (user_id, type, title, inputs) VALUES (?, ?, ?, ?)')
                    ->execute([$user['id'], $type, mb_substr($title, 0, 120), json_encode($vals, JSON_UNESCAPED_UNICODE)]);
                $newId = (int) db()->lastInsertId();
                flash('success', t('saved_ok'));
                redirect('view.php?id=' . $newId);
            }
        }
    }
}

function nc_field(string $name, string $label, array $form, array $errors, string $hint = '', string $suffix = '', string $mode = 'decimal'): void
{
    $id = 'f_' . $name;
    $err = $errors[$name] ?? null;
    echo '<div class="field' . ($err ? ' has-error' : '') . '"><label for="' . e($id) . '">' . e($label) . '</label>'
        . '<div class="input-wrap"><input id="' . e($id) . '" name="' . e($name) . '" type="text" inputmode="' . e($mode) . '"'
        . ' value="' . e($form[$name] ?? '') . '" autocomplete="off"'
        . ($err ? ' aria-invalid="true" aria-describedby="' . e($id) . '-e"' : '') . '>'
        . ($suffix !== '' ? '<span class="suffix">' . e($suffix) . '</span>' : '') . '</div>';
    if ($hint !== '') {
        echo '<small class="hint">' . e($hint) . '</small>';
    }
    if ($err) {
        echo '<small class="error" id="' . e($id) . '-e">' . e($err) . '</small>';
    }
    echo '</div>';
}

$sym = NC_CURRENCIES[$form['currency'] ?? 'EUR'] ?? '€';
$yrs = t('unit_years');

render_header(t('calc_title'), 'calculator', 'calculator.php?type=' . $type);
?>
<div class="container page">
  <h1><?= e(t('calc_title')) ?></h1>
  <p class="muted"><?= e(t('calc_sub')) ?></p>

  <nav class="pills" aria-label="<?= e(t('choose_goal')) ?>">
    <?php foreach (NC_TYPES as $ty): ?>
      <a class="pill" href="calculator.php?type=<?= $ty ?>"<?= $ty === $type ? ' aria-current="page"' : '' ?>><?= nc_icon($ty, 18) ?><span><?= e(t('type_' . $ty)) ?></span></a>
    <?php endforeach; ?>
  </nav>

  <form class="card form" method="post" action="calculator.php?type=<?= e($type) ?>" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="type" value="<?= e($type) ?>">

    <?php if ($errors): ?>
      <div class="alert alert-error" role="alert"><?= e($errors['_form'] ?? t('err_fix')) ?></div>
    <?php endif; ?>

    <fieldset>
      <legend><?= e(t('sec_goal')) ?></legend>
      <div class="cols">
        <?php nc_field('title', t('f_title'), $form, $errors, '', '', 'text'); ?>
        <div class="field">
          <label for="f_currency"><?= e(t('f_currency')) ?></label>
          <select id="f_currency" name="currency">
            <?php foreach (NC_CURRENCIES as $code => $s): ?>
              <option value="<?= e($code) ?>"<?= ($form['currency'] ?? '') === $code ? ' selected' : '' ?>><?= e($code . ' (' . $s . ')') ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if ($type === 'retirement'): ?>
          <?php nc_field('age', t('f_age'), $form, $errors, '', $yrs, 'numeric'); ?>
          <?php nc_field('retire_age', t('f_retire_age'), $form, $errors, '', $yrs, 'numeric'); ?>
          <?php nc_field('life', t('f_life'), $form, $errors, '', $yrs, 'numeric'); ?>
          <?php nc_field('income', t('f_income'), $form, $errors, t('f_income_h'), $sym); ?>
          <?php nc_field('pension', t('f_pension'), $form, $errors, t('f_pension_h'), $sym); ?>
        <?php elseif ($type === 'house'): ?>
          <?php nc_field('price', t('f_price'), $form, $errors, '', $sym); ?>
          <?php nc_field('down', t('f_down'), $form, $errors, '', '%'); ?>
          <?php nc_field('costs', t('f_costs'), $form, $errors, '', '%'); ?>
          <?php nc_field('years', t('f_years'), $form, $errors, '', $yrs, 'numeric'); ?>
        <?php else: ?>
          <?php nc_field('target', t('f_target'), $form, $errors, '', $sym); ?>
          <?php nc_field('years', t('f_years'), $form, $errors, '', $yrs, 'numeric'); ?>
        <?php endif; ?>
      </div>
    </fieldset>

    <fieldset>
      <legend><?= e(t('sec_you')) ?></legend>
      <div class="cols">
        <?php nc_field('start', t('f_start'), $form, $errors, '', $sym); ?>
        <?php nc_field('monthly', t('f_monthly'), $form, $errors, t('f_monthly_h'), $sym); ?>
      </div>
    </fieldset>

    <details class="more"<?= array_intersect_key($errors, array_flip(['return', 'return_post', 'inflation'])) ? ' open' : '' ?>>
      <summary><?= e(t('advanced')) ?></summary>
      <div class="cols">
        <?php nc_field('return', t('f_return'), $form, $errors, t('f_return_h'), '%'); ?>
        <?php if ($type === 'retirement') { nc_field('return_post', t('f_return_post'), $form, $errors, t('f_return_post_h'), '%'); } ?>
        <?php nc_field('inflation', t('f_inflation'), $form, $errors, t('f_inflation_h'), '%'); ?>
      </div>
      <label class="check">
        <input type="checkbox" name="indexed" value="1"<?= ($form['indexed'] ?? '1') === '1' ? ' checked' : '' ?>>
        <span><?= e(t('f_indexed')) ?></span>
      </label>
    </details>

    <div class="actions">
      <button class="btn btn-primary" type="submit" name="action" value="calc"><?= e(t('btn_calc')) ?></button>
      <button class="btn btn-ghost" type="submit" name="action" value="save"><?= e(t('btn_save')) ?></button>
    </div>
  </form>

  <?php if ($res && $vals) { render_results($type, $vals, $res); } ?>
</div>
<?php render_footer();
