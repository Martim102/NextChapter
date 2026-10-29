<?php
/**
 * Configuração do NextChapter.
 *
 * Em XAMPP os valores por defeito funcionam (utilizador "root", sem palavra-passe).
 * Em produção (Vercel) defina estas variáveis de ambiente no painel do projeto:
 *   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_SSL=1 (se o fornecedor exigir SSL)
 */
$pass = getenv('DB_PASS');

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'nextchapter',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => $pass === false ? '' : $pass,
        'ssl'  => getenv('DB_SSL') === '1',
        'dsn'  => getenv('DB_DSN') ?: '', // opcional, só para testes
    ],
];
