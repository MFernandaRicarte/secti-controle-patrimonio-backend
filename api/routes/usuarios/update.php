<?php
require __DIR__.'/../../lib/http.php';
require __DIR__.'/../../config/config.php';
require __DIR__.'/../../lib/auth.php';

cors();
requireSuperAdmin();

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
$data_nascimento = array_key_exists('data_nascimento', $data) ? ($data['data_nascimento'] ?: null) : null;
$celular         = array_key_exists('celular', $data) ? trim((string)$data['celular']) : null;
$cep             = array_key_exists('cep', $data) ? trim((string)$data['cep']) : null;
$bairro          = array_key_exists('bairro', $data) ? trim((string)$data['bairro']) : null;
$complemento     = array_key_exists('complemento', $data) ? trim((string)$data['complemento']) : null;
$numero          = array_key_exists('numero', $data) ? trim((string)$data['numero']) : null;
$cidade          = array_key_exists('cidade', $data) ? trim((string)$data['cidade']) : null;

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
    if (array_key_exists('data_nascimento', $data)) { $sets[] = 'data_nascimento = ?'; $vals[] = $data_nascimento; }
    if ($celular !== null)     { $sets[] = 'celular = ?';      $vals[] = ($celular === '' ? null : $celular); }
    if ($cep !== null)         { $sets[] = 'cep = ?';          $vals[] = ($cep === '' ? null : $cep); }
    if ($bairro !== null)      { $sets[] = 'bairro = ?';       $vals[] = ($bairro === '' ? null : $bairro); }
    if ($complemento !== null) { $sets[] = 'complemento = ?';  $vals[] = ($complemento === '' ? null : $complemento); }

    if ($numero !== null)      { $sets[] = 'numero = ?';       $vals[] = ($numero === '' ? null : $numero); }
    if ($cidade !== null)      { $sets[] = 'cidade = ?';       $vals[] = ($cidade === '' ? null : $cidade); }

    if ($sets) {
        $sql = 'UPDATE usuarios SET '.implode(', ', $sets).' WHERE id = ?';
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
            u.numero, u.cidade,
        FROM usuarios u
        LEFT JOIN perfis p ON p.id = u.perfil_id
        WHERE u.id = ?
        ');

    $st->execute([$id]);
    $user = $st->fetch();
    json($user ?: ['error' => 'Usuário não encontrado'], $user ? 200 : 404);
} catch (Throwable $e) {
    json(['error' => $e->getMessage()], 500);
}