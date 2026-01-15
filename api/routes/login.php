<?php
require_once __DIR__ . '/../lib/http.php';
require_once __DIR__ . '/../lib/db.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido'], 405);
}

$input = file_get_contents('php://input');
$data  = json_decode($input, true);

if (!is_array($data)) {
    json(['error' => 'JSON inválido'], 400);
}

$email = trim($data['email'] ?? '');
$senha = (string)($data['senha'] ?? '');

if ($email === '' || $senha === '') {
    json(['error' => 'E-mail e senha são obrigatórios'], 400);
}

try {
    $pdo = db();

    $stmt = $pdo->prepare("
    SELECT 
        u.id, u.matricula, u.email, u.nome, u.senha_hash, u.perfil_id, u.criado_em,
        p.nome AS perfil_nome
    FROM usuarios u
    LEFT JOIN perfis p ON p.id = u.perfil_id
    WHERE u.email = ?
    LIMIT 1
");

    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        json(['error' => 'Usuário ou senha inválidos'], 401);
    }

    if (!password_verify($senha, $user['senha_hash'])) {
        json(['error' => 'Usuário ou senha inválidos'], 401);
    }

    unset($user['senha_hash']);

    json([
        'message' => 'Login realizado com sucesso',
        'usuario' => $user,
    ]);
} catch (Throwable $e) {
    json(['error' => $e->getMessage()], 500);
}
