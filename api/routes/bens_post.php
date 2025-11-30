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

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$idPatrimonial   = trim($input['id_patrimonial'] ?? '');
$descricao       = trim($input['descricao'] ?? '');
$tipoEletronico  = trim($input['tipo_eletronico'] ?? '');
$estado          = trim($input['estado'] ?? 'ativo');
$setorId         = !empty($input['setor_id']) ? (int)$input['setor_id'] : null;
$salaId          = !empty($input['sala_id']) ? (int)$input['sala_id'] : null;
$valor           = isset($input['valor']) ? (float)$input['valor'] : null;

$erros = [];

if ($idPatrimonial === '') {
    $erros[] = 'id_patrimonial é obrigatório.';
}
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

$sqlInsert = "
    INSERT INTO bens_patrimoniais (
        id_patrimonial,
        descricao,
        tipo_eletronico,
        estado,
        valor,
        setor_id,
        sala_id
    ) VALUES (
        :id_patrimonial,
        :descricao,
        :tipo_eletronico,
        :estado,
        :valor,
        :setor_id,
        :sala_id
    )
";

try {
    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute([
        ':id_patrimonial' => $idPatrimonial,
        ':descricao'      => $descricao,
        ':tipo_eletronico'=> $tipoEletronico,
        ':estado'         => $estado,
        ':valor'          => $valor,
        ':setor_id'       => $setorId,
        ':sala_id'        => $salaId,
    ]);

    $novoId = (int) $pdo->lastInsertId();
} catch (PDOException $e) {
    json(['error' => 'Erro ao salvar o bem.', 'detalhes' => $e->getMessage()], 500);
    exit;
}

$sqlSelect = "
SELECT
    b.id,
    b.id_patrimonial,
    b.descricao,
    b.tipo_eletronico,
    c.nome  AS categoria,
    s.nome  AS setor,
    sa.nome AS sala,
    u.nome  AS responsavel,
    b.estado,
    b.data_aquisicao,
    b.valor
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

$localizacao = null;
if (!empty($row['setor']) && !empty($row['sala'])) {
    $localizacao = $row['setor'] . ' / ' . $row['sala'];
} elseif (!empty($row['setor'])) {
    $localizacao = $row['setor'];
} elseif (!empty($row['sala'])) {
    $localizacao = $row['sala'];
}

$bem = [
    'id'             => (int) $row['id'],
    'patrimonial'    => $row['id_patrimonial'],
    'descricao'      => $row['descricao'],
    'tipo_eletronico'=> $row['tipo_eletronico'],
    'categoria'      => $row['categoria'],
    'localizacao'    => $localizacao,
    'responsavel'    => $row['responsavel'],
    'estado'         => $row['estado'],
    'data_aquisicao' => $row['data_aquisicao'],
    'valor'          => $row['valor'],
];

json($bem, 201);