<?php
require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';
require __DIR__ . '/../../lib/auth.php';

cors();

$user = requireAuth();
$perfilUsuario = strtoupper($user['perfil_nome'] ?? '');

if (!in_array($perfilUsuario, ['SUPERADMIN', 'ADMINISTRADOR', 'ADMIN_LANHOUSE'])) {
    json(['error' => 'Acesso negado.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    json(['error' => 'JSON inválido'], 400);
}

$matricula = trim($data['matricula'] ?? '');
$email     = trim($data['email'] ?? '');
$nome      = trim($data['nome'] ?? '');
$senha     = (string)($data['senha'] ?? '');
$perfil_id = isset($data['perfil_id']) ? (int)$data['perfil_id'] : null;
$data_nascimento = $data['data_nascimento'] ?? null;
$celular         = trim((string)($data['celular'] ?? ''));
$cep         = trim((string)($data['cep'] ?? ''));
$cidade      = trim((string)($data['cidade'] ?? ''));
$bairro      = trim((string)($data['bairro'] ?? ''));
$numero      = trim((string)($data['numero'] ?? ''));
$complemento = trim((string)($data['complemento'] ?? ''));

if ($matricula === '' || $email === '' || $nome === '' || $senha === '') {
    json(['error' => 'Campos obrigatórios: matrícula, nome, e-mail e senha'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json(['error' => 'E-mail inválido'], 422);
}

try {
    $pdo = db();

    $check = $pdo->prepare("SELECT id FROM usuarios WHERE matricula = ? OR email = ?");
    $check->execute([$matricula, $email]);
    if ($check->fetch()) {
        json(['error' => 'Matrícula ou e-mail já cadastrado'], 409);
    }

    $p = $pdo->prepare("SELECT id, nome FROM perfis WHERE id = ?");
    $p->execute([$perfil_id]);
    $perfilAlvo = $p->fetch();
    if (!$perfilAlvo) {
        json(['error' => 'Perfil inválido'], 422);
    }

    if ($perfilUsuario === 'ADMIN_LANHOUSE' && strtoupper($perfilAlvo['nome']) !== 'PROFESSOR') {
        json(['error' => 'Admin Lan House só pode criar usuários com perfil Professor.'], 403);
    }

    $sql = "
        INSERT INTO usuarios (
            matricula,
            email,
            senha_hash,
            nome,
            data_nascimento,
            celular,
            cep,
            cidade,
            bairro,
            numero,
            complemento,
            perfil_id,
            criado_em
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
        )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $matricula,
        $email,
        password_hash($senha, PASSWORD_BCRYPT),
        $nome,
        $data_nascimento ?: null,
        $celular ?: null,
        $cep ?: null,
        $cidade ?: null,
        $bairro ?: null,
        $numero ?: null,
        $complemento ?: null,
        $perfil_id,
    ]);

    json(['success' => true], 201);

} catch (Throwable $e) {
    json(['error' => $e->getMessage()], 500);
}