<?php
require_once __DIR__ . '/../../lib/http.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/cors.php';

cors();

$usuario = requireAuth();

try {
    // Extrair o ID dos route params definidos no router
    $id = $GLOBALS['routeParams']['id'] ?? null;

    if (!$id) {
        json(['erro' => 'ID do fornecedor não fornecido'], 400);
        exit;
    }

    $pdo = db();

    // GET - Visualizar fornecedor
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("SELECT id, nome, cnpj, email, telefone, criado_em 
                             FROM fornecedores WHERE id = ?");
        $stmt->execute([$id]);
        $fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fornecedor) {
            json(['erro' => 'Fornecedor não encontrado'], 404);
            exit;
        }

        json([
            'sucesso' => true,
            'dados' => $fornecedor
        ]);
        exit;
    }

    // PUT - Editar fornecedor
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $dados = json_decode(file_get_contents('php://input'), true);

        // Validações
        $nome = trim($dados['nome'] ?? '');
        $cnpj = trim($dados['cnpj'] ?? '');
        $email = trim($dados['email'] ?? '') ?: null;
        $telefone = trim($dados['telefone'] ?? '') ?: null;

        // Verificar se fornecedor existe
        $stmt = $pdo->prepare("SELECT id FROM fornecedores WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            json(['erro' => 'Fornecedor não encontrado'], 404);
            exit;
        }

        if (empty($nome)) {
            json(['erro' => 'Nome é obrigatório'], 400);
            exit;
        }

        if (empty($cnpj)) {
            json(['erro' => 'CNPJ é obrigatório'], 400);
            exit;
        }

        // Verificar CNPJ duplicado (excluindo o próprio fornecedor)
        $stmt = $pdo->prepare("SELECT id FROM fornecedores WHERE cnpj = ? AND id != ?");
        $stmt->execute([$cnpj, $id]);
        if ($stmt->fetch()) {
            json(['erro' => 'CNPJ já cadastrado para outro fornecedor'], 400);
            exit;
        }

        // Atualizar fornecedor
        $sql = "UPDATE fornecedores 
                SET nome = ?, cnpj = ?, email = ?, telefone = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $cnpj, $email, $telefone, $id]);

        // Retornar fornecedor atualizado
        $stmt = $pdo->prepare("SELECT id, nome, cnpj, email, telefone, criado_em 
                             FROM fornecedores WHERE id = ?");
        $stmt->execute([$id]);
        $fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);

        json([
            'sucesso' => true,
            'mensagem' => 'Fornecedor atualizado com sucesso',
            'dados' => $fornecedor
        ]);
        exit;
    }

    // DELETE - Excluir fornecedor
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Verificar se fornecedor existe
        $stmt = $pdo->prepare("SELECT id FROM fornecedores WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            json(['erro' => 'Fornecedor não encontrado'], 404);
            exit;
        }

        // Verificar se há contratos ou outras relacionações
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM contratos WHERE fornecedor_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row['total'] > 0) {
            json(['erro' => 'Não é possível excluir fornecedor com contratos associados'], 400);
            exit;
        }

        // Excluir fornecedor
        $stmt = $pdo->prepare("DELETE FROM fornecedores WHERE id = ?");
        $stmt->execute([$id]);

        json([
            'sucesso' => true,
            'mensagem' => 'Fornecedor excluído com sucesso'
        ]);
        exit;
    }

    json(['erro' => 'Método não permitido'], 405);

} catch (Exception $e) {
    json(['erro' => 'Erro: ' . $e->getMessage()], 500);
}
?>
