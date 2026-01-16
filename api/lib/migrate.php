<?php
require_once __DIR__ . '/../config/config.php';

$migrationDir = __DIR__ . '/../db/migracoes/';
$migrationFiles = glob($migrationDir . '*.sql');

sort($migrationFiles); // Ensure they are in order

$pdo = db();

foreach ($migrationFiles as $file) {
    echo "Executando migração: " . basename($file) . "\n";
    $sql = file_get_contents($file);
    try {
        $pdo->exec($sql);
        echo "Sucesso!\n";
    } catch (Exception $e) {
        echo "Erro: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "Todas as migrações executadas com sucesso!\n";
?>