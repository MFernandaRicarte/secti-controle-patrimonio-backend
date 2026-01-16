<?php

require __DIR__ . '/../lib/http.php';
require __DIR__ . '/../lib/db.php';

cors();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'error' => 'Método não permitido. Use POST.',
    ]);
    exit;
}

// Obter ID da licitação da URL
$id = $GLOBALS['routeParams']['id'] ?? null;

if (!is_numeric($id) || $id <= 0) {
    http_response_code(400);
    echo json_encode([
        'error' => 'ID da licitação inválido.',
    ]);
    exit;
}

$pdo = db();

// Verificar se a licitação existe
$stmt = $pdo->prepare("SELECT COUNT(*) FROM licitacoes WHERE id = ?");
$stmt->execute([$id]);
if ($stmt->fetchColumn() == 0) {
    http_response_code(404);
    echo json_encode([
        'error' => 'Licitação não encontrada.',
    ]);
    exit;
}

// Verificar se há arquivo enviado
if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Arquivo não enviado ou erro no upload.',
    ]);
    exit;
}

$tipo = trim($_POST['tipo'] ?? '');
$titulo = trim($_POST['titulo'] ?? '');

if (empty($tipo) || empty($titulo)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Tipo e título são obrigatórios.',
    ]);
    exit;
}

// Validar tipo
$tiposPermitidos = ['TR', 'edital', 'ata', 'parecer', 'contrato', 'outros'];
if (!in_array($tipo, $tiposPermitidos)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Tipo de documento inválido.',
    ]);
    exit;
}

$file = $_FILES['arquivo'];
$allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($extension, $allowedExtensions)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Tipo de arquivo não permitido. Apenas PDF, DOC, DOCX, JPG, JPEG, PNG.',
    ]);
    exit;
}

// Criar diretório se não existir
$uploadDir = __DIR__ . '/../public/uploads/licitacoes/' . $id . '/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Gerar nome único para o arquivo
$filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\-_.]/', '_', $file['name']);
$filepath = $uploadDir . $filename;

// Mover arquivo
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao salvar o arquivo.',
    ]);
    exit;
}

// Salvar no banco
try {
    $stmt = $pdo->prepare("
        INSERT INTO licitacoes_documentos (licitacao_id, nome, tipo, caminho_arquivo, tamanho_arquivo, criado_por, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $id,
        $titulo,
        $tipo,
        'uploads/licitacoes/' . $id . '/' . $filename,
        $file['size'],
        1 // TODO: Obter usuário logado
    ]);

    echo json_encode([
        'message' => 'Documento anexado com sucesso.',
        'id' => $pdo->lastInsertId(),
    ]);
} catch (PDOException $e) {
    // Remover arquivo se erro no banco
    unlink($filepath);
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao salvar documento no banco.',
        'details' => $e->getMessage(),
    ]);
}