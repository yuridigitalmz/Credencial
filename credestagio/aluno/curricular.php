<?php
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/nav_admin.php';
$u = exigir('estudante');
$rows = consulta('SELECT d.ano, d.nome, n.nota FROM disciplinas d LEFT JOIN notas n ON n.disciplina_id = d.id AND n.estudante_id = ? WHERE d.curso_id = ? ORDER BY d.ano, d.nome', [$u['id'], $u['curso_id']]);
$porAno = []; foreach ($rows as $r) $porAno[$r['ano']][] = $r;
topo('Plano curricular', $u, nav_aluno(), 'c');
?>
<h1>Plano curricular</h1>
<p class="sub"><?= e($u['curso']) ?>. Para pedir estágio, as cadeiras do 1.º ao 3.º ano devem estar aprovadas (nota 10 ou mais).</p>
<?php if (!$porAno): ?><div class="aviso info">A secretaria ainda não registou as disciplinas do seu curso.</div><?php endif; ?>
<?php foreach ($porAno as $ano => $lista): ?>
<section class="card"><h2><?= (int) $ano ?>.º ano</h2><div class="tabela"><table>
  <tr><th>Disciplina</th><th>Nota final</th><th>Situação</th></tr>
  <?php foreach ($lista as $d): ?><tr><td><?= e($d['nome']) ?></td><td><?= $d['nota'] === null ? '—' : e(number_format((float) $d['nota'], 1)) ?></td>
    <td><?php if ($d['nota'] === null): ?><span class="tag Pendente">Sem nota</span><?php elseif ($d['nota'] >= 10): ?><span class="tag Aprovado">Aprovado</span><?php else: ?><span class="tag Rejeitado">Reprovado</span><?php endif; ?></td></tr><?php endforeach; ?>
</table></div></section>
<?php endforeach; rodape();
