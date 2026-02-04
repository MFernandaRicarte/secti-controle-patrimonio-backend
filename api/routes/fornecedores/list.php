<?php
require_once __DIR__ . '/../../lib/http.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/cors.php';

cors();

$usuario = requireAuth();

try {
    $pdo = db();
    
    $sql = "SELECT id, nome, cnpj, email, telefone, criado_em 
            FROM fornecedores 
            ORDER BY nome ASC";
    
    $params = [];
    
    if (isset($_GET['busca']) && !empty($_GET['busca'])) {
        $busca = '%' . $_GET['busca'] . '%';
        $sql = "SELECT id, nome, cnpj, email, telefone, criado_em 
                FROM fornecedores 
                WHERE nome LIKE ? OR cnpj LIKE ?
                ORDER BY nome ASC";
        $params = [$busca, $busca];
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    json([
        'sucesso' => true,
        'dados' => $fornecedores,
        'total' => count($fornecedores)
    ]);
    
} catch (Exception $e) {
    json([
        'sucesso' => false,
        'erro' => 'Erro ao listar fornecedores: ' . $e->getMessage()
    ], 500);
}