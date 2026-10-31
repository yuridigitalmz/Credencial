<?php
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/nav_admin.php';
require_once __DIR__ . '/../inc/elegibilidade.php';
$u = exigir('estudante'); $pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'lidas') {
    csrf_exigir();
    $pdo->prepare('UPDATE notificacoes SET lida = 1 WHERE estudante_id = ?')->execute([$u['id']]);
    redirect('/aluno/painel.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'cancelar') {
    csrf_exigir();
    $st = $pdo->prepare("DELETE FROM pedidos WHERE id = ? AND estudante_id = ? AND estado = 'Pendente'");
    $st->execute([(int) ($_POST['id'] ?? 0), $u['id']]);
    if ($st->rowCount()) { registar('Pedido cancelado pelo estudante', '#' . (int) $_POST['id']); flash('Pedido cancelado. Pode fazer um novo quando quiser.'); }
    redirect('/aluno/painel.php');
}

$el = elegibilidade($u); $elegivel = $el['elegivel']; $motivos = $el['motivos'];
$temPendente = (int) consulta("SELECT COUNT(*) n FROM pedidos WHERE estudante_id = ? AND estado = 'Pendente'", [$u['id']])[0]['n'] > 0;

$erros = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'enviar') {
    csrf_exigir();
    $local = trim($_POST['local'] ?? ''); $sector = trim($_POST['sector'] ?? '');
    $periodo = (int) ($_POST['periodo'] ?? 0); $inicio = (string) ($_POST['inicio'] ?? '');
    $d = DateTime::createFromFormat('Y-m-d', $inicio);

    if ($local === '' || mb_strlen($local) > 180) $erros[] = 'Indique o local de estágio (máximo 180 caracteres).';
    if (mb_strlen($sector) > 180) $erros[] = 'O sector tem no máximo 180 caracteres.';
    if ($periodo < 3 || $periodo > 6) $erros[] = 'Escolha um período entre 3 e 6 meses.';
    if (!$d || $d->format('Y-m-d') !== $inicio || $inicio < date('Y-m-d')) $erros[] = 'Indique uma data de início válida, a partir de hoje.';
    if ($temPendente) $erros[] = 'Já tem um pedido a aguardar análise.';

    if (!$erros) {
        $pdo->prepare('INSERT INTO pedidos (estudante_id, local_estagio, sector, meses, data_inicio, estado, motivo) VALUES (?,?,?,?,?,?,?)')
            ->execute([$u['id'], $local, $sector ?: null, $periodo, $inicio, $elegivel ? 'Pendente' : 'Rejeitado', $elegivel ? null : mb_substr(implode(' ', $motivos), 0, 250)]);
        registar($elegivel ? 'Pedido de credencial enviado' : 'Pedido de credencial rejeitado automaticamente', $local);
        $elegivel ? flash('Pedido enviado. A secretaria vai analisá-lo e a resposta aparece nesta página.')
                  : flash('O pedido foi registado, mas não cumpre os requisitos. Veja o motivo no histórico.', 'erro');
        redirect('/aluno/painel.php');
    }
}

$historico = consulta('SELECT * FROM pedidos WHERE estudante_id = ? ORDER BY criado_em DESC', [$u['id']]);
$notifs = consulta('SELECT * FROM notificacoes WHERE estudante_id = ? AND lida = 0 ORDER BY criado_em DESC', [$u['id']]);
$falta = array_keys(array_filter(['pai' => $u['nome_pai'], 'mãe' => $u['nome_mae'], 'BI' => $u['bi'], 'emissão do BI' => $u['bi_emissao'], 'contacto' => $u['contacto']], fn($v) => !$v));

topo('O meu painel', $u, nav_aluno(), 'p');
?>
<h1>Olá, <?= e(explode(' ', $u['nome'])[0]) ?></h1>
<p class="sub"><?= e($u['curso']) ?> · <?= (int) $u['ano'] ?>.º ano · Regime <?= e($u['regime']) ?> · Nº <?= e($u['numero']) ?></p>

<?php if ($notifs): ?>
<section class="card" aria-labelledby="tn"><h2 id="tn">Novidades</h2>
  <?php foreach ($notifs as $n): ?><div class="notif"><?= e($n['mensagem']) ?><br><small><?= date('d/m/Y H:i', strtotime($n['criado_em'])) ?></small></div><?php endforeach; ?>
  <form method="post" class="mt"><?= csrf_field() ?><input type="hidden" name="acao" value="lidas"><button class="btn sec peq">Marcar como lidas</button></form>
