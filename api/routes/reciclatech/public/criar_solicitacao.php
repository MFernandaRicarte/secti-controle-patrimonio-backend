<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido. Use POST.'], 405);
}

$pdo = db();
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$nome        = trim($input['nome'] ?? '');
$telefone    = trim($input['telefone'] ?? '');
$email       = trim($input['email'] ?? '');
$endereco    = trim($input['endereco'] ?? '');
$referencia  = trim($input['referencia'] ?? '');
$observacoes = trim($input['observacoes'] ?? '');
$itens       = $input['itens'] ?? [];

$erros = [];

if ($nome === '')      $erros[] = 'nome é obrigatório.';
if ($telefone === '')  $erros[] = 'telefone é obrigatório.';
if ($endereco === '')  $erros[] = 'endereco é obrigatório.';

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erros[] = 'email inválido.';
}

if (!is_array($itens) || count($itens) === 0) {
    $erros[] = 'itens é obrigatório (ao menos 1 item).';
}

if (count($itens) > 30) {
    $erros[] = 'Máximo de 30 itens por solicitação.';
}

foreach ($itens as $idx => $item) {
    $tipo = trim($item['tipo'] ?? '');
    $qtd  = isset($item['quantidade']) ? (int)$item['quantidade'] : 0;

    if ($tipo === '')  $erros[] = "itens[$idx].tipo é obrigatório.";
    if ($qtd <= 0)     $erros[] = "itens[$idx].quantidade deve ser maior que 0.";
    if ($qtd > 100)    $erros[] = "itens[$idx].quantidade máxima é 100.";
}

if ($erros) {
    json(['error' => 'Dados inválidos.', 'detalhes' => $erros], 422);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$stCheck = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM rct_solicitacoes
    WHERE DATE(criado_em) = CURDATE()
      AND ip_origem = ?
");
try {
    $stCheck->execute([$ip]);
    $total = (int)$stCheck->fetchColumn();
    if ($total >= 5) {
        json(['error' => 'Limite de solicitações diárias atingido. Tente novamente amanhã.'], 429);
    }
} catch (PDOException $e) {
}

$pdo->beginTransaction();

try {
    $tmpProto = 'RCT-TMP';

    try {
        $stmt = $pdo->prepare("
            INSERT INTO rct_solicitacoes
            (protocolo, nome, telefone, email, endereco, referencia, observacoes, status, criado_por, ip_origem)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'ABERTA', NULL, ?)
        ");
        $stmt->execute([
            $tmpProto,
            $nome,
            $telefone,
            $email ?: null,
            $endereco,
            $referencia ?: null,
            $observacoes ?: null,
            $ip,
        ]);
    } catch (PDOException $e) {
        $stmt = $pdo->prepare("
            INSERT INTO rct_solicitacoes
            (protocolo, nome, telefone, email, endereco, referencia, observacoes, status, criado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'ABERTA', NULL)
        ");
        $stmt->execute([
            $tmpProto,
            $nome,
            $telefone,
            $email ?: null,
            $endereco,
            $referencia ?: null,
            $observacoes ?: null,
        ]);
    }

    $id = (int)$pdo->lastInsertId();
    $protocolo = 'RCT-' . date('Ymd') . '-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);

    $upd = $pdo->prepare("UPDATE rct_solicitacoes SET protocolo = ? WHERE id = ?");
    $upd->execute([$protocolo, $id]);

    $stmtItem = $pdo->prepare("
        INSERT INTO rct_solicitacao_itens
        (solicitacao_id, tipo, quantidade, descricao, categoria_id)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmtItemFallback = null;

    foreach ($itens as $item) {
        $categoriaId = isset($item['categoria_id']) ? (int)$item['categoria_id'] : null;

        try {
            $stmtItem->execute([
                $id,
                trim($item['tipo']),
                (int)$item['quantidade'],
                trim($item['descricao'] ?? '') ?: null,
                $categoriaId,
            ]);
        } catch (PDOException $e) {
            if (!$stmtItemFallback) {
                $stmtItemFallback = $pdo->prepare("
                    INSERT INTO rct_solicitacao_itens
                    (solicitacao_id, tipo, quantidade, descricao)
                    VALUES (?, ?, ?, ?)
                ");
            }
            $stmtItemFallback->execute([
                $id,
                trim($item['tipo']),
                (int)$item['quantidade'],
                trim($item['descricao'] ?? '') ?: null,
            ]);
        }
    }

    $pdo->commit();

    json([
        'ok' => true,
        'solicitacao' => [
            'id'        => $id,
            'protocolo' => $protocolo,
            'status'    => 'ABERTA',
        ],
        'mensagem' => "Sua solicitação foi registrada com sucesso! Guarde o protocolo $protocolo para acompanhar o andamento.",
    ], 201);

} catch (PDOException $e) {
    $pdo->rollBack();
    json(['error' => 'Erro ao criar solicitação. Tente novamente.', 'debug' => $e->getMessage()], 500);
}
