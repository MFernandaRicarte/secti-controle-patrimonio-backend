<?php
require __DIR__.'/../../lib/http.php';
require __DIR__.'/../../lib/auth.php';
require __DIR__.'/../../lib/db.php';

cors();

$currentUser = requireAuth();
$perfilAtual = strtoupper($currentUser['perfil_nome'] ?? '');

if (!in_array($perfilAtual, ['SUPERADMIN', 'ADMINISTRADOR', 'ADMIN_LANHOUSE'])) {
    json(['error' => 'Acesso negado.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
  json(['error' => 'Método não permitido'], 405);
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) json(['error' => 'ID inválido'], 400);

$input = file_get_contents('php://input');
$data  = json_decode($input, true);
if (!is_array($data)) json(['error' => 'JSON inválido'], 400);

if (array_key_exists('matricula', $data)) {
  json(['error' => 'A matrícula não pode ser alterada'], 400);
}

$email     = array_key_exists('email', $data) ? trim((string)$data['email']) : null;
$nome      = array_key_exists('nome',  $data) ? trim((string)$data['nome'])  : null;
$senha     = array_key_exists('senha', $data) ? (string)$data['senha']       : null;
$perfil_id = array_key_exists('perfil_id', $data) ? ($data['perfil_id'] === null ? null : (int)$data['perfil_id']) : null;

$data_nascimento = array_key_exists('data_nascimento', $data) ? ($data['data_nascimento'] ?: null) : null;
$celular         = array_key_exists('celular', $data) ? trim((string)$data['celular']) : null;
$cep             = array_key_exists('cep', $data) ? trim((string)$data['cep']) : null;
$bairro          = array_key_exists('bairro', $data) ? trim((string)$data['bairro']) : null;
$complemento     = array_key_exists('complemento', $data) ? trim((string)$data['complemento']) : null;
$numero          = array_key_exists('numero', $data) ? trim((string)$data['numero']) : null;
$cidade          = array_key_exists('cidade', $data) ? trim((string)$data['cidade']) : null;

if ($email === '' || $nome === '') json(['error' => 'Campos não podem ser vazios'], 422);
$temAlgumaCoisa =
  array_key_exists('email', $data) ||
  array_key_exists('nome', $data) ||
  array_key_exists('senha', $data) ||
  array_key_exists('perfil_id', $data) ||
  array_key_exists('data_nascimento', $data) ||
  array_key_exists('celular', $data) ||
  array_key_exists('cep', $data) ||
  array_key_exists('bairro', $data) ||
  array_key_exists('complemento', $data) ||
  array_key_exists('numero', $data) ||
  array_key_exists('cidade', $data);

if (!$temAlgumaCoisa) json(['error' => 'Nada para atualizar'], 400);

try {
  $pdo = db();

  $st = $pdo->prepare('SELECT id FROM usuarios WHERE id = ?');
  $st->execute([$id]);
  if (!$st->fetch()) json(['error' => 'Usuário não encontrado'], 404);

  if ($perfilAtual === 'ADMIN_LANHOUSE') {
    $stCheck = $pdo->prepare("
      SELECT p.nome AS perfil_nome FROM usuarios u
      JOIN perfis p ON p.id = u.perfil_id
      WHERE u.id = ?
    ");
    $stCheck->execute([$id]);
    $alvo = $stCheck->fetch();
    if (!$alvo || strtoupper($alvo['perfil_nome']) !== 'PROFESSOR') {
      json(['error' => 'Admin Lan House só pode editar usuários Professor.'], 403);
    }
  }

  if ($email !== null) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json(['error' => 'E-mail inválido'], 422);
    $st = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? AND id <> ?');
    $st->execute([$email, $id]);
    if ($st->fetch()) json(['error' => 'E-mail já cadastrado'], 409);
  }

  if (array_key_exists('perfil_id', $data) && $perfil_id !== null) {
    $p = $pdo->prepare("SELECT id FROM perfis WHERE id = ?");
    $p->execute([$perfil_id]);
    if (!$p->fetch()) json(['error' => 'perfil_id inválido'], 422);
  }

  $sets = [];
  $vals = [];

  if (array_key_exists('email', $data))        { $sets[] = 'email = ?';          $vals[] = ($email === '' ? null : $email); }
  if (array_key_exists('nome', $data))         { $sets[] = 'nome = ?';           $vals[] = ($nome === '' ? null : $nome); }
  if (array_key_exists('senha', $data) && $senha !== null && $senha !== '') {
    $sets[] = 'senha_hash = ?';
    $vals[] = password_hash($senha, PASSWORD_BCRYPT);
  }

  if (array_key_exists('perfil_id', $data))       { $sets[] = 'perfil_id = ?';       $vals[] = $perfil_id; }
  if (array_key_exists('data_nascimento', $data)) { $sets[] = 'data_nascimento = ?'; $vals[] = $data_nascimento; }
  if (array_key_exists('celular', $data))         { $sets[] = 'celular = ?';         $vals[] = ($celular === '' ? null : $celular); }
  if (array_key_exists('cep', $data))             { $sets[] = 'cep = ?';             $vals[] = ($cep === '' ? null : $cep); }
  if (array_key_exists('bairro', $data))          { $sets[] = 'bairro = ?';          $vals[] = ($bairro === '' ? null : $bairro); }
  if (array_key_exists('complemento', $data))     { $sets[] = 'complemento = ?';     $vals[] = ($complemento === '' ? null : $complemento); }
  if (array_key_exists('numero', $data))          { $sets[] = 'numero = ?';          $vals[] = ($numero === '' ? null : $numero); }
  if (array_key_exists('cidade', $data))          { $sets[] = 'cidade = ?';          $vals[] = ($cidade === '' ? null : $cidade); }

  if ($sets) {
    $sql = 'UPDATE usuarios SET ' . implode(', ', $sets) . ' WHERE id = ?';
    $vals[] = $id;
    $upd = $pdo->prepare($sql);
    $upd->execute($vals);
  }

  $st = $pdo->prepare('
    SELECT
      u.id, u.matricula, u.email, u.nome,
      u.perfil_id, p.nome AS perfil_nome,
      u.criado_em,
      u.data_nascimento, u.celular,
      u.cep, u.bairro, u.complemento,
      u.numero, u.cidade
    FROM usuarios u
    LEFT JOIN perfis p ON p.id = u.perfil_id
    WHERE u.id = ?
    LIMIT 1
  ');
  $st->execute([$id]);
  $user = $st->fetch();

  if (!$user) json(['error' => 'Usuário não encontrado'], 404);
  json($user);

} catch (Throwable $e) {
  json(['error' => $e->getMessage()], 500);
}