<?php
require_once __DIR__ . '/../../lib/http.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/cors.php';

cors();

// Verificar autenticação
$usuario = requireAuth();

try {
    $pdo = getDB();
    $dados = json_decode(file_get_contents('php://input'), true);
    
    // Validações
    if (empty($dados['nome'])) {
        erroValidacao('Nome é obrigatório');
    }
    
    if (empty($dados['cnpj'])) {
        erroValidacao('CNPJ é obrigatório');
    }
    
    // Verificar CNPJ duplicado
    $stmt = $pdo->prepare("SELECT id FROM fornecedores WHERE cnpj = ?");
    $stmt->execute([$dados['cnpj']]);
    if ($stmt->fetch()) {
        erroValidacao('CNPJ já cadastrado');
    }
    
    // Inserir fornecedor
    $sql = "INSERT INTO fornecedores (nome, cnpj, email, telefone)
            VALUES (?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $dados['nome'],
        $dados['cnpj'],
        $dados['email'] ?? null,
        $dados['telefone'] ?? null
    ]);
    
    $id = $pdo->lastInsertId();
    
    responder([
        'sucesso' => true,
        'mensagem' => 'Fornecedor criado com sucesso',
        'id' => $id
    ], 201);
    
} catch (Exception $e) {
    erroServidor('Erro ao criar fornecedor: ' . $e->getMessage());
}
?>
