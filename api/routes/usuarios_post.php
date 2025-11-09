<?php
require __DIR__.'/../lib/http.php';
require __DIR__.'/../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) json(['error' => 'JSON inválido'], 400);

$matricula = trim($data['matricula'] ?? '');
$email     = trim($data['email'] ?? '');
$nome      = trim($data['nome'] ?? '');
$senha     = trim($data['senha'] ?? '');

if ($matricula === '' || $email === '' || $nome === '' || $senha === '') {
    json(['error' => 'Campos obrigatórios: matricula, email, nome, senha'], 400);
}

try {
    $pdo = db();

    $check = $pdo->prepare("SELECT id FROM usuarios WHERE matricula = ? OR email = ?");
    $check->execute([$matricula, $email]);
    if ($check->fetch()) {
        json(['error' => 'Matrícula ou e-mail já cadastrado'], 409);
    }

    $sql = "INSERT INTO usuarios (matricula, email, nome, senha_hash, criado_em)
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$matricula, $email, $nome, password_hash($senha, PASSWORD_BCRYPT)]);

    $id = $pdo->lastInsertId();
    json(['id' => (int)$id, 'matricula' => $matricula, 'email' => $email, 'nome' => $nome], 201);
} catch (Throwable $e) {
    json(['error' => $e->getMessage()], 500);
}