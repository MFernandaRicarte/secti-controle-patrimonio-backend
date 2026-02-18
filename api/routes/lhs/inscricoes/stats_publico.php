<?php
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

try {
    $stmtCursos = $pdo->query("SELECT COUNT(*) FROM lhs_cursos WHERE ativo = 1");
    $cursosDisponiveis = (int) $stmtCursos->fetchColumn();

    $stmtTurmas = $pdo->query("SELECT COUNT(*) FROM lhs_turmas WHERE status IN ('aberta', 'em_andamento')");
    $turmasAbertas = (int) $stmtTurmas->fetchColumn();

    $stmtFormados = $pdo->query("SELECT COUNT(DISTINCT aluno_id) FROM lhs_turma_alunos WHERE status = 'aprovado'");
    $alunosFormados = (int) $stmtFormados->fetchColumn();

    $stmtAtivos = $pdo->query("SELECT COUNT(*) FROM lhs_alunos WHERE ativo = 1");
    $alunosAtivos = (int) $stmtAtivos->fetchColumn();

    $stmtCertificados = $pdo->query("SELECT COUNT(*) FROM lhs_certificados");
    $certificadosEmitidos = (int) $stmtCertificados->fetchColumn();

    $stmtPresenca = $pdo->query("
        SELECT ROUND(
            (SELECT COUNT(*) FROM lhs_presencas WHERE presente = 1) * 100.0 / 
            NULLIF((SELECT COUNT(*) FROM lhs_presencas), 0)
        , 0) as media
    ");
    $presencaMedia = (int) ($stmtPresenca->fetchColumn() ?? 0);

    $stmtTotalTurmasConcluidas = $pdo->query("SELECT COUNT(*) FROM lhs_turmas WHERE status = 'concluida'");
    $turmasConcluidas = (int) $stmtTotalTurmasConcluidas->fetchColumn();

    json([
        'cursos_disponiveis' => $cursosDisponiveis,
        'turmas_abertas' => $turmasAbertas,
        'turmas_concluidas' => $turmasConcluidas,
        'alunos_formados' => $alunosFormados,
        'alunos_ativos' => $alunosAtivos,
        'certificados_emitidos' => $certificadosEmitidos,
        'taxa_presenca' => $presencaMedia,
        'taxa_presenca_formatada' => $presencaMedia > 0 ? $presencaMedia . '%' : 'N/A',
        'numeros_destaque' => [
            ['label' => 'Cursos Disponíveis', 'valor' => $cursosDisponiveis, 'icone' => 'BookOpen'],
            ['label' => 'Alunos Formados', 'valor' => $alunosFormados, 'icone' => 'GraduationCap'],
            ['label' => 'Certificados Emitidos', 'valor' => $certificadosEmitidos, 'icone' => 'Award'],
            ['label' => 'Taxa de Presença', 'valor' => $presencaMedia > 0 ? $presencaMedia . '%' : 'N/A', 'icone' => 'TrendingUp'],
        ],
    ]);
} catch (PDOException $e) {
    json(['error' => 'Erro ao buscar estatísticas.'], 500);
    exit;
}
