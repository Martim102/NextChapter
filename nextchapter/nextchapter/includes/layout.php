<?php
/** Cabeçalho e rodapé comuns. */

function render_header(string $title, string $page = '', string $back = ''): void
{
    $u = current_user();
    $lang = nc_lang();
    $theme = nc_theme();
    $back = $back !== '' ? $back : ($page !== '' ? $page . '.php' : 'index.php');
    $nextTheme = $theme === 'dark' ? 'light' : 'dark';
    $full = $title === '' ? 'NextChapter' : $title . ' · NextChapter';

    echo '<!doctype html><html lang="' . e($lang) . '" data-theme="' . e($theme) . '"><head>'
        . '<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . e($full) . '</title>'
        . '<meta name="description" content="' . e(t('meta_desc')) . '">'
        . '<meta name="theme-color" content="#05080c">'
        . '<link rel="icon" type="image/svg+xml" href="assets/favicon.svg">'
        . '<link rel="stylesheet" href="assets/css/style.css?v=1"></head><body>';

    echo '<a class="skip" href="#main">' . e(t('skip')) . '</a>';
    echo '<header class="site-header"><div class="container header-in">';
    echo '<a class="brand" href="index.php" aria-label="NextChapter">'
        . '<svg width="28" height="28" viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="8" fill="#4cc2ff"/>'
        . '<path d="M9 22V10l7 7V10M20 10v12" fill="none" stroke="#05080c" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        . '<span>Next<b>Chapter</b></span></a>';

    echo '<input type="checkbox" id="nav-toggle" class="nav-toggle">'
        . '<label for="nav-toggle" class="burger" aria-label="' . e(t('menu')) . '"><span></span></label>';

    echo '<nav class="nav" aria-label="' . e(t('menu')) . '"><ul class="nav-links">';
    if ($u) {
        echo '<li><a href="calculator.php"' . ($page === 'calculator' ? ' aria-current="page"' : '') . '>' . e(t('nav_calculator')) . '</a></li>';
        echo '<li><a href="dashboard.php"' . ($page === 'dashboard' ? ' aria-current="page"' : '') . '>' . e(t('nav_dashboard')) . '</a></li>';
    } else {
        echo '<li><a href="index.php"' . ($page === 'index' ? ' aria-current="page"' : '') . '>' . e(t('nav_home')) . '</a></li>';
        echo '<li><a href="login.php"' . ($page === 'login' ? ' aria-current="page"' : '') . '>' . e(t('nav_login')) . '</a></li>';
        echo '<li><a class="btn btn-primary btn-sm" href="register.php">' . e(t('nav_register')) . '</a></li>';
    }
    echo '</ul><div class="nav-tools">';

    echo '<div class="langs" role="group" aria-label="' . e(t('lang_label')) . '">';
    foreach (NC_LANGS as $code => $name) {
        echo '<a href="set.php?lang=' . $code . '&amp;back=' . rawurlencode($back) . '" hreflang="' . $code . '" lang="' . $code . '" title="' . e($name) . '"'
            . ($code === $lang ? ' aria-current="true"' : '') . '>' . strtoupper($code) . '</a>';
    }
    echo '</div>';

    $themeLabel = $theme === 'dark' ? t('theme_to_light') : t('theme_to_dark');
    $icon = $theme === 'dark'
        ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>'
        : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 13A9 9 0 1 1 11 3a7 7 0 0 0 10 10z"/></svg>';
    echo '<a class="theme-btn" rel="nofollow" href="set.php?theme=' . $nextTheme . '&amp;back=' . rawurlencode($back) . '" title="' . e($themeLabel) . '" aria-label="' . e($themeLabel) . '">' . $icon . '</a>';

    if ($u) {
        echo '<span class="who">' . e(t('hello', $u['name'])) . '</span>'
            . '<form method="post" action="logout.php" class="inline">' . csrf_field()
            . '<button class="btn btn-ghost btn-sm" type="submit">' . e(t('nav_logout')) . '</button></form>';
    }
    echo '</div></nav></div></header>';

    echo '<main id="main">';
    $flash = flash_take();
    if ($flash) {
        echo '<div class="container flashes">';
        foreach ($flash as $f) {
            $cls = in_array($f['type'], ['success', 'error', 'info'], true) ? $f['type'] : 'info';
            echo '<div class="alert alert-' . $cls . '" role="status">' . e($f['msg']) . '</div>';
        }
        echo '</div>';
    }
}

function render_footer(): void
{
    echo '</main><footer class="site-footer"><div class="container">'
        . '<p>' . e(t('footer_note')) . '</p>'
        . '<p class="muted">© ' . date('Y') . ' NextChapter. ' . e(t('footer_rights')) . '</p>'
        . '</div></footer></body></html>';
}
