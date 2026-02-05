<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

$user = requireProfessorOrAdmin();
$pdo = db();

$turmaId = $GLOBALS['routeParams']['id'] ?? 0;
if (!$turmaId) {
    json(['error' => 'ID da turma não informado.'], 400);
}

if (!professorPodeTurma($user, $turmaId)) {
    json(['error' => 'Acesso negado a esta turma.'], 403);
}

$stmtTurma = $pdo->prepare("
    SELECT t.*, c.nome AS curso_nome, c.carga_horaria
    FROM lhs_turmas t
    LEFT JOIN lhs_cursos c ON c.id = t.curso_id
    WHERE t.id = ?
");
$stmtTurma->execute([$turmaId]);
$turma = $stmtTurma->fetch(PDO::FETCH_ASSOC);

if (!$turma) {
    json(['error' => 'Turma não encontrada.'], 404);
}

$stmtTotalAulas = $pdo->prepare("SELECT COUNT(*) FROM lhs_aulas WHERE turma_id = ?");
$stmtTotalAulas->execute([$turmaId]);
$totalAulas = (int) $stmtTotalAulas->fetchColumn();

$limiteFaltas = isset($_GET['limite']) ? (float) $_GET['limite'] : 25.0;

$sql = "
    SELECT 
        a.id AS aluno_id,
        a.nome AS aluno_nome,
        a.cpf,
        a.email,
        a.telefone,
        ta.status AS status_matricula,
        (SELECT COUNT(*) FROM lhs_presencas p 
         JOIN lhs_aulas au ON au.id = p.aula_id 
         WHERE p.aluno_id = a.id AND au.turma_id = :turma_id) AS total_aulas_aluno,
        (SELECT COUNT(*) FROM lhs_presencas p 
         JOIN lhs_aulas au ON au.id = p.aula_id 
         WHERE p.aluno_id = a.id AND au.turma_id = :turma_id AND p.presente = 1) AS total_presencas
    FROM lhs_turma_alunos ta
    JOIN lhs_alunos a ON a.id = ta.aluno_id
    WHERE ta.turma_id = :turma_id
    ORDER BY a.nome ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':turma_id' => $turmaId]);
$alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$alunosRisco = [];
$alunosOk = [];

foreach ($alunos as $aluno) {
    $totalAulasAluno = (int) $aluno['total_aulas_aluno'];
    $totalPresencas = (int) $aluno['total_presencas'];
    $totalFaltas = $totalAulasAluno - $totalPresencas;
    
    $percentualPresenca = $totalAulasAluno > 0 
        ? round(($totalPresencas / $totalAulasAluno) * 100, 2) 
        : 100;
    
    $percentualFaltas = $totalAulasAluno > 0 
        ? round(($totalFaltas / $totalAulasAluno) * 100, 2) 
        : 0;
    
    $emRisco = $percentualFaltas >= $limiteFaltas;
    
    $alunoFormatado = [
        'aluno_id' => (int) $aluno['aluno_id'],
        'aluno_nome' => $aluno['aluno_nome'],
        'cpf' => $aluno['cpf'],
        'email' => $aluno['email'],
        'telefone' => $aluno['telefone'],
        'status_matricula' => $aluno['status_matricula'],
        'total_aulas' => $totalAulasAluno,
        'total_presencas' => $totalPresencas,
        'total_faltas' => $totalFaltas,
        'percentual_presenca' => $percentualPresenca,
        'percentual_faltas' => $percentualFaltas,
        'em_risco' => $emRisco,
    ];
    
    if ($emRisco) {
        $alunosRisco[] = $alunoFormatado;
    } else {
        $alunosOk[] = $alunoFormatado;
    }
}

usort($alunosRisco, fn($a, $b) => $b['percentual_faltas'] <=> $a['percentual_faltas']);

json([
    'turma' => [
        'id' => (int) $turma['id'],
        'nome' => $turma['nome'],
        'curso_nome' => $turma['curso_nome'],
        'carga_horaria' => (int) $turma['carga_horaria'],
        'status' => $turma['status'],
    ],
    'total_aulas_registradas' => $totalAulas,
    'limite_faltas_percentual' => $limiteFaltas,
    'total_alunos' => count($alunos),
    'total_em_risco' => count($alunosRisco),
    'alunos_em_risco' => $alunosRisco,
    'alunos_regulares' => $alunosOk,
]);
