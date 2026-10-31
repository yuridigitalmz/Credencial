<?php
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/nav_admin.php';
$u = exigir('admin'); $pdo = db(); $dep = escopo($u);
$pasta = __DIR__ . '/../uploads/assinaturas/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    $id = (int) ($_POST['id'] ?? 0);
    $st = $pdo->prepare('SELECT * FROM departamentos WHERE id = ?'); $st->execute([$id]); $d = $st->fetch();
    if (!$d || ($dep !== null && $dep !== $id)) { http_response_code(403); exit('Sem permissão para este departamento.'); }

    if (($_POST['acao'] ?? '') === 'remover') {
        if ($d['assinatura']) @unlink($pasta . basename($d['assinatura']));
        $pdo->prepare('UPDATE departamentos SET assinatura = NULL WHERE id = ?')->execute([$id]);
        flash('Assinatura removida.'); registar('Assinatura removida', $d['nome']);
    } else {
        $chefe = mb_substr(trim($_POST['chefe_nome'] ?? ''), 0, 120);
        $f = $_FILES['assinatura'] ?? ['error' => UPLOAD_ERR_NO_FILE];
        $novo = $d['assinatura']; $erro = null;
        if ($f['error'] === UPLOAD_ERR_OK) {
            $ext = ['image/png' => 'png', 'image/jpeg' => 'jpg'][(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name'])] ?? null;
            if ($f['size'] > 1048576) $erro = 'A imagem excede 1 MB.';
            elseif (!$ext || !@getimagesize($f['tmp_name'])) $erro = 'Envie uma imagem PNG ou JPG válida.';
            else {
                $nome = 'ass_' . $id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                if (move_uploaded_file($f['tmp_name'], $pasta . $nome)) {
                    if ($d['assinatura']) @unlink($pasta . basename($d['assinatura']));
                    $novo = $nome;
                } else $erro = 'Não foi possível guardar a imagem (verifique as permissões de uploads/).';
            }
        } elseif ($f['error'] !== UPLOAD_ERR_NO_FILE) $erro = 'Falha no envio da imagem.';
        if ($erro) flash($erro, 'erro');
        else { $pdo->prepare('UPDATE departamentos SET chefe_nome = ?, assinatura = ? WHERE id = ?')->execute([$chefe ?: null, $novo, $id]); registar('Assinatura/chefe actualizados', $d['nome']); flash('Dados guardados. A assinatura já aparece nas credenciais deste departamento.'); }
    }
    redirect('/admin/assinaturas.php');
}
$deps = consulta('SELECT * FROM departamentos' . ($dep ? ' WHERE id = ?' : '') . ' ORDER BY nome', $dep ? [$dep] : []);

topo('Assinaturas', $u, nav_admin($u), 'a');
?>
<h1>Assinatura do chefe de departamento</h1>
<p class="sub">A imagem carregada aparece em todas as credenciais do departamento. Use PNG com fundo transparente ou JPG (máx. 1 MB).</p>
<?php foreach ($deps as $d): ?>
<section class="card"><h2><?= e($d['nome']) ?></h2>
  <?php if ($d['assinatura']): ?><div class="ass-prev"><img src="<?= url('/assinatura.php?id=' . (int) $d['id']) ?>" alt="Assinatura actual de <?= e($d['chefe_nome'] ?: 'chefe de departamento') ?>"></div>
  <?php else: ?><p class="aviso info">Ainda sem assinatura carregada.</p><?php endif; ?>
  <form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
    <div class="campos">
      <div><label for="cn<?= (int) $d['id'] ?>">Nome do chefe de departamento</label><input id="cn<?= (int) $d['id'] ?>" name="chefe_nome" maxlength="120" value="<?= e($d['chefe_nome']) ?>"></div>
      <div><label for="as<?= (int) $d['id'] ?>">Nova assinatura (PNG ou JPG)</label><input id="as<?= (int) $d['id'] ?>" type="file" name="assinatura" accept="image/png,image/jpeg"></div>
    </div>
    <button class="btn" type="submit">Guardar</button>
  </form>
  <?php if ($d['assinatura']): ?><form method="post" class="espaco" data-confirm="Remover a assinatura deste departamento?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $d['id'] ?>"><input type="hidden" name="acao" value="remover"><button class="btn sec peq">Remover assinatura</button></form><?php endif; ?>
</section>
<?php endforeach; rodape();
