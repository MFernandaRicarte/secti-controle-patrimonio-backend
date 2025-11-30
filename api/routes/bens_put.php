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
];

$sets   = [];
$params = [];

foreach ($camposPermitidos as $campo) {
    if (array_key_exists($campo, $input)) {
        $sets[] = "$campo = :$campo";
        $params[":$campo"] = $input[$campo] === '' ? null : $input[$campo];
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
    SELECT id, id_patrimonial, descricao, tipo_eletronico, estado,
           setor_id, sala_id, responsavel_usuario_id, data_aquisicao, valor, criado_em
    FROM bens_patrimoniais
    WHERE id = ?
");
$stmt->execute([$id]);
$bem = $stmt->fetch(PDO::FETCH_ASSOC);

json($bem);