<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

$codigo = trim($_GET['codigo'] ?? '');

if ($codigo === '') {
    json(['error' => 'Código de validação é obrigatório.'], 400);
}

$pdo = db();

$stmt = $pdo->prepare("
    SELECT 
        cert.*,
        a.nome AS aluno_nome,
        a.cpf AS aluno_cpf,
        t.nome AS turma_nome,
        t.data_inicio,
        t.data_fim,
        c.nome AS curso_nome,
        c.carga_horaria,
        c.ementa
    FROM lhs_certificados cert
    JOIN lhs_alunos a ON a.id = cert.aluno_id
    JOIN lhs_turmas t ON t.id = cert.turma_id
    JOIN lhs_cursos c ON c.id = t.curso_id
    WHERE cert.codigo_validacao = ?
");
$stmt->execute([$codigo]);
$certificado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$certificado) {
    json([
        'valido' => false,
        'error' => 'Certificado não encontrado com este código.'
    ], 404);
}

json([
    'valido' => true,
    'certificado' => [
        'codigo_validacao' => $certificado['codigo_validacao'],
        'aluno_nome' => $certificado['aluno_nome'],
        'aluno_cpf_parcial' => substr($certificado['aluno_cpf'], 0, 3) . '.***.***-' . substr($certificado['aluno_cpf'], -2),
        'curso_nome' => $certificado['curso_nome'],
        'turma_nome' => $certificado['turma_nome'],
        'carga_horaria' => (int) $certificado['carga_horaria'],
        'frequencia_final' => (float) $certificado['frequencia_final'],
        'data_inicio_turma' => $certificado['data_inicio'],
        'data_fim_turma' => $certificado['data_fim'],
        'emitido_em' => $certificado['emitido_em'],
    ],
]);
