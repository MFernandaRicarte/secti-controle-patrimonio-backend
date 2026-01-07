<?php

require __DIR__ . '/../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido. Use POST.'], 405);
    exit;
}

$dsn  = 'mysql:host=127.0.0.1;port=3307;dbname=secti;charset=utf8mb4';
$user = 'secti';
$pass = 'secti';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
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

function gerarProximoPatrimonio(PDO $pdo): string
{
    $stmt = $pdo->query("SELECT GET_LOCK('bens_patrimoniais_seq', 10) AS l");
    $lock = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$lock || (int)$lock['l'] !== 1) {
        throw new Exception('Não foi possível obter lock para gerar o tombamento.');
    }

    try {
        $stmt = $pdo->query("
            SELECT MAX(CAST(id_patrimonial AS UNSIGNED)) AS mx
            FROM bens_patrimoniais
            WHERE id_patrimonial REGEXP '^[0-9]{5}$'
        ");

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $max = $row && $row['mx'] !== null ? (int)$row['mx'] : 0;

        $next = $max + 1;
        return str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('bens_patrimoniais_seq')");
    }
}

$hasTombamentoExistente = colunaExiste($pdo, 'bens_patrimoniais', 'tombamento_existente');

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$descricao       = trim($input['descricao'] ?? '');
$tipoEletronico  = trim($input['tipo_eletronico'] ?? '');
$estado          = trim($input['estado'] ?? 'ativo');
$setorId         = !empty($input['setor_id']) ? (int)$input['setor_id'] : null;
$salaId          = !empty($input['sala_id']) ? (int)$input['sala_id'] : null;
$categoriaId     = !empty($input['categoria_id']) ? (int)$input['categoria_id'] : null;
$responsavelId   = !empty($input['responsavel_usuario_id']) ? (int)$input['responsavel_usuario_id'] : null;

$tombamentoExistente = trim($input['tombamento_existente'] ?? '');
if ($tombamentoExistente === '') {
    $tombamentoExistente = null;
}

$valor = isset($input['valor']) && $input['valor'] !== ''
    ? (float)$input['valor']
    : null;

$erros = [];

if ($descricao === '') {
    $erros[] = 'descricao é obrigatória.';
}
if ($tipoEletronico === '') {
    $erros[] = 'tipo_eletronico é obrigatório.';
}

$estadosValidos = ['ativo', 'baixado', 'manutencao'];
if (!in_array($estado, $estadosValidos, true)) {
    $erros[] = 'estado inválido.';
}

if ($erros) {
    json(['error' => 'Dados inválidos.', 'detalhes' => $erros], 422);
    exit;
}

try {
    $idPatrimonial = gerarProximoPatrimonio($pdo);

    if ($hasTombamentoExistente) {
        $sqlInsert = "
            INSERT INTO bens_patrimoniais (
                id_patrimonial,
                tombamento_existente,
                descricao,
                tipo_eletronico,
                estado,
                valor,
                setor_id,
                sala_id,
                categoria_id,
                responsavel_usuario_id
            ) VALUES (
                :id_patrimonial,
                :tombamento_existente,
                :descricao,
                :tipo_eletronico,
                :estado,
                :valor,
                :setor_id,
                :sala_id,
                :categoria_id,
                :responsavel_usuario_id
            )
        ";

        $paramsInsert = [
            ':id_patrimonial'         => $idPatrimonial,
            ':tombamento_existente'   => $tombamentoExistente,
            ':descricao'              => $descricao,
            ':tipo_eletronico'        => $tipoEletronico,
            ':estado'                 => $estado,
            ':valor'                  => $valor,
            ':setor_id'               => $setorId,
            ':sala_id'                => $salaId,
            ':categoria_id'           => $categoriaId,
            ':responsavel_usuario_id' => $responsavelId,
        ];
    } else {
        $sqlInsert = "
            INSERT INTO bens_patrimoniais (
                id_patrimonial,
                descricao,
                tipo_eletronico,
                estado,
                valor,
                setor_id,
                sala_id,
                categoria_id,
                responsavel_usuario_id
            ) VALUES (
                :id_patrimonial,
                :descricao,
                :tipo_eletronico,
                :estado,
                :valor,
                :setor_id,
                :sala_id,
                :categoria_id,
                :responsavel_usuario_id
            )
        ";

        $paramsInsert = [
            ':id_patrimonial'         => $idPatrimonial,
            ':descricao'              => $descricao,
            ':tipo_eletronico'        => $tipoEletronico,
            ':estado'                 => $estado,
            ':valor'                  => $valor,
            ':setor_id'               => $setorId,
            ':sala_id'                => $salaId,
            ':categoria_id'           => $categoriaId,
            ':responsavel_usuario_id' => $responsavelId,
        ];
    }

    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute($paramsInsert);

    $novoId = (int)$pdo->lastInsertId();
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        json(['error' => 'Conflito ao gerar tombamento do sistema. Tente novamente.'], 409);
        exit;
    }
    json(['error' => 'Erro ao salvar o bem.', 'detalhes' => $e->getMessage()], 500);
    exit;
} catch (Exception $e) {
    json(['error' => $e->getMessage()], 500);
    exit;
}

