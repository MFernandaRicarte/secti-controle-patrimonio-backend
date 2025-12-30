<?php
require __DIR__ . '/../../lib/cors.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../lib/db.php';
$pdo = db();

try {

    // 1️⃣ Materiais de consumo → SOMA DO ESTOQUE ATUAL
    $materiaisConsumo = (int)$pdo->query("
        SELECT COALESCE(SUM(estoque_atual), 0)
        FROM itens_estoque
    ")->fetchColumn();

    // 2️⃣ Bens permanentes cadastrados
    $bensPermanentes = (int)$pdo->query("
        SELECT COUNT(*)
        FROM bens_patrimoniais
    ")->fetchColumn();

    // 3️⃣ Transferências
    $transferencias = (int)$pdo->query("
        SELECT COUNT(*)
        FROM transferencias_bens
    ")->fetchColumn();

    // 4️⃣ Entradas totais
    $entradasTotais = $materiaisConsumo + $bensPermanentes;

    echo json_encode([
        'entradas_totais'   => $entradasTotais,
        'bens_permanentes'  => $bensPermanentes,
        'transferencias'    => $transferencias,
        'materiais_consumo' => $materiaisConsumo
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'erro' => 'Erro ao calcular KPIs',
        'detalhe' => $e->getMessage()
    ]);
}