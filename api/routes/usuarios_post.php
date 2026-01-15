<?php
require_once __DIR__ . '/../lib/http.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
requireSuperAdmin();

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
$perfil_id = isset($data['perfil_id']) ? (int)$data['perfil_id'] : null;

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

    if ($perfil_id !== null) {
        $p = $pdo->prepare("SELECT id FROM perfis WHERE id = ?");
        $p->execute([$perfil_id]);
        if (!$p->fetch()) json(['error' => 'perfil_id inválido'], 422);
    }

    $sql = "INSERT INTO usuarios (matricula, email, nome, senha_hash, perfil_id, criado_em)
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$matricula, $email, $nome, password_hash($senha, PASSWORD_BCRYPT), $perfil_id]);

    $id = (int)$pdo->lastInsertId();
    json(['id' => $id, 'matricula' => $matricula, 'email' => $email, 'nome' => $nome, 'perfil_id' => $perfil_id], 201);
} catch (Throwable $e) {
    json(['error' => $e->getMessage()], 500);
}