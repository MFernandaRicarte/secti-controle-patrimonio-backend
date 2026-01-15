<?php
// api/routes/transferencias_post.php

require_once __DIR__ . '/../lib/http.php';
require_once __DIR__ . '/../config/config.php';

cors();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($method !== 'POST') {
    json(['error' => 'Método não permitido'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$bem_id                 = (int)($input['bem_id'] ?? 0);
$setor_destino_id       = (int)($input['setor_destino_id'] ?? 0);
$sala_destino_id        = (int)($input['sala_destino_id'] ?? 0);
$responsavel_destino_id = isset($input['responsavel_destino_id']) && $input['responsavel_destino_id'] !== ''
    ? (int)$input['responsavel_destino_id']
    : null;
$usuario_operacao_id    = (int)($input['usuario_operacao_id'] ?? 0);
$observacao             = isset($input['observacao']) && $input['observacao'] !== ''
    ? $input['observacao']
    : null;

// validações básicas
if (!$bem_id || !$setor_destino_id || !$sala_destino_id || !$usuario_operacao_id) {
    json(['error' => 'Dados obrigatórios ausentes.'], 400);
}

$pdo = null;

try {
    $pdo = db();
    $pdo->beginTransaction();

    // 1) Buscar dados atuais do bem (origem) e travar linha
    $stmt = $pdo->prepare("
        SELECT id, setor_id, sala_id, responsavel_usuario_id
        FROM bens_patrimoniais
        WHERE id = ?
        FOR UPDATE
    ");
    $stmt->execute([$bem_id]);
    $bem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bem) {
        $pdo->rollBack();
        json(['error' => 'Bem não encontrado.'], 404);
    }

    $setor_origem_id       = $bem['setor_id'] ?? null;
    $sala_origem_id        = $bem['sala_id'] ?? null;
    $responsavel_origem_id = $bem['responsavel_usuario_id'] ?? null;

    // 2) Inserir registro de transferência
    $stmt = $pdo->prepare("
        INSERT INTO transferencias_bens (
            bem_id,
            setor_origem_id,
            sala_origem_id,
            setor_destino_id,
            sala_destino_id,
            responsavel_origem_id,
            responsavel_destino_id,
            usuario_operacao_id,
            observacao
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $bem_id,
        $setor_origem_id,
        $sala_origem_id,
        $setor_destino_id,
        $sala_destino_id,
        $responsavel_origem_id,
        $responsavel_destino_id,
        $usuario_operacao_id,
        $observacao,
    ]);

    $transferenciaId = (int)$pdo->lastInsertId();

    // 3) Atualizar o bem com as infos de destino
    $novoResponsavel = $responsavel_destino_id ?? $responsavel_origem_id;

    $stmt = $pdo->prepare("
        UPDATE bens_patrimoniais
        SET setor_id = ?, sala_id = ?, responsavel_usuario_id = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $setor_destino_id,
        $sala_destino_id,
        $novoResponsavel,
        $bem_id,
    ]);

    // 4) Buscar a transferência recém-criada para devolver ao front
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
        WHERE t.id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$transferenciaId]);
    $transferencia = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    json($transferencia, 201);
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Erro em POST /api/transferencias: ' . $e->getMessage());

    json(
        [
            'error'   => 'Erro ao registrar transferência.',
            'details' => $e->getMessage(),
        ],
        500
    );
}