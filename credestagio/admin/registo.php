<?php
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/nav_admin.php';
$u = exigir('admin'); so_secretaria($u);
$emails = ($_GET['v'] ?? '') === 'emails';
$rows = $emails ? consulta('SELECT * FROM emails_log ORDER BY criado_em DESC LIMIT 100') : consulta('SELECT * FROM registo ORDER BY criado_em DESC, id DESC LIMIT 200');
topo('Registo', $u, nav_admin($u), 'g');
?>
<h1>Registo</h1>
<nav class="filtros"><a href="<?= url('/admin/registo.php') ?>"<?= !$emails ? ' aria-current="page"' : '' ?>>Actividade</a><a href="<?= url('/admin/registo.php?v=emails') ?>"<?= $emails ? ' aria-current="page"' : '' ?>>E-mails enviados</a></nav>
<section class="card"><div class="tabela"><table>
<?php if ($emails): ?>
  <p class="sub">Cópia de todos os e-mails do sistema, mesmo que o servidor não tenha SMTP configurado.</p>
  <tr><th>Data</th><th>Para</th><th>Assunto</th><th>Estado</th></tr>
  <?php foreach ($rows as $r): ?><tr><td><?= date('d/m/Y H:i', strtotime($r['criado_em'])) ?></td><td><?= e($r['destinatario']) ?></td>
    <td><details><summary><?= e($r['assunto']) ?></summary><pre class="mail"><?= preg_replace('~(https?://[^\s<]+)~', '<a href="$1">$1</a>', e($r['corpo'])) ?></pre></details></td><td><?= e($r['estado']) ?></td></tr><?php endforeach; ?>
<?php else: ?>
  <tr><th>Data</th><th>Quem</th><th>Acção</th><th>Detalhe</th><th>IP</th></tr>
  <?php foreach ($rows as $r): ?><tr><td><?= date('d/m/Y H:i', strtotime($r['criado_em'])) ?></td><td><?= e($r['nome'] ?? '—') ?><br><small><?= e($r['papel'] ?? '') ?></small></td><td><?= e($r['acao']) ?></td><td><?= e($r['detalhe']) ?></td><td><small><?= e($r['ip']) ?></small></td></tr><?php endforeach; ?>
<?php endif; ?>
<?php if (!$rows): ?><tr><td colspan="5">Sem registos.</td></tr><?php endif; ?>
</table></div></section>
<?php rodape();
