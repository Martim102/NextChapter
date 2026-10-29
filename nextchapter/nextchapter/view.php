<?php
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();

$id = (int) ($_GET['id'] ?? 0);
$st = db()->prepare('SELECT id, type, title, inputs, created_at FROM simulations WHERE id = ? AND user_id = ?');
$st->execute([$id, $user['id']]);
$row = $st->fetch();
if (!$row) {
    flash('error', t('not_found'));
    redirect('dashboard.php');
}

$type = in_array($row['type'], NC_TYPES, true) ? $row['type'] : 'other';
[$v, $err] = nc_parse($type, json_decode((string) $row['inputs'], true) ?: [], true);
if ($err) {
    flash('error', t('err_server'));
    redirect('dashboard.php');
}
$res = nc_compute($type, $v);
$cur = $v['currency'];

$money = ['start', 'monthly', 'income', 'pension', 'target', 'price'];
$pct = ['return', 'return_post', 'inflation', 'down', 'costs'];
$order = ['target', 'price', 'down', 'costs', 'years', 'age', 'retire_age', 'life', 'income', 'pension', 'start', 'monthly', 'return', 'return_post', 'inflation', 'indexed'];

render_header($row['title'], 'view', 'view.php?id=' . $id);
?>
<div class="container page">
  <p><a href="dashboard.php">← <?= e(t('nav_dashboard')) ?></a></p>
  <div class="page-head">
    <div class="head-ico">
      <span class="goal-ico"><?= nc_icon($type, 26) ?></span>
      <div>
        <h1><?= e($row['title']) ?></h1>
        <p class="muted"><?= e(t('type_' . $type)) ?> · <?= e(t('created_on', fmt_date($row['created_at']))) ?></p>
      </div>
    </div>
    <div class="actions">
      <a class="btn btn-primary" href="calculator.php?type=<?= e($type) ?>&amp;copy=<?= $id ?>"><?= e(t('edit_copy')) ?></a>
      <details class="confirm">
        <summary class="btn btn-ghost"><?= e(t('delete')) ?></summary>
        <form method="post" action="dashboard.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $id ?>">
          <p><?= e(t('delete_q')) ?></p>
          <button class="btn btn-danger btn-sm" type="submit"><?= e(t('delete_yes')) ?></button>
        </form>
      </details>
    </div>
  </div>

  <details class="more card-lite">
    <summary><?= e(t('inputs_title')) ?></summary>
    <dl class="dl">
      <?php foreach ($order as $k):
          if (!array_key_exists($k, $v) || $v[$k] === null) { continue; }
          $val = $v[$k];
          if (in_array($k, $money, true)) { $out = money((float) $val, $cur); }
          elseif (in_array($k, $pct, true)) { $out = nf((float) $val, 2) . '%'; }
          elseif ($k === 'indexed') { $out = $val ? '✓' : '—'; }
          else { $out = nf((float) $val, 0); }
      ?>
        <div><dt><?= e(t('f_' . $k)) ?></dt><dd><?= e($out) ?></dd></div>
      <?php endforeach; ?>
    </dl>
  </details>

  <?php render_results($type, $v, $res); ?>
</div>
<?php render_footer();
