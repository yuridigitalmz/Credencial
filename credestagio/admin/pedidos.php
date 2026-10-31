<?php
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/nav_admin.php';
require_once __DIR__ . '/../inc/notificar.php';
$u = exigir('admin'); $pdo = db(); $dep = escopo($u);
$par = $dep ? [$dep] : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    $acao = $_POST['acao'] ?? ''; $id = (int) ($_POST['id'] ?? 0); $motivo = trim(mb_substr($_POST['motivo'] ?? '', 0, 250));
    $ped = in_array($acao, ['aprovar', 'rejeitar'], true)
        ? (consulta('SELECT p.*, e.email, e.nome, d.assinatura FROM pedidos p JOIN estudantes e ON e.id = p.estudante_id JOIN cursos c ON c.id = e.curso_id JOIN departamentos d ON d.id = c.departamento_id
            WHERE p.id = ? AND p.estado = "Pendente"' . ($dep ? ' AND c.departamento_id = ?' : ''), array_merge([$id], $par))[0] ?? null) : null;
    if (!$ped) flash('Pedido não encontrado, já decidido ou fora do seu departamento.', 'info');
    elseif ($acao === 'rejeitar' && $motivo === '') flash('Indique o motivo da rejeição.', 'erro');
    else {
        $aprov = $acao === 'aprovar';
        $pdo->prepare("UPDATE pedidos SET estado = ?, motivo = ?, decidido_em = NOW(), decidido_por = ?, codigo = ? WHERE id = ? AND estado = 'Pendente'")
            ->execute([$aprov ? 'Aprovado' : 'Rejeitado', $aprov ? null : $motivo, $u['id'], $aprov ? bin2hex(random_bytes(8)) : null, $id]);
        registar($aprov ? 'Pedido aprovado' : 'Pedido rejeitado', 'Pedido #' . $id . ' — ' . $ped['nome']);
        notificar((int) $ped['estudante_id'], $aprov
            ? "O seu pedido de credencial para «{$ped['local_estagio']}» foi aprovado. Já pode imprimir a credencial no seu painel."
            : "O seu pedido de credencial para «{$ped['local_estagio']}» foi rejeitado. Motivo: $motivo", $ped['email']);
        flash('Pedido ' . ($aprov ? 'aprovado' : 'rejeitado') . '. O estudante foi avisado.' . ($aprov && !$ped['assinatura'] ? ' Atenção: ainda não há assinatura do chefe deste departamento.' : ''));
    }
    redirect('/admin/pedidos.php' . (!empty($_GET['estado']) ? '?estado=' . urlencode($_GET['estado']) : ''));
}

$estado = in_array($_GET['estado'] ?? '', ['Pendente', 'Aprovado', 'Rejeitado'], true) ? $_GET['estado'] : '';
$q = trim($_GET['q'] ?? ''); $cf = (int) ($_GET['curso'] ?? 0); $pag = max(1, (int) ($_GET['p'] ?? 1)); $por = 20;
$cursosF = consulta('SELECT id, nome FROM cursos' . ($dep ? ' WHERE departamento_id = ?' : '') . ' ORDER BY nome', $par);
$onde = []; $ps = [];
if ($estado) { $onde[] = 'p.estado = ?'; $ps[] = $estado; }
if ($dep) { $onde[] = 'c.departamento_id = ?'; $ps[] = $dep; }
if ($cf) { $onde[] = 'c.id = ?'; $ps[] = $cf; }
if ($q !== '') { $onde[] = '(e.nome LIKE ? OR e.numero LIKE ?)'; $ps[] = "%$q%"; $ps[] = "%$q%"; }
$de = ' FROM pedidos p JOIN estudantes e ON e.id = p.estudante_id JOIN cursos c ON c.id = e.curso_id' . ($onde ? ' WHERE ' . implode(' AND ', $onde) : '');
$total = (int) consulta('SELECT COUNT(*) n' . $de, $ps)[0]['n'];
$pedidos = consulta('SELECT p.*, e.nome, e.numero, e.ano, c.nome AS curso' . $de . ' ORDER BY (p.estado = "Pendente") DESC, p.criado_em DESC LIMIT ' . $por . ' OFFSET ' . (($pag - 1) * $por), $ps);

topo('Pedidos', $u, nav_admin($u), 'r');
?>
<h1>Pedidos de credencial</h1>
<p class="sub">Analise os pedidos que cumprem os requisitos.</p>
<nav class="filtros" aria-label="Filtrar por estado">
  <?php foreach (['' => 'Todos', 'Pendente' => 'Pendentes', 'Aprovado' => 'Aprovados', 'Rejeitado' => 'Rejeitados'] as $k => $r): ?>
    <a href="<?= url('/admin/pedidos.php' . ($k ? '?estado=' . $k : '')) ?>"<?= $estado === $k ? ' aria-current="page"' : '' ?>><?= $r ?></a>
  <?php endforeach; ?>
</nav>
<form method="get" class="inline pesquisa" role="search">
  <?php if ($estado): ?><input type="hidden" name="estado" value="<?= e($estado) ?>"><?php endif; ?>
  <label class="sr-only" for="q">Pesquisar</label><input id="q" name="q" type="search" placeholder="Nome ou número de estudante" value="<?= e($q) ?>">
  <label class="sr-only" for="cu">Curso</label><select id="cu" name="curso"><option value="">Todos os cursos</option><?php foreach ($cursosF as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $cf === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option><?php endforeach; ?></select>
  <button class="btn sec peq" type="submit">Pesquisar</button>
  <a class="btn sec peq" href="<?= url('/admin/exportar.php') ?>">Exportar tudo (Excel/CSV)</a>
</form>
<section class="card"><div class="tabela"><table>
  <tr><th>Estudante</th><th>Estágio</th><th>Data</th><th>Estado</th><th>Decisão</th></tr>
  <?php foreach ($pedidos as $p): ?>
  <tr>
    <td><strong><?= e($p['nome']) ?></strong><br><small><?= e($p['numero']) ?> · <?= e($p['curso']) ?> · <?= (int) $p['ano'] ?>.º ano</small></td>
    <td><?= e($p['local_estagio']) ?><?= $p['sector'] ? '<br><small>' . e($p['sector']) . '</small>' : '' ?><br><small><?= (int) $p['meses'] ?> meses desde <?= data_pt($p['data_inicio']) ?></small></td>
    <td><?= data_pt($p['criado_em']) ?></td>
    <td><span class="tag <?= e($p['estado']) ?>"><?= e($p['estado']) ?></span></td>
    <td><?php if ($p['estado'] === 'Pendente'): ?>
      <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
        <input name="motivo" placeholder="Motivo (se rejeitar)" aria-label="Motivo da rejeição" maxlength="250">
        <button class="btn peq" name="acao" value="aprovar">Aprovar</button>
        <button class="btn peq perigo" name="acao" value="rejeitar">Rejeitar</button>
      </form>
    <?php else: ?><small><?= e($p['motivo'] ?? 'Aprovado em ' . data_pt($p['decidido_em'])) ?></small><?php endif; ?></td>
  </tr>
  <?php endforeach; if (!$pedidos): ?><tr><td colspan="5">Sem pedidos neste filtro.</td></tr><?php endif; ?>
</table></div></section>
<?php paginacao($total, $pag, $por); rodape();
