<?php
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/nav_admin.php';
$u = exigir('admin'); so_secretaria($u); $pdo = db();
$cursos = consulta('SELECT id, nome FROM cursos ORDER BY nome');
$cursoIds = array_map('intval', array_column($cursos, 'id'));
$t = fn($k, $n = 120) => mb_substr(trim($_POST[$k] ?? ''), 0, $n);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    $acao = $_POST['acao'] ?? ''; $id = (int) ($_POST['id'] ?? 0);
    if ($acao === 'criar' || $acao === 'editar') {
        $numero = $t('numero', 30); $nome = $t('nome'); $email = $t('email'); $curso = (int) ($_POST['curso_id'] ?? 0); $ano = (int) ($_POST['ano'] ?? 0);
        $regime = ($_POST['regime'] ?? '') === 'Pós-laboral' ? 'Pós-laboral' : 'Laboral';
        $fin = ($_POST['financeiro'] ?? '') === 'Em dívida' ? 'Em dívida' : 'Regularizada';
        $dup = consulta('SELECT id FROM estudantes WHERE numero = ? AND id <> ?', [$numero, $acao === 'editar' ? $id : 0]);
        if ($numero === '' || $nome === '' || !in_array($curso, $cursoIds, true) || $ano < 1 || $ano > 4 || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            flash('Verifique os campos: número, nome, curso, ano e e-mail.', 'erro');
        } elseif ($dup) {
            flash('Já existe um estudante com esse número.', 'erro');
        } else {
            $dados = [$numero, $nome, $t('pai') ?: null, $t('mae') ?: null, $t('bi', 30) ?: null, $t('bi_emissao') ?: null, $t('contacto', 30) ?: null, $email ?: null, $curso, $ano, $regime, $fin];
            if ($acao === 'criar') {
                $tmp = substr(bin2hex(random_bytes(4)), 0, 8);
                $pdo->prepare('INSERT INTO estudantes (numero, nome, nome_pai, nome_mae, bi, bi_emissao, contacto, email, curso_id, ano, regime, financeiro, senha_hash, trocar_senha) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1)')
                    ->execute([...$dados, password_hash($tmp, PASSWORD_DEFAULT)]);
                registar('Estudante criado', "$numero — $nome");
                flash("Estudante criado. Senha temporária (mostrada só agora; terá de a mudar no primeiro acesso): $tmp");
            } else {
                $pdo->prepare('UPDATE estudantes SET numero=?, nome=?, nome_pai=?, nome_mae=?, bi=?, bi_emissao=?, contacto=?, email=?, curso_id=?, ano=?, regime=?, financeiro=? WHERE id=?')
                    ->execute([...$dados, $id]);
                registar('Estudante editado', "$numero — $nome");
                flash('Dados do estudante actualizados.');
            }
        }
    } elseif ($acao === 'financeiro') {
        $fin = ($_POST['financeiro'] ?? '') === 'Em dívida' ? 'Em dívida' : 'Regularizada';
        $pdo->prepare('UPDATE estudantes SET financeiro = ? WHERE id = ?')->execute([$fin, $id]);
        registar('Situação financeira alterada', "Estudante #$id → $fin");
        flash('Situação financeira actualizada.');
    } elseif ($acao === 'senha') {
        $tmp = substr(bin2hex(random_bytes(4)), 0, 8);
        $pdo->prepare('UPDATE estudantes SET senha_hash = ?, trocar_senha = 1 WHERE id = ?')->execute([password_hash($tmp, PASSWORD_DEFAULT), $id]);
        registar('Senha reposta', "Estudante #$id");
        flash("Nova senha temporária (mostrada só agora): $tmp");
    } elseif ($acao === 'apagar') {
        $pdo->prepare('DELETE FROM estudantes WHERE id = ?')->execute([$id]);
        registar('Estudante removido', "#$id");
        flash('Estudante removido, com os seus pedidos e notas.');
    }
    redirect('/admin/estudantes.php');
}
$q = trim($_GET['q'] ?? '');
$pag = max(1, (int) ($_GET['p'] ?? 1)); $por = 30;
$w = $q !== '' ? ' WHERE e.nome LIKE ? OR e.numero LIKE ?' : ''; $pq = $q !== '' ? ["%$q%", "%$q%"] : [];
$total = (int) consulta('SELECT COUNT(*) n FROM estudantes e' . $w, $pq)[0]['n'];
$lista = consulta('SELECT e.*, c.nome AS curso FROM estudantes e JOIN cursos c ON c.id = e.curso_id' . $w . ' ORDER BY c.nome, e.nome LIMIT ' . $por . ' OFFSET ' . (($pag - 1) * $por), $pq);
$ed = isset($_GET['editar']) ? (consulta('SELECT * FROM estudantes WHERE id = ?', [(int) $_GET['editar']])[0] ?? null) : null;
$x = fn($k) => e($ed[$k] ?? '');

