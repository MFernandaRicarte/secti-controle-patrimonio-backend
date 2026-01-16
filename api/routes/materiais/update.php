<?php
require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/http.php';

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

$stmt = $pdo->prepare('SELECT id FROM itens_estoque WHERE id = ?');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    json(['error' => 'Material não encontrado'], 404);
}

$camposPermitidos = [
    'descricao',
    'unidade',
    'estoque_atual',
    'estoque_minimo',
    'local_guarda',
    'valor_unitario',
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

$sql = "UPDATE itens_estoque SET " . implode(', ', $sets) . " WHERE id = :id";
$params[':id'] = $id;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$stmt = $pdo->prepare("
    SELECT
        i.id,
        i.descricao,
        i.unidade,
        i.estoque_atual,
        i.estoque_minimo,
        i.local_guarda,
        i.valor_unitario,
        i.criado_em,
        c.nome AS categoria,
        u.nome AS usuario_cadastro
    FROM itens_estoque i
    LEFT JOIN categorias c ON c.id = i.categoria_id
    LEFT JOIN usuarios   u ON u.id = i.criado_por_usuario_id
    WHERE i.id = ?
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$item = [
    'id'               => (int)$row['id'],
    'produto_base'     => $row['descricao'],
    'descricao'        => null,
    'unidade'          => $row['unidade'],
    'estoque_atual'    => (int)$row['estoque_atual'],
    'estoque_minimo'   => (int)$row['estoque_minimo'],
    'local_guarda'     => $row['local_guarda'],
    'categoria'        => $row['categoria'],
    'valor_unitario'   => $row['valor_unitario'] !== null
                          ? (float)$row['valor_unitario']
                          : null,
    'data_cadastro'    => $row['criado_em'],
    'usuario_cadastro' => $row['usuario_cadastro'],
];

json($item);