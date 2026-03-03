<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido. Use POST.'], 405);
}

$user = requireLhsAdmin();
$pdo = db();

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$turmaId = isset($input['turma_id']) ? (int) $input['turma_id'] : 0;
$alunosIds = isset($input['alunos_ids']) && is_array($input['alunos_ids'])
    ? $input['alunos_ids']
    : [];

$alunosIds = array_values(array_unique(array_map('intval', $alunosIds)));

if ($turmaId <= 0 || count($alunosIds) === 0) {
    json(['error' => 'turma_id e alunos_ids são obrigatórios.'], 422);
}

// 1) valida turma + curso
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

$frequenciaMinima = isset($input['frequencia_minima']) ? (float) $input['frequencia_minima'] : 75.0;

// resposta detalhada
$emitidos = [];
$ignorados = [];

$pdo->beginTransaction();

try {
    foreach ($alunosIds as $alunoId) {

        // 2) já existe certificado?
        $stmtCheck = $pdo->prepare("SELECT id FROM lhs_certificados WHERE aluno_id = ? AND turma_id = ?");
        $stmtCheck->execute([$alunoId, $turmaId]);
        $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            $ignorados[] = [
                'aluno_id' => (int) $alunoId,
                'motivo' => 'ja_emitido',
                'certificado_id' => (int) $existente['id']
            ];
            continue;
        }

        // 3) aluno está matriculado na turma?
        $stmtMatricula = $pdo->prepare("
            SELECT ta.*, a.nome AS aluno_nome, a.cpf
            FROM lhs_turma_alunos ta
            JOIN lhs_alunos a ON a.id = ta.aluno_id
            WHERE ta.turma_id = ? AND ta.aluno_id = ?
        ");
        $stmtMatricula->execute([$turmaId, $alunoId]);
        $matricula = $stmtMatricula->fetch(PDO::FETCH_ASSOC);

        if (!$matricula) {
            $ignorados[] = [
                'aluno_id' => (int) $alunoId,
                'motivo' => 'nao_esta_na_turma'
            ];
            continue;
        }

        // 4) calcula frequência
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
            $ignorados[] = [
                'aluno_id' => (int) $alunoId,
                'aluno_nome' => $matricula['aluno_nome'],
                'motivo' => 'turma_sem_aulas'
            ];
            continue;
        }

        $frequenciaFinal = round(($totalPresencas / $totalAulas) * 100, 2);

        if ($frequenciaFinal < $frequenciaMinima) {
            $ignorados[] = [
                'aluno_id' => (int) $alunoId,
                'aluno_nome' => $matricula['aluno_nome'],
                'motivo' => 'frequencia_insuficiente',
                'frequencia_final' => $frequenciaFinal,
                'frequencia_minima' => $frequenciaMinima
            ];
            continue;
        }

        // 5) gera código de validação único
        $ano = date('Y');
        do {
            $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            $codigoValidacao = "CERT-LHS-{$ano}-{$random}";
            $stmtCodigo = $pdo->prepare("SELECT id FROM lhs_certificados WHERE codigo_validacao = ?");
            $stmtCodigo->execute([$codigoValidacao]);
        } while ($stmtCodigo->fetch());

        // 6) insere certificado
        $stmtInsert = $pdo->prepare("
            INSERT INTO lhs_certificados (aluno_id, turma_id, codigo_validacao, frequencia_final, emitido_por)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtInsert->execute([$alunoId, $turmaId, $codigoValidacao, $frequenciaFinal, $user['id']]);

        $certId = (int) $pdo->lastInsertId();

        $emitidos[] = [
            'id' => $certId,
            'codigo_validacao' => $codigoValidacao,
            'aluno_id' => (int) $alunoId,
            'aluno_nome' => $matricula['aluno_nome'],
            'aluno_cpf' => $matricula['cpf'],
            'turma_id' => (int) $turmaId,
            'turma_nome' => $turma['nome'],
            'curso_nome' => $turma['curso_nome'],
            'carga_horaria' => (int) $turma['carga_horaria'],
            'frequencia_final' => $frequenciaFinal,
        ];
    }

    $pdo->commit();

    json([
        'ok' => true,
        'message' => 'Processo concluído.',
        'turma_id' => (int) $turmaId,
        'frequencia_minima' => $frequenciaMinima,
        'total_solicitados' => count($alunosIds),
        'total_emitidos' => count($emitidos),
        'total_ignorados' => count($ignorados),
        'emitidos' => $emitidos,
        'ignorados' => $ignorados
    ], 201);

} catch (Throwable $e) {
    $pdo->rollBack();
    json([
        'error' => 'Erro ao emitir certificados em lote.',
        'details' => $e->getMessage()
    ], 500);
}