<?php
require __DIR__ . '/../../../../lib/db.php';
require __DIR__ . '/../../../../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido. Use POST.'], 405);
}

$cursoId = $GLOBALS['routeParams']['id'] ?? null;
$cursoId = (int)$cursoId;

if ($cursoId <= 0) {
    json(['error' => 'ID do curso inválido.'], 400);
}

$pdo = db();

$stmt = $pdo->prepare("SELECT id FROM lhs_cursos WHERE id = ?");
$stmt->execute([$cursoId]);
if (!$stmt->fetch()) {
    json(['error' => 'Curso não encontrado.'], 404);
}

if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
    $errorMsg = 'Arquivo não enviado ou erro no upload.';
    if (isset($_FILES['arquivo']['error'])) {
        switch ($_FILES['arquivo']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMsg = 'Arquivo muito grande.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMsg = 'Nenhum arquivo enviado.';
                break;
        }
    }
    json(['error' => $errorMsg], 400);
}

$arquivo = $_FILES['arquivo'];
$maxBytes = 10 * 1024 * 1024;

if ($arquivo['size'] > $maxBytes) {
    json(['error' => 'Arquivo muito grande. Máximo: 10MB.'], 422);
}

$nomeOriginal = $arquivo['name'];
$ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

$extensoesPermitidas = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png', 'zip'];
if (!in_array($ext, $extensoesPermitidas)) {
    json(['error' => 'Formato de arquivo não permitido.'], 422);
}

$nomeArquivo = bin2hex(random_bytes(16)) . '.' . $ext;

$dir = __DIR__ . '/../public/uploads/lhs/materiais';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$destino = $dir . '/' . $nomeArquivo;

if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
    json(['error' => 'Falha ao salvar arquivo.'], 500);
}

$publicPath = '/uploads/lhs/materiais/' . $nomeArquivo;
$uploadedPor = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : null;

$sql = "
    INSERT INTO lhs_materiais_didaticos (curso_id, nome_arquivo, path, uploaded_por)
    VALUES (:curso_id, :nome_arquivo, :path, :uploaded_por)
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':curso_id'     => $cursoId,
    ':nome_arquivo' => $nomeOriginal,
    ':path'         => $publicPath,
    ':uploaded_por' => $uploadedPor,
]);

$id = (int)$pdo->lastInsertId();

json([
    'ok'       => true,
    'material' => [
        'id'           => $id,
        'curso_id'     => $cursoId,
        'nome_arquivo' => $nomeOriginal,
        'path'         => $publicPath,
    ],
], 201);
