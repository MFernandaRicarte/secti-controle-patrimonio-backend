<?php
/**
 * PUT /api/lhs/inscricoes/{id}/rejeitar
 * Rejeita uma inscrição com motivo opcional.
 * Endpoint administrativo.
 */

require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    json(['error' => 'Método não permitido. Use PUT ou PATCH.'], 405);
    exit;
}

$inscricaoId = $GLOBALS['routeParams']['id'] ?? 0;

if ($inscricaoId <= 0) {
    json(['error' => 'ID da inscrição inválido.'], 400);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$motivo = trim($input['motivo'] ?? '');

// Buscar inscrição
try {
    $stmt = $pdo->prepare("SELECT * FROM lhs_inscricoes WHERE id = :id");
    $stmt->execute([':id' => $inscricaoId]);
    $inscricao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inscricao) {
        json(['error' => 'Inscrição não encontrada.'], 404);
        exit;
    }

    if ($inscricao['status'] !== 'pendente') {
        json(['error' => 'Esta inscrição já foi processada.'], 409);
        exit;
    }
} catch (PDOException $e) {
    json(['error' => 'Erro ao buscar inscrição.'], 500);
    exit;
}

// Atualizar inscrição
try {
    $stmt = $pdo->prepare("
        UPDATE lhs_inscricoes 
        SET status = 'rejeitado', motivo_rejeicao = :motivo
        WHERE id = :id
    ");
    $stmt->execute([
        ':motivo' => $motivo ?: null,
        ':id' => $inscricaoId,
    ]);

    // Buscar inscrição atualizada com joins
    $stmt = $pdo->prepare("
        SELECT 
            i.*,
            c.nome AS curso_nome
        FROM lhs_inscricoes i
        LEFT JOIN lhs_cursos c ON c.id = i.curso_id
        WHERE i.id = :id
    ");
    $stmt->execute([':id' => $inscricaoId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $resultado = [
        'id' => (int) $row['id'],
        'curso_id' => (int) $row['curso_id'],
        'curso_nome' => $row['curso_nome'],
        'nome' => $row['nome'],
        'cpf' => $row['cpf'],
        'status' => $row['status'],
        'motivo_rejeicao' => $row['motivo_rejeicao'],
        'mensagem' => 'Inscrição rejeitada.',
    ];

    json($resultado);
} catch (PDOException $e) {
    json(['error' => 'Erro ao rejeitar inscrição.', 'detalhes' => $e->getMessage()], 500);
    exit;
}