</section>
<?php endif; ?>
<?php if ($falta): ?><div class="aviso info">Faltam dados pessoais no seu registo (<?= e(implode(', ', $falta)) ?>). A credencial sairia com campos em branco: peça à secretaria para os completar.</div><?php endif; ?>

<div class="grid">
  <div class="stat"><b><?= (int) $u['ano'] ?>.º</b><span>Ano de frequência</span></div>
  <div class="stat"><b><span class="tag <?= $u['financeiro'] === 'Regularizada' ? 'Regularizada' : 'Divida' ?>"><?= e($u['financeiro']) ?></span></b><span>Situação financeira</span></div>
  <div class="stat"><b><?= count($historico) ?></b><span>Pedidos feitos</span></div>
</div>

<section class="card" aria-labelledby="t1">
  <h2 id="t1">Pedir credencial de estágio</h2>
  <?php if (!$elegivel): ?>
    <div class="aviso erro"><strong>Ainda não cumpre os requisitos:</strong><ul><?php foreach ($motivos as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
    <?php if ($el['pendentes']): ?><details><summary>Ver cadeiras por aprovar</summary><ul><?php foreach ($el['pendentes'] as $c): ?><li><?= (int) $c['ano'] ?>.º ano — <?= e($c['nome']) ?> (<?= $c['nota'] === null ? 'sem nota' : 'nota ' . e($c['nota']) ?>)</li><?php endforeach; ?></ul></details><?php endif; ?></div>
  <?php elseif ($temPendente): ?>
    <div class="aviso info">Tem um pedido a aguardar análise. Só pode fazer outro depois da resposta.</div>
  <?php endif; ?>
  <?php if ($erros): ?><div class="aviso erro" role="alert"><ul><?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="acao" value="enviar">
    <div class="campos">
      <div class="full"><label for="local">Local de estágio</label><input id="local" name="local" maxlength="180" required value="<?= e($_POST['local'] ?? '') ?>" placeholder="Ex.: Instituto Nacional de Estatística, Delegação da Beira"></div>
      <div class="full"><label for="sector">Sector ou núcleo (opcional)</label><input id="sector" name="sector" maxlength="180" value="<?= e($_POST['sector'] ?? '') ?>"></div>
      <div><label for="periodo">Duração</label>
        <select id="periodo" name="periodo"><?php for ($i = 3; $i <= 6; $i++): ?><option value="<?= $i ?>" <?= (int) ($_POST['periodo'] ?? 3) === $i ? 'selected' : '' ?>><?= $i ?> meses</option><?php endfor; ?></select></div>
      <div><label for="inicio">Data de início prevista</label><input id="inicio" name="inicio" type="date" min="<?= date('Y-m-d') ?>" required value="<?= e($_POST['inicio'] ?? '') ?>"></div>
    </div>
    <button class="btn" type="submit">Enviar pedido</button>
  </form>
</section>

<section class="card" aria-labelledby="t2">
  <h2 id="t2">Os meus pedidos</h2>
  <?php if (!$historico): ?><p class="sub">Ainda não fez nenhum pedido.</p><?php else: ?>
  <div class="tabela"><table>
    <tr><th>Data</th><th>Local</th><th>Duração</th><th>Estado</th><th>Detalhe</th></tr>
    <?php foreach ($historico as $p): ?>
      <tr>
        <td><?= data_pt($p['criado_em']) ?></td>
        <td><?= e($p['local_estagio']) ?><?= $p['sector'] ? '<br><small>' . e($p['sector']) . '</small>' : '' ?></td>
        <td><?= (int) $p['meses'] ?> meses</td>
        <td><span class="tag <?= e($p['estado']) ?>"><?= e($p['estado']) ?></span></td>
        <td><?php if ($p['estado'] === 'Aprovado'): ?><a class="btn peq" href="<?= url('/aluno/credencial.php?id=' . (int) $p['id']) ?>">Ver e imprimir</a>
            <?php elseif ($p['estado'] === 'Pendente'): ?>A aguardar análise
            <form method="post" class="mt" data-confirm="Cancelar este pedido?"><?= csrf_field() ?><input type="hidden" name="acao" value="cancelar"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><button class="btn sec peq">Cancelar pedido</button></form>
            <?php else: ?><?= e($p['motivo']) ?><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
</section>
<?php rodape();
