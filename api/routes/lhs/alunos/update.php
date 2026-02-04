<?php

require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    json(['error' => 'Método não permitido. Use PUT ou PATCH.'], 405);
    exit;
}

$id = $GLOBALS['routeParams']['id'] ?? 0;
if (!$id) {
    json(['error' => 'ID do aluno não informado.'], 400);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

// Verificar se aluno existe
$stmt = $pdo->prepare("SELECT * FROM lhs_alunos WHERE id = :id");
$stmt->execute([':id' => $id]);
$aluno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aluno) {
    json(['error' => 'Aluno não encontrado.'], 404);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$nome = isset($input['nome']) ? trim($input['nome']) : $aluno['nome'];
$cpf = isset($input['cpf']) ? trim($input['cpf']) : $aluno['cpf'];
$telefone = isset($input['telefone']) ? trim($input['telefone']) : $aluno['telefone'];
$email = isset($input['email']) ? trim($input['email']) : $aluno['email'];
$endereco = isset($input['endereco']) ? trim($input['endereco']) : $aluno['endereco'];
$ativo = isset($input['ativo']) ? (bool) $input['ativo'] : (bool) $aluno['ativo'];

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

// Verifica CPF duplicado (exceto o próprio aluno)
try {
    $stmt = $pdo->prepare("SELECT id FROM lhs_alunos WHERE cpf = :cpf AND id != :id");
    $stmt->execute([':cpf' => $cpf, ':id' => $id]);
    if ($stmt->fetch()) {
        json(['error' => 'Já existe outro aluno com este CPF.'], 409);
        exit;
    }
} catch (PDOException $e) {
    json(['error' => 'Erro ao verificar CPF.'], 500);
    exit;
}

try {
    $sqlUpdate = "
        UPDATE lhs_alunos SET
            nome = :nome,
            cpf = :cpf,
            telefone = :telefone,
            email = :email,
            endereco = :endereco,
            ativo = :ativo
        WHERE id = :id
    ";

    $stmt = $pdo->prepare($sqlUpdate);
    $stmt->execute([
        ':nome' => $nome,
        ':cpf' => $cpf,
        ':telefone' => $telefone ?: null,
        ':email' => $email ?: null,
        ':endereco' => $endereco ?: null,
        ':ativo' => $ativo ? 1 : 0,
        ':id' => $id,
    ]);

    // Buscar aluno atualizado
    $stmt2 = $pdo->prepare("SELECT * FROM lhs_alunos WHERE id = :id");
    $stmt2->execute([':id' => $id]);
    $row = $stmt2->fetch(PDO::FETCH_ASSOC);

    $alunoResp = [
        'id' => (int) $row['id'],
        'nome' => $row['nome'],
        'cpf' => $row['cpf'],
        'telefone' => $row['telefone'],
        'email' => $row['email'],
        'endereco' => $row['endereco'],
        'ativo' => (bool) $row['ativo'],
        'criado_em' => $row['criado_em'],
    ];

    json($alunoResp);
} catch (PDOException $e) {
    json(['error' => 'Erro ao atualizar aluno.', 'detalhes' => $e->getMessage()], 500);
    exit;
}
