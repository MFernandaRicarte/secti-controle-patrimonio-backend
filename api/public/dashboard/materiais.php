
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo json_encode(["ok" => true, "step" => "inicio", "dir" => __DIR__]);
exit;

require __DIR__ . '/../../routes/dashboard/materiais.php';

$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');

$sql = "
  SELECT
    MONTH(data_cadastro) AS mes_num,
    LPAD(MONTH(data_cadastro), 2, '0') AS mes,
    COUNT(*) AS total
  FROM itens_estoque
  WHERE YEAR(data_cadastro) = ?
  GROUP BY MONTH(data_cadastro)
  ORDER BY mes_num
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$ano]);

$out = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $out[] = [
    'mes' => $row['mes'],
    'total' => (int)$row['total'],
  ];
}

echo json_encode($out);