<?php
require_once __DIR__ . '/../lib/db.php';

function fail($msg, $code = 400) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
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

  $mes = (int)($valor ?? date('n'));
  if ($mes < 1 || $mes > 12) fail("Mês inválido (1..12)");
  $ini = new DateTime(sprintf("%04d-%02d-01 00:00:00", $ano, $mes));
  $fim = (clone $ini)->modify("+1 month");
  return [$ini, $fim];
}

function csv_escape($v) {
  if ($v === null) return '';
  $v = (string)$v;
  $needs = strpbrk($v, "\"\n\r,;") !== false;
  $v = str_replace("\"", "\"\"", $v);
  return $needs ? "\"$v\"" : $v;
}

function array_to_csv($rows, $headerMap) {
  // $headerMap = ['coluna_no_array' => 'Titulo do CSV', ...] na ordem desejada
  $out = [];
  $out[] = implode(",", array_map('csv_escape', array_values($headerMap)));

  foreach ($rows as $r) {
    $line = [];
    foreach ($headerMap as $key => $_title) {
      $line[] = csv_escape($r[$key] ?? null);
    }
    $out[] = implode(",", $line);
  }
  return implode("\r\n", $out) . "\r\n";
}

try {
  $pdo = db();

  $tipo  = $_GET['periodo_tipo'] ?? 'MES';
  $ano   = $_GET['ano'] ?? date('Y');
  $valor = $_GET['valor'] ?? null;

  [$ini, $fim] = resolvePeriod($tipo, $ano, $valor);
  $iniStr = $ini->format('Y-m-d H:i:s');
  $fimStr = $fim->format('Y-m-d H:i:s');

  // ---------- Entradas de bens ----------
  $sqlEb = "
    SELECT
      e.id,
      e.data_entrada,
      e.documento,
      f.nome AS fornecedor_nome,
      e.valor AS valor_entrada,

      b.id_patrimonial,
      b.descricao AS bem_descricao,
      b.marca_modelo,
      b.tipo_eletronico,
      b.estado,
      c.nome AS categoria_nome,
      s.nome AS setor_nome,
      sa.nome AS sala_nome,
      u.nome AS responsavel_nome
    FROM entradas_bem e
    INNER JOIN bens_patrimoniais b ON b.id = e.bem_id
    LEFT JOIN fornecedores f ON f.id = e.fornecedor_id
    LEFT JOIN categorias c ON c.id = b.categoria_id
    LEFT JOIN setores s ON s.id = b.setor_id
    LEFT JOIN salas sa ON sa.id = b.sala_id
    LEFT JOIN usuarios u ON u.id = b.responsavel_usuario_id
    WHERE e.data_entrada >= :ini AND e.data_entrada < :fim
    ORDER BY e.data_entrada ASC, e.id ASC
  ";
  $stEb = $pdo->prepare($sqlEb);
  $stEb->execute([':ini' => $iniStr, ':fim' => $fimStr]);
  $entradasBens = $stEb->fetchAll(PDO::FETCH_ASSOC);

  // ---------- Transferências de bens ----------
  $sqlTb = "
    SELECT
      t.id,
      t.data_transferencia,
      b.id_patrimonial,
      b.descricao AS bem_descricao,
      so.nome AS setor_origem_nome,
      sao.nome AS sala_origem_nome,
      uo.nome AS responsavel_origem_nome,
      sd.nome AS setor_destino_nome,
      sad.nome AS sala_destino_nome,
      ud.nome AS responsavel_destino_nome,
      up.nome AS usuario_operacao_nome,
      t.observacao
    FROM transferencias_bens t
    INNER JOIN bens_patrimoniais b ON b.id = t.bem_id
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

  // ---------- Entradas de materiais ----------
  $sqlEe = "
    SELECT
      e.id,
      e.data_entrada,
      e.documento,
      f.nome AS fornecedor_nome,
      u.nome AS usuario_operacao_nome,
      e.quantidade,
      e.custo_unitario,
      i.codigo,
      i.produto_base,
      i.descricao AS item_descricao,
      i.unidade,
      c.nome AS categoria_nome
    FROM entradas_estoque e
    INNER JOIN itens_estoque i ON i.id = e.item_id
    LEFT JOIN fornecedores f ON f.id = e.fornecedor_id
    LEFT JOIN usuarios u ON u.id = e.usuario_operacao_id
    LEFT JOIN categorias c ON c.id = i.categoria_id
    WHERE e.data_entrada >= :ini AND e.data_entrada < :fim
    ORDER BY e.data_entrada ASC, e.id ASC
  ";
  $stEe = $pdo->prepare($sqlEe);
  $stEe->execute([':ini' => $iniStr, ':fim' => $fimStr]);
  $entradasMateriais = $stEe->fetchAll(PDO::FETCH_ASSOC);

  // Cabeçalhos (ordem das colunas no CSV)
  $csvEntradasBens = array_to_csv($entradasBens, [
    'id' => 'ID',
    'data_entrada' => 'Data Entrada',
    'documento' => 'Documento',
    'fornecedor_nome' => 'Fornecedor',
    'valor_entrada' => 'Valor Entrada',
    'id_patrimonial' => 'Tombamento',
    'bem_descricao' => 'Bem',
    'marca_modelo' => 'Marca/Modelo',
    'tipo_eletronico' => 'Tipo',
    'estado' => 'Estado',
    'categoria_nome' => 'Categoria',
    'setor_nome' => 'Setor',
    'sala_nome' => 'Sala',
    'responsavel_nome' => 'Responsável'
  ]);

  $csvTransferencias = array_to_csv($transferenciasBens, [
    'id' => 'ID',
    'data_transferencia' => 'Data Transferência',
    'id_patrimonial' => 'Tombamento',
    'bem_descricao' => 'Bem',
    'setor_origem_nome' => 'Setor Origem',
    'sala_origem_nome' => 'Sala Origem',
    'responsavel_origem_nome' => 'Responsável Origem',
    'setor_destino_nome' => 'Setor Destino',
    'sala_destino_nome' => 'Sala Destino',
    'responsavel_destino_nome' => 'Responsável Destino',
    'usuario_operacao_nome' => 'Usuário Operação',
    'observacao' => 'Observação'
  ]);

  $csvEntradasMateriais = array_to_csv($entradasMateriais, [
    'id' => 'ID',
    'data_entrada' => 'Data Entrada',
    'documento' => 'Documento',
    'fornecedor_nome' => 'Fornecedor',
    'usuario_operacao_nome' => 'Usuário Operação',
    'quantidade' => 'Quantidade',
    'custo_unitario' => 'Custo Unitário',
    'codigo' => 'Código',
    'produto_base' => 'Produto Base',
    'item_descricao' => 'Item',
    'unidade' => 'Unidade',
    'categoria_nome' => 'Categoria'
  ]);

  // Monta ZIP em arquivo temporário
  $zipPath = tempnam(sys_get_temp_dir(), 'rel_') . '.zip';
  $zip = new ZipArchive();
  if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
    fail("Não foi possível criar o ZIP", 500);
  }

  $zip->addFromString("entradas_bens.csv", $csvEntradasBens);
  $zip->addFromString("transferencias_bens.csv", $csvTransferencias);
  $zip->addFromString("entradas_materiais.csv", $csvEntradasMateriais);

  $zip->close();

  $nome = sprintf(
    "relatorio_geral_%s_%s_%s.zip",
    strtoupper($tipo),
    $ano,
    $valor ? $valor : "ANO"
  );

  header('Content-Type: application/zip');
  header('Content-Disposition: attachment; filename="'.$nome.'"');
  header('Content-Length: ' . filesize($zipPath));
  readfile($zipPath);
  @unlink($zipPath);
  exit;

} catch (Exception $e) {
  fail("Erro interno: " . $e->getMessage(), 500);
}