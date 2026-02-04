<?php
require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';
require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/cors.php';

cors();

try {
    $id = $GLOBALS['routeParams']['id'] ?? null;

    if (!$id) {
        json(['error' => 'ID do contrato é obrigatório'], 400);
        exit;
    }

    // Verificar autenticação
    $headers = getallheaders();
    $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
    
    if (!$token) {
        json(['error' => 'Token de autenticação não fornecido'], 401);
        exit;
    }

    // Deletar contrato
    $query = $pdo->prepare('DELETE FROM contratos WHERE id = ?');
    $query->execute([$id]);

    if ($query->rowCount() === 0) {
        json(['error' => 'Contrato não encontrado'], 404);
        exit;
    }

    json(['message' => 'Contrato deletado com sucesso'], 200);

} catch (Exception $e) {
    json(['error' => $e->getMessage()], 500);
}
