<?php
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (csrf_check()) {
        db()->prepare('DELETE FROM simulations WHERE id = ? AND user_id = ?')
            ->execute([(int) ($_POST['id'] ?? 0), $user['id']]);
        flash('success', t('deleted'));
    } else {
        flash('error', t('err_csrf'));
    }
    redirect('dashboard.php');
}

$st = db()->prepare('SELECT id, type, title, inputs, created_at FROM simulations WHERE user_id = ? ORDER BY id DESC');
$st->execute([$user['id']]);
$rows = $st->fetchAll();

render_header(t('dash_title'), 'dashboard');
?>
<div class="container page">
  <div class="page-head">
    <div>
      <h1><?= e(t('dash_title')) ?></h1>
      <p class="muted"><?= e(t('dash_sub')) ?></p>
    </div>
    <a class="btn btn-primary" href="calculator.php"><?= e(t('cta_calc')) ?></a>
  </div>

  <?php if (!$rows): ?>
    <div class="card empty">
      <?= nc_icon('other', 36) ?>
      <h2><?= e(t('empty_title')) ?></h2>
      <p class="muted"><?= e(t('empty_sub')) ?></p>
      <a class="btn btn-primary" href="calculator.php"><?= e(t('cta_calc')) ?></a>
    </div>
  <?php else: ?>
    <div class="grid sims">
      <?php foreach ($rows as $row):
          $type = in_array($row['type'], NC_TYPES, true) ? $row['type'] : 'other';
          [$v, $err] = nc_parse($type, json_decode((string) $row['inputs'], true) ?: [], true);
          $res = $err ? null : nc_compute($type, $v);
      ?>
        <article class="card sim">
          <div class="sim-top">
            <span class="goal-ico"><?= nc_icon($type, 22) ?></span>
            <div>
              <h2><a href="view.php?id=<?= (int) $row['id'] ?>"><?= e($row['title']) ?></a></h2>
              <span class="muted"><?= e(t('type_' . $type)) ?> · <?= e(t('created_on', fmt_date($row['created_at']))) ?></span>
            </div>
          </div>
          <?php if ($res): $real = $res['scen']['real']; ?>
            <div class="mini">
              <div><span class="kpi-label"><?= e(t('goal_label')) ?></span><strong><?= e(money($res['target_today'], $v['currency'])) ?></strong></div>
              <div><span class="kpi-label"><?= e(t('need_label')) ?></span><strong><?= e(money($real['required'], $v['currency'])) ?></strong></div>
            </div>
            <span class="chip <?= $real['ok'] ? 'chip-ok' : 'chip-warn' ?>"><?= e($real['ok'] ? t('res_ontrack') : t('res_offtrack')) ?></span>
          <?php endif; ?>
          <div class="sim-actions">
            <a class="btn btn-primary btn-sm" href="view.php?id=<?= (int) $row['id'] ?>"><?= e(t('view')) ?></a>
            <details class="confirm">
              <summary class="btn btn-ghost btn-sm"><?= e(t('delete')) ?></summary>
              <form method="post" action="dashboard.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                <p><?= e(t('delete_q')) ?></p>
                <button class="btn btn-danger btn-sm" type="submit"><?= e(t('delete_yes')) ?></button>
              </form>
            </details>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php render_footer();
