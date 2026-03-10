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

// Rate limiting config
const MAX_ATTEMPTS    = 5;
const LOCKOUT_SECONDS = 30;

// Identifica o cliente por IP + email (mais preciso que só IP)
$ip         = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ip         = trim(explode(',', $ip)[0]); // pega o primeiro IP se vier lista
$rate_key   = hash('sha256', $ip . '|' . strtolower($email));

try {
    $pdo = db();

    // Garante que a tabela existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            rate_key     CHAR(64)    NOT NULL,
            attempts     INT         NOT NULL DEFAULT 0,
            last_attempt DATETIME    NOT NULL,
            locked_until DATETIME    NULL,
            PRIMARY KEY (rate_key)
        )
    ");

    // Busca registro atual
    $stmt = $pdo->prepare("SELECT * FROM login_attempts WHERE rate_key = ? LIMIT 1");
    $stmt->execute([$rate_key]);
    $rateRecord = $stmt->fetch();

    // Verifica bloqueio ativo
    if ($rateRecord && $rateRecord['locked_until'] !== null) {
        $lockedUntil = strtotime($rateRecord['locked_until']);
        if (time() < $lockedUntil) {
            $wait = $lockedUntil - time();
            json([
                'error'      => "Muitas tentativas. Aguarde {$wait} segundo(s) para tentar novamente.",
                'locked'     => true,
                'retry_after' => $wait,
            ], 429);
        }

        // Bloqueio expirou — reseta o registro
        $pdo->prepare("DELETE FROM login_attempts WHERE rate_key = ?")->execute([$rate_key]);
        $rateRecord = null;
    }

    // Busca usuário
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

    $credentialsValid = $user && password_verify($senha, $user['senha_hash']);

    if (!$credentialsValid) {
        // Incrementa tentativas
        $newAttempts = ($rateRecord['attempts'] ?? 0) + 1;
        $lockedUntil = null;

        if ($newAttempts >= MAX_ATTEMPTS) {
            $lockedUntil = date('Y-m-d H:i:s', time() + LOCKOUT_SECONDS);
        }

        $pdo->prepare("
            INSERT INTO login_attempts (rate_key, attempts, last_attempt, locked_until)
            VALUES (?, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE
                attempts     = VALUES(attempts),
                last_attempt = NOW(),
                locked_until = VALUES(locked_until)
        ")->execute([$rate_key, $newAttempts, $lockedUntil]);

        $remaining = max(0, MAX_ATTEMPTS - $newAttempts);
        $message   = 'Usuário ou senha inválidos';
        if ($remaining > 0) {
            $message .= " ({$remaining} tentativa(s) restante(s))";
        }

        json(['error' => $message], 401);
    }

    // Login bem-sucedido — limpa tentativas
    $pdo->prepare("DELETE FROM login_attempts WHERE rate_key = ?")->execute([$rate_key]);

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
        'uid'      => $user['id'],
        'perfil_id' => $user['perfil_id'],
    ]);

    json([
        'message'    => 'Login realizado com sucesso',
        'token'      => $token,
        'usuario'    => $user,
        'permissoes' => $permissoes,
    ]);

} catch (Throwable $e) {
    json(['error' => $e->getMessage()], 500);
}