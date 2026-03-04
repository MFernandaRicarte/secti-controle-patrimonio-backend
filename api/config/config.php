<?php
const DB_HOST = '186.227.194.2';
const DB_PORT = 3306;
const DB_NAME = 'secticampinagran_administrativo';
const DB_USER = 'secticampinagran_fernanda';
const DB_PASS = 'sectiadmin2025';

function db(): PDO
{
    static $pdo;
    if ($pdo) return $pdo;

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}