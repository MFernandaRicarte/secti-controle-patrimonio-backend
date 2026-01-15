<?php

require_once __DIR__ . '/../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido. Use POST.'], 405);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$produtoBase   = trim($input['produto_base'] ?? '');
$descricao     = trim($input['descricao'] ?? '');
$unidade       = trim($input['unidade'] ?? '');
$estoqueAtual  = isset($input['estoque_atual']) ? (int)$input['estoque_atual'] : 0;
$localGuarda   = trim($input['local_guarda'] ?? '');
$valorUnitario = isset($input['valor_unitario']) && $input['valor_unitario'] !== ''
    ? (float)$input['valor_unitario']
    : null;
$criadoPorUsuarioId = isset($input['usuario_id']) ? (int)$input['usuario_id'] : null;
$categoriaId        = null;

$erros = [];

if ($produtoBase === '') {
    $erros[] = 'produto_base é obrigatório.';
}
if ($descricao === '') {
    $erros[] = 'descricao é obrigatória.';
}
if ($unidade === '') {
    $erros[] = 'unidade é obrigatória.';
}

if ($erros) {
    json(['error' => 'Dados inválidos.', 'detalhes' => $erros], 422);
    exit;
}

$dsn  = 'mysql:host=127.0.0.1;port=3307;dbname=secti;charset=utf8mb4';
$user = 'secti';
$pass = 'secti';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

try {
    $pdo->beginTransaction();

    $codigo = 'MAT-' . date('YmdHis') . '-' . bin2hex(random_bytes(2));

    $sqlInsert = "
        INSERT INTO itens_estoque (
            codigo,
            produto_base,
            descricao,
            unidade,
            valor_unitario,
            criado_por_usuario_id,
            estoque_atual,
            estoque_minimo,
            local_guarda,
            categoria_id
        ) VALUES (
            :codigo,
            :produto_base,
            :descricao,
            :unidade,
            :valor_unitario,
            :criado_por_usuario_id,
            :estoque_atual,
            0,
            :local_guarda,
            :categoria_id
        )
    ";

    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute([
        ':codigo'               => $codigo,
        ':produto_base'         => $produtoBase,
        ':descricao'            => $descricao,
        ':unidade'              => $unidade,
        ':valor_unitario'       => $valorUnitario,
        ':criado_por_usuario_id'=> $criadoPorUsuarioId,
        ':estoque_atual'        => $estoqueAtual,
        ':local_guarda'         => $localGuarda,
        ':categoria_id'         => $categoriaId,
    ]);

    $novoId = (int)$pdo->lastInsertId();

    $sqlSelect = "
        SELECT
            i.id,
            i.produto_base,
            i.descricao,
            i.unidade,
            i.estoque_atual,
            i.valor_unitario,
            i.local_guarda,
            i.criado_em,
            c.nome AS categoria,
            u.nome AS usuario_cadastro
        FROM itens_estoque i
        LEFT JOIN categorias c ON c.id = i.categoria_id
        LEFT JOIN usuarios   u ON u.id = i.criado_por_usuario_id
        WHERE i.id = :id
    ";

    $stmt2 = $pdo->prepare($sqlSelect);
    $stmt2->execute([':id' => $novoId]);
    $row = $stmt2->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    if (!$row) {
        json(['error' => 'Erro ao buscar material criado.'], 500);
        exit;
    }

    $item = [
        'id'              => (int)$row['id'],
        'produto_base'    => $row['produto_base'],
        'descricao'       => $row['descricao'],
        'unidade'         => $row['unidade'],
        'estoque_atual'   => (int)$row['estoque_atual'],
        'valor_unitario'  => $row['valor_unitario'],
        'local_guarda'    => $row['local_guarda'],
        'categoria'       => $row['categoria'],
        'usuario_cadastro'=> $row['usuario_cadastro'],
        'data_cadastro'   => $row['criado_em'],
    ];

    json($item, 201);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json(['error' => 'Erro ao salvar o material.', 'detalhes' => $e->getMessage()], 500);
    exit;
}