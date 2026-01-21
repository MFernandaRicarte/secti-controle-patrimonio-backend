<?php
require_once __DIR__ . '/../config/config.php';

$migrationDir = __DIR__ . '/../db/migracoes/';
$migrationFiles = glob($migrationDir . '*.sql');

if (empty($migrationFiles)) {
    echo "Nenhuma migração encontrada.\n";
    exit(0);
}

sort($migrationFiles);

$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::MYSQL_ATTR_MULTI_STATEMENTS, true);

// Tabela de controle
$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$executadas = $pdo
    ->query("SELECT filename FROM migrations")
    ->fetchAll(PDO::FETCH_COLUMN);

foreach ($migrationFiles as $file) {
    $filename = basename($file);

    if (in_array($filename, $executadas)) {
        echo "Pulando: {$filename}\n";
        continue;
    }

    echo "Executando: {$filename}\n";

    $sql = file_get_contents($file);

    try {
        $statements = array_filter(
            array_map('trim', preg_split('/;(\s*\n|\s*$)/', $sql))
        );

        foreach ($statements as $statement) {
            $pdo->exec($statement);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO migrations (filename) VALUES (?)"
        );
        $stmt->execute([$filename]);

        echo "✓ OK\n";
    } catch (Exception $e) {
        echo "✗ ERRO em {$filename}:\n";
        echo $e->getMessage() . "\n";
        exit(1);
    }
}

echo "\nTodas as migrações executadas com sucesso!\n";
