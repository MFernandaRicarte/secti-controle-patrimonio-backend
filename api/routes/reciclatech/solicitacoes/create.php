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

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$nome = trim($input['nome'] ?? '');
$telefone = trim($input['telefone'] ?? '');
$email = trim($input['email'] ?? '');
$endereco = trim($input['endereco'] ?? '');
$referencia = trim($input['referencia'] ?? '');
$observacoes = trim($input['observacoes'] ?? '');
$itens = $input['itens'] ?? [];

$erros = [];

if ($nome === '') $erros[] = 'nome é obrigatório.';
if ($endereco === '') $erros[] = 'endereco é obrigatório.';
if (!is_array($itens) || count($itens) === 0) $erros[] = 'itens é obrigatório (array).';

foreach ($itens as $idx => $item) {
    $tipo = trim($item['tipo'] ?? '');
    $quantidade = isset($item['quantidade']) ? (int)$item['quantidade'] : 0;

    if ($tipo === '') $erros[] = "itens[$idx].tipo é obrigatório.";
    if ($quantidade <= 0) $erros[] = "itens[$idx].quantidade deve ser > 0.";
}

if ($erros) {
    json(['error' => 'Dados inválidos.', 'detalhes' => $erros], 422);
}

$pdo->beginTransaction();

try {
    $tmpProto = 'RCT-TMP';

    $stmt = $pdo->prepare("
        INSERT INTO rct_solicitacoes
        (protocolo, nome, telefone, email, endereco, referencia, observacoes, status, criado_por)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'ABERTA', ?)
    ");

    $stmt->execute([
        $tmpProto,
        $nome,
        $telefone ?: null,
        $email ?: null,
        $endereco,
        $referencia ?: null,
        $observacoes ?: null,
        (int)$user['id'],
    ]);

    $id = (int)$pdo->lastInsertId();
    $protocolo = 'RCT-' . date('Ymd') . '-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);

    $upd = $pdo->prepare("UPDATE rct_solicitacoes SET protocolo = ? WHERE id = ?");
    $upd->execute([$protocolo, $id]);

    $stmtItem = $pdo->prepare("
        INSERT INTO rct_solicitacao_itens
        (solicitacao_id, tipo, quantidade, descricao)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($itens as $item) {
        $stmtItem->execute([
            $id,
            trim($item['tipo']),
            (int)$item['quantidade'],
            trim($item['descricao'] ?? '') ?: null,
        ]);
    }

    $pdo->commit();

    json([
        'ok' => true,
        'solicitacao' => [
            'id' => $id,
            'protocolo' => $protocolo,
            'status' => 'ABERTA',
        ],
    ], 201);

} catch (PDOException $e) {
    $pdo->rollBack();
    json(['error' => 'Erro ao criar solicitação.', 'debug' => $e->getMessage()], 500);
}