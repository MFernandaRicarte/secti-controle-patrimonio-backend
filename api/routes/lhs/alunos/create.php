<?php

require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido. Use POST.'], 405);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$nome = trim($input['nome'] ?? '');
$cpf = trim($input['cpf'] ?? '');
$telefone = trim($input['telefone'] ?? '');
$email = trim($input['email'] ?? '');
$endereco = trim($input['endereco'] ?? '');
$ativo = isset($input['ativo']) ? (bool) $input['ativo'] : true;

$erros = [];

if ($nome === '') {
    $erros[] = 'nome é obrigatório.';
}

if ($cpf === '') {
    $erros[] = 'cpf é obrigatório.';
}

if ($erros) {
    json(['error' => 'Dados inválidos.', 'detalhes' => $erros], 422);
    exit;
}

// Verifica CPF duplicado
try {
    $stmt = $pdo->prepare("SELECT id FROM lhs_alunos WHERE cpf = :cpf");
    $stmt->execute([':cpf' => $cpf]);
    if ($stmt->fetch()) {
        json(['error' => 'Já existe um aluno com este CPF.'], 409);
        exit;
    }
} catch (PDOException $e) {
    json(['error' => 'Erro ao verificar CPF.'], 500);
    exit;
}

try {
    $sqlInsert = "
        INSERT INTO lhs_alunos (nome, cpf, telefone, email, endereco, ativo)
        VALUES (:nome, :cpf, :telefone, :email, :endereco, :ativo)
    ";

    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute([
        ':nome' => $nome,
        ':cpf' => $cpf,
        ':telefone' => $telefone ?: null,
        ':email' => $email ?: null,
        ':endereco' => $endereco ?: null,
        ':ativo' => $ativo ? 1 : 0,
    ]);

    $novoId = (int) $pdo->lastInsertId();

    // Buscar aluno criado
    $stmt2 = $pdo->prepare("SELECT * FROM lhs_alunos WHERE id = :id");
    $stmt2->execute([':id' => $novoId]);
    $row = $stmt2->fetch(PDO::FETCH_ASSOC);

    $aluno = [
        'id' => (int) $row['id'],
        'nome' => $row['nome'],
        'cpf' => $row['cpf'],
        'telefone' => $row['telefone'],
        'email' => $row['email'],
        'endereco' => $row['endereco'],
        'ativo' => (bool) $row['ativo'],
        'criado_em' => $row['criado_em'],
    ];

    json($aluno, 201);
} catch (PDOException $e) {
    json(['error' => 'Erro ao salvar aluno.', 'detalhes' => $e->getMessage()], 500);
    exit;
}
