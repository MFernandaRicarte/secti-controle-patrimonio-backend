<?php
/**
 * POST /api/lhs/inscricoes
 * Submete interesse em um curso (pré-matrícula pública).
 * Endpoint público - não requer autenticação.
 */

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

$cursoId = isset($input['curso_id']) ? (int) $input['curso_id'] : 0;
$nome = trim($input['nome'] ?? '');
$cpf = trim($input['cpf'] ?? '');
$telefone = trim($input['telefone'] ?? '');
$email = trim($input['email'] ?? '');
$endereco = trim($input['endereco'] ?? '');

$erros = [];

if ($nome === '') {
    $erros[] = 'Nome é obrigatório.';
}

if ($cpf === '') {
    $erros[] = 'CPF é obrigatório.';
}

if ($cursoId <= 0) {
    $erros[] = 'Curso é obrigatório.';
}

if ($erros) {
    json(['error' => 'Dados inválidos.', 'detalhes' => $erros], 422);
    exit;
}

// Verificar se curso existe e está ativo
try {
    $stmt = $pdo->prepare("SELECT id, nome FROM lhs_cursos WHERE id = :id AND ativo = 1");
    $stmt->execute([':id' => $cursoId]);
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$curso) {
        json(['error' => 'Curso não encontrado ou não está disponível.'], 404);
        exit;
    }
} catch (PDOException $e) {
    json(['error' => 'Erro ao verificar curso.'], 500);
    exit;
}

// Verificar se já existe inscrição pendente para mesmo CPF + curso
try {
    $stmt = $pdo->prepare("
        SELECT id FROM lhs_inscricoes 
        WHERE cpf = :cpf AND curso_id = :curso_id AND status = 'pendente'
    ");
    $stmt->execute([':cpf' => $cpf, ':curso_id' => $cursoId]);

    if ($stmt->fetch()) {
        json(['error' => 'Já existe uma inscrição pendente para este CPF neste curso.'], 409);
        exit;
    }
} catch (PDOException $e) {
    json(['error' => 'Erro ao verificar inscrições existentes.'], 500);
    exit;
}

// Criar inscrição
try {
    $sqlInsert = "
        INSERT INTO lhs_inscricoes (curso_id, nome, cpf, telefone, email, endereco, status)
        VALUES (:curso_id, :nome, :cpf, :telefone, :email, :endereco, 'pendente')
    ";

    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute([
        ':curso_id' => $cursoId,
        ':nome' => $nome,
        ':cpf' => $cpf,
        ':telefone' => $telefone ?: null,
        ':email' => $email ?: null,
        ':endereco' => $endereco ?: null,
    ]);

    $novoId = (int) $pdo->lastInsertId();

    $inscricao = [
        'id' => $novoId,
        'curso_id' => $cursoId,
        'curso_nome' => $curso['nome'],
        'nome' => $nome,
        'cpf' => $cpf,
        'telefone' => $telefone ?: null,
        'email' => $email ?: null,
        'endereco' => $endereco ?: null,
        'status' => 'pendente',
        'mensagem' => 'Sua inscrição foi recebida com sucesso! Aguarde a análise da equipe.',
    ];

    json($inscricao, 201);
} catch (PDOException $e) {
    json(['error' => 'Erro ao salvar inscrição.', 'detalhes' => $e->getMessage()], 500);
    exit;
}
