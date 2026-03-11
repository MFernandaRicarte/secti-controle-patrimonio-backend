<?php

// =============================
// CORS
// =============================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

// =============================
// CONEXÃO
// =============================
require_once __DIR__ . '/../../lib/db.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

try {

    // =====================================================
    // LISTAR VISITAS
    // =====================================================
    if ($method === 'GET') {

    // CONSULTA HORÁRIOS OCUPADOS POR DATA
    if (isset($_GET['data'])) {

        $stmt = $pdo->prepare("
            SELECT horario_visita
            FROM laboratorio_visitas
            WHERE data_visita = :data
            AND status = 'confirmado'
        ");

        $stmt->execute([
            ':data' => $_GET['data']
        ]);

        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
        exit;
    }

    // LISTAR TODAS AS VISITAS
    $sql = "
        SELECT
            id,
            instituicao,
            responsavel,
            telefone,
            email,
            numero_participantes AS participantes,
            ano_escolar,
            objetivo_visita,
            acessibilidade,
            descricao_acessibilidade,
            data_visita AS data,
            horario_visita AS horario,
            status
        FROM laboratorio_visitas
        ORDER BY data_visita DESC
    ";

    $stmt = $pdo->query($sql);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}
    // =====================================================
    // CRIAR NOVA VISITA
    // =====================================================
    if ($method === 'POST') {

        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) {
            throw new Exception("Dados inválidos.");
        }

        // =============================
        // BLOQUEAR HORÁRIO DUPLICADO
        // =============================
        $check = $pdo->prepare("
            SELECT COUNT(*)
            FROM laboratorio_visitas
            WHERE data_visita = :data
            AND horario_visita = :horario
            AND status != 'rejeitado'
        ");

        $check->execute([
            ':data' => $data['data_visita'],
            ':horario' => $data['horario_visita']
        ]);

        if ($check->fetchColumn() > 0) {

            http_response_code(409);

            echo json_encode([
                "error" => "Já existe uma visita agendada para este horário."
            ]);

            exit;
        }
        // verificar se já existe visita confirmada no mesmo horário
        $check = $pdo->prepare("
            SELECT COUNT(*) 
            FROM laboratorio_visitas
            WHERE data_visita = :data
            AND horario_visita = :horario
            AND status = 'confirmado'
        ");

        $check->execute([
            ':data' => $data['data_visita'],
            ':horario' => $data['horario']
        ]);

        if ($check->fetchColumn() > 0) {

            http_response_code(400);

            echo json_encode([
                "error" => "Já existe uma visita confirmada para este horário."
        ]);

        exit;
    }

        // =============================
        // INSERT
        // =============================
        $sql = "
            INSERT INTO laboratorio_visitas
            (
                instituicao,
                responsavel,
                telefone,
                email,
                data_visita,
                horario_visita,
                numero_participantes,
                ano_escolar,
                objetivo_visita,
                acessibilidade,
                descricao_acessibilidade,
                autorizacao_imagem,
                status
            )
            VALUES
            (
                :instituicao,
                :responsavel,
                :telefone,
                :email,
                :data_visita,
                :horario_visita,
                :numero_participantes,
                :ano_escolar,
                :objetivo_visita,
                :acessibilidade,
                :descricao_acessibilidade,
                :autorizacao_imagem,
                'pendente'
            )
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':instituicao' => $data['instituicao'] ?? null,
            ':responsavel' => $data['responsavel'] ?? null,
            ':telefone' => $data['telefone'] ?? null,
            ':email' => $data['email'] ?? null,
            ':data_visita' => $data['data_visita'] ?? null,
            ':horario_visita' => $data['horario_visita'] ?? null,
            ':numero_participantes' => $data['numero_participantes'] ?? null,
            ':ano_escolar' => $data['ano_escolar'] ?? null,
            ':objetivo_visita' => $data['objetivo_visita'] ?? null,
            ':acessibilidade' => $data['acessibilidade'] ?? false,
            ':descricao_acessibilidade' => $data['descricao_acessibilidade'] ?? null,
            ':autorizacao_imagem' => $data['autorizacao_imagem'] ?? false
        ]);

        echo json_encode([
            "success" => true,
            "message" => "Visita registrada com sucesso."
        ]);

        exit;
    }

    // =====================================================
    // APROVAR / REJEITAR VISITA
    // =====================================================
    if ($method === 'PATCH') {

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['id']) || !isset($data['status'])) {
            throw new Exception("Dados incompletos.");
        }

        $stmt = $pdo->prepare("
            UPDATE laboratorio_visitas
            SET status = :status
            WHERE id = :id
        ");

        $stmt->execute([
            ':status' => $data['status'],
            ':id' => $data['id']
        ]);

        echo json_encode([
            "success" => true,
            "message" => "Status atualizado com sucesso."
        ]);

        exit;
    }

    // =====================================================
// EXCLUIR VISITA
// =====================================================
if ($method === 'DELETE') {

    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['id'])) {
        throw new Exception("ID não informado.");
    }

    $stmt = $pdo->prepare("
        DELETE FROM laboratorio_visitas
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $data['id']
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Visita excluída com sucesso."
    ]);

    exit;
}

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        "error" => $e->getMessage()
    ]);
}