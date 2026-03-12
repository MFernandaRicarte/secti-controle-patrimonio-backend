<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/http.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/cors.php';

cors();

$authUser = requireAuth();

$pdo = db();

if (!isset($_GET['usuario_id']) || !is_numeric($_GET['usuario_id'])) {
    error(400, 'ID do usuário é obrigatório');
}

$usuario_id = (int) $_GET['usuario_id'];

try {

    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.matricula,
            u.nome,
            u.email,
            u.celular,
            u.data_nascimento,
            u.cep,
            u.cidade,
            u.bairro,
            u.numero,
            u.complemento,
            u.criado_em,
            p.nome as perfil_nome
        FROM usuarios u
        LEFT JOIN perfis p ON u.perfil_id = p.id
        WHERE u.id = ?
    ");

    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        error(404, 'Usuário não encontrado');
    }

    /* Buscar modelo de contrato */
    $stmt = $pdo->query("
        SELECT conteudo 
        FROM modelo_contrato 
        ORDER BY id DESC 
        LIMIT 1
    ");

    $modelo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$modelo) {
        error(404, 'Modelo de contrato não encontrado. Cadastre um modelo primeiro.');
    }

    $conteudo = $modelo['conteudo'];

    /* Placeholders */
    $placeholders = [
        '{id}'              => $usuario['id'],
        '{matricula}'       => $usuario['matricula'] ?? '',
        '{nome}'            => $usuario['nome'] ?? '',
        '{email}'           => $usuario['email'] ?? '',
        '{celular}'         => $usuario['celular'] ?? '',
        '{data_nascimento}' => $usuario['data_nascimento'] 
                                ? date('d/m/Y', strtotime($usuario['data_nascimento'])) 
                                : '',
        '{cep}'             => $usuario['cep'] ?? '',
        '{cidade}'          => $usuario['cidade'] ?? '',
        '{bairro}'          => $usuario['bairro'] ?? '',
        '{numero}'          => $usuario['numero'] ?? '',
        '{complemento}'     => $usuario['complemento'] ?? '',
        '{criado_em}'       => $usuario['criado_em'] 
                                ? date('d/m/Y H:i:s', strtotime($usuario['criado_em'])) 
                                : '',
        '{perfil}'          => $usuario['perfil_nome'] ?? '',
    ];

    /* Substituição */
    $contrato_preenchido = str_replace(
        array_keys($placeholders),
        array_values($placeholders),
        $conteudo
    );

    /* Detectar HTML ou texto */
    $is_html = $contrato_preenchido !== strip_tags($contrato_preenchido);

    $corpo = $is_html
        ? $contrato_preenchido
        : nl2br(htmlspecialchars($contrato_preenchido));

    header('Content-Type: text/html; charset=UTF-8');

    $nome_safe = htmlspecialchars($usuario['nome'] ?? '');
    $matricula_safe = htmlspecialchars($usuario['matricula'] ?? '');

    echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width, initial-scale=1.0'>
<title>Contrato — {$nome_safe}</title>

<style>

/* Reset */

*, *::before, *::after {
box-sizing: border-box;
margin: 0;
padding: 0;
}

/* Fonte */

@import url('https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;1,400&display=swap');

body {
font-family: 'Lora', 'Georgia', serif;
font-size: 12pt;
line-height: 1.8;
color: #1a1a1a;
background: #fff;
}

/* Página */

.page {
width: 210mm;
min-height: 297mm;
margin: 0 auto;
padding: 25mm 20mm 20mm;
background: #fff;
}

/* Cabeçalho */

.header {
text-align: center;
margin-bottom: 18mm;
padding-bottom: 6mm;
border-bottom: 2px solid #1a1a1a;
}

.header h1 {
font-size: 15pt;
font-weight: 600;
letter-spacing: 0.12em;
text-transform: uppercase;
margin-bottom: 3mm;
}

.header .meta {
font-size: 9pt;
color: #555;
letter-spacing: 0.04em;
}

/* Corpo */

.contrato {
text-align: justify;
hyphens: auto;
}

.contrato p {
margin-bottom: 6mm;
}

/* Rodapé */

.footer {
margin-top: 16mm;
padding-top: 5mm;
border-top: 1px solid #ccc;
display: flex;
justify-content: space-between;
align-items: flex-end;
font-size: 9pt;
color: #666;
}

.footer .assinatura {
text-align: center;
}

.footer .assinatura .linha {
width: 60mm;
border-bottom: 1px solid #1a1a1a;
margin-bottom: 2mm;
}

/* Impressão */

@media print {

body {
padding-top: 0;
}

.page {
width: 100%;
padding: 15mm;
margin: 0;
}

@page {
size: A4;
margin: 0;
}

}

</style>
</head>

<body>

<div class='page'>

<div class='header'>
<h1>Contrato de Trabalho</h1>
<div class='meta'>
{$nome_safe} · Matrícula {$matricula_safe}
</div>
</div>

<div class='contrato'>
{$corpo}
</div>

</div>

</body>
</html>";

} catch (Throwable $e) {

    error(500, 'Erro ao gerar contrato: ' . $e->getMessage());

}