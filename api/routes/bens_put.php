<?php
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    json(['error' => 'Method Not Allowed'], 405);
}

$id = $GLOBALS['routeParams']['id'] ?? null;
$id = (int)$id;
if ($id <= 0) {
    json(['error' => 'ID inválido'], 400);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    json(['error' => 'JSON inválido'], 400);
}

$pdo = db();

$stmt = $pdo->prepare('SELECT id FROM bens_patrimoniais WHERE id = ?');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    json(['error' => 'Bem não encontrado'], 404);
}

$camposPermitidos = [
    'id_patrimonial',
    'descricao',
    'categoria_id',
    'marca_modelo',
    'tipo_eletronico',
    'estado',
    'data_aquisicao',
    'valor',
    'setor_id',
    'sala_id',
    'responsavel_usuario_id',
    'observacao',
    'imagem_path',
];

$sets   = [];
$params = [];

foreach ($camposPermitidos as $campo) {
    if (array_key_exists($campo, $input)) {
        $sets[] = "$campo = :$campo";
        $params[":$campo"] = ($input[$campo] === '' ? null : $input[$campo]);
    }
}

if (!$sets) {
    json(['error' => 'Nenhum campo para atualizar'], 400);
}


if (isset($input['id_patrimonial'])) {
    $stmt = $pdo->prepare('SELECT id FROM bens_patrimoniais WHERE id_patrimonial = ? AND id <> ?');
    $stmt->execute([$input['id_patrimonial'], $id]);
    if ($stmt->fetch()) {
        json(['error' => 'Já existe outro bem com esse id_patrimonial'], 409);
    }
}

$sql = "UPDATE bens_patrimoniais SET " . implode(', ', $sets) . " WHERE id = :id";
$params[':id'] = $id;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$stmt = $pdo->prepare("
SELECT
    b.id,
    b.id_patrimonial,
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
    b.observacao,
    b.imagem_path,
    b.criado_em
FROM bens_patrimoniais b
LEFT JOIN categorias c  ON c.id  = b.categoria_id
LEFT JOIN setores    s  ON s.id  = b.setor_id
LEFT JOIN salas      sa ON sa.id = b.sala_id
LEFT JOIN usuarios   u  ON u.id  = b.responsavel_usuario_id
WHERE b.id = ?
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    json(['error' => 'Bem não encontrado após atualizar'], 500);
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
    'id'                     => (int)$row['id'],
    'patrimonial'            => $row['id_patrimonial'],
    'descripcion'            => $row['descricao'],
    'descricao'              => $row['descricao'],
    'tipo_eletronico'        => $row['tipo_eletronico'],

    'categoria_id'           => $row['categoria_id'] ? (int)$row['categoria_id'] : null,
    'setor_id'               => $row['setor_id'] ? (int)$row['setor_id'] : null,
    'sala_id'                => $row['sala_id'] ? (int)$row['sala_id'] : null,
    'responsavel_usuario_id' => $row['responsavel_usuario_id'] ? (int)$row['responsavel_usuario_id'] : null,

    'categoria'              => $row['categoria'],
    'localizacao'            => $localizacao,
    'responsavel'            => $row['responsavel'],

    'estado'                 => $row['estado'],
    'data_aquisicao'         => $row['data_aquisicao'],
    'valor'                  => $row['valor'],

    'observacao' => $row['observacao'],
    'imagem_path' => $row['imagem_path'],
];

json($bem);