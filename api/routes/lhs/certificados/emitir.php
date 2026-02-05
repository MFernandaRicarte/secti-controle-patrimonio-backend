<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido. Use POST.'], 405);
}

$user = requireAdminOrSuperAdmin();
$pdo = db();

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$alunoId = isset($input['aluno_id']) ? (int) $input['aluno_id'] : 0;
$turmaId = isset($input['turma_id']) ? (int) $input['turma_id'] : 0;

if ($alunoId <= 0 || $turmaId <= 0) {
    json(['error' => 'aluno_id e turma_id são obrigatórios.'], 422);
}

$stmtCheck = $pdo->prepare("
    SELECT id FROM lhs_certificados WHERE aluno_id = ? AND turma_id = ?
");
$stmtCheck->execute([$alunoId, $turmaId]);
if ($stmtCheck->fetch()) {
    json(['error' => 'Já existe um certificado emitido para este aluno nesta turma.'], 409);
}

$stmtTurma = $pdo->prepare("
    SELECT t.*, c.nome AS curso_nome, c.carga_horaria
    FROM lhs_turmas t
    JOIN lhs_cursos c ON c.id = t.curso_id
    WHERE t.id = ?
");
$stmtTurma->execute([$turmaId]);
$turma = $stmtTurma->fetch(PDO::FETCH_ASSOC);

if (!$turma) {
    json(['error' => 'Turma não encontrada.'], 404);
}

if ($turma['status'] !== 'concluida') {
    json(['error' => 'Apenas turmas concluídas podem emitir certificados.'], 422);
}

$stmtMatricula = $pdo->prepare("
    SELECT ta.*, a.nome AS aluno_nome, a.cpf
    FROM lhs_turma_alunos ta
    JOIN lhs_alunos a ON a.id = ta.aluno_id
    WHERE ta.turma_id = ? AND ta.aluno_id = ?
");
$stmtMatricula->execute([$turmaId, $alunoId]);
$matricula = $stmtMatricula->fetch(PDO::FETCH_ASSOC);

if (!$matricula) {
    json(['error' => 'Aluno não encontrado nesta turma.'], 404);
}

$stmtPresenca = $pdo->prepare("
    SELECT 
        COUNT(*) as total_aulas,
        SUM(CASE WHEN p.presente = 1 THEN 1 ELSE 0 END) as total_presencas
    FROM lhs_aulas au
    LEFT JOIN lhs_presencas p ON p.aula_id = au.id AND p.aluno_id = ?
    WHERE au.turma_id = ?
");
$stmtPresenca->execute([$alunoId, $turmaId]);
$presenca = $stmtPresenca->fetch(PDO::FETCH_ASSOC);

$totalAulas = (int) $presenca['total_aulas'];
$totalPresencas = (int) $presenca['total_presencas'];

if ($totalAulas === 0) {
    json(['error' => 'Não há aulas registradas nesta turma.'], 422);
}

$frequenciaFinal = round(($totalPresencas / $totalAulas) * 100, 2);

$frequenciaMinima = isset($input['frequencia_minima']) ? (float) $input['frequencia_minima'] : 75.0;

if ($frequenciaFinal < $frequenciaMinima) {
    json([
        'error' => 'Aluno não atingiu a frequência mínima para emissão de certificado.',
        'frequencia_final' => $frequenciaFinal,
        'frequencia_minima' => $frequenciaMinima,
    ], 422);
}

$ano = date('Y');
$random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
$codigoValidacao = "CERT-LHS-{$ano}-{$random}";

$stmtCodigo = $pdo->prepare("SELECT id FROM lhs_certificados WHERE codigo_validacao = ?");
$stmtCodigo->execute([$codigoValidacao]);
while ($stmtCodigo->fetch()) {
    $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    $codigoValidacao = "CERT-LHS-{$ano}-{$random}";
    $stmtCodigo->execute([$codigoValidacao]);
}

$stmtInsert = $pdo->prepare("
    INSERT INTO lhs_certificados (aluno_id, turma_id, codigo_validacao, frequencia_final, emitido_por)
    VALUES (?, ?, ?, ?, ?)
");
$stmtInsert->execute([$alunoId, $turmaId, $codigoValidacao, $frequenciaFinal, $user['id']]);

$id = (int) $pdo->lastInsertId();

json([
    'ok' => true,
    'message' => 'Certificado emitido com sucesso.',
    'certificado' => [
        'id' => $id,
        'codigo_validacao' => $codigoValidacao,
        'aluno_nome' => $matricula['aluno_nome'],
        'aluno_cpf' => $matricula['cpf'],
        'curso_nome' => $turma['curso_nome'],
        'turma_nome' => $turma['nome'],
        'carga_horaria' => (int) $turma['carga_horaria'],
        'frequencia_final' => $frequenciaFinal,
    ],
], 201);
