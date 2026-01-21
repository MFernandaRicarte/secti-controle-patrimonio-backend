<?php

// Habilitar exibição de erros para debug (remover em produção)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/auth.php';
require __DIR__ . '/../../lib/cors.php';

cors();

$usuario = requireAuth();

header('Content-Type: application/json; charset=utf-8');

try {
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

    // Log de debug
    error_log("=== UPLOAD DE DOCUMENTO ===");
    error_log("ID recebido: " . print_r($id, true));
    error_log("FILES: " . print_r($_FILES, true));
    error_log("POST: " . print_r($_POST, true));
    error_log("Usuario autenticado: " . print_r($usuario, true));

    if (!is_numeric($id) || $id <= 0) {
        http_response_code(400);
        echo json_encode([
            'error' => 'ID da licitação inválido.',
            'id_recebido' => $id
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
        $errorMsg = 'Arquivo não enviado ou erro no upload.';
        
        if (isset($_FILES['arquivo']['error'])) {
            switch ($_FILES['arquivo']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMsg = 'Arquivo muito grande.';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMsg = 'Upload parcial. Tente novamente.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMsg = 'Nenhum arquivo enviado.';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errorMsg = 'Diretório temporário não encontrado.';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errorMsg = 'Erro ao escrever arquivo no disco.';
                    break;
            }
        }
        
        http_response_code(400);
        echo json_encode([
            'error' => $errorMsg,
            'files_info' => $_FILES
        ]);
        exit;
    }

    $tipo = trim($_POST['tipo'] ?? '');
    $titulo = trim($_POST['titulo'] ?? '');

    error_log("Tipo: $tipo, Titulo: $titulo");

    if (empty($tipo) || empty($titulo)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Tipo e título são obrigatórios.',
            'tipo_recebido' => $tipo,
            'titulo_recebido' => $titulo
        ]);
        exit;
    }

    // Validar tipo
    $tiposPermitidos = ['TR', 'edital', 'ata', 'parecer', 'contrato', 'outros'];
    if (!in_array($tipo, $tiposPermitidos)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Tipo de documento inválido.',
            'tipo_recebido' => $tipo,
            'tipos_permitidos' => $tiposPermitidos
        ]);
        exit;
    }

    $file = $_FILES['arquivo'];
    $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    error_log("Arquivo: " . $file['name'] . ", Extensão: $extension");

    if (!in_array($extension, $allowedExtensions)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Tipo de arquivo não permitido. Apenas PDF, DOC, DOCX, JPG, JPEG, PNG.',
            'extensao_recebida' => $extension
        ]);
        exit;
    }

    // Limite de tamanho: 10MB
    if ($file['size'] > 10 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Arquivo muito grande. Máximo: 10MB.',
            'tamanho_recebido' => $file['size']
        ]);
        exit;
    }

    // CORRIGIDO: Caminho correto do diretório
    $uploadDir = __DIR__ . '/../../public/uploads/licitacoes/' . $id . '/';
    
    error_log("Diretório de upload: " . $uploadDir);
    error_log("Diretório absoluto: " . realpath(dirname($uploadDir)));
    
    // Criar diretório se não existir
    if (!is_dir($uploadDir)) {
        error_log("Criando diretório: " . $uploadDir);
        if (!mkdir($uploadDir, 0755, true)) {
            error_log("ERRO ao criar diretório!");
            http_response_code(500);
            echo json_encode([
                'error' => 'Erro ao criar diretório de upload.',
                'diretorio' => $uploadDir,
                'diretorio_pai' => dirname($uploadDir),
                'pai_existe' => is_dir(dirname($uploadDir)),
                'pai_gravavel' => is_writable(dirname($uploadDir))
            ]);
            exit;
        }
        error_log("Diretório criado com sucesso");
    } else {
        error_log("Diretório já existe");
    }

    // Gerar nome único para o arquivo
    $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\-_.]/', '_', basename($file['name']));
    $filepath = $uploadDir . $filename;

    error_log("Caminho completo do arquivo: " . $filepath);
    error_log("Arquivo temporário: " . $file['tmp_name']);
    error_log("Arquivo temp existe: " . (file_exists($file['tmp_name']) ? 'SIM' : 'NÃO'));

    // Mover arquivo
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        error_log("ERRO ao mover arquivo!");
        http_response_code(500);
        echo json_encode([
            'error' => 'Erro ao salvar o arquivo no servidor.',
            'arquivo_temp' => $file['tmp_name'],
            'destino' => $filepath,
            'diretorio_existe' => is_dir($uploadDir),
            'diretorio_gravavel' => is_writable($uploadDir),
            'arquivo_temp_existe' => file_exists($file['tmp_name'])
        ]);
        exit;
    }

    error_log("Arquivo movido com sucesso!");

    // Caminho relativo para salvar no banco
    $caminhoRelativo = 'uploads/licitacoes/' . $id . '/' . $filename;
    
    error_log("Salvando no banco. Caminho relativo: " . $caminhoRelativo);

    // Salvar no banco
    $stmt = $pdo->prepare("
        INSERT INTO licitacoes_documentos 
        (licitacao_id, nome, tipo, caminho_arquivo, tamanho_arquivo, criado_por)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    // Usar ID do usuário autenticado
    $usuarioId = $usuario['id'] ?? 1;
    
    $stmt->execute([
        $id,
        $titulo,
        $tipo,
        $caminhoRelativo,
        $file['size'],
        $usuarioId
    ]);

    $documentoId = $pdo->lastInsertId();

    error_log("Documento salvo com sucesso! ID: " . $documentoId);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Documento anexado com sucesso.',
        'id' => $documentoId,
        'documento' => [
            'id' => $documentoId,
            'nome' => $titulo,
            'tipo' => $tipo,
            'caminho_arquivo' => $caminhoRelativo,
            'tamanho_arquivo' => $file['size']
        ]
    ]);

} catch (PDOException $e) {
    // Remover arquivo se erro no banco
    if (isset($filepath) && file_exists($filepath)) {
        unlink($filepath);
        error_log("Arquivo removido após erro no banco");
    }
    
    error_log("Erro PDO: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao salvar documento no banco de dados.',
        'details' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch (Exception $e) {
    error_log("Erro geral: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro interno do servidor.',
        'details' => $e->getMessage()
    ]);
}