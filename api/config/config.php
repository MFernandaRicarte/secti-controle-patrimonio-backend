<?php
// Credenciais do banco de dados
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'secti';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo;
    if ($pdo)
        return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}
