<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'], true)) {
    json(['error' => 'Método não permitido. Use POST/PUT.'], 405);
}

$user = requireAdminOrSuperAdmin();
$pdo = db();

$osId = (int)($GLOBALS['routeParams']['id'] ?? 0);
if ($osId <= 0) {
    json(['error' => 'id inválido.'], 400);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$equipamentos = $input['equipamentos'] ?? [];
$finalizar = !empty($input['finalizar_os']);

if (!is_array($equipamentos) || count($equipamentos) === 0) {
    json(['error' => 'equipamentos é obrigatório.'], 422);
}

$destinosPermitidos = [
    'AULA_MONTAGEM_MANUTENCAO',
    'DESCARTE',
    'REFORMADO',
    'APROVEITAMENTO_PECAS',
    'USO_INTERNO',
    'DOACAO',
    'OUTRO',
];

$stmtOs = $pdo->prepare("SELECT id FROM rct_os WHERE id = ? LIMIT 1");
$stmtOs->execute([$osId]);
if (!$stmtOs->fetch()) {
    json(['error' => 'OS não encontrada.'], 404);
}

$pdo->beginTransaction();

try {
    $updEquip = $pdo->prepare("
        UPDATE rct_os_equipamentos
        SET destino_padrao = ?, destino_outro = ?, destino_final = ?, atualizado_em = CURRENT_TIMESTAMP
        WHERE id = ? AND os_id = ?
    ");

    foreach ($equipamentos as $eq) {
        $id = (int)($eq['id'] ?? 0);
        $destinoPadrao = strtoupper(trim($eq['destino_padrao'] ?? ''));
        $destinoOutro = trim($eq['destino_outro'] ?? '');

        if ($id <= 0) {
            throw new Exception('Equipamento inválido.');
        }

        if (!in_array($destinoPadrao, $destinosPermitidos, true)) {
            throw new Exception('Destino inválido para equipamento ID ' . $id . '.');
        }

        if ($destinoPadrao === 'OUTRO' && $destinoOutro === '') {
            throw new Exception('Destino "Outro" exige descrição para equipamento ID ' . $id . '.');
        }

        $destinoFinal = match ($destinoPadrao) {
            'AULA_MONTAGEM_MANUTENCAO' => 'Aula de montagem e manutenção de computadores',
            'DESCARTE' => 'Descarte',
            'REFORMADO' => 'Reformado',
            'APROVEITAMENTO_PECAS' => 'Aproveitamento de peças',
            'USO_INTERNO' => 'Uso interno',
            'DOACAO' => 'Doação',
            'OUTRO' => $destinoOutro,
            default => null,
        };

        $updEquip->execute([
            $destinoPadrao,
            $destinoOutro !== '' ? $destinoOutro : null,
            $destinoFinal,
            $id,
            $osId
        ]);
    }

    if ($finalizar) {
        $stmtCheck = $pdo->prepare("
            SELECT COUNT(*) 
            FROM rct_os_equipamentos
            WHERE os_id = ?
              AND (destino_final IS NULL OR TRIM(destino_final) = '')
        ");
        $stmtCheck->execute([$osId]);
        $faltando = (int)$stmtCheck->fetchColumn();

        if ($faltando > 0) {
            throw new Exception('Todos os equipamentos devem ter destino antes da finalização.');
        }

        $stmtFinaliza = $pdo->prepare("
            UPDATE rct_os
            SET status = 'FINALIZADA',
                atualizado_em = CURRENT_TIMESTAMP,
                atualizado_por = ?
            WHERE id = ?
        ");
        $stmtFinaliza->execute([(int)$user['id'], $osId]);
    }

    $pdo->commit();

    json([
        'ok' => true,
        'message' => $finalizar
            ? 'Equipamentos registrados e OS finalizada com sucesso.'
            : 'Destinos dos equipamentos salvos com sucesso.'
    ]);
} catch (Throwable $e) {
    $pdo->rollBack();
    json([
        'error' => 'Erro ao salvar destinos dos equipamentos.',
        'debug' => $e->getMessage(),
    ], 500);
}