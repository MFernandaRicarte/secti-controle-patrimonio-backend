<?php

require __DIR__ . '/../lib/http.php';
cors();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
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
    echo json_encode(['error' => 'Erro ao conectar ao banco de dados.']);
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$whereParts = ["b.excluido_em IS NULL"];
$params = [];

if ($q !== '') {
    $whereParts[] = "(
        b.id_patrimonial LIKE :q
        OR b.descricao LIKE :q
        OR COALESCE(c.nome, '') LIKE :q
        OR COALESCE(s.nome, '') LIKE :q
        OR COALESCE(sa.nome, '') LIKE :q
        OR COALESCE(u.nome, '') LIKE :q
    )";
    $params[':q'] = '%' . $q . '%';
}

$where = "WHERE " . implode(" AND ", $whereParts);

$sql = "
SELECT
    b.id,
    b.id_patrimonial,
    b.descricao,
    b.tipo_eletronico,

    b.categoria_id,
    c.nome  AS categoria,

    b.setor_id,
    s.nome  AS setor,

    b.sala_id,
    sa.nome AS sala,

    b.responsavel_usuario_id,
    u.nome  AS responsavel,

    b.estado,
    b.data_aquisicao,
    b.valor,
    b.criado_em
FROM bens_patrimoniais b
LEFT JOIN categorias c  ON c.id  = b.categoria_id
LEFT JOIN setores    s  ON s.id  = b.setor_id
LEFT JOIN salas      sa ON sa.id = b.sala_id
LEFT JOIN usuarios   u  ON u.id  = b.responsavel_usuario_id
{$where}
ORDER BY b.criado_em DESC, b.id DESC
LIMIT 200
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $bens = array_map(function ($row) {
        $localizacao = null;

        if (!empty($row['setor']) && !empty($row['sala'])) {
            $localizacao = $row['setor'] . ' / ' . $row['sala'];
        } elseif (!empty($row['setor'])) {
            $localizacao = $row['setor'];
        } elseif (!empty($row['sala'])) {
            $localizacao = $row['sala'];
        }

        return [
            'id'                     => (int) $row['id'],
            'patrimonial'            => $row['id_patrimonial'],
            'descricao'              => $row['descricao'],
            'tipo_eletronico'        => $row['tipo_eletronico'],
            'categoria_id'           => $row['categoria_id'] ? (int)$row['categoria_id'] : null,
            'setor_id'               => $row['setor_id'] ? (int)$row['setor_id'] : null,
            'sala_id'                => $row['sala_id'] ? (int)$row['sala_id'] : null,
            'responsavel_usuario_id' => $row['responsavel_usuario_id'] ? (int)$row['responsavel_usuario_id'] : null,
            'categoria'              => $row['categoria'],
            'localizacao'            => $localizacao,
            'responsavel'            => $row['responsavel'],

            'estado'                 => $row['estado'],
            'data_aquisicao'         => $row['data_aquisicao'],
            'valor'                  => $row['valor'],
        ];
    }, $rows);

    echo json_encode($bens);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar bens.']);
}