<?php
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    json(['error' => 'Método não permitido.'], 405);
}

$id = $GLOBALS['routeParams']['id'] ?? null;
$id = (int)$id;

if ($id <= 0) {
    json(['error' => 'ID inválido.'], 400);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    json(['error' => 'JSON inválido.'], 400);
}

$pdo = db();

$stmt = $pdo->prepare('SELECT id FROM lhs_cursos WHERE id = ?');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    json(['error' => 'Curso não encontrado.'], 404);
}

$camposPermitidos = ['nome', 'carga_horaria', 'ementa', 'ativo'];
$sets   = [];
$params = [];

foreach ($camposPermitidos as $campo) {
    if (array_key_exists($campo, $input)) {
        $sets[] = "$campo = :$campo";
        $valor = $input[$campo];
        if ($campo === 'ativo') {
            $valor = $valor ? 1 : 0;
        }
        $params[":$campo"] = $valor === '' ? null : $valor;
    }
}

if (!$sets) {
    json(['error' => 'Nenhum campo para atualizar.'], 400);
}

if (isset($input['nome']) && trim($input['nome']) !== '') {
    $stmt = $pdo->prepare("SELECT id FROM lhs_cursos WHERE nome = ? AND id != ?");
    $stmt->execute([trim($input['nome']), $id]);
    if ($stmt->fetch()) {
        json(['error' => 'Já existe outro curso com este nome.'], 409);
    }
}

$sql = "UPDATE lhs_cursos SET " . implode(', ', $sets) . " WHERE id = :id";
$params[':id'] = $id;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$stmt = $pdo->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM lhs_materiais_didaticos WHERE curso_id = c.id) AS total_materiais
    FROM lhs_cursos c 
    WHERE c.id = ?
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

json([
    'ok'    => true,
    'curso' => [
        'id'              => (int)$row['id'],
        'nome'            => $row['nome'],
        'carga_horaria'   => (int)$row['carga_horaria'],
        'ementa'          => $row['ementa'],
        'ativo'           => (bool)$row['ativo'],
        'criado_em'       => $row['criado_em'],
        'total_materiais' => (int)$row['total_materiais'],
    ],
]);
