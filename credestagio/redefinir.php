<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';
if (utilizador()) redirect('/index.php');
$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$t = preg_match('/^[a-f0-9]{64}$/', $token)
    ? (consulta('SELECT t.id, t.estudante_id, e.nome FROM reset_tokens t JOIN estudantes e ON e.id = t.estudante_id WHERE t.token_hash = ? AND t.usado = 0 AND t.expira_em > NOW()', [hash('sha256', $token)])[0] ?? null) : null;
$erro = null;
if ($t && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    $nova = (string) ($_POST['nova'] ?? ''); $conf = (string) ($_POST['conf'] ?? '');
    if (mb_strlen($nova) < 8) $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
    elseif ($nova !== $conf) $erro = 'A confirmação não coincide com a nova senha.';
    else {
        db()->prepare('UPDATE estudantes SET senha_hash = ?, trocar_senha = 0 WHERE id = ?')->execute([password_hash($nova, PASSWORD_DEFAULT), $t['estudante_id']]);
        db()->prepare('UPDATE reset_tokens SET usado = 1 WHERE estudante_id = ?')->execute([$t['estudante_id']]);
        registar('Senha redefinida por recuperação', '', 'estudante', (int) $t['estudante_id'], $t['nome']);
        flash('Senha redefinida. Já pode entrar.');
        redirect('/index.php');
    }
}
topo('Nova senha');
?>
<div class="centro"><section class="card">
<?php if (!$t): ?>
  <h1>Ligação inválida</h1><p>Esta ligação já foi usada ou expirou. <a href="<?= url('/esqueci.php') ?>">Peça uma nova</a>.</p>
<?php else: ?>
  <h1>Nova senha</h1><p class="sub">Olá, <?= e($t['nome']) ?>. Escolha uma senha com pelo menos 8 caracteres.</p>
  <?php if ($erro): ?><div class="aviso erro" role="alert"><?= e($erro) ?></div><?php endif; ?>
  <form method="post" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>">
    <div class="campos">
      <div class="full"><label for="n">Nova senha</label><input id="n" name="nova" type="password" required minlength="8" autocomplete="new-password"></div>
      <div class="full"><label for="c">Confirmar nova senha</label><input id="c" name="conf" type="password" required minlength="8" autocomplete="new-password"></div>
    </div>
    <button class="btn" type="submit">Guardar nova senha</button>
  </form>
<?php endif; ?>
</section></div>
<?php rodape();
