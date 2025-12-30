<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../lib/db.php';

function fail($msg, $code = 400) {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

function resolvePeriod($tipo, $ano, $valor) {
  $tipo = strtoupper(trim($tipo ?? 'MES'));
  $ano  = (int)($ano ?? date('Y'));

  if ($ano < 2000 || $ano > 2100) fail("Ano inválido");

  if ($tipo === 'ANO') {
    $ini = new DateTime("$ano-01-01 00:00:00");
    $fim = new DateTime(($ano + 1) . "-01-01 00:00:00");
    return [$ini, $fim];
  }

  if ($tipo === 'SEMESTRE') {
    $s = strtoupper(trim($valor ?? 'S1'));
    if (!in_array($s, ['S1','S2'], true)) fail("Semestre inválido (use S1 ou S2)");
    $mesIni = ($s === 'S1') ? 1 : 7;
    $ini = new DateTime(sprintf("%04d-%02d-01 00:00:00", $ano, $mesIni));
    $fim = (clone $ini)->modify("+6 months");
    return [$ini, $fim];
  }

  if ($tipo === 'TRIMESTRE') {
    $t = strtoupper(trim($valor ?? 'T1'));
    $map = ['T1'=>1,'T2'=>4,'T3'=>7,'T4'=>10];
    if (!isset($map[$t])) fail("Trimestre inválido (use T1, T2, T3, T4)");
    $ini = new DateTime(sprintf("%04d-%02d-01 00:00:00", $ano, $map[$t]));
    $fim = (clone $ini)->modify("+3 months");
    return [$ini, $fim];
  }

  // default: MES
  $mes = (int)($valor ?? date('n'));
  if ($mes < 1 || $mes > 12) fail("Mês inválido (1..12)");
  $ini = new DateTime(sprintf("%04d-%02d-01 00:00:00", $ano, $mes));
  $fim = (clone $ini)->modify("+1 month");
  return [$ini, $fim];
}

try {
  $pdo = db();

  $tipo  = $_GET['periodo_tipo'] ?? 'MES';   // MES | TRIMESTRE | SEMESTRE | ANO
  $ano   = $_GET['ano'] ?? date('Y');
  $valor = $_GET['valor'] ?? null;           // mês (1..12) OU T1..T4 OU S1..S2

  [$ini, $fim] = resolvePeriod($tipo, $ano, $valor);
  $iniStr = $ini->format('Y-m-d H:i:s');
  $fimStr = $fim->format('Y-m-d H:i:s');

  // =========================
  // 1) ENTRADAS DE BENS
  // =========================
// 1) BENS CADASTRADOS NO PERÍODO (em vez de entradas_bem)
$sqlEb = "
  SELECT
    b.id,
    b.criado_em AS data_cadastro,
    b.id_patrimonial,
    b.descricao AS bem_descricao,
    b.marca_modelo,
    b.tipo_eletronico,
    b.estado,
    b.data_aquisicao,
    b.valor AS valor_bem,
    c.nome AS categoria_nome,
    s.nome AS setor_nome,
    sa.nome AS sala_nome,
    u.nome AS responsavel_nome,
    u.email AS responsavel_email
  FROM bens_patrimoniais b
  LEFT JOIN categorias c ON c.id = b.categoria_id
  LEFT JOIN setores s ON s.id = b.setor_id
  LEFT JOIN salas sa ON sa.id = b.sala_id
  LEFT JOIN usuarios u ON u.id = b.responsavel_usuario_id
  WHERE b.criado_em >= :ini AND b.criado_em < :fim
  ORDER BY b.criado_em ASC, b.id ASC
";
$stEb = $pdo->prepare($sqlEb);
$stEb->execute([':ini' => $iniStr, ':fim' => $fimStr]);
$entradasBens = $stEb->fetchAll(PDO::FETCH_ASSOC);

  // =========================
  // 2) TRANSFERÊNCIAS DE BENS
  // =========================
  $sqlTb = "
    SELECT
      t.id,
      t.data_transferencia,
      t.observacao,

      t.bem_id,
      b.id_patrimonial,
      b.descricao AS bem_descricao,
      b.marca_modelo,
      b.tipo_eletronico,
      b.estado,

      b.categoria_id,
      c.nome AS categoria_nome,

      -- origem
      t.setor_origem_id,
      so.nome AS setor_origem_nome,
      t.sala_origem_id,
      sao.nome AS sala_origem_nome,
      t.responsavel_origem_id,
      uo.nome AS responsavel_origem_nome,

      -- destino
      t.setor_destino_id,
      sd.nome AS setor_destino_nome,
      t.sala_destino_id,
      sad.nome AS sala_destino_nome,
      t.responsavel_destino_id,
      ud.nome AS responsavel_destino_nome,

      -- quem fez
      t.usuario_operacao_id,
      up.nome AS usuario_operacao_nome,
      up.email AS usuario_operacao_email

    FROM transferencias_bens t
    INNER JOIN bens_patrimoniais b ON b.id = t.bem_id
    LEFT JOIN categorias c ON c.id = b.categoria_id

    LEFT JOIN setores so ON so.id = t.setor_origem_id
    LEFT JOIN salas sao ON sao.id = t.sala_origem_id
    LEFT JOIN usuarios uo ON uo.id = t.responsavel_origem_id

    LEFT JOIN setores sd ON sd.id = t.setor_destino_id
    LEFT JOIN salas sad ON sad.id = t.sala_destino_id
    LEFT JOIN usuarios ud ON ud.id = t.responsavel_destino_id

    LEFT JOIN usuarios up ON up.id = t.usuario_operacao_id

    WHERE t.data_transferencia >= :ini AND t.data_transferencia < :fim
    ORDER BY t.data_transferencia ASC, t.id ASC
  ";
  $stTb = $pdo->prepare($sqlTb);
  $stTb->execute([':ini' => $iniStr, ':fim' => $fimStr]);
  $transferenciasBens = $stTb->fetchAll(PDO::FETCH_ASSOC);

  // =========================
  // 3) ENTRADAS DE MATERIAIS (CONSUMO)
  // =========================
$sqlEe = "
  SELECT
    i.id,
    i.criado_em AS data_cadastro,
    i.codigo,
    i.produto_base,
    i.descricao AS item_descricao,
    i.unidade,
    i.valor_unitario,
    i.estoque_atual,
    i.local_guarda,
    c.nome AS categoria_nome,
    u.nome AS usuario_cadastro_nome,
    u.email AS usuario_cadastro_email
  FROM itens_estoque i
  LEFT JOIN categorias c ON c.id = i.categoria_id
  LEFT JOIN usuarios u ON u.id = i.criado_por_usuario_id
  WHERE i.criado_em >= :ini AND i.criado_em < :fim
  ORDER BY i.criado_em ASC, i.id ASC
";
$stEe = $pdo->prepare($sqlEe);
$stEe->execute([':ini' => $iniStr, ':fim' => $fimStr]);
$entradasMateriais = $stEe->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'ok' => true,
    'periodo' => [
      'tipo' => strtoupper($tipo),
      'ano' => (int)$ano,
      'valor' => $valor,
      'data_ini' => $iniStr,
      'data_fim' => $fimStr
    ],
    'resumo' => [
      'qtd_entradas_bens' => count($entradasBens),
      'qtd_transferencias_bens' => count($transferenciasBens),
      'qtd_entradas_materiais' => count($entradasMateriais),
    ],
    'dados' => [
      'entradas_bens' => $entradasBens,
      'transferencias_bens' => $transferenciasBens,
      'entradas_materiais' => $entradasMateriais
    ]
  ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
  fail("Erro interno: " . $e->getMessage(), 500);
}