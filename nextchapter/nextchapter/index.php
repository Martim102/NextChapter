<?php
require __DIR__ . '/includes/bootstrap.php';
$user = current_user();

render_header('', 'index');
?>
<section class="hero">
  <div class="container hero-in">
    <div class="hero-text">
      <p class="eyebrow"><?= e(t('site_tagline')) ?></p>
      <h1><?= t('hero_title') /* contém <em> controlado */ ?></h1>
      <p class="lead"><?= e(t('hero_sub')) ?></p>
      <div class="actions">
        <?php if ($user): ?>
          <a class="btn btn-primary btn-lg" href="dashboard.php"><?= e(t('cta_dashboard')) ?></a>
          <a class="btn btn-ghost btn-lg" href="calculator.php"><?= e(t('cta_calc')) ?></a>
        <?php else: ?>
          <a class="btn btn-primary btn-lg" href="register.php"><?= e(t('cta_start')) ?></a>
          <a class="btn btn-ghost btn-lg" href="login.php"><?= e(t('cta_login')) ?></a>
        <?php endif; ?>
      </div>
    </div>
    <div class="hero-card card" aria-hidden="true">
      <span class="chip chip-ok"><?= e(t('hero_card_status')) ?></span>
      <p class="muted"><?= e(t('hero_card_label')) ?></p>
      <h2><?= e(t('hero_card_goal')) ?></h2>
      <div class="mini">
        <div><span class="kpi-label"><?= e(t('hero_card_need')) ?></span><strong><?= e(money(412000, nc_lang() === 'en' ? 'USD' : 'EUR')) ?></strong></div>
        <div><span class="kpi-label"><?= e(t('hero_card_save')) ?></span><strong><?= e(money(486, nc_lang() === 'en' ? 'USD' : 'EUR')) ?></strong></div>
      </div>
      <div class="bars"><i style="height:22%"></i><i style="height:32%"></i><i style="height:42%"></i><i style="height:55%"></i><i style="height:68%"></i><i style="height:82%"></i><i style="height:100%"></i></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <h2><?= e(t('goals_title')) ?></h2>
    <p class="muted"><?= e(t('goals_sub')) ?></p>
    <div class="grid goals">
      <?php foreach (NC_TYPES as $ty): ?>
        <a class="card goal" href="<?= $user ? 'calculator.php?type=' . $ty : 'register.php' ?>">
          <span class="goal-ico"><?= nc_icon($ty, 26) ?></span>
          <strong><?= e(t('type_' . $ty)) ?></strong>
          <span class="muted"><?= e(t('type_' . $ty . '_d')) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section alt">
  <div class="container">
    <h2><?= e(t('how_title')) ?></h2>
    <ol class="grid steps">
      <?php for ($i = 1; $i <= 3; $i++): ?>
        <li class="card"><span class="num"><?= $i ?></span><strong><?= e(t("how{$i}_t")) ?></strong><span class="muted"><?= e(t("how{$i}_d")) ?></span></li>
      <?php endfor; ?>
    </ol>
  </div>
</section>

<section class="section">
  <div class="container split">
    <div>
      <h2><?= e(t('real_title')) ?></h2>
      <p class="muted"><?= e(t('real_d')) ?></p>
    </div>
    <ul class="checks">
      <?php for ($i = 1; $i <= 4; $i++): ?>
        <li><?= e(t("real_$i")) ?></li>
      <?php endfor; ?>
    </ul>
  </div>
</section>

<section class="section cta">
  <div class="container center">
    <h2><?= e(t('final_title')) ?></h2>
    <p><?= e(t('final_sub')) ?></p>
    <a class="btn btn-primary btn-lg" href="<?= $user ? 'calculator.php' : 'register.php' ?>"><?= e($user ? t('cta_calc') : t('cta_start')) ?></a>
  </div>
</section>
<?php render_footer();
