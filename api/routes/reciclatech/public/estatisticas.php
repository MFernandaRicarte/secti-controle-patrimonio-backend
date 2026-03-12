<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

$pdo = db();

try {
    $totalSolicitacoes = (int)$pdo->query("
        SELECT COUNT(*) FROM rct_solicitacoes
    ")->fetchColumn();

    $totalItens = (int)$pdo->query("
        SELECT COALESCE(SUM(quantidade), 0) FROM rct_solicitacao_itens
    ")->fetchColumn();

    $coletadas = (int)$pdo->query("
        SELECT COUNT(*) FROM rct_solicitacoes WHERE status = 'COLETADA'
    ")->fetchColumn();

    $osFinalizadas = (int)$pdo->query("
        SELECT COUNT(*) FROM rct_os WHERE status = 'FINALIZADA'
    ")->fetchColumn();

    $porCategoria = [];
    try {
        $stCat = $pdo->query("
            SELECT
                c.nome AS categoria,
                c.icone,
                COALESCE(SUM(i.quantidade), 0) AS total
            FROM rct_categorias c
            LEFT JOIN rct_solicitacao_itens i ON i.categoria_id = c.id
            WHERE c.ativo = 1
            GROUP BY c.id, c.nome, c.icone
            ORDER BY total DESC
        ");
        $porCategoria = $stCat->fetchAll(PDO::FETCH_ASSOC);
        $porCategoria = array_map(function ($r) {
            return [
                'categoria' => $r['categoria'],
                'icone'     => $r['icone'],
                'total'     => (int)$r['total'],
            ];
        }, $porCategoria);
    } catch (PDOException $e) {
    }

    json([
        'total_solicitacoes' => $totalSolicitacoes,
        'total_itens'        => $totalItens,
        'coletas_realizadas' => $coletadas,
        'os_finalizadas'     => $osFinalizadas,
        'por_categoria'      => $porCategoria,
    ]);

} catch (PDOException $e) {
    json(['error' => 'Erro ao buscar estatísticas.'], 500);
}
