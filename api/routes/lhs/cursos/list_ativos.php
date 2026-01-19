<?php
/**
 * GET /api/lhs/cursos/ativos
 * Lista cursos ativos para a página pública de inscrição.
 * Endpoint público - não requer autenticação.
 */

require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

try {
    $sql = "
        SELECT id, nome, carga_horaria, ementa
        FROM lhs_cursos
        WHERE ativo = 1
        ORDER BY nome ASC
    ";

    $stmt = $pdo->query($sql);
    $cursos = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cursos[] = [
            'id' => (int) $row['id'],
            'nome' => $row['nome'],
            'carga_horaria' => (int) $row['carga_horaria'],
            'ementa' => $row['ementa'],
        ];
    }

    json($cursos);
} catch (PDOException $e) {
    json(['error' => 'Erro ao buscar cursos.'], 500);
    exit;
}
