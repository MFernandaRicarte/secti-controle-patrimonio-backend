<?php
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
$turmaPreferenciaId = isset($input['turma_preferencia_id']) ? (int) $input['turma_preferencia_id'] : null;
$nome = trim($input['nome'] ?? '');
$cpf = trim($input['cpf'] ?? '');
$telefone = trim($input['telefone'] ?? '');
$email = trim($input['email'] ?? '');
$endereco = trim($input['endereco'] ?? '');
$dataNascimento = trim($input['data_nascimento'] ?? '');
$escolaridade = trim($input['escolaridade'] ?? '');
$comoSoube = trim($input['como_soube'] ?? '');
$turmaPreferenciaHorario = trim($input['turma_preferencia_horario'] ?? '');
$necessidadesEspeciais = trim($input['necessidades_especiais'] ?? '');

$erros = [];

if ($nome === '') {
    $erros[] = 'Nome completo é obrigatório.';
} elseif (mb_strlen($nome) < 5) {
    $erros[] = 'Nome deve ter pelo menos 5 caracteres.';
}

if ($cpf === '') {
    $erros[] = 'CPF é obrigatório.';
} else {
    $cpfLimpo = preg_replace('/\D/', '', $cpf);
    if (strlen($cpfLimpo) !== 11) {
        $erros[] = 'CPF deve conter 11 dígitos.';
    }
}

if ($cursoId <= 0) {
    $erros[] = 'Curso é obrigatório.';
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erros[] = 'E-mail informado não é válido.';
}

if ($dataNascimento !== '') {
    $dataParsed = DateTime::createFromFormat('Y-m-d', $dataNascimento);
    if (!$dataParsed || $dataParsed->format('Y-m-d') !== $dataNascimento) {
        $erros[] = 'Data de nascimento inválida. Use o formato AAAA-MM-DD.';
    } else {
        $idade = (int) $dataParsed->diff(new DateTime())->y;
        if ($idade < 14) {
            $erros[] = 'A idade mínima para inscrição é 14 anos.';
        }
    }
}

$escolaridadesValidas = [
    '', 'fundamental_incompleto', 'fundamental_completo',
    'medio_incompleto', 'medio_completo',
    'superior_incompleto', 'superior_completo',
    'pos_graduacao', 'nao_informado'
];
if ($escolaridade !== '' && !in_array($escolaridade, $escolaridadesValidas)) {
    $erros[] = 'Escolaridade informada não é válida.';
}

if ($erros) {
    json(['error' => 'Dados inválidos.', 'detalhes' => $erros], 422);
    exit;
}

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

if ($turmaPreferenciaId) {
    try {
        $stmt = $pdo->prepare("
            SELECT id FROM lhs_turmas 
            WHERE id = :id AND curso_id = :curso_id AND status IN ('aberta', 'em_andamento')
        ");
        $stmt->execute([':id' => $turmaPreferenciaId, ':curso_id' => $cursoId]);
        if (!$stmt->fetch()) {
            $turmaPreferenciaId = null;
        }
    } catch (PDOException $e) {
        $turmaPreferenciaId = null;
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT id, numero_protocolo FROM lhs_inscricoes 
        WHERE cpf = :cpf AND curso_id = :curso_id AND status = 'pendente'
    ");
    $stmt->execute([':cpf' => $cpf, ':curso_id' => $cursoId]);
    $existente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existente) {
        json([
            'error' => 'Já existe uma inscrição pendente para este CPF neste curso.',
            'numero_protocolo_existente' => $existente['numero_protocolo'],
            'dica' => 'Use o número de protocolo acima para acompanhar sua inscrição.',
        ], 409);
        exit;
    }
} catch (PDOException $e) {
    json(['error' => 'Erro ao verificar inscrições existentes.'], 500);
    exit;
}

$dataProtocolo = date('Ymd');
$randomPart = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
$numeroProtocolo = "LHS-{$dataProtocolo}-{$randomPart}";

try {
    $sqlInsert = "
        INSERT INTO lhs_inscricoes (
            numero_protocolo, curso_id, turma_preferencia_id, nome, cpf, 
            telefone, email, endereco, data_nascimento, escolaridade,
            como_soube, turma_preferencia_horario, necessidades_especiais, status
        )
        VALUES (
            :numero_protocolo, :curso_id, :turma_preferencia_id, :nome, :cpf,
            :telefone, :email, :endereco, :data_nascimento, :escolaridade,
            :como_soube, :turma_preferencia_horario, :necessidades_especiais, 'pendente'
        )
    ";

    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute([
        ':numero_protocolo' => $numeroProtocolo,
        ':curso_id' => $cursoId,
        ':turma_preferencia_id' => $turmaPreferenciaId ?: null,
        ':nome' => $nome,
        ':cpf' => $cpf,
        ':telefone' => $telefone ?: null,
        ':email' => $email ?: null,
        ':endereco' => $endereco ?: null,
        ':data_nascimento' => $dataNascimento ?: null,
        ':escolaridade' => $escolaridade ?: null,
        ':como_soube' => $comoSoube ?: null,
        ':turma_preferencia_horario' => $turmaPreferenciaHorario ?: null,
        ':necessidades_especiais' => $necessidadesEspeciais ?: null,
    ]);

    $novoId = (int) $pdo->lastInsertId();

    $stmtTurmas = $pdo->prepare("
        SELECT t.nome, t.horario_inicio, t.horario_fim, t.data_inicio, t.dias_semana, t.local_aula
        FROM lhs_turmas t
        WHERE t.curso_id = :curso_id AND t.status IN ('aberta', 'em_andamento')
        ORDER BY t.data_inicio ASC
        LIMIT 3
    ");
    $stmtTurmas->execute([':curso_id' => $cursoId]);
    $turmasDisponiveis = $stmtTurmas->fetchAll(PDO::FETCH_ASSOC);

    $turmasInfo = array_map(function ($t) {
        return [
            'nome' => $t['nome'],
            'horario' => substr($t['horario_inicio'], 0, 5) . ' às ' . substr($t['horario_fim'], 0, 5),
            'data_inicio' => $t['data_inicio'],
            'dias_semana' => $t['dias_semana'],
            'local' => $t['local_aula'],
        ];
    }, $turmasDisponiveis);

    $inscricao = [
        'id' => $novoId,
        'numero_protocolo' => $numeroProtocolo,
        'curso_id' => $cursoId,
        'curso_nome' => $curso['nome'],
        'nome' => $nome,
        'cpf_parcial' => substr($cpf, 0, 3) . '.***.***-' . substr(preg_replace('/\D/', '', $cpf), -2),
        'email' => $email ?: null,
        'status' => 'pendente',
        'status_texto' => 'Aguardando Análise',
        'turmas_disponiveis' => $turmasInfo,
        'mensagem' => 'Sua inscrição foi recebida com sucesso! Guarde seu número de protocolo para acompanhar o status.',
        'instrucoes' => [
            'Anote ou salve seu número de protocolo: ' . $numeroProtocolo,
            'Acompanhe o status da sua inscrição na página de consulta.',
            'A equipe analisará sua inscrição e você será notificado sobre o resultado.',
            'Em caso de dúvidas, entre em contato com a equipe do Lan House Social.',
        ],
        'criado_em' => date('Y-m-d H:i:s'),
    ];

    json($inscricao, 201);
} catch (PDOException $e) {
    json(['error' => 'Erro ao salvar inscrição.'], 500);
    exit;
}
