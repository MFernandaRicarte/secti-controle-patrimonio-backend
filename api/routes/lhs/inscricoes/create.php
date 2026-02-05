<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido. Use POST.'], 405);
}

$pdo = db();
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$cursoId = isset($input['curso_id']) ? (int) $input['curso_id'] : 0;
$turmaPreferenciaId = isset($input['turma_preferencia_id']) ? (int) $input['turma_preferencia_id'] : null;
$nome = trim($input['nome'] ?? '');
$cpf = trim($input['cpf'] ?? '');
$telefone = trim($input['telefone'] ?? '');
$email = trim($input['email'] ?? '');
$endereco = trim($input['endereco'] ?? '');

$erros = [];

if ($cursoId <= 0) {
    $erros[] = 'curso_id é obrigatório.';
}

if ($nome === '') {
    $erros[] = 'nome é obrigatório.';
}

if ($cpf === '') {
    $erros[] = 'cpf é obrigatório.';
}

if ($erros) {
    json(['error' => 'Dados inválidos.', 'detalhes' => $erros], 422);
}

$stmt = $pdo->prepare("SELECT id, ativo FROM lhs_cursos WHERE id = ?");
$stmt->execute([$cursoId]);
$curso = $stmt->fetch();

if (!$curso) {
    json(['error' => 'Curso não encontrado.'], 404);
}

if (!$curso['ativo']) {
    json(['error' => 'Este curso não está ativo para inscrições.'], 422);
}

if ($turmaPreferenciaId) {
    $stmtTurma = $pdo->prepare("SELECT id FROM lhs_turmas WHERE id = ? AND curso_id = ? AND status = 'aberta'");
    $stmtTurma->execute([$turmaPreferenciaId, $cursoId]);
    if (!$stmtTurma->fetch()) {
        $turmaPreferenciaId = null;
    }
}

$stmtCheck = $pdo->prepare("
    SELECT id, status, numero_protocolo 
    FROM lhs_inscricoes 
    WHERE cpf = ? AND curso_id = ? AND status IN ('pendente', 'aprovado')
");
$stmtCheck->execute([$cpf, $cursoId]);
$inscricaoExistente = $stmtCheck->fetch();

if ($inscricaoExistente) {
    if ($inscricaoExistente['status'] === 'pendente') {
        json([
            'error' => 'Já existe uma inscrição pendente para este CPF neste curso.',
            'numero_protocolo' => $inscricaoExistente['numero_protocolo']
        ], 409);
    } else {
        json(['error' => 'Este CPF já possui matrícula aprovada neste curso.'], 409);
    }
}

$dataAtual = date('Ymd');
$random = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
$numeroProtocolo = "LHS-{$dataAtual}-{$random}";

$stmtProtocolo = $pdo->prepare("SELECT id FROM lhs_inscricoes WHERE numero_protocolo = ?");
$stmtProtocolo->execute([$numeroProtocolo]);
while ($stmtProtocolo->fetch()) {
    $random = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
    $numeroProtocolo = "LHS-{$dataAtual}-{$random}";
    $stmtProtocolo->execute([$numeroProtocolo]);
}

$stmtInsert = $pdo->prepare("
    INSERT INTO lhs_inscricoes 
    (numero_protocolo, curso_id, turma_preferencia_id, nome, cpf, telefone, email, endereco, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendente')
");
$stmtInsert->execute([
    $numeroProtocolo,
    $cursoId,
    $turmaPreferenciaId ?: null,
    $nome,
    $cpf,
    $telefone ?: null,
    $email ?: null,
    $endereco ?: null
]);

$id = (int) $pdo->lastInsertId();

json([
    'ok' => true,
    'message' => 'Inscrição realizada com sucesso.',
    'numero_protocolo' => $numeroProtocolo,
    'inscricao' => [
        'id' => $id,
        'numero_protocolo' => $numeroProtocolo,
        'status' => 'pendente',
    ],
], 201);
