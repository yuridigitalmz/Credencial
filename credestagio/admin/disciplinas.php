<?php
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/nav_admin.php';
$u = exigir('admin'); so_secretaria($u); $pdo = db();
$cursos = consulta('SELECT id, nome FROM cursos ORDER BY nome');
$cid = (int) ($_GET['curso_id'] ?? ($_POST['curso_id'] ?? ($cursos[0]['id'] ?? 0)));
if (!in_array($cid, array_map('intval', array_column($cursos, 'id')), true)) $cid = (int) ($cursos[0]['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    if (($_POST['acao'] ?? '') === 'criar') {
        $ano = (int) ($_POST['ano'] ?? 0); $nome = mb_substr(trim($_POST['nome'] ?? ''), 0, 120);
        if ($nome === '' || $ano < 1 || $ano > 4) flash('Indique o ano (1 a 4) e o nome da disciplina.', 'erro');
        elseif (consulta('SELECT id FROM disciplinas WHERE curso_id = ? AND ano = ? AND nome = ?', [$cid, $ano, $nome])) flash('Essa disciplina já existe neste ano.', 'erro');
        else { $pdo->prepare('INSERT INTO disciplinas (curso_id, ano, nome) VALUES (?,?,?)')->execute([$cid, $ano, $nome]); registar('Disciplina criada', $nome); flash('Disciplina adicionada.'); }
    } elseif (($_POST['acao'] ?? '') === 'apagar') {
        $pdo->prepare('DELETE FROM disciplinas WHERE id = ? AND curso_id = ?')->execute([(int) ($_POST['id'] ?? 0), $cid]); registar('Disciplina removida', '#' . (int) $_POST['id']); flash('Disciplina removida, com as notas associadas.');
    }
    redirect('/admin/disciplinas.php?curso_id=' . $cid);
}
$rows = consulta('SELECT * FROM disciplinas WHERE curso_id = ? ORDER BY ano, nome', [$cid]);
$porAno = []; foreach ($rows as $r) $porAno[$r['ano']][] = $r;

topo('Disciplinas', $u, nav_admin($u), 'd');
?>
<h1>Plano curricular</h1>
<p class="sub">Para pedir credencial, o estudante precisa de ter aprovadas todas as disciplinas do 1.º ao 3.º ano registadas aqui.</p>
<form method="get" class="inline pesquisa"><label for="cc">Curso</label><select id="cc" name="curso_id"><?php foreach ($cursos as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $cid ? 'selected' : '' ?>><?= e($c['nome']) ?></option><?php endforeach; ?></select><button class="btn sec peq">Abrir</button></form>
<section class="card"><h2>Adicionar disciplina</h2>
  <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="acao" value="criar"><input type="hidden" name="curso_id" value="<?= $cid ?>">
    <label class="sr-only" for="an">Ano</label><select id="an" name="ano"><?php for ($i = 1; $i <= 4; $i++): ?><option value="<?= $i ?>"><?= $i ?>.º ano</option><?php endfor; ?></select>
    <label class="sr-only" for="dn">Nome</label><input id="dn" name="nome" required maxlength="120" placeholder="Nome da disciplina"><button class="btn peq">Adicionar</button></form></section>
<?php foreach ($porAno as $ano => $l): ?>
<section class="card"><h2><?= (int) $ano ?>.º ano</h2><div class="tabela"><table>
  <?php foreach ($l as $d): ?><tr><td><?= e($d['nome']) ?></td><td><form method="post" data-confirm="Remover esta disciplina e as notas associadas?"><?= csrf_field() ?><input type="hidden" name="acao" value="apagar"><input type="hidden" name="curso_id" value="<?= $cid ?>"><input type="hidden" name="id" value="<?= (int) $d['id'] ?>"><button class="btn perigo peq">Remover</button></form></td></tr><?php endforeach; ?>
</table></div></section>
<?php endforeach; if (!$porAno): ?><div class="aviso info">Este curso ainda não tem disciplinas. Sem disciplinas registadas, a regra das cadeiras aprovadas não se aplica.</div><?php endif; rodape();
