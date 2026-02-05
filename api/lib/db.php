<?php
require_once __DIR__ . '/../config/config.php';

if (!function_exists('getDB')) {
    function getDB(): PDO {
        return db();
    }
}