<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido. Use POST.'], 405);
}

$user = requireAdminOrSuperAdmin();
$pdo = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    json(['error' => 'id é obrigatório.'], 422);
}

function tableHasColumn(PDO $pdo, string $table, string $column): bool {
    $st = $pdo->prepare("
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
    ");
    $st->execute([$table, $column]);
    return (bool)$st->fetchColumn();
}

try {
    $stmt = $pdo->prepare("
        SELECT id, status
        FROM rct_solicitacoes
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $sol = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sol) {
        json(['error' => 'Solicitação não encontrada.'], 404);
    }

    if (($sol['status'] ?? '') === 'CANCELADA') {
        json(['error' => 'Solicitação cancelada. Não é possível gerar OS.'], 422);
    }

    $stCheck = $pdo->prepare("SELECT id FROM rct_os WHERE solicitacao_id = ? LIMIT 1");
    $stCheck->execute([$id]);
    $existing = $stCheck->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        json(['error' => 'Já existe OS para esta solicitação.', 'os_id' => (int)$existing['id']], 409);
    }

    $stmtItens = $pdo->prepare("
        SELECT tipo, quantidade, descricao
        FROM rct_solicitacao_itens
        WHERE solicitacao_id = ?
        ORDER BY id ASC
    ");
    $stmtItens->execute([$id]);
    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

    if (!$itens) {
        json(['error' => 'Solicitação não possui itens.'], 422);
    }

    $pdo->beginTransaction();

    $hasCriadoPor = tableHasColumn($pdo, 'rct_os', 'criado_por');

    if ($hasCriadoPor) {
        $stOs = $pdo->prepare("
            INSERT INTO rct_os (solicitacao_id, status, criado_por)
            VALUES (?, 'ABERTA', ?)
        ");
        $stOs->execute([$id, (int)$user['id']]);
    } else {
        $stOs = $pdo->prepare("
            INSERT INTO rct_os (solicitacao_id, status)
            VALUES (?, 'ABERTA')
        ");
        $stOs->execute([$id]);
    }

    $osId = (int)$pdo->lastInsertId();

    $stItem = $pdo->prepare("
        INSERT INTO rct_os_itens (os_id, tipo, quantidade, descricao)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($itens as $i) {
        $stItem->execute([
            $osId,
            $i['tipo'],
            (int)$i['quantidade'],
            ($i['descricao'] ?? '') !== '' ? $i['descricao'] : null
        ]);
    }

    $stUpd = $pdo->prepare("
        UPDATE rct_solicitacoes
        SET status = 'TRIAGEM'
        WHERE id = ?
    ");
    $stUpd->execute([$id]);

    $pdo->commit();

    json([
        'ok' => true,
        'os' => [
            'id' => $osId,
            'solicitacao_id' => $id,
            'status' => 'ABERTA',
        ]
    ], 201);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json([
        'error' => 'Erro ao gerar OS.',
        'debug' => $e->getMessage(),
    ], 500);
}