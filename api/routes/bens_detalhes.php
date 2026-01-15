<?php
require_once __DIR__ . '/../lib/http.php';
require_once __DIR__ . '/../config/config.php';

cors();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($method !== 'GET') {
    json(['error' => 'Método não permitido'], 405);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    json(['error' => 'ID inválido'], 400);
}

function colunaExiste(PDO $pdo, string $tabela, string $coluna): bool
{
    $stmt = $pdo->prepare("
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :tabela
          AND COLUMN_NAME = :coluna
        LIMIT 1
    ");
    $stmt->execute([
        ':tabela' => $tabela,
        ':coluna' => $coluna,
    ]);
    return (bool)$stmt->fetchColumn();
}

try {
    $pdo = db();
    $hasTomb = colunaExiste($pdo, 'bens_patrimoniais', 'tombamento_existente');
    $hasObs  = colunaExiste($pdo, 'bens_patrimoniais', 'observacao');
    $hasImg  = colunaExiste($pdo, 'bens_patrimoniais', 'imagem_path');

    $selectExtras = [];
    if ($hasTomb) $selectExtras[] = "b.tombamento_existente";
    if ($hasObs)  $selectExtras[] = "b.observacao";
    if ($hasImg)  $selectExtras[] = "b.imagem_path";

    $extrasSql = $selectExtras ? (",\n            " . implode(",\n            ", $selectExtras)) : "";

    $stmt = $pdo->prepare("
        SELECT
            b.id,
            b.id_patrimonial,
            b.descricao,
            b.tipo_eletronico,
            b.estado,
            b.data_aquisicao,
            b.valor,
            b.criado_em,
            b.categoria_id,
            c.nome AS categoria,
            b.setor_id,
            s.nome AS setor,
            b.sala_id,
            sa.nome AS sala,
            b.responsavel_usuario_id,
            u.nome AS responsavel
            {$extrasSql}
        FROM bens_patrimoniais b
        LEFT JOIN categorias c ON c.id = b.categoria_id
        LEFT JOIN setores s    ON s.id = b.setor_id
        LEFT JOIN salas sa     ON sa.id = b.sala_id
        LEFT JOIN usuarios u   ON u.id = b.responsavel_usuario_id
        WHERE b.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $bem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bem) {
        json(['error' => 'Bem não encontrado'], 404);
    }
    if (!$hasTomb) $bem['tombamento_existente'] = null;
    if (!$hasObs)  $bem['observacao'] = null;
    if (!$hasImg)  $bem['imagem_path'] = null;

    $stmt = $pdo->prepare("
        SELECT
            t.id,
            t.bem_id,

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
            uo.nome AS usuario_operacao_nome,

            t.observacao,
            t.data_transferencia
        FROM transferencias_bens t
        LEFT JOIN setores so  ON so.id  = t.setor_origem_id
        LEFT JOIN salas sa_o  ON sa_o.id = t.sala_origem_id
        LEFT JOIN setores sd  ON sd.id  = t.setor_destino_id
        LEFT JOIN salas sa_d  ON sa_d.id = t.sala_destino_id
        LEFT JOIN usuarios ro ON ro.id  = t.responsavel_origem_id
        LEFT JOIN usuarios rd ON rd.id  = t.responsavel_destino_id
        LEFT JOIN usuarios uo ON uo.id  = t.usuario_operacao_id
        WHERE t.bem_id = ?
        ORDER BY t.data_transferencia DESC, t.id DESC
    ");
    $stmt->execute([$id]);
    $transferencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json([
        'bem' => $bem,
        'transferencias' => $transferencias,
    ]);
} catch (Throwable $e) {
    error_log('Erro em GET /api/bens-detalhes: ' . $e->getMessage());

    json([
        'error' => 'Erro ao carregar detalhes do bem.',
        'details' => $e->getMessage(),
    ], 500);
}