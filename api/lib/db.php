<?php
require_once __DIR__ . '/../config/config.php';

// A função db() está definida em config.php
// Criamos getDB() como alias para compatibilidade
function getDB() {
    return db();
}