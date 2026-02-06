<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

$user = requireAdminOrSuperAdmin();
$pdo = db();

$turmaId = $GLOBALS['routeParams']['id'] ?? 0;
if (!$turmaId) {
    json(['error' => 'ID da turma não informado.'], 400);
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

$frequenciaMinima = isset($_GET['frequencia_minima']) ? (float) $_GET['frequencia_minima'] : 75.0;

$sql = "
    SELECT 
        a.id AS aluno_id,
        a.nome AS aluno_nome,
        a.cpf,
        ta.status AS status_matricula,
        (SELECT COUNT(*) FROM lhs_aulas WHERE turma_id = :turma_id) AS total_aulas_turma,
        (SELECT COUNT(*) FROM lhs_presencas p 
         JOIN lhs_aulas au ON au.id = p.aula_id 
         WHERE p.aluno_id = a.id AND au.turma_id = :turma_id AND p.presente = 1) AS total_presencas,
        (SELECT id FROM lhs_certificados WHERE aluno_id = a.id AND turma_id = :turma_id) AS certificado_existente
    FROM lhs_turma_alunos ta
    JOIN lhs_alunos a ON a.id = ta.aluno_id
    WHERE ta.turma_id = :turma_id
    ORDER BY a.nome ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':turma_id' => $turmaId]);
$alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$elegiveis = [];
$naoElegiveis = [];
$jaEmitidos = [];

foreach ($alunos as $aluno) {
    $totalAulas = (int) $aluno['total_aulas_turma'];
    $totalPresencas = (int) $aluno['total_presencas'];
    
    $frequencia = $totalAulas > 0 
        ? round(($totalPresencas / $totalAulas) * 100, 2) 
        : 0;
    
    $alunoFormatado = [
        'aluno_id' => (int) $aluno['aluno_id'],
        'aluno_nome' => $aluno['aluno_nome'],
        'cpf' => $aluno['cpf'],
        'status_matricula' => $aluno['status_matricula'],
        'total_aulas' => $totalAulas,
        'total_presencas' => $totalPresencas,
        'frequencia' => $frequencia,
    ];
    
    if ($aluno['certificado_existente']) {
        $alunoFormatado['certificado_id'] = (int) $aluno['certificado_existente'];
        $jaEmitidos[] = $alunoFormatado;
    } elseif ($frequencia >= $frequenciaMinima) {
        $elegiveis[] = $alunoFormatado;
    } else {
        $naoElegiveis[] = $alunoFormatado;
    }
}

json([
    'turma' => [
        'id' => (int) $turma['id'],
        'nome' => $turma['nome'],
        'curso_nome' => $turma['curso_nome'],
        'carga_horaria' => (int) $turma['carga_horaria'],
        'status' => $turma['status'],
    ],
    'frequencia_minima' => $frequenciaMinima,
    'total_alunos' => count($alunos),
    'total_elegiveis' => count($elegiveis),
    'total_ja_emitidos' => count($jaEmitidos),
    'total_nao_elegiveis' => count($naoElegiveis),
    'elegiveis' => $elegiveis,
    'ja_emitidos' => $jaEmitidos,
    'nao_elegiveis' => $naoElegiveis,
]);
