<?php
require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';
require __DIR__ . '/../../lib/jwt.php';


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
        SELECT u.id, u.matricula, u.email, u.nome, u.senha_hash, u.perfil_id, u.criado_em,
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

    $perfilNome = strtoupper($user['perfil_nome'] ?? '');

    $permissoes = [];
    if ($perfilNome === 'SUPERADMIN') {
        $permissoes = ['sistema_completo', 'admin_patrimonio', 'admin_lanhouse', 'lhs_gerenciar', 'lhs_professores', 'lhs_presenca'];
    } elseif ($perfilNome === 'ADMINISTRADOR') {
        $permissoes = ['admin_patrimonio', 'admin_lanhouse', 'lhs_gerenciar', 'lhs_professores', 'lhs_presenca'];
    } elseif ($perfilNome === 'ADMIN_LANHOUSE') {
        $permissoes = ['admin_lanhouse', 'lhs_gerenciar', 'lhs_professores', 'lhs_presenca'];
    } elseif ($perfilNome === 'PROFESSOR') {
        $permissoes = ['lhs_minhas_turmas', 'lhs_meus_alunos', 'lhs_presenca'];
    }

    $token = jwt_generate([
        'uid' => $user['id'],
        'perfil_id' => $user['perfil_id'],
    ]);

    json([
        'message' => 'Login realizado com sucesso',
        'token'   => $token,
        'usuario' => $user,
        'permissoes' => $permissoes,
    ]);

} catch (Throwable $e) {
    json(['error' => $e->getMessage()], 500);
}
