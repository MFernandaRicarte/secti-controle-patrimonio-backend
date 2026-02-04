<?php
require_once __DIR__ . '/../../lib/http.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/cors.php';

cors();

// Verificar autenticação
$usuario = requireAuth();

try {
    $pdo = db(); // Usar db() ao invés de getDB()
    
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $where = '';
    $params = [];
    
    if ($q !== '') {
        $where = "WHERE (
            l.numero LIKE :q
            OR l.objeto LIKE :q
            OR l.modalidade LIKE :q
            OR COALESCE(s.nome, '') LIKE :q
        )";
        $params[':q'] = '%' . $q . '%';
    }
    
    $sql = "
    SELECT
        l.id,
        l.numero,
        l.modalidade,
        l.objeto,
        l.secretaria_id,
        s.nome AS secretaria,
        l.data_abertura,
        l.valor_estimado,
        l.status,
        l.criado_em,
        l.atualizado_em
    FROM licitacoes l
    LEFT JOIN setores s ON s.id = l.secretaria_id
    {$where}
    ORDER BY l.data_abertura DESC, l.criado_em DESC, l.id DESC
    LIMIT 200
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $licitacoes = array_map(function ($row) {
        return [
            'id' => (int) $row['id'],
            'numero' => $row['numero'],
            'modalidade' => $row['modalidade'],
            'objeto' => $row['objeto'],
            'secretaria_id' => $row['secretaria_id'] ? (int) $row['secretaria_id'] : null,
            'secretaria' => $row['secretaria'],
            'data_abertura' => $row['data_abertura'],
            'valor_estimado' => $row['valor_estimado'],
            'status' => $row['status'],
            'criado_em' => $row['criado_em'],
            'atualizado_em' => $row['atualizado_em'],
        ];
    }, $rows);
    
    json([
        'sucesso' => true,
        'dados' => $licitacoes,
        'total' => count($licitacoes)
    ]);
    
} catch (Exception $e) {
    json([
        'sucesso' => false,
        'erro' => 'Erro ao listar licitações: ' . $e->getMessage()
    ], 500);
}