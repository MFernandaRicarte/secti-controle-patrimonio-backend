<?php
require __DIR__ . '/../../lib/cors.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../lib/db.php';
$pdo = db();

$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');

try {

    $sql = "
        SELECT
            MONTH(criado_em) AS mes,
            SUM(estoque_atual) AS total
        FROM itens_estoque
        WHERE YEAR(criado_em) = ?
        GROUP BY MONTH(criado_em)
        ORDER BY MONTH(criado_em)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ano]);

    $out = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $out[] = [
            'mes'   => str_pad($row['mes'], 2, '0', STR_PAD_LEFT),
            'total' => (int)$row['total'],
        ];
    }

    echo json_encode($out);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'erro'    => 'Erro ao gerar materiais_por_mes',
        'detalhe' => $e->getMessage(),
    ]);
}