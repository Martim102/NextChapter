<?php
/** Motor de cálculo: simulações realistas com inflação e 3 cenários. */

const NC_CURRENCIES = ['EUR' => '€', 'USD' => '$', 'BRL' => 'R$', 'GBP' => '£'];
const NC_TYPES = ['retirement', 'house', 'travel', 'car', 'education', 'emergency', 'other'];

function nc_icon(string $type, int $size = 24): string
{
    $p = [
        'retirement' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'house'      => '<path d="M3 10.5 12 3l9 7.5V21H3z"/><path d="M9 21v-6h6v6"/>',
        'travel'     => '<path d="M2 16l20-8-4 14-6-5-4 3v-5z"/><path d="M22 8 10 15"/>',
        'car'        => '<path d="M3 17h18v-4l-2-6H5l-2 6z"/><path d="M3 13h18"/><circle cx="7.5" cy="17" r="1.6"/><circle cx="16.5" cy="17" r="1.6"/>',
        'education'  => '<path d="M22 9 12 4 2 9l10 5z"/><path d="M6 11v5c0 1.5 3 3 6 3s6-1.5 6-3v-5"/>',
        'emergency'  => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="M12 8v5M12 16v.5"/>',
        'other'      => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
    ];
    return '<svg class="ico" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . ($p[$type] ?? $p['other']) . '</svg>';
}

/** Valores por defeito (números). */
function nc_defaults(string $type): array
{
    $base = [
        'title' => '', 'currency' => nc_lang() === 'en' ? 'USD' : 'EUR',
        'start' => 0, 'monthly' => 200, 'return' => 4, 'inflation' => 2.5, 'indexed' => 1,
    ];
    $by = [
        'retirement' => ['age' => 30, 'retire_age' => 67, 'life' => 90, 'income' => 1500, 'pension' => 0,
            'monthly' => 300, 'return' => 6, 'return_post' => 4],
        'house'      => ['price' => 250000, 'down' => 20, 'costs' => 8, 'years' => 8, 'start' => 5000, 'monthly' => 600, 'return' => 3],
        'travel'     => ['target' => 5000, 'years' => 2, 'monthly' => 150, 'return' => 3],
        'car'        => ['target' => 15000, 'years' => 4, 'monthly' => 250, 'return' => 3],
        'education'  => ['target' => 20000, 'years' => 10, 'monthly' => 150, 'return' => 5],
        'emergency'  => ['target' => 12000, 'years' => 2, 'monthly' => 400, 'return' => 2],
        'other'      => ['target' => 10000, 'years' => 5, 'monthly' => 150, 'return' => 4],
    ];
    return array_merge($base, $by[$type] ?? $by['other']);
}

function nc_fmt_input($n): string
{
    if (!is_numeric($n)) {
        return (string) $n;
    }
    $s = rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
    return nc_lang() === 'en' ? $s : str_replace('.', ',', $s);
}

/** Converte valores numéricos em texto para os campos do formulário. */
function nc_to_form(array $vals): array
{
    $out = [];
    foreach ($vals as $k => $v) {
        $out[$k] = ($k === 'title' || $k === 'currency' || $k === 'indexed') ? (string) $v : nc_fmt_input($v);
    }
    return $out;
}

/** Lê um número do formulário. */
function nc_num(array $src, string $key, float $min, float $max, array &$errors, bool $required = true, ?float $default = null, bool $fromDb = false): ?float
{
    $raw = $src[$key] ?? '';
    if (is_int($raw) || is_float($raw)) {
        $raw = (string) $raw;
    }
    $raw = trim((string) $raw);
    if ($raw === '') {
        if ($required) {
            $errors[$key] = t('err_required');
            return null;
        }
        return $default;
    }
    $n = str_replace([' ', "\u{00A0}"], '', $raw);
    if ($fromDb) {
        $n = str_replace(',', '.', $n);
    } elseif (strpos($n, ',') !== false && strpos($n, '.') !== false) {
        if (strrpos($n, ',') > strrpos($n, '.')) {
            $n = str_replace(',', '.', str_replace('.', '', $n)); // 1.234,56
        } else {
            $n = str_replace(',', '', $n);                         // 1,234.56
        }
    } elseif (nc_lang() !== 'en' && preg_match('/^[1-9]\d{0,2}(\.\d{3})+$/', $n)) {
        $n = str_replace('.', '', $n);                             // 1.500 = 1500
    } else {
        $n = str_replace(',', '.', $n);
    }
    if (!is_numeric($n) || !is_finite((float) $n)) {
        $errors[$key] = t('err_number');
        return null;
    }
    $f = (float) $n;
    if ($f < $min || $f > $max) {
        $errors[$key] = t('err_range', nf($min), nf($max));
        return null;
    }
    return $f;
}

