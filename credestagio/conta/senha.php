<?php
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/nav_admin.php';
$admin = ($_SESSION['papel'] ?? '') === 'admin';
$u = exigir($admin ? 'admin' : 'estudante');
$tabela = $admin ? 'administradores' : 'estudantes';
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    $atual = (string) ($_POST['atual'] ?? ''); $nova = (string) ($_POST['nova'] ?? ''); $conf = (string) ($_POST['conf'] ?? '');
    $hash = consulta("SELECT senha_hash FROM $tabela WHERE id = ?", [$u['id']])[0]['senha_hash'] ?? '';
    if (!password_verify($atual, $hash)) $erro = 'A senha actual está incorrecta.';
    elseif (mb_strlen($nova) < 8) $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
    elseif ($nova === $atual) $erro = 'A nova senha deve ser diferente da actual.';
    elseif ($nova !== $conf) $erro = 'A confirmação não coincide com a nova senha.';
    else {
        db()->prepare("UPDATE $tabela SET senha_hash = ?, trocar_senha = 0 WHERE id = ?")->execute([password_hash($nova, PASSWORD_DEFAULT), $u['id']]);
        registar('Senha alterada');
        session_regenerate_id(true);
        flash('Senha alterada com sucesso.');
        redirect('/conta/senha.php');
    }
}
topo('Alterar senha', $u, $admin ? nav_admin($u) : nav_aluno(), 's');
?>
<h1>Alterar senha</h1>
<p class="sub">Escolha uma senha com pelo menos 8 caracteres que só você conheça.</p>
<section class="card limite"><form method="post" autocomplete="off"><?= csrf_field() ?>
  <?php if ($erro): ?><div class="aviso erro" role="alert"><?= e($erro) ?></div><?php endif; ?>
  <div class="campos">
    <div class="full"><label for="a">Senha actual</label><input id="a" name="atual" type="password" required autocomplete="current-password"></div>
    <div class="full"><label for="n">Nova senha</label><input id="n" name="nova" type="password" required minlength="8" autocomplete="new-password"></div>
    <div class="full"><label for="c">Confirmar nova senha</label><input id="c" name="conf" type="password" required minlength="8" autocomplete="new-password"></div>
  </div>
  <button class="btn" type="submit">Guardar nova senha</button>
</form></section>
<?php rodape();
