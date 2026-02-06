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

$isProfessor = isProfessor($user);
$professorId = $isProfessor ? $user['id'] : null;

$stats = [];

$stmtCursos = $pdo->query("SELECT COUNT(*) FROM lhs_cursos WHERE ativo = 1");
$stats['cursos_ativos'] = (int) $stmtCursos->fetchColumn();

$stmtCursosTotal = $pdo->query("SELECT COUNT(*) FROM lhs_cursos");
$stats['cursos_total'] = (int) $stmtCursosTotal->fetchColumn();

$stmtAlunos = $pdo->query("SELECT COUNT(*) FROM lhs_alunos WHERE ativo = 1");
$stats['alunos_ativos'] = (int) $stmtAlunos->fetchColumn();

$stmtAlunosTotal = $pdo->query("SELECT COUNT(*) FROM lhs_alunos");
$stats['alunos_total'] = (int) $stmtAlunosTotal->fetchColumn();

if ($isProfessor) {
    $stmtTurmas = $pdo->prepare("
        SELECT COUNT(*) FROM lhs_turmas 
        WHERE professor_id = ? AND status IN ('aberta', 'em_andamento')
    ");
    $stmtTurmas->execute([$professorId]);
    $stats['turmas_ativas'] = (int) $stmtTurmas->fetchColumn();
    
    $stmtTurmasTotal = $pdo->prepare("SELECT COUNT(*) FROM lhs_turmas WHERE professor_id = ?");
    $stmtTurmasTotal->execute([$professorId]);
    $stats['turmas_total'] = (int) $stmtTurmasTotal->fetchColumn();
} else {
    $stmtTurmas = $pdo->query("
        SELECT COUNT(*) FROM lhs_turmas WHERE status IN ('aberta', 'em_andamento')
    ");
    $stats['turmas_ativas'] = (int) $stmtTurmas->fetchColumn();
    
    $stmtTurmasTotal = $pdo->query("SELECT COUNT(*) FROM lhs_turmas");
    $stats['turmas_total'] = (int) $stmtTurmasTotal->fetchColumn();
}

$stmtTurmasStatus = $pdo->query("
    SELECT status, COUNT(*) as total 
    FROM lhs_turmas 
    GROUP BY status
");
$turmasStatus = [];
while ($row = $stmtTurmasStatus->fetch(PDO::FETCH_ASSOC)) {
    $turmasStatus[$row['status']] = (int) $row['total'];
}
$stats['turmas_por_status'] = $turmasStatus;

$stmtInscricoesPendentes = $pdo->query("
    SELECT COUNT(*) FROM lhs_inscricoes WHERE status = 'pendente'
");
$stats['inscricoes_pendentes'] = (int) $stmtInscricoesPendentes->fetchColumn();

$stmtInscricoesHoje = $pdo->query("
    SELECT COUNT(*) FROM lhs_inscricoes WHERE DATE(criado_em) = CURDATE()
");
$stats['inscricoes_hoje'] = (int) $stmtInscricoesHoje->fetchColumn();

if ($isProfessor) {
    $stmtAulasHoje = $pdo->prepare("
        SELECT COUNT(*) FROM lhs_aulas a
        JOIN lhs_turmas t ON t.id = a.turma_id
        WHERE t.professor_id = ? AND a.data_aula = CURDATE()
    ");
    $stmtAulasHoje->execute([$professorId]);
    $stats['aulas_hoje'] = (int) $stmtAulasHoje->fetchColumn();
    
    $stmtAulasMes = $pdo->prepare("
        SELECT COUNT(*) FROM lhs_aulas a
        JOIN lhs_turmas t ON t.id = a.turma_id
        WHERE t.professor_id = ? AND MONTH(a.data_aula) = MONTH(CURDATE()) AND YEAR(a.data_aula) = YEAR(CURDATE())
    ");
    $stmtAulasMes->execute([$professorId]);
    $stats['aulas_mes'] = (int) $stmtAulasMes->fetchColumn();
} else {
    $stmtAulasHoje = $pdo->query("SELECT COUNT(*) FROM lhs_aulas WHERE data_aula = CURDATE()");
    $stats['aulas_hoje'] = (int) $stmtAulasHoje->fetchColumn();
    
    $stmtAulasMes = $pdo->query("
        SELECT COUNT(*) FROM lhs_aulas 
        WHERE MONTH(data_aula) = MONTH(CURDATE()) AND YEAR(data_aula) = YEAR(CURDATE())
    ");
    $stats['aulas_mes'] = (int) $stmtAulasMes->fetchColumn();
}

$stmtMatriculas = $pdo->query("SELECT COUNT(*) FROM lhs_turma_alunos WHERE status = 'matriculado'");
$stats['matriculas_ativas'] = (int) $stmtMatriculas->fetchColumn();

if ($isProfessor) {
    $stmtAlunosTurmas = $pdo->prepare("
        SELECT COUNT(DISTINCT ta.aluno_id) 
        FROM lhs_turma_alunos ta
        JOIN lhs_turmas t ON t.id = ta.turma_id
        WHERE t.professor_id = ?
    ");
    $stmtAlunosTurmas->execute([$professorId]);
    $stats['meus_alunos'] = (int) $stmtAlunosTurmas->fetchColumn();
}

$stmtCursosPop = $pdo->query("
    SELECT c.nome, COUNT(ta.id) as total_alunos
    FROM lhs_cursos c
    LEFT JOIN lhs_turmas t ON t.curso_id = c.id
    LEFT JOIN lhs_turma_alunos ta ON ta.turma_id = t.id
    WHERE c.ativo = 1
    GROUP BY c.id, c.nome
    ORDER BY total_alunos DESC
    LIMIT 5
");
$stats['cursos_populares'] = $stmtCursosPop->fetchAll(PDO::FETCH_ASSOC);

$stmtPresencaMedia = $pdo->query("
    SELECT 
        ROUND(
            (SELECT COUNT(*) FROM lhs_presencas WHERE presente = 1) * 100.0 / 
            NULLIF((SELECT COUNT(*) FROM lhs_presencas), 0)
        , 2) as media
");
$stats['presenca_media_geral'] = (float) ($stmtPresencaMedia->fetchColumn() ?? 0);

$stmtNotificacoesNaoLidas = $pdo->query("
    SELECT COUNT(*) FROM lhs_notificacoes WHERE lida = 0
");
$stats['notificacoes_nao_lidas'] = (int) $stmtNotificacoesNaoLidas->fetchColumn();

$stats['atualizado_em'] = date('Y-m-d H:i:s');

json($stats);
