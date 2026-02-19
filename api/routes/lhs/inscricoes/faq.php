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
        SELECT id, pergunta, resposta 
        FROM lhs_faq 
        WHERE ativo = 1 
        ORDER BY ordem ASC
    ");
    $faq = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json(array_map(function ($item) {
        return [
            'id' => (int) $item['id'],
            'pergunta' => $item['pergunta'],
            'resposta' => $item['resposta'],
        ];
    }, $faq));
} catch (PDOException $e) {
    json(['error' => 'Erro ao buscar FAQ.'], 500);
    exit;
}
