<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

// recomendo restringir: só admin abre esse documento
$user = requireLhsAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    json(['error' => 'id é obrigatório.'], 400);
}

$pdo = db();

$stmt = $pdo->prepare("
    SELECT 
        cert.id,
        cert.codigo_validacao,
        cert.emitido_em,
        a.nome AS aluno_nome,
        t.nome AS turma_nome,
        t.data_inicio,
        t.data_fim,
        c.nome AS curso_nome,
        c.carga_horaria
    FROM lhs_certificados cert
    JOIN lhs_alunos a ON a.id = cert.aluno_id
    JOIN lhs_turmas t ON t.id = cert.turma_id
    JOIN lhs_cursos c ON c.id = t.curso_id
    WHERE cert.id = ?
");
$stmt->execute([$id]);
$cert = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cert) {
    json(['error' => 'Certificado não encontrado.'], 404);
}

// formatações
$aluno = htmlspecialchars($cert['aluno_nome']);
$curso = htmlspecialchars($cert['curso_nome']);
$turma = htmlspecialchars($cert['turma_nome']);
$carga = (int) $cert['carga_horaria'];
$codigo = htmlspecialchars($cert['codigo_validacao']);

$inicio = $cert['data_inicio'] ? date('d/m/Y', strtotime($cert['data_inicio'])) : '';
$fim = $cert['data_fim'] ? date('d/m/Y', strtotime($cert['data_fim'])) : '';
$periodo = ($inicio && $fim) ? "{$inicio} a {$fim}" : null;

// nomes
$prefeito = "Bruno Cunha Lima Branco";
$secretaria = "Fabiana Gomes";

// resposta HTML (não json)
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <title>Certificado - <?= $aluno ?></title>
  <style>
    body { font-family: Arial, sans-serif; margin: 40px; }
    .box { border: 2px solid #1f7a52; padding: 34px; border-radius: 14px; }
    h1 { margin: 0 0 16px; letter-spacing: 1px; }
    .nome { font-size: 26px; font-weight: 700; margin: 10px 0 18px; }
    .texto { font-size: 16px; line-height: 1.7; }
    .assinaturas { display: flex; gap: 60px; margin-top: 44px; }
    .ass { width: 320px; }
    .linha { border-top: 1px solid #333; margin-top: 48px; }
    .cargo { font-size: 12px; margin-top: 6px; color: #333; }
    .codigo { margin-top: 18px; font-size: 12px; color: #444; }
    @media print { body { margin: 0; } .box { border: none; } }
  </style>
</head>
<body>
  <div class="box">
    <h1>CERTIFICADO</h1>

    <div class="texto">Certifico que</div>
    <div class="nome"><?= $aluno ?></div>

    <div class="texto">
      concluiu com êxito o curso de <strong><?= $curso ?></strong>, promovido pela Prefeitura Municipal de Campina Grande,
      por meio da Secretaria de Ciência, Tecnologia e Inovação<?= $periodo ? ", no período de {$periodo}" : "" ?>,
      com carga horária total de <strong><?= $carga ?> horas</strong>.
    </div>

    <div class="assinaturas">
      <div class="ass">
        <div class="linha"></div>
        <div class="cargo"><strong><?= $prefeito ?></strong><br/>Prefeito Municipal de Campina Grande</div>
      </div>
      <div class="ass">
        <div class="linha"></div>
        <div class="cargo"><strong><?= $secretaria ?></strong><br/>Secretária de Ciência, Tecnologia e Inovação</div>
      </div>
    </div>

    <div class="codigo">
      código de validação: <strong><?= $codigo ?></strong><br/>
      turma: <?= $turma ?>
    </div>
  </div>
</body>
</html>