topo('Estudantes', $u, nav_admin($u), 'e');
?>
<h1>Estudantes</h1>
<p class="sub">Os dados pessoais aparecem na credencial: mantenha-os completos.</p>
<p><a class="btn sec" href="<?= url('/admin/importar.php') ?>">Importar vários estudantes (CSV)</a></p>
<section class="card"><h2><?= $ed ? 'A editar: ' . e($ed['nome']) : 'Novo estudante' ?></h2>
<form method="post"><?= csrf_field() ?><input type="hidden" name="acao" value="<?= $ed ? 'editar' : 'criar' ?>"><?php if ($ed): ?><input type="hidden" name="id" value="<?= (int) $ed['id'] ?>"><?php endif; ?>
  <div class="campos">
    <div><label for="n">Número</label><input id="n" name="numero" required maxlength="30" value="<?= $x('numero') ?>" placeholder="04.0099.2026"></div>
    <div><label for="no">Nome completo</label><input id="no" name="nome" required maxlength="120" value="<?= $x('nome') ?>"></div>
    <div><label for="pai">Nome do pai</label><input id="pai" name="pai" maxlength="120" value="<?= $x('nome_pai') ?>"></div>
    <div><label for="mae">Nome da mãe</label><input id="mae" name="mae" maxlength="120" value="<?= $x('nome_mae') ?>"></div>
    <div><label for="bi">Bilhete de Identidade</label><input id="bi" name="bi" maxlength="30" value="<?= $x('bi') ?>"></div>
    <div><label for="bie">BI emitido (data — local)</label><input id="bie" name="bi_emissao" maxlength="120" value="<?= $x('bi_emissao') ?>" placeholder="05/07/2020 — Dondo"></div>
    <div><label for="ct">Contacto</label><input id="ct" name="contacto" maxlength="30" value="<?= $x('contacto') ?>"></div>
    <div><label for="em">E-mail (para avisos e recuperação de senha)</label><input id="em" name="email" type="email" maxlength="120" value="<?= $x('email') ?>"></div>
    <div><label for="c">Curso</label><select id="c" name="curso_id"><?php foreach ($cursos as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $ed && (int) $ed['curso_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option><?php endforeach; ?></select></div>
    <div><label for="a">Ano</label><select id="a" name="ano"><?php for ($i = 1; $i <= 4; $i++): ?><option value="<?= $i ?>" <?= $ed && (int) $ed['ano'] === $i ? 'selected' : '' ?>><?= $i ?>.º ano</option><?php endfor; ?></select></div>
    <div><label for="r">Regime</label><select id="r" name="regime"><?php foreach (['Laboral', 'Pós-laboral'] as $o): ?><option <?= ($ed['regime'] ?? '') === $o ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select></div>
    <div><label for="f">Situação financeira</label><select id="f" name="financeiro"><?php foreach (['Regularizada', 'Em dívida'] as $o): ?><option <?= ($ed['financeiro'] ?? '') === $o ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select></div>
  </div>
  <button class="btn" type="submit"><?= $ed ? 'Guardar alterações' : 'Criar estudante' ?></button>
  <?php if ($ed): ?><a class="btn sec" href="<?= url('/admin/estudantes.php') ?>">Cancelar</a><?php endif; ?>
</form></section>

<form method="get" class="inline pesquisa" role="search"><label class="sr-only" for="q">Pesquisar</label><input id="q" name="q" type="search" placeholder="Nome ou número" value="<?= e($q) ?>"><button class="btn sec peq">Pesquisar</button></form>
<section class="card"><div class="tabela"><table>
  <tr><th>Estudante</th><th>Curso</th><th>Ano</th><th>Financeiro</th><th>Acções</th></tr>
  <?php foreach ($lista as $s): ?>
  <tr>
    <td><strong><?= e($s['nome']) ?></strong><br><small><?= e($s['numero']) ?></small></td>
    <td><?= e($s['curso']) ?></td><td><?= (int) $s['ano'] ?>.º</td>
    <td><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="acao" value="financeiro"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
      <select name="financeiro" aria-label="Situação financeira de <?= e($s['nome']) ?>"><option <?= $s['financeiro'] === 'Regularizada' ? 'selected' : '' ?>>Regularizada</option><option <?= $s['financeiro'] !== 'Regularizada' ? 'selected' : '' ?>>Em dívida</option></select>
      <button class="btn sec peq">Guardar</button></form></td>
    <td class="inline">
      <a class="btn sec peq" href="<?= url('/admin/estudantes.php?editar=' . (int) $s['id']) ?>">Editar</a>
      <form method="post" data-confirm="Gerar uma nova senha temporária para este estudante?"><?= csrf_field() ?><input type="hidden" name="acao" value="senha"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><button class="btn sec peq">Repor senha</button></form>
      <form method="post" data-confirm="Remover este estudante e todos os seus pedidos e notas? Não pode ser desfeito."><?= csrf_field() ?><input type="hidden" name="acao" value="apagar"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><button class="btn perigo peq">Remover</button></form>
    </td>
  </tr>
  <?php endforeach; if (!$lista): ?><tr><td colspan="5">Nenhum estudante encontrado.</td></tr><?php endif; ?>
</table></div></section>
<?php paginacao($total, $pag, $por); rodape();
