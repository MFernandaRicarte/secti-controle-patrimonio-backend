<?php
require_once __DIR__ . '/../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido. Use POST.'], 405);
    exit;
}

if (!isset($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
    json(['error' => 'Arquivo "imagem" não enviado ou inválido.'], 400);
    exit;
}

$arquivo = $_FILES['imagem'];

$maxBytes = 5 * 1024 * 1024;
if ($arquivo['size'] > $maxBytes) {
    json(['error' => 'Imagem muito grande. Máximo: 5MB.'], 422);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($arquivo['tmp_name']);
$permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

if (!isset($permitidos[$mime])) {
    json(['error' => 'Formato inválido. Use JPG, PNG ou WEBP.'], 422);
    exit;
}

$ext = $permitidos[$mime];
$nome = bin2hex(random_bytes(16)) . '.' . $ext;

$dir = __DIR__ . '/../public/uploads/bens';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$destino = $dir . '/' . $nome;

if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
    json(['error' => 'Falha ao salvar imagem.'], 500);
    exit;
}

$publicPath = '/uploads/bens/' . $nome;

json([
    'ok' => true,
    'imagem_path' => $publicPath,
], 201);