/** Valida e normaliza. Devolve [valores, erros]. */
function nc_parse(string $type, array $src, bool $fromDb = false): array
{
    $errors = [];
    $n = function (string $k, float $min, float $max, bool $req = true, ?float $def = null) use ($src, &$errors, $fromDb) {
        return nc_num($src, $k, $min, $max, $errors, $req, $def, $fromDb);
    };
    $v = [];
    $v['title'] = mb_substr(trim((string) ($src['title'] ?? '')), 0, 120);
    $cur = (string) ($src['currency'] ?? 'EUR');
    $v['currency'] = isset(NC_CURRENCIES[$cur]) ? $cur : 'EUR';
    $v['start'] = $n('start', 0, 1e9, false, 0.0);
    $v['monthly'] = $n('monthly', 0, 1e7, false, 0.0);
    $v['return'] = $n('return', -5, 30);
    $v['inflation'] = $n('inflation', 0, 30);
    $v['indexed'] = !empty($src['indexed']) ? 1 : 0;

    if ($type === 'retirement') {
        $age = $n('age', 16, 80);
        $ret = $n('retire_age', ($age ?? 16) + 1, 90);
        $life = $n('life', ($ret ?? 17) + 1, 110);
        $v['age'] = $age === null ? null : (int) round($age);
        $v['retire_age'] = $ret === null ? null : (int) round($ret);
        $v['life'] = $life === null ? null : (int) round($life);
        $v['income'] = $n('income', 1, 1e6);
        $v['pension'] = $n('pension', 0, 1e6, false, 0.0);
        $v['return_post'] = $n('return_post', -5, 20);
    } elseif ($type === 'house') {
        $v['price'] = $n('price', 1, 1e10);
        $v['down'] = $n('down', 0, 100);
        $v['costs'] = $n('costs', 0, 50);
        $y = $n('years', 1, 60);
        $v['years'] = $y === null ? null : (int) round($y);
    } else {
        $v['target'] = $n('target', 1, 1e10);
        $y = $n('years', 1, 60);
        $v['years'] = $y === null ? null : (int) round($y);
    }
    return [$v, $errors];
}

/** Evolução mensal. Devolve valor final e séries anuais. */
function nc_simulate(float $start, float $monthly, int $months, float $annualReturn, float $infl, bool $indexed): array
{
    $rm = pow(1 + $annualReturn / 100, 1 / 12) - 1;
    $bal = $start;
    $c = $monthly;
    $dep = $start;
    $series = [$bal];
    $contrib = [$dep];
    for ($m = 1; $m <= $months; $m++) {
        $bal = $bal * (1 + $rm) + $c;
        $dep += $c;
        if ($m % 12 === 0) {
            $series[] = $bal;
            $contrib[] = $dep;
            if ($indexed) {
                $c *= (1 + $infl / 100);
            }
        }
    }
    return ['final' => $bal, 'series' => $series, 'contrib_series' => $contrib];
}

/** Tempo (anos) até atingir o objetivo ao ritmo atual; null se > 100 anos. */
function nc_eta(float $targetToday, float $start, float $monthly, float $ret, float $infl, bool $indexed): ?float
{
    if ($start >= $targetToday) {
        return 0.0;
    }
    $rm = pow(1 + $ret / 100, 1 / 12) - 1;
    $bal = $start;
    $c = $monthly;
    for ($m = 1; $m <= 1200; $m++) {
        $bal = $bal * (1 + $rm) + $c;
        if ($bal >= $targetToday * pow(1 + $infl / 100, $m / 12)) {
            return $m / 12;
        }
        if ($m % 12 === 0 && $indexed) {
            $c *= (1 + $infl / 100);
        }
    }
    return null;
}

function nc_compute(string $type, array $v): array
{
    $infl = (float) $v['inflation'];
    $indexed = !empty($v['indexed']);
    $extra = [];

    if ($type === 'retirement') {
        $years = (int) $v['retire_age'] - (int) $v['age'];
        $inflF = pow(1 + $infl / 100, $years);
        $needToday = max(0.0, (float) $v['income'] - (float) $v['pension']);
        $w0 = $needToday * $inflF;
        $n = ((int) $v['life'] - (int) $v['retire_age']) * 12;
        $realPost = (1 + (float) $v['return_post'] / 100) / (1 + $infl / 100) - 1;
        $m = pow(1 + $realPost, 1 / 12) - 1;
        $targetFuture = abs($m) < 1e-9 ? $w0 * $n : $w0 * (1 - pow(1 + $m, -$n)) / $m;
        $targetToday = $targetFuture / $inflF;
        $extra = [
            'need_today'    => $needToday,
            'income_future' => (float) $v['income'] * $inflF,
        ];
    } else {
        $years = (int) $v['years'];
        $inflF = pow(1 + $infl / 100, $years);
        if ($type === 'house') {
            $targetToday = (float) $v['price'] * ((float) $v['down'] + (float) $v['costs']) / 100;
        } else {
            $targetToday = (float) $v['target'];
        }
        $targetFuture = $targetToday * $inflF;
    }

    $months = $years * 12;
    $start = (float) $v['start'];
    $monthly = (float) $v['monthly'];
    $scen = [];
    foreach (['pess' => -2, 'real' => 0, 'opt' => 2] as $k => $d) {
        $r = (float) $v['return'] + $d;
        $sim = nc_simulate($start, $monthly, $months, $r, $infl, $indexed);
        $base = nc_simulate($start, 0, $months, $r, $infl, $indexed)['final'];
        $unit = nc_simulate(0, 1, $months, $r, $infl, $indexed)['final'];
        $req = $unit > 0 ? max(0.0, ($targetFuture - $base) / $unit) : 0.0;
        $proj = $sim['final'];
        $s = [
            'return'          => $r,
            'projected'       => $proj,
            'projected_today' => $proj / $inflF,
            'gap'             => $targetFuture - $proj,
            'required'        => $req,
            'ok'              => $proj >= $targetFuture - 0.5,
            'series'          => $sim['series'],
            'contrib'         => $sim['contrib_series'],
        ];
        if ($type === 'retirement') {
            $s['supported_income'] = $targetFuture > 0
                ? (float) $v['pension'] + $extra['need_today'] * ($proj / $targetFuture)
                : (float) $v['pension'];
        }
        $scen[$k] = $s;
    }

    return [
        'years'         => $years,
        'infl_factor'   => $inflF,
        'target_today'  => $targetToday,
        'target_future' => $targetFuture,
        'scen'          => $scen,
        'eta'           => $type === 'retirement' ? null : nc_eta($targetToday, $start, $monthly, (float) $v['return'], $infl, $indexed),
        'extra'         => $extra,
    ];
}
