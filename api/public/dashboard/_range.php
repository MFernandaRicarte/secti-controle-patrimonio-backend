<?php
function dashboard_get_range(): array {
  $ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
  $mes = isset($_GET['mes']) ? (int)$_GET['mes'] : 0;
  $sem = isset($_GET['semestre']) ? (int)$_GET['semestre'] : 0;

  if ($mes >= 1 && $mes <= 12) {
    $start = sprintf('%04d-%02d-01', $ano, $mes);
    $end = date('Y-m-d', strtotime($start . ' +1 month'));
    return [$start, $end, $ano];
  }

  if ($sem === 1 || $sem === 2) {
    $mStart = ($sem === 1) ? 1 : 7;
    $start = sprintf('%04d-%02d-01', $ano, $mStart);
    $end = date('Y-m-d', strtotime($start . ' +6 month'));
    return [$start, $end, $ano];
  }

  // ano inteiro
  $start = sprintf('%04d-01-01', $ano);
  $end = sprintf('%04d-01-01', $ano + 1);
  return [$start, $end, $ano];
}