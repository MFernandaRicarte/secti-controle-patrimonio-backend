<?php

function env_value(string $key, $default = null)
{
    $value = $_ENV[$key] ?? getenv($key);
    return ($value !== false && $value !== null && $value !== '') ? $value : $default;
}

function db(): PDO
{
    static $pdo;
    if ($pdo) return $pdo;

    $host = env_value('DB_HOST', '127.0.0.1');
    $port = (int) env_value('DB_PORT', 3306);
    $name = env_value('DB_NAME', '');
    $user = env_value('DB_USER', '');
    $pass = env_value('DB_PASS', '');

    $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4';

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}