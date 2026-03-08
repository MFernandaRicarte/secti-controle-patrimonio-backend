<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

requireAdminOrSuperAdmin();
$pdo = db();

$tipo = strtolower(trim($_GET['tipo'] ?? 'ambos'));
$periodo = strtolower(trim($_GET['periodo'] ?? 'mes'));
$dataRef = trim($_GET['data_ref'] ?? date('Y-m-d'));

if (!in_array($tipo, ['solicitacoes', 'os', 'ambos'], true)) {
    json(['error' => 'Tipo inválido.'], 422);
}

if (!in_array($periodo, ['dia', 'semana', 'mes', 'ano'], true)) {
    json(['error' => 'Período inválido.'], 422);
}

try {
    $dt = new DateTime($dataRef);
} catch (Exception $e) {
    json(['error' => 'data_ref inválida. Use YYYY-MM-DD.'], 422);
}

switch ($periodo) {
    case 'dia':
        $inicio = clone $dt;
        $inicio->setTime(0, 0, 0);
        $fim = clone $dt;
        $fim->setTime(23, 59, 59);
        break;

    case 'semana':
        $inicio = clone $dt;
        $inicio->modify('monday this week')->setTime(0, 0, 0);
        $fim = clone $dt;
        $fim->modify('sunday this week')->setTime(23, 59, 59);
        break;

    case 'mes':
        $inicio = new DateTime($dt->format('Y-m-01') . ' 00:00:00');
        $fim = new DateTime($dt->format('Y-m-t') . ' 23:59:59');
        break;

    case 'ano':
        $inicio = new DateTime($dt->format('Y-01-01') . ' 00:00:00');
        $fim = new DateTime($dt->format('Y-12-31') . ' 23:59:59');
        break;
}

$inicioStr = $inicio->format('Y-m-d H:i:s');
$fimStr = $fim->format('Y-m-d H:i:s');

$solicitacoes = [];
$ordens = [];

if ($tipo === 'solicitacoes' || $tipo === 'ambos') {
    $sqlSol = "
        SELECT
            s.id,
            s.protocolo,
            s.nome,
            s.endereco,
            s.status,
            s.criado_em,
            (
                SELECT GROUP_CONCAT(CONCAT(i.quantidade, ' ', i.tipo) SEPARATOR ', ')
                FROM rct_solicitacao_itens i
                WHERE i.solicitacao_id = s.id
            ) AS itens_resumo
        FROM rct_solicitacoes s
        WHERE s.criado_em BETWEEN :inicio AND :fim
        ORDER BY s.criado_em DESC, s.id DESC
    ";

    $stmtSol = $pdo->prepare($sqlSol);
    $stmtSol->execute([
        ':inicio' => $inicioStr,
        ':fim' => $fimStr,
    ]);

    $solicitacoes = array_map(function($r) {
        return [
            'tipo_registro' => 'SOLICITACAO',
            'id' => (int)$r['id'],
            'protocolo' => $r['protocolo'],
            'nome' => $r['nome'],
            'endereco' => $r['endereco'],
            'status' => $r['status'],
            'criado_em' => $r['criado_em'],
            'itens_resumo' => $r['itens_resumo'] ?: '',
        ];
    }, $stmtSol->fetchAll(PDO::FETCH_ASSOC));
}

if ($tipo === 'os' || $tipo === 'ambos') {
    $sqlOs = "
        SELECT
            o.id,
            o.solicitacao_id,
            o.status,
            o.criado_em,
            s.protocolo,
            s.nome,
            s.endereco,
            (
                SELECT COUNT(*)
                FROM rct_os_itens i
                WHERE i.os_id = o.id
            ) AS total_itens
        FROM rct_os o
        JOIN rct_solicitacoes s ON s.id = o.solicitacao_id
        WHERE o.criado_em BETWEEN :inicio AND :fim
        ORDER BY o.criado_em DESC, o.id DESC
    ";

    $stmtOs = $pdo->prepare($sqlOs);
    $stmtOs->execute([
        ':inicio' => $inicioStr,
        ':fim' => $fimStr,
    ]);

    $ordens = array_map(function($r) {
        return [
            'tipo_registro' => 'OS',
            'id' => (int)$r['id'],
            'solicitacao_id' => (int)$r['solicitacao_id'],
            'protocolo' => $r['protocolo'],
            'nome' => $r['nome'],
            'endereco' => $r['endereco'],
            'status' => $r['status'],
            'criado_em' => $r['criado_em'],
            'total_itens' => (int)$r['total_itens'],
        ];
    }, $stmtOs->fetchAll(PDO::FETCH_ASSOC));
}

json([
    'filtros' => [
        'tipo' => $tipo,
        'periodo' => $periodo,
        'data_ref' => $dataRef,
        'inicio' => $inicioStr,
        'fim' => $fimStr,
    ],
    'resumo' => [
        'total_solicitacoes' => count($solicitacoes),
        'total_os' => count($ordens),
        'total_geral' => count($solicitacoes) + count($ordens),
    ],
    'solicitacoes' => $solicitacoes,
    'os' => $ordens,
]);