<?php
// api/routes/transferencias.php

require __DIR__ . '/../lib/http.php';
require __DIR__ . '/../config/config.php';

cors();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($method !== 'GET') {
    json(['error' => 'Método não permitido'], 405);
}

try {
    $pdo = db();

    $sql = "
        SELECT
            t.id,
            t.bem_id,
            b.id_patrimonial AS patrimonial,
            b.descricao AS bem_descricao,

            t.setor_origem_id,
            so.nome  AS setor_origem_nome,
            t.sala_origem_id,
            sa_o.nome AS sala_origem_nome,

            t.setor_destino_id,
            sd.nome  AS setor_destino_nome,
            t.sala_destino_id,
            sa_d.nome AS sala_destino_nome,

            t.responsavel_origem_id,
            ro.nome  AS responsavel_origem_nome,
            t.responsavel_destino_id,
            rd.nome  AS responsavel_destino_nome,

            t.usuario_operacao_id,
            u.nome   AS usuario_operacao_nome,

            t.observacao,
            t.data_transferencia
        FROM transferencias_bens t
        JOIN bens_patrimoniais b
          ON b.id = t.bem_id
        LEFT JOIN setores so
          ON so.id = t.setor_origem_id
        LEFT JOIN salas sa_o
          ON sa_o.id = t.sala_origem_id
        JOIN setores sd
          ON sd.id = t.setor_destino_id
        JOIN salas sa_d
          ON sa_d.id = t.sala_destino_id
        LEFT JOIN usuarios ro
          ON ro.id = t.responsavel_origem_id
        LEFT JOIN usuarios rd
          ON rd.id = t.responsavel_destino_id
        JOIN usuarios u
          ON u.id = t.usuario_operacao_id
        ORDER BY t.data_transferencia DESC
    ";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json($rows);
} catch (Throwable $e) {
    // loga só pro servidor
    error_log('Erro em GET /api/transferencias: ' . $e->getMessage());

    json(
        [
            'error'   => 'Erro ao carregar transferências.',
            'details' => $e->getMessage(),
        ],
        500
    );
}