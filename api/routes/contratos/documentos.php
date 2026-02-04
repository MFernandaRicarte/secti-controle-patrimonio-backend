<?php

require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';
require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/cors.php';
require __DIR__ . '/../../lib/auth.php';

cors();

$usuario = requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$contratoId = isset($GLOBALS['routeParams']['id']) ? (int) $GLOBALS['routeParams']['id'] : 0;

if ($contratoId <= 0) {
    json(['error' => 'ID do contrato inválido'], 400);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

// Verificar se contrato existe
$stmt = $pdo->prepare("SELECT id FROM contratos WHERE id = ?");
$stmt->execute([$contratoId]);
if (!$stmt->fetch()) {
    json(['error' => 'Contrato não encontrado'], 404);
    exit;
}

if ($method === 'GET') {
    // Listar documentos
    $stmt = $pdo->prepare("
        SELECT 
            id,
            nome,
            tipo,
            caminho_arquivo,
            tamanho_arquivo,
            criado_por,
            criado_em,
            (SELECT nome FROM usuarios WHERE id = criado_por) as criado_por_nome
        FROM contratos_documentos
        WHERE contrato_id = ?
        ORDER BY criado_em DESC
    ");
    $stmt->execute([$contratoId]);
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    json([
        'sucesso' => true,
        'dados' => array_map(function ($doc) {
            return [
                'id' => (int) $doc['id'],
                'nome' => $doc['nome'],
                'tipo' => $doc['tipo'],
                'caminho_arquivo' => $doc['caminho_arquivo'],
                'tamanho_arquivo' => (int) $doc['tamanho_arquivo'],
                'criado_por' => (int) $doc['criado_por'],
                'criado_por_nome' => $doc['criado_por_nome'],
                'criado_em' => $doc['criado_em'],
            ];
        }, $documentos),
        'total' => count($documentos)
    ]);
    exit;
}

if ($method === 'POST') {
    // Upload de documento
    if (empty($_FILES['arquivo'])) {
        json(['error' => 'Nenhum arquivo enviado'], 400);
        exit;
    }

    $arquivo = $_FILES['arquivo'];
    $tipo = $_POST['tipo'] ?? 'outros';
    $titulo = $_POST['titulo'] ?? $arquivo['name'];

    // Validações
    $erros = [];
    
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        $erros[] = 'Erro ao fazer upload do arquivo.';
    }

    $extensoes_permitidas = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extensao, $extensoes_permitidas)) {
        $erros[] = 'Tipo de arquivo não permitido. Use: ' . implode(', ', $extensoes_permitidas);
    }

    $tamanho_max = 10 * 1024 * 1024; // 10MB
    if ($arquivo['size'] > $tamanho_max) {
        $erros[] = 'Arquivo muito grande. Máximo: 10MB.';
    }

    if (!empty($erros)) {
        json(['error' => 'Validação falhou.', 'detalhes' => $erros], 400);
        exit;
    }

    // Criar diretório se não existir
    $diretorio = __DIR__ . '/../../public/uploads/contratos';
    if (!is_dir($diretorio)) {
        mkdir($diretorio, 0755, true);
    }

    // Salvar arquivo
    $nome_arquivo = uniqid('contrato_') . '.' . $extensao;
    $caminho_completo = $diretorio . '/' . $nome_arquivo;
    $caminho_relativo = 'public/uploads/contratos/' . $nome_arquivo;

    if (!move_uploaded_file($arquivo['tmp_name'], $caminho_completo)) {
        json(['error' => 'Erro ao salvar o arquivo no servidor.'], 500);
        exit;
    }

    // Registrar no banco de dados
    try {
        $stmt = $pdo->prepare("
            INSERT INTO contratos_documentos (contrato_id, nome, tipo, caminho_arquivo, tamanho_arquivo, criado_por, criado_em)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $contratoId,
            $titulo,
            $tipo,
            $caminho_relativo,
            $arquivo['size'],
            $usuario['id']
        ]);

        $documentoId = $pdo->lastInsertId();

        json([
            'sucesso' => true,
            'mensagem' => 'Documento enviado com sucesso.',
            'dados' => [
                'id' => (int) $documentoId,
                'nome' => $titulo,
                'tipo' => $tipo,
                'caminho_arquivo' => $caminho_relativo,
                'tamanho_arquivo' => $arquivo['size']
            ]
        ], 201);
    } catch (PDOException $e) {
        // Remover arquivo em caso de erro
        @unlink($caminho_completo);
        
        error_log("Erro ao inserir documento: " . $e->getMessage());
        json(['error' => 'Erro ao registrar documento no banco. ' . $e->getMessage()], 500);
    }
    exit;
}

json(['error' => 'Método não permitido'], 405);
