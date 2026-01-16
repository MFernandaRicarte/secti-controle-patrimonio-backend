<?php
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido. Use POST.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$nome         = trim($input['nome'] ?? '');
$cargaHoraria = isset($input['carga_horaria']) ? (int)$input['carga_horaria'] : 0;
$ementa       = trim($input['ementa'] ?? '');
$ativo        = isset($input['ativo']) ? (bool)$input['ativo'] : true;

$erros = [];

if ($nome === '') {
    $erros[] = 'nome é obrigatório.';
}

if ($erros) {
    json(['error' => 'Dados inválidos.', 'detalhes' => $erros], 422);
}

$pdo = db();

$stmt = $pdo->prepare("SELECT id FROM lhs_cursos WHERE nome = ?");
$stmt->execute([$nome]);
if ($stmt->fetch()) {
    json(['error' => 'Já existe um curso com este nome.'], 409);
}

$sql = "
    INSERT INTO lhs_cursos (nome, carga_horaria, ementa, ativo)
    VALUES (:nome, :carga_horaria, :ementa, :ativo)
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':nome'          => $nome,
    ':carga_horaria' => $cargaHoraria,
    ':ementa'        => $ementa ?: null,
    ':ativo'         => $ativo ? 1 : 0,
]);

$id = (int)$pdo->lastInsertId();

$stmt = $pdo->prepare("SELECT * FROM lhs_cursos WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

json([
    'ok'    => true,
    'curso' => [
        'id'            => (int)$row['id'],
        'nome'          => $row['nome'],
        'carga_horaria' => (int)$row['carga_horaria'],
        'ementa'        => $row['ementa'],
        'ativo'         => (bool)$row['ativo'],
        'criado_em'     => $row['criado_em'],
    ],
], 201);
