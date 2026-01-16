<?php
require_once __DIR__ . '/../../lib/http.php';
require_once __DIR__ . '/../../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido'], 405);
}

try {
    $pdo = db();

    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $whereParts = ["b.excluido_em IS NOT NULL"];
    $params = [];

    if ($q !== '') {
        $whereParts[] = "(
            b.id_patrimonial LIKE :q
            OR b.descricao LIKE :q
            OR COALESCE(s.nome, '') LIKE :q
            OR COALESCE(sa.nome, '') LIKE :q
            OR COALESCE(u.nome, '') LIKE :q
        )";
        $params[':q'] = '%' . $q . '%';
    }

    $where = "WHERE " . implode(" AND ", $whereParts);

    $sql = "
        SELECT
            b.id,
            b.id_patrimonial,
            b.descricao,
            b.tipo_eletronico,

            b.setor_id,
            s.nome AS setor,

            b.sala_id,
            sa.nome AS sala,

            b.estado,
            b.valor,
            b.criado_em,

            b.excluido_em,
            b.excluido_por_usuario_id,
            uex.nome AS excluido_por_nome
        FROM bens_patrimoniais b
        LEFT JOIN setores s ON s.id = b.setor_id
        LEFT JOIN salas sa ON sa.id = b.sala_id
        LEFT JOIN usuarios uex ON uex.id = b.excluido_por_usuario_id
        {$where}
        ORDER BY b.excluido_em DESC, b.id DESC
        LIMIT 300
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out = array_map(function ($r) {
        $localizacao = null;
        if (!empty($r['setor']) && !empty($r['sala']))
            $localizacao = $r['setor'] . ' / ' . $r['sala'];
        elseif (!empty($r['setor']))
            $localizacao = $r['setor'];
        elseif (!empty($r['sala']))
            $localizacao = $r['sala'];

        return [
            'id' => (int) $r['id'],
            'patrimonial' => $r['id_patrimonial'],
            'descricao' => $r['descricao'],
            'tipo_eletronico' => $r['tipo_eletronico'],
            'localizacao' => $localizacao,
            'estado' => $r['estado'],
            'valor' => $r['valor'],
            'criado_em' => $r['criado_em'],
            'excluido_em' => $r['excluido_em'],
            'excluido_por_usuario_id' => $r['excluido_por_usuario_id'] ? (int) $r['excluido_por_usuario_id'] : null,
            'excluido_por_nome' => $r['excluido_por_nome'] ?? null,
        ];
    }, $rows);

    json($out);
} catch (Throwable $e) {
    error_log("Erro em GET /api/bens-excluidos: " . $e->getMessage());
    json(['error' => 'Erro ao carregar bens excluídos.', 'details' => $e->getMessage()], 500);
}