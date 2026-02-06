<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

requireAdminOrSuperAdmin();
$pdo = db();

$sql = "
    SELECT 
        u.id,
        u.nome,
        u.email,
        u.matricula
    FROM usuarios u
    JOIN perfis p ON p.id = u.perfil_id
    WHERE UPPER(p.nome) = 'PROFESSOR'
    ORDER BY u.nome ASC
";

$stmt = $pdo->query($sql);
$professores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$professoresFormatados = array_map(function ($p) {
    return [
        'id' => (int) $p['id'],
        'nome' => $p['nome'],
        'email' => $p['email'],
        'matricula' => $p['matricula'],
    ];
}, $professores);

json($professoresFormatados);
