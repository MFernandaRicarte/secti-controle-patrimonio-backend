<?php

require __DIR__ . '/../lib/http.php';

cors();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido. Use GET.']);
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
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao conectar ao banco.']);
    exit;
}

$sql = "
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
    ORDER BY i.criado_em DESC, i.id DESC
";

try {
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $itens = array_map(function ($row) {
        return [
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
    }, $rows);

    echo json_encode($itens);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar materiais.']);
}