try {
    $selectExtra = $hasTombamentoExistente ? "b.tombamento_existente," : "";

    $sqlSelect = "
    SELECT
        b.id,
        b.id_patrimonial,
        {$selectExtra}
        b.descricao,
        b.tipo_eletronico,

        b.categoria_id,
        c.nome  AS categoria,

        b.setor_id,
        s.nome  AS setor,

        b.sala_id,
        sa.nome AS sala,

        b.responsavel_usuario_id,
        u.nome  AS responsavel,

        b.estado,
        b.data_aquisicao,
        b.valor,
        b.criado_em
    FROM bens_patrimoniais b
    LEFT JOIN categorias c  ON c.id  = b.categoria_id
    LEFT JOIN setores    s  ON s.id  = b.setor_id
    LEFT JOIN salas      sa ON sa.id = b.sala_id
    LEFT JOIN usuarios   u  ON u.id  = b.responsavel_usuario_id
    WHERE b.id = :id
    ";

    $stmt2 = $pdo->prepare($sqlSelect);
    $stmt2->execute([':id' => $novoId]);
    $row = $stmt2->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        json(['error' => 'Erro ao buscar bem criado.'], 500);
        exit;
    }
} catch (PDOException $e) {
    json(['error' => 'Erro ao buscar bem criado.', 'detalhes' => $e->getMessage()], 500);
    exit;
}

$localizacao = null;
if (!empty($row['setor']) && !empty($row['sala'])) {
    $localizacao = $row['setor'] . ' / ' . $row['sala'];
} elseif (!empty($row['setor'])) {
    $localizacao = $row['setor'];
} elseif (!empty($row['sala'])) {
    $localizacao = $row['sala'];
}

$bem = [
    'id'                    => (int)$row['id'],
    'patrimonial'           => $row['id_patrimonial'],
    'tombamento_existente'  => $hasTombamentoExistente ? ($row['tombamento_existente'] ?? null) : null,
    'descricao'             => $row['descricao'],
    'tipo_eletronico'       => $row['tipo_eletronico'],

    'categoria_id'          => $row['categoria_id'] ? (int)$row['categoria_id'] : null,
    'setor_id'              => $row['setor_id'] ? (int)$row['setor_id'] : null,
    'sala_id'               => $row['sala_id'] ? (int)$row['sala_id'] : null,
    'responsavel_usuario_id'=> $row['responsavel_usuario_id'] ? (int)$row['responsavel_usuario_id'] : null,

    'categoria'             => $row['categoria'],
    'localizacao'           => $localizacao,
    'responsavel'           => $row['responsavel'],

    'estado'                => $row['estado'],
    'data_aquisicao'        => $row['data_aquisicao'],
    'valor'                 => $row['valor'],
];

json($bem, 201);