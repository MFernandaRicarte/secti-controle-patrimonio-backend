<?php

// Habilita exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/auth.php';

cors();

try {
    $usuario = requireAuth();
} catch (Exception $e) {
    error_log("Erro na autenticação: " . $e->getMessage());
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado', 'details' => $e->getMessage()]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'error' => 'Método não permitido. Use GET.',
    ]);
    exit;
}

// Obter ID da licitação da URL
$id = $GLOBALS['routeParams']['id'] ?? null;

if (!is_numeric($id) || $id <= 0) {
    http_response_code(400);
    echo json_encode([
        'error' => 'ID da licitação inválido.',
    ]);
    exit;
}

error_log("Buscando detalhes da licitação ID: $id");

try {
    $pdo = db();
    error_log("Conexão com banco estabelecida");
} catch (Exception $e) {
    error_log("Erro ao conectar ao banco: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao conectar ao banco de dados.',
        'details' => $e->getMessage()
    ]);
    exit;
}

try {
    // Verificar se a licitação existe
    error_log("Verificando se licitação existe");
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM licitacoes WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() == 0) {
        error_log("Licitação não encontrada: $id");
        http_response_code(404);
        echo json_encode([
            'error' => 'Licitação não encontrada.',
        ]);
        exit;
    }

    // Dados da licitação
    error_log("Buscando dados da licitação");
    $sqlLicitacao = "
        SELECT
            l.id,
            l.numero,
            l.modalidade,
            l.objeto,
            l.secretaria_id,
            s.nome AS secretaria,
            l.data_abertura,
            l.valor_estimado,
            l.status,
            l.criado_por,
            u_criado.nome AS criado_por_nome,
            l.atualizado_por,
            u_atualizado.nome AS atualizado_por_nome,
            l.criado_em,
            l.atualizado_em
        FROM licitacoes l
        LEFT JOIN setores s ON s.id = l.secretaria_id
        LEFT JOIN usuarios u_criado ON u_criado.id = l.criado_por
        LEFT JOIN usuarios u_atualizado ON u_atualizado.id = l.atualizado_por
        WHERE l.id = ?
    ";
    $stmt = $pdo->prepare($sqlLicitacao);
    $stmt->execute([$id]);
    $licitacao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$licitacao) {
        error_log("Licitação retornou vazia após busca: $id");
        http_response_code(404);
        echo json_encode(['error' => 'Dados da licitação não encontrados.']);
        exit;
    }

    error_log("Licitação encontrada: " . $licitacao['numero']);

    // Fases
    error_log("Buscando fases");
    $sqlFases = "
        SELECT
            lf.id,
            lf.fase,
            lf.data_inicio,
            lf.data_fim,
            lf.prazo_dias,
            lf.responsavel_id,
            u.nome AS responsavel_nome,
            lf.observacoes,
            lf.criado_em
        FROM licitacoes_fases lf
        LEFT JOIN usuarios u ON u.id = lf.responsavel_id
        WHERE lf.licitacao_id = ?
        ORDER BY lf.data_inicio ASC
    ";
    $stmt = $pdo->prepare($sqlFases);
    $stmt->execute([$id]);
    $fases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Fases encontradas: " . count($fases));

    // Documentos
    error_log("Buscando documentos");
    $sqlDocumentos = "
        SELECT
            ld.id,
            ld.nome,
            ld.tipo,
            ld.caminho_arquivo,
            ld.tamanho_arquivo,
            ld.criado_por,
            u.nome AS criado_por_nome,
            ld.criado_em
        FROM licitacoes_documentos ld
        LEFT JOIN usuarios u ON u.id = ld.criado_por
        WHERE ld.licitacao_id = ?
        ORDER BY ld.criado_em DESC
    ";
    $stmt = $pdo->prepare($sqlDocumentos);
    $stmt->execute([$id]);
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Documentos encontrados: " . count($documentos));

    // Tramitações
    error_log("Buscando tramitações");
    $sqlTramitacoes = "
        SELECT
            t.id,
            t.acao,
            t.parecer,
            t.usuario_id,
            u.nome AS usuario_nome,
            t.criado_em
        FROM tramitacoes t
        LEFT JOIN usuarios u ON u.id = t.usuario_id
        WHERE t.licitacao_id = ?
        ORDER BY t.criado_em DESC
    ";
    $stmt = $pdo->prepare($sqlTramitacoes);
    $stmt->execute([$id]);
    $tramitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Tramitações encontradas: " . count($tramitacoes));

    // Alertas
    error_log("Buscando alertas");
    $sqlAlertas = "
        SELECT
            a.id,
            a.tipo,
            a.descricao,
            a.data_vencimento,
            a.status,
            a.criado_em
        FROM alertas a
        WHERE a.licitacao_id = ?
        ORDER BY a.data_vencimento ASC, a.criado_em DESC
    ";
    $stmt = $pdo->prepare($sqlAlertas);
    $stmt->execute([$id]);
    $alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Alertas encontrados: " . count($alertas));

    // Resposta
    error_log("Montando resposta");
    $response = [
        'licitacao' => [
            'id' => (int) $licitacao['id'],
            'numero' => $licitacao['numero'],
            'modalidade' => $licitacao['modalidade'],
            'objeto' => $licitacao['objeto'],
            'secretaria_id' => $licitacao['secretaria_id'] ? (int) $licitacao['secretaria_id'] : null,
            'secretaria' => $licitacao['secretaria'],
            'data_abertura' => $licitacao['data_abertura'],
            'valor_estimado' => (float) $licitacao['valor_estimado'],
            'status' => $licitacao['status'],
            'criado_por' => $licitacao['criado_por'] ? (int) $licitacao['criado_por'] : null,
            'criado_por_nome' => $licitacao['criado_por_nome'],
            'atualizado_por' => $licitacao['atualizado_por'] ? (int) $licitacao['atualizado_por'] : null,
            'atualizado_por_nome' => $licitacao['atualizado_por_nome'],
            'criado_em' => $licitacao['criado_em'],
            'atualizado_em' => $licitacao['atualizado_em'],
        ],
        'fases' => array_map(function ($fase) {
            return [
                'id' => (int) $fase['id'],
                'fase' => $fase['fase'],
                'data_inicio' => $fase['data_inicio'],
                'data_fim' => $fase['data_fim'],
                'prazo_dias' => $fase['prazo_dias'] ? (int) $fase['prazo_dias'] : null,
                'responsavel_id' => $fase['responsavel_id'] ? (int) $fase['responsavel_id'] : null,
                'responsavel_nome' => $fase['responsavel_nome'],
                'observacoes' => $fase['observacoes'],
                'criado_em' => $fase['criado_em'],
            ];
        }, $fases),
        'documentos' => array_map(function ($doc) {
            return [
                'id' => (int) $doc['id'],
                'nome' => $doc['nome'],
                'tipo' => $doc['tipo'],
                'caminho_arquivo' => $doc['caminho_arquivo'],
                'tamanho_arquivo' => $doc['tamanho_arquivo'] ? (int) $doc['tamanho_arquivo'] : null,
                'criado_por' => $doc['criado_por'] ? (int) $doc['criado_por'] : null,
                'criado_por_nome' => $doc['criado_por_nome'],
                'criado_em' => $doc['criado_em'],
            ];
        }, $documentos),
        'tramitacoes' => array_map(function ($tram) {
            return [
                'id' => (int) $tram['id'],
                'acao' => $tram['acao'],
                'parecer' => $tram['parecer'],
                'usuario_id' => $tram['usuario_id'] ? (int) $tram['usuario_id'] : null,
                'usuario_nome' => $tram['usuario_nome'],
                'criado_em' => $tram['criado_em'],
            ];
        }, $tramitacoes),
        'alertas' => array_map(function ($alerta) {
            return [
                'id' => (int) $alerta['id'],
                'tipo' => $alerta['tipo'],
                'descricao' => $alerta['descricao'],
                'data_vencimento' => $alerta['data_vencimento'],
                'status' => $alerta['status'],
                'criado_em' => $alerta['criado_em'],
            ];
        }, $alertas),
    ];

    error_log("Enviando resposta com sucesso");
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    error_log("Erro PDO em licitacoes/details.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao buscar detalhes da licitação.',
        'details' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch (Exception $e) {
    error_log("Erro geral em licitacoes/details.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro interno do servidor.',
        'details' => $e->getMessage(),
    ]);
}