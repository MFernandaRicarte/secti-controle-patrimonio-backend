<?php
require __DIR__.'/../lib/http.php';
require __DIR__.'/../config/config.php';

cors();
if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    json(['error' => 'Método não permitido'], 405);
}

$id = (int)($GLOBALS['routeParams']['id'] ?? 0);
if ($id <= 0) json(['error' => 'ID inválido'], 400);

$input = file_get_contents('php://input');
$data  = json_decode($input, true);
if (!is_array($data)) json(['error' => 'JSON inválido'], 400);

if (isset($data['matricula'])) {
    json(['error' => 'A matrícula não pode ser alterada'], 400);
}

$email     = array_key_exists('email', $data) ? trim((string)$data['email']) : null;
$nome      = array_key_exists('nome',  $data) ? trim((string)$data['nome'])  : null;
$senha     = array_key_exists('senha', $data) ? (string)$data['senha']       : null;
$perfil_id = array_key_exists('perfil_id', $data) ? ($data['perfil_id'] === null ? null : (int)$data['perfil_id']) : null;

if ($email === '' || $nome === '') json(['error' => 'Campos não podem ser vazios'], 422);
if ($email === null && $nome === null && $senha === null && $perfil_id === null) {
    json(['error' => 'Nada para atualizar'], 400);
}

try {
    $pdo = db();

    $st = $pdo->prepare('SELECT id FROM usuarios WHERE id = ?');
    $st->execute([$id]);
    if (!$st->fetch()) json(['error' => 'Usuário não encontrado'], 404);

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
    if ($email !== null)    { $sets[] = 'email = ?';       $vals[] = $email; }
    if ($nome  !== null)    { $sets[] = 'nome = ?';        $vals[] = $nome; }
    if ($senha !== null)    { $sets[] = 'senha_hash = ?';  $vals[] = password_hash($senha, PASSWORD_BCRYPT); }
    if (array_key_exists('perfil_id', $data)) { $sets[] = 'perfil_id = ?'; $vals[] = $perfil_id; }

    if ($sets) {
        $sql = 'UPDATE usuarios SET '.implode(', ', $sets).' WHERE id = ?';
        $vals[] = $id;
        $upd = $pdo->prepare($sql);
        $upd->execute($vals);
    }

    $st = $pdo->prepare('SELECT id, matricula, email, nome, perfil_id, criado_em FROM usuarios WHERE id = ?');
    $st->execute([$id]);
    $user = $st->fetch();
    json($user ?: ['error' => 'Usuário não encontrado'], $user ? 200 : 404);
} catch (Throwable $e) {
    json(['error' => $e->getMessage()], 500);
}