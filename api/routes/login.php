<?php
require __DIR__ . '/../lib/http.php';
require __DIR__ . '/../config/config.php';

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

    // busca usuário pelo e-mail
    $stmt = $pdo->prepare("
        SELECT id, matricula, email, nome, senha_hash, perfil_id, criado_em
        FROM usuarios
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        json(['error' => 'Usuário ou senha inválidos'], 401);
    }

    // confere a senha
    if (!password_verify($senha, $user['senha_hash'])) {
        json(['error' => 'Usuário ou senha inválidos'], 401);
    }

    // tudo certo: remove o hash antes de devolver
    unset($user['senha_hash']);

    // aqui no futuro podemos gerar token / sessão etc.
    json([
        'message' => 'Login realizado com sucesso',
        'usuario' => $user,
    ]);
} catch (Throwable $e) {
    json(['error' => $e->getMessage()], 500);
}
