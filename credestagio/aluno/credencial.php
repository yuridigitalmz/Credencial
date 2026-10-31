<?php
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';
$u = exigir('estudante');
$st = db()->prepare("SELECT * FROM pedidos WHERE id = ? AND estudante_id = ? AND estado = 'Aprovado'");
$st->execute([(int) ($_GET['id'] ?? 0), $u['id']]);
$p = $st->fetch();
if (!$p) { http_response_code(404); exit('Credencial não encontrada.'); }
if (empty($p['codigo'])) { $p['codigo'] = bin2hex(random_bytes(8)); db()->prepare('UPDATE pedidos SET codigo = ? WHERE id = ?')->execute([$p['codigo'], $p['id']]); }
$vurl = site_url('/verificar.php?c=' . $p['codigo']);
$meses = [3 => '3 (três) meses', 4 => '4 (quatro) meses', 5 => '5 (cinco) meses', 6 => '6 (seis) meses'];
$v = fn($x) => e($x ?: '—');
$logo = url('/assets/logo.png');

topo('Credencial de estágio');
?>
<div class="acoes">
  <button class="btn" type="button" data-print>Imprimir ou guardar em PDF</button>
  <a class="btn sec" href="<?= url('/aluno/painel.php') ?>">Voltar</a>
</div>
<article class="carta">
  <img class="agua" src="<?= $logo ?>" alt="" aria-hidden="true">
  <div class="conteudo">
    <div class="cab">
      <img class="logo" src="<?= $logo ?>" alt="Logótipo da <?= e(INSTITUICAO) ?>">
      <strong class="fac"><?= e($u['faculdade']) ?></strong>
      <span class="dep"><?= e($u['departamento']) ?></span>
      <span class="curso">Curso de <?= e($u['curso']) ?></span>
    </div>
    <div class="assunto">Exmo. Senhor Director<b>Assunto: Pedido de Credencial</b></div>
    <p class="texto"><span class="d"><?= e($u['nome']) ?></span>, filho de <span class="d"><?= $v($u['nome_pai']) ?></span> e de <span class="d"><?= $v($u['nome_mae']) ?></span>,
      Bilhete de Identidade Nº: <span class="d"><?= $v($u['bi']) ?></span>, emitido: <span class="d"><?= $v($u['bi_emissao']) ?></span>,
      com contacto: <span class="d"><?= $v($u['contacto']) ?></span>, estudante do <span class="d"><?= (int) $u['ano'] ?>º ano</span>, do curso de <span class="d"><?= e($u['curso']) ?></span>,
      no regime <span class="d"><?= e($u['regime']) ?></span>, vem mui respeitosamente solicitar a V. Excia se digne autorizar a elaboração da credencial do estágio
      com o objectivo de realização do Estágio Profissional, no: <span class="d"><?= e($p['local_estagio']) ?></span><?= $p['sector'] ? ', no sector <span class="d">' . e($p['sector']) . '</span>' : '' ?>,
      com a duração de <span class="d"><?= $meses[(int) $p['meses']] ?? (int) $p['meses'] . ' meses' ?></span>, com início previsto em <span class="d"><?= data_pt($p['data_inicio']) ?></span>. Pelo que:</p>
    <div class="deferimento"><span>Pede Deferimento</span></div>
    <p class="dir"><?= e(LOCAL_EMISSAO) ?>, <?= date('d / m / Y', strtotime($p['decidido_em'])) ?></p>
    <div class="rodape-ass">
      <div class="ass">
        <?php if ($u['assinatura']): ?><img src="<?= url('/assinatura.php?id=' . (int) $u['dep_id']) ?>" alt="Assinatura do chefe de departamento"><?php endif; ?>
        <span class="linha">O Chefe de Departamento</span>
        <?php if ($u['chefe_nome']): ?><small><?= e($u['chefe_nome']) ?></small><?php endif; ?>
      </div>
    </div>
    <div class="verif"><div class="qr" data-qr="<?= e($vurl) ?>" role="img" aria-label="Código QR de verificação"></div>
      <p>Verifique a autenticidade desta credencial lendo o código QR ou abrindo<br><?= e($vurl) ?></p></div>
    <p class="ref">Referência CE-<?= date('Y', strtotime($p['criado_em'])) ?>-<?= str_pad((string) $p['id'], 5, '0', STR_PAD_LEFT) ?> · <?= e(INSTITUICAO) ?> · Documento emitido pelo <?= APP_NOME ?></p>
  </div>
</article>
<?php rodape(['/assets/qrcode.js', '/assets/qr.js']);
