<?php
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/nav_admin.php';
$u = exigir('admin'); $dep = escopo($u);
$rows = consulta('SELECT e.numero, e.nome, c.nome AS curso, e.ano, p.local_estagio, p.sector, p.meses, p.data_inicio, p.estado, p.motivo, p.criado_em, p.decidido_em
    FROM pedidos p JOIN estudantes e ON e.id = p.estudante_id JOIN cursos c ON c.id = e.curso_id'
    . ($dep ? ' WHERE c.departamento_id = ?' : '') . ' ORDER BY p.criado_em DESC', $dep ? [$dep] : []);
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="pedidos_credencial_' . date('Ymd') . '.csv"');
$o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
fputcsv($o, ['Nº estudante', 'Nome', 'Curso', 'Ano', 'Local de estágio', 'Sector', 'Meses', 'Início', 'Estado', 'Motivo', 'Pedido em', 'Decidido em'], ';');
foreach ($rows as $r) fputcsv($o, array_map(fn($v) => preg_match('/^[=+\-@]/', (string) $v) ? "'" . $v : $v, $r), ';');
