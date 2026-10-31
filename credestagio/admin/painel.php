<?php
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/nav_admin.php';
$u = exigir('admin'); $dep = escopo($u);
$par = $dep ? [$dep] : [];
$base = 'FROM pedidos p JOIN estudantes e ON e.id = p.estudante_id JOIN cursos c ON c.id = e.curso_id';
$and = $dep ? ' AND c.departamento_id = ?' : '';

$cont = array_column(consulta("SELECT p.estado, COUNT(*) n $base WHERE 1=1$and GROUP BY p.estado", $par), 'n', 'estado');
$ap = (int) ($cont['Aprovado'] ?? 0); $rj = (int) ($cont['Rejeitado'] ?? 0); $pe = (int) ($cont['Pendente'] ?? 0);
$taxa = ($ap + $rj) ? round($ap / ($ap + $rj) * 100) : 0;

$porCurso = consulta('SELECT c.nome, COUNT(p.id) tot, COALESCE(SUM(p.estado="Aprovado"),0) ap, COALESCE(SUM(p.estado="Rejeitado"),0) rj, COALESCE(SUM(p.estado="Pendente"),0) pe
    FROM cursos c LEFT JOIN estudantes e ON e.curso_id = c.id LEFT JOIN pedidos p ON p.estudante_id = e.id'
    . ($dep ? ' WHERE c.departamento_id = ?' : '') . ' GROUP BY c.id ORDER BY c.nome', $par);

$mm = array_column(consulta("SELECT DATE_FORMAT(p.decidido_em, '%Y-%m') m, COUNT(*) n $base
    WHERE p.estado = 'Aprovado' AND p.decidido_em >= DATE_SUB(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL 5 MONTH)$and GROUP BY m", $par), 'n', 'm');
$nomes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
$meses = [];
for ($i = 5; $i >= 0; $i--) {
    $d = new DateTime('first day of this month'); $d->modify("-$i month");
    $meses[] = [$nomes[(int) $d->format('n') - 1] . ' ' . $d->format('Y'), (int) ($mm[$d->format('Y-m')] ?? 0)];
}
$maxMes = max(1, ...array_column($meses, 1));
$locais = consulta("SELECT p.local_estagio l, COUNT(*) n $base WHERE p.estado = 'Aprovado'$and GROUP BY p.local_estagio ORDER BY n DESC LIMIT 5", $par);
$deps = $dep ? [] : consulta('SELECT d.nome, COUNT(p.id) tot, COALESCE(SUM(p.estado="Aprovado"),0) ap FROM departamentos d
    LEFT JOIN cursos c ON c.departamento_id = d.id LEFT JOIN estudantes e ON e.curso_id = c.id LEFT JOIN pedidos p ON p.estudante_id = e.id GROUP BY d.id ORDER BY d.nome');
$semAss = consulta('SELECT nome FROM departamentos WHERE assinatura IS NULL' . ($dep ? ' AND id = ?' : ''), $par);

topo('Estatísticas', $u, nav_admin($u), 'p');
?>
<h1>Estatísticas das credenciais</h1>
<p class="nao-imprimir"><button class="btn sec peq" type="button" data-print>Imprimir relatório</button></p>
<p class="sub"><?= $dep ? e($u['departamento']) : 'Todos os departamentos' ?></p>
<?php if ($semAss): ?><div class="aviso info">Falta carregar a assinatura do chefe de departamento (<?= e(implode(', ', array_column($semAss, 'nome'))) ?>). <a href="<?= url('/admin/assinaturas.php') ?>">Carregar agora</a></div><?php endif; ?>

<div class="grid">
  <div class="stat"><b><?= $ap ?></b><span>Credenciais emitidas</span></div>
  <div class="stat"><b><?= $pe ?></b><span>A aguardar decisão</span></div>
  <div class="stat"><b><?= $rj ?></b><span>Pedidos rejeitados</span></div>
  <div class="stat"><b><?= $taxa ?>%</b><span>Taxa de aprovação</span></div>
</div>

<div class="duas">
<section class="card"><h2>Emissões por mês</h2><p class="sub">Últimos 6 meses</p>
  <?php foreach ($meses as [$r, $n]): ?><div class="linha-b"><span><?= e($r) ?></span><progress value="<?= $n ?>" max="<?= $maxMes ?>" aria-label="<?= e($r) ?>"></progress><b><?= $n ?></b></div><?php endforeach; ?>
</section>
<section class="card"><h2>Locais de estágio mais pedidos</h2><p class="sub">Credenciais emitidas</p>
  <?php if (!$locais): ?><p>Ainda não há credenciais emitidas.</p><?php endif; ?>
  <?php foreach ($locais as $l): ?><div class="linha-b"><span><?= e($l['l']) ?></span><progress value="<?= (int) $l['n'] ?>" max="<?= (int) $locais[0]['n'] ?>" aria-label="<?= e($l['l']) ?>"></progress><b><?= (int) $l['n'] ?></b></div><?php endforeach; ?>
</section>
</div>

<section class="card"><h2>Por curso</h2><div class="tabela"><table>
  <tr><th>Curso</th><th>Pedidos</th><th>Emitidas</th><th>Rejeitadas</th><th>Pendentes</th></tr>
  <?php foreach ($porCurso as $c): ?><tr><td><?= e($c['nome']) ?></td><td><?= (int) $c['tot'] ?></td><td><?= (int) $c['ap'] ?></td><td><?= (int) $c['rj'] ?></td><td><?= (int) $c['pe'] ?></td></tr><?php endforeach; ?>
</table></div></section>

<?php if ($deps): ?><section class="card"><h2>Por departamento</h2><div class="tabela"><table>
  <tr><th>Departamento</th><th>Pedidos</th><th>Emitidas</th></tr>
  <?php foreach ($deps as $d): ?><tr><td><?= e($d['nome']) ?></td><td><?= (int) $d['tot'] ?></td><td><?= (int) $d['ap'] ?></td></tr><?php endforeach; ?>
</table></div></section><?php endif; ?>
<?php rodape();
