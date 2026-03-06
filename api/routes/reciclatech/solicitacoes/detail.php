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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    json(['error' => 'id é obrigatório.'], 422);
}

$stmt = $pdo->prepare("
    SELECT id, protocolo, nome, telefone, email, endereco, referencia, observacoes, status, criado_em
    FROM rct_solicitacoes
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$sol = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sol) {
    json(['error' => 'Solicitação não encontrada.'], 404);
}

$stmtItens = $pdo->prepare("
    SELECT id, solicitacao_id, tipo, quantidade, descricao
    FROM rct_solicitacao_itens
    WHERE solicitacao_id = ?
    ORDER BY id ASC
");
$stmtItens->execute([$id]);
$itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

$stmtOs = $pdo->prepare("
    SELECT id, solicitacao_id, status, criado_em
    FROM rct_os
    WHERE solicitacao_id = ?
    ORDER BY id DESC
    LIMIT 1
");
$stmtOs->execute([$id]);
$os = $stmtOs->fetch(PDO::FETCH_ASSOC);

json([
    'solicitacao' => [
        'id' => (int)$sol['id'],
        'protocolo' => $sol['protocolo'],
        'nome' => $sol['nome'],
        'telefone' => $sol['telefone'],
        'email' => $sol['email'],
        'endereco' => $sol['endereco'],
        'referencia' => $sol['referencia'],
        'observacoes' => $sol['observacoes'],
        'status' => $sol['status'],
        'criado_em' => $sol['criado_em'],
        'itens' => array_map(function($r) {
            return [
                'id' => (int)$r['id'],
                'solicitacao_id' => (int)$r['solicitacao_id'],
                'tipo' => $r['tipo'],
                'quantidade' => (int)$r['quantidade'],
                'descricao' => $r['descricao'],
            ];
        }, $itens),
        'os' => $os ? [
            'id' => (int)$os['id'],
            'status' => $os['status'],
            'criado_em' => $os['criado_em'],
        ] : null
    ]
]);