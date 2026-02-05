<?php
require_once __DIR__ . '/../../lib/http.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/cors.php';

cors();

$usuario = requireAuth();

try {
    // Extrair o ID da URL
    $id = null;
    
    if (isset($GLOBALS['routeParams']['id'])) {
        $id = $GLOBALS['routeParams']['id'];
    } else {
        $uri = $_SERVER['REQUEST_URI'];
        $uri = strtok($uri, '?');
        if (preg_match('/\/fornecedores\/(\d+)/', $uri, $matches)) {
            $id = $matches[1];
        }
    }

    $id = (int)$id;

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

        if (!$dados) {
            json(['erro' => 'Dados inválidos'], 400);
            exit;
        }

        // Validações e limpeza
        $nome = trim($dados['nome'] ?? '');
        $cnpj = preg_replace('/\D+/', '', (string)($dados['cnpj'] ?? ''));
        $email = trim($dados['email'] ?? '');
        $telefone = preg_replace('/\D+/', '', (string)($dados['telefone'] ?? ''));

        // Verificar se fornecedor existe
        $stmt = $pdo->prepare("SELECT id, cnpj FROM fornecedores WHERE id = ?");
        $stmt->execute([$id]);
        $fornecedorAtual = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$fornecedorAtual) {
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

        // Verificar CNPJ duplicado APENAS se o CNPJ foi alterado
        if ($cnpj !== $fornecedorAtual['cnpj']) {
            $stmt = $pdo->prepare("SELECT id FROM fornecedores WHERE cnpj = ?");
            $stmt->execute([$cnpj]);
            if ($stmt->fetch()) {
                json(['erro' => 'CNPJ já cadastrado para outro fornecedor'], 400);
                exit;
            }
        }

        // Atualizar fornecedor
        $sql = "UPDATE fornecedores 
                SET nome = ?, cnpj = ?, email = ?, telefone = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nome, 
            $cnpj, 
            $email !== '' ? $email : null, 
            $telefone !== '' ? $telefone : null, 
            $id
        ]);

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
        $stmt = $pdo->prepare("SELECT id FROM fornecedores WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            json(['erro' => 'Fornecedor não encontrado'], 404);
            exit;
        }

        // Verificar contratos
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM contratos WHERE fornecedor_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row['total'] > 0) {
            json(['erro' => 'Não é possível excluir fornecedor com contratos associados'], 400);
            exit;
        }

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