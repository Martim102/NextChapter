<?php
require __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    logout_user();
    flash('success', t('logged_out'));
}
redirect('index.php');
