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

$id = (int)($GLOBALS['routeParams']['id'] ?? 0);
if ($id <= 0) {
    json(['error' => 'id inválido.'], 400);
}

// OS
$stmt = $pdo->prepare("
    SELECT
        o.*,
        s.protocolo,
        s.nome,
        s.telefone,
        s.email,
        s.endereco,
        s.referencia,
        s.observacoes,
        s.status AS solicitacao_status,
        s.criado_em AS solicitacao_criado_em
    FROM rct_os o
    JOIN rct_solicitacoes s ON s.id = o.solicitacao_id
    WHERE o.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$os = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$os) {
    json(['error' => 'OS não encontrada.'], 404);
}

// Itens da OS
$stmt2 = $pdo->prepare("
    SELECT
        i.*
    FROM rct_os_itens i
    WHERE i.os_id = ?
    ORDER BY i.id ASC
");
$stmt2->execute([$id]);
$itens = $stmt2->fetchAll(PDO::FETCH_ASSOC);

json([
    'os' => $os,
    'itens' => $itens,
]);