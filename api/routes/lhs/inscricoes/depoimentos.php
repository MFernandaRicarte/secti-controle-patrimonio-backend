<?php
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT id, nome, curso_nome, texto, nota 
        FROM lhs_depoimentos 
        WHERE aprovado = 1 
        ORDER BY nota DESC, criado_em DESC 
        LIMIT 10
    ");
    $depoimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json(array_map(function ($item) {
        return [
            'id' => (int) $item['id'],
            'nome' => $item['nome'],
            'curso' => $item['curso_nome'],
            'texto' => $item['texto'],
            'nota' => (int) $item['nota'],
        ];
    }, $depoimentos));
} catch (PDOException $e) {
    json(['error' => 'Erro ao buscar depoimentos.'], 500);
    exit;
}
