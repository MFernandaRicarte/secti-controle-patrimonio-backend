<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/http.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/cors.php';

cors();

$usuario = requireAuth();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Buscar o modelo de contrato
        try {
            $stmt = $pdo->query("SELECT id, titulo, conteudo, criado_em, atualizado_em FROM modelo_contrato ORDER BY id DESC LIMIT 1");
            $modelo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$modelo) {
                // Se não existir, criar um padrão
                $stmt = $pdo->prepare("INSERT INTO modelo_contrato (titulo, conteudo) VALUES (?, ?)");
                $stmt->execute([
                    'Modelo de Contrato Padrão',
                    'CONTRATO DE TRABALHO

Eu, {nome}, portador(a) da matrícula {matricula}, residente à {numero} {complemento}, {bairro}, {cidade} - {cep}, telefone {celular}, email {email}, nascido(a) em {data_nascimento}, venho por meio deste firmar contrato de trabalho com a SECTI.

Data: {criado_em}

Assinatura: ____________________________'
                ]);
                $modelo = [
                    'id' => $pdo->lastInsertId(),
                    'titulo' => 'Modelo de Contrato Padrão',
                    'conteudo' => 'CONTRATO DE TRABALHO

Eu, {nome}, portador(a) da matrícula {matricula}, residente à {numero} {complemento}, {bairro}, {cidade} - {cep}, telefone {celular}, email {email}, nascido(a) em {data_nascimento}, venho por meio deste firmar contrato de trabalho com a SECTI.

Data: {criado_em}

Assinatura: ____________________________',
                    'criado_em' => date('Y-m-d H:i:s'),
                    'atualizado_em' => date('Y-m-d H:i:s')
                ];
            }

            json($modelo);
        } catch (Exception $e) {
            error(500, 'Erro ao buscar modelo: ' . $e->getMessage());
        }
        break;

    case 'POST':
    case 'PUT':
        // Salvar o modelo de contrato
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['conteudo']) || trim($data['conteudo']) === '') {
            error(400, 'Conteúdo do modelo é obrigatório');
        }

        try {
            $titulo = $data['titulo'] ?? 'Modelo de Contrato';
            $conteudo = trim($data['conteudo']);

            // Verificar se já existe um modelo
            $stmt = $pdo->query("SELECT id FROM modelo_contrato ORDER BY id DESC LIMIT 1");
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Atualizar
                $stmt = $pdo->prepare("UPDATE modelo_contrato SET titulo = ?, conteudo = ? WHERE id = ?");
                $stmt->execute([$titulo, $conteudo, $existing['id']]);
            } else {
                // Inserir novo
                $stmt = $pdo->prepare("INSERT INTO modelo_contrato (titulo, conteudo) VALUES (?, ?)");
                $stmt->execute([$titulo, $conteudo]);
            }

            json(['success' => true, 'message' => 'Modelo salvo com sucesso']);
        } catch (Exception $e) {
            error(500, 'Erro ao salvar modelo: ' . $e->getMessage());
        }
        break;

    default:
        error(405, 'Método não permitido');
}
?>