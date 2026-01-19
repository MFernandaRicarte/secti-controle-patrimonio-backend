<?php
require __DIR__.'/../../lib/http.php';
require __DIR__.'/../../lib/auth.php';
require __DIR__.'/../../lib/db.php';

cors();
$usuario = requireAuth();
$userId = (int)$usuario['id'];
$perfilNome = $usuario['perfil_nome'] ?? null;

if (!$perfilNome) {
  $perfilNome = ((int)($usuario['perfil_id'] ?? 0) === 2) ? 'SUPERADMIN' : 'RESTRITO';
}

try {
  $pdo = db();

  $publico = ($perfilNome === 'SUPERADMIN') ? ['TODOS', 'SUPERADMIN'] : ['TODOS', 'RESTRITO'];

  $in = implode(',', array_fill(0, count($publico), '?'));

  $sql = "
    SELECT
      n.id,
      n.titulo,
      n.mensagem,
      n.publico,
      n.criado_em,
      n.criado_por_usuario_id,
      u.nome AS criado_por_nome,
      nu.lida_em
    FROM notificacoes n
    LEFT JOIN usuarios u ON u.id = n.criado_por_usuario_id
    LEFT JOIN notificacoes_usuario nu
      ON nu.notificacao_id = n.id AND nu.usuario_id = ?
    WHERE n.publico IN ($in)
    ORDER BY n.criado_em DESC
    LIMIT 80
  ";

  $params = array_merge([$userId], $publico);
  $st = $pdo->prepare($sql);
  $st->execute($params);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  $st2 = $pdo->prepare("
    SELECT id, nome
    FROM usuarios
    WHERE data_nascimento IS NOT NULL
      AND DATE_FORMAT(data_nascimento, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
    ORDER BY nome ASC
  ");
  $st2->execute();
  $aniversariantes = $st2->fetchAll(PDO::FETCH_ASSOC);

  $birthdayNotifs = [];
  foreach ($aniversariantes as $a) {
    $birthdayNotifs[] = [
      'id' => 'birthday-' . $a['id'],
      'titulo' => 'Aniversário 🎉',
      'mensagem' => 'Hoje é aniversário de ' . $a['nome'] . '!',
      'publico' => 'TODOS',
      'criado_em' => date('Y-m-d 00:00:00'),
      'criado_por_usuario_id' => null,
      'criado_por_nome' => null,
      'lida_em' => null,
      'tipo' => 'birthday',
    ];
  }

  $unread = 0;
  foreach ($rows as $r) {
    if (empty($r['lida_em'])) $unread++;
  }

  json([
    'unread_count' => $unread + count($birthdayNotifs),
    'items' => array_merge($birthdayNotifs, $rows),
  ]);
} catch (Throwable $e) {
  json(['error' => $e->getMessage()], 500);
}