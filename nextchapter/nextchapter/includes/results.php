<?php
/** Apresentação dos resultados (cartões, cenários, gráfico SVG). */

function nc_kpi(string $label, string $value, string $hint = '', bool $big = false): void
{
    echo '<div class="kpi' . ($big ? ' kpi-big' : '') . '"><span class="kpi-label">' . e($label) . '</span>'
        . '<strong class="kpi-value">' . $value . '</strong>'
        . ($hint !== '' ? '<span class="kpi-hint">' . e($hint) . '</span>' : '') . '</div>';
}

function render_results(string $type, array $v, array $r): void
{
    $cur = $v['currency'];
    $real = $r['scen']['real'];
    $pess = $r['scen']['pess'];
    $indexed = !empty($v['indexed']);

    echo '<section class="results" aria-labelledby="res-h"><h2 id="res-h">' . e(t('res_title')) . '</h2>';

    if ($real['ok'] && $pess['ok']) {
        echo '<div class="status ok"><strong>' . e(t('res_solid')) . '</strong><p>' . e(t('res_solid_d')) . '</p></div>';
    } elseif ($real['ok']) {
        echo '<div class="status ok"><strong>' . e(t('res_ontrack')) . '</strong><p>' . e(t('res_ontrack_d')) . '</p></div>';
    } else {
        echo '<div class="status warn"><strong>' . e(t('res_offtrack')) . '</strong><p>'
            . e(t('res_offtrack_d', money($real['gap'], $cur))) . '</p></div>';
    }

    echo '<div class="kpis">';
    nc_kpi(t('k_required'), e(money($real['required'], $cur)) . '<small>' . e(t('per_month')) . '</small>',
        $indexed ? t('k_required_idx') : t('k_required_flat'), true);
    nc_kpi(t('k_target_today'), e(money($r['target_today'], $cur)));
    nc_kpi(t('k_target_future'), e(money($r['target_future'], $cur)));
    nc_kpi(t('k_projected'), e(money($real['projected'], $cur)), t('k_projected_h'));
    if ($type === 'retirement') {
        nc_kpi(t('k_income_supported'), e(money($real['supported_income'], $cur)) . '<small>' . e(t('per_month')) . '</small>', t('k_income_supported_h'));
        nc_kpi(t('k_income_future'), e(money($r['extra']['income_future'], $cur)) . '<small>' . e(t('per_month')) . '</small>', t('k_income_future_h'));
    } else {
        if ($r['eta'] === null) {
            $eta = t('eta_never');
        } elseif ($r['eta'] <= 0) {
            $eta = t('eta_now');
        } else {
            $eta = t('eta_years', nf($r['eta'], 1));
        }
        nc_kpi(t('k_eta'), e($eta));
    }
    echo '</div>';

    // Cenários
    echo '<h3>' . e(t('scen_title')) . '</h3><p class="muted">' . e(t('scen_sub')) . '</p>';
    echo '<div class="table-wrap"><table class="table stack"><thead><tr>'
        . '<th>' . e(t('col_scenario')) . '</th><th>' . e(t('col_return')) . '</th><th>' . e(t('col_projected')) . '</th>'
        . '<th>' . e(t('col_today')) . '</th><th>' . e(t('col_required')) . '</th><th>' . e(t('col_result')) . '</th>'
        . '</tr></thead><tbody>';
    foreach (['pess', 'real', 'opt'] as $k) {
        $s = $r['scen'][$k];
        echo '<tr class="' . ($k === 'real' ? 'row-main' : '') . '">'
            . '<td data-label="' . e(t('col_scenario')) . '"><span class="dot dot-' . $k . '"></span>' . e(t('scen_' . $k)) . '</td>'
            . '<td data-label="' . e(t('col_return')) . '">' . e(nf($s['return'], 1)) . '%</td>'
            . '<td data-label="' . e(t('col_projected')) . '">' . e(money($s['projected'], $cur)) . '</td>'
            . '<td data-label="' . e(t('col_today')) . '">' . e(money($s['projected_today'], $cur)) . '</td>'
            . '<td data-label="' . e(t('col_required')) . '">' . e(money($s['required'], $cur)) . e(t('per_month')) . '</td>'
            . '<td data-label="' . e(t('col_result')) . '"><span class="chip ' . ($s['ok'] ? 'chip-ok' : 'chip-warn') . '">'
            . e($s['ok'] ? t('reaches') : t('misses')) . '</span></td></tr>';
    }
    echo '</tbody></table></div>';

    // Gráfico
    echo '<h3>' . e(t('chart_title')) . '</h3>';
    render_chart($r, $cur);

    // Tabela ano a ano
    echo '<details class="more"><summary>' . e(t('table_title')) . '</summary><div class="table-wrap"><table class="table"><thead><tr>'
        . '<th>' . e(t('col_year')) . '</th><th>' . e(t('col_balance')) . '</th><th>' . e(t('col_contrib')) . '</th></tr></thead><tbody>';
    foreach ($real['series'] as $i => $bal) {
        echo '<tr><td>' . $i . '</td><td>' . e(money($bal, $cur)) . '</td><td>' . e(money($real['contrib'][$i], $cur)) . '</td></tr>';
    }
    echo '</tbody></table></div></details>';

    echo '<div class="note"><strong>' . e(t('method_title')) . '.</strong> ' . e(t('method_d')) . '<br><em>' . e(t('disclaimer')) . '</em></div>';
    echo '</section>';
}

function render_chart(array $r, string $cur): void
{
    $W = 480; $H = 300; $ml = 52; $mr = 12; $mt = 14; $mb = 34;
    $pw = $W - $ml - $mr;
    $ph = $H - $mt - $mb;
    $years = max(1, (int) $r['years']);

    $max = max($r['target_future'], 1.0);
    foreach ($r['scen'] as $s) {
        foreach ($s['series'] as $val) {
            $max = max($max, $val);
        }
    }
    $max *= 1.06;

    $x = function (float $i) use ($ml, $pw, $years) { return $ml + ($i / $years) * $pw; };
    $y = function (float $val) use ($mt, $ph, $max) { return $mt + $ph - max(0.0, min(1.0, $val / $max)) * $ph; };

    $label = t('chart_title') . ' — ' . t('chart_target') . ': ' . money($r['target_future'], $cur);
    echo '<figure class="chart"><svg viewBox="0 0 ' . $W . ' ' . $H . '" role="img" aria-labelledby="ch-t">'
        . '<title id="ch-t">' . e($label) . '</title>';

    for ($i = 0; $i <= 4; $i++) {
        $val = $max * $i / 4;
        $yy = $y($val);
        echo '<line class="ch-grid" x1="' . $ml . '" x2="' . ($W - $mr) . '" y1="' . round($yy, 1) . '" y2="' . round($yy, 1) . '"/>'
            . '<text class="ch-txt" x="' . ($ml - 6) . '" y="' . round($yy + 4, 1) . '" text-anchor="end">' . e(money_short($val)) . '</text>';
    }
    $step = max(1, (int) ceil($years / 6));
    for ($i = 0; $i <= $years; $i += $step) {
        echo '<text class="ch-txt" x="' . round($x($i), 1) . '" y="' . ($H - 12) . '" text-anchor="middle">' . $i . '</text>';
    }
    if ($years % $step !== 0) {
        echo '<text class="ch-txt" x="' . round($x($years), 1) . '" y="' . ($H - 12) . '" text-anchor="middle">' . $years . '</text>';
    }

    foreach (['pess', 'opt', 'real'] as $k) {
        $pts = [];
        foreach ($r['scen'][$k]['series'] as $i => $val) {
            $pts[] = round($x($i), 1) . ',' . round($y($val), 1);
        }
        echo '<polyline class="ch-line ch-' . $k . '" points="' . implode(' ', $pts) . '"/>';
    }
    $ty = round($y($r['target_future']), 1);
    echo '<line class="ch-target" x1="' . $ml . '" x2="' . ($W - $mr) . '" y1="' . $ty . '" y2="' . $ty . '"/>';
    echo '</svg><figcaption class="legend">'
        . '<span><i class="sw sw-pess"></i>' . e(t('scen_pess')) . '</span>'
        . '<span><i class="sw sw-real"></i>' . e(t('scen_real')) . '</span>'
        . '<span><i class="sw sw-opt"></i>' . e(t('scen_opt')) . '</span>'
        . '<span><i class="sw sw-target"></i>' . e(t('chart_target')) . '</span>'
        . '<span class="muted">' . e(t('chart_years')) . '</span></figcaption></figure>';
}
