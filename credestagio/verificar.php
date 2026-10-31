<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';
$c = preg_match('/^[a-f0-9]{16}$/', $_GET['c'] ?? '') ? $_GET['c'] : '';
$r = $c ? (consulta("SELECT p.local_estagio, p.meses, p.decidido_em, e.nome, e.numero, cu.nome AS curso, d.nome AS departamento
    FROM pedidos p JOIN estudantes e ON e.id = p.estudante_id JOIN cursos cu ON cu.id = e.curso_id JOIN departamentos d ON d.id = cu.departamento_id
    WHERE p.codigo = ? AND p.estado = 'Aprovado'", [$c])[0] ?? null) : null;
topo('Verificar credencial');
?>
<div class="centro">
<?php if ($r): ?>
  <section class="card valido"><h1>Credencial válida</h1>
    <p class="sub">Emitida pela <?= e(INSTITUICAO) ?> em <?= data_pt($r['decidido_em']) ?>.</p>
    <p><strong><?= e($r['nome']) ?></strong> (Nº <?= e($r['numero']) ?>)<br><?= e($r['curso']) ?><br><?= e($r['departamento']) ?></p>
    <p>Estágio em <strong><?= e($r['local_estagio']) ?></strong>, durante <?= (int) $r['meses'] ?> meses.</p></section>
<?php else: ?>
  <section class="card invalido"><h1>Credencial não reconhecida</h1>
    <p>Este código não corresponde a nenhuma credencial emitida. Se recebeu o documento impresso, confirme com a secretaria.</p></section>
<?php endif; ?>
</div>
<?php rodape();
