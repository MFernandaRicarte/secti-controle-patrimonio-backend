<?php
require __DIR__.'/../lib/http.php';
require __DIR__.'/../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    json(['error' => 'JSON inválido'], 400);
}

$email = trim($data['email'] ?? '');
$nome  = trim($data['nome'] ?? '');
$senha = trim($data['senha'] ?? '');

if ($email === '' || $nome === '' || $senha === '') {
    json(['error' => 'Campos obrigatórios: email, nome, senha'], 400);
}

try {
    $pdo = db();

    // verifica duplicidade
    $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        json(['error' => 'E-mail já cadastrado'], 409);
    }

    // insere o usuário
    $sql = "INSERT INTO usuarios (email, nome, senha_hash, criado_em)
            VALUES (?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email, $nome, password_hash($senha, PASSWORD_BCRYPT)]);

    $id = $pdo->lastInsertId();
    json(['id' => (int)$id, 'email' => $email, 'nome' => $nome], 201);
} catch (Throwable $e) {
    json(['error' => $e->getMessage()], 500);
}
