<?php
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/nav_admin.php';
$u = exigir('admin'); so_secretaria($u); $pdo = db();
$est = consulta('SELECT e.id, e.numero, e.nome, e.curso_id, e.ano, c.nome AS curso FROM estudantes e JOIN cursos c ON c.id = e.curso_id ORDER BY c.nome, e.nome');
$eid = (int) ($_GET['id'] ?? ($_POST['id'] ?? ($est[0]['id'] ?? 0)));
$sel = null; foreach ($est as $s) if ((int) $s['id'] === $eid) $sel = $s;
$disc = $sel ? consulta('SELECT d.id, d.ano, d.nome, n.nota FROM disciplinas d LEFT JOIN notas n ON n.disciplina_id = d.id AND n.estudante_id = ? WHERE d.curso_id = ? ORDER BY d.ano, d.nome', [$eid, $sel['curso_id']]) : [];

if ($sel && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    $up = $pdo->prepare('INSERT INTO notas (estudante_id, disciplina_id, nota) VALUES (?,?,?) ON DUPLICATE KEY UPDATE nota = VALUES(nota)');
    $del = $pdo->prepare('DELETE FROM notas WHERE estudante_id = ? AND disciplina_id = ?');
    foreach ($disc as $d) {
        $v = trim((string) ($_POST['nota'][$d['id']] ?? ''));
        if ($v === '') $del->execute([$eid, $d['id']]);
        elseif (is_numeric($v)) $up->execute([$eid, $d['id'], max(0, min(20, round((float) $v, 1)))]);
    }
    registar('Notas lançadas', $sel['numero'] . ' — ' . $sel['nome']);
    flash('Notas guardadas.');
    redirect('/admin/notas.php?id=' . $eid);
}
topo('Notas', $u, nav_admin($u), 'n');
?>
<h1>Lançamento de notas</h1>
<p class="sub">Nota final de 0 a 20. Aprovado com 10 ou mais. Deixe em branco se ainda não houver nota.</p>
<form method="get" class="inline pesquisa"><label for="es">Estudante</label><select id="es" name="id"><?php foreach ($est as $s): ?><option value="<?= (int) $s['id'] ?>" <?= (int) $s['id'] === $eid ? 'selected' : '' ?>><?= e($s['numero'] . ' — ' . $s['nome'] . ' (' . $s['curso'] . ', ' . $s['ano'] . '.º)') ?></option><?php endforeach; ?></select><button class="btn sec peq">Abrir</button></form>
<?php if ($sel): ?>
<section class="card"><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $eid ?>">
  <div class="tabela"><table><tr><th>Ano</th><th>Disciplina</th><th>Nota final</th></tr>
  <?php foreach ($disc as $d): ?><tr><td><?= (int) $d['ano'] ?>.º</td><td><?= e($d['nome']) ?></td>
    <td><label class="sr-only" for="n<?= (int) $d['id'] ?>">Nota de <?= e($d['nome']) ?></label><input class="nota-in" id="n<?= (int) $d['id'] ?>" name="nota[<?= (int) $d['id'] ?>]" type="number" min="0" max="20" step="0.1" value="<?= $d['nota'] === null ? '' : e($d['nota']) ?>"></td></tr><?php endforeach; ?>
  <?php if (!$disc): ?><tr><td colspan="3">Este curso ainda não tem disciplinas registadas.</td></tr><?php endif; ?></table></div>
  <?php if ($disc): ?><button class="btn mt">Guardar notas</button><?php endif; ?>
</form></section>
<?php endif; rodape();
