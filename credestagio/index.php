<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';

if (isset($_SESSION['papel']) && utilizador()) {
    redirect($_SESSION['papel'] === 'admin' ? '/admin/painel.php' : '/aluno/painel.php');
}

$erro = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
    $id  = trim($_POST['utilizador'] ?? '');
    $pw  = (string) ($_POST['senha'] ?? '');
    $pdo = db();

    $n = $pdo->prepare('SELECT COUNT(*) FROM tentativas_login WHERE ip = ? AND tipo = \'login\' AND criado_em > NOW() - INTERVAL 10 MINUTE');
    $n->execute([$ip]);
    if ((int) $n->fetchColumn() >= 8) {
        $erro = 'Demasiadas tentativas falhadas. Aguarde 10 minutos e tente novamente.';
    } else {
        $papel = null; $reg = null;
        $st = $pdo->prepare('SELECT * FROM administradores WHERE usuario = ?'); $st->execute([$id]);
        if (($reg = $st->fetch()) && password_verify($pw, $reg['senha_hash'])) $papel = 'admin';
        if (!$papel) {
            $st = $pdo->prepare('SELECT * FROM estudantes WHERE numero = ?'); $st->execute([$id]);
            if (($reg = $st->fetch()) && password_verify($pw, $reg['senha_hash'])) $papel = 'estudante';
        }
        if ($papel) {
            session_regenerate_id(true);
            $_SESSION = ['papel' => $papel, 'uid' => (int) $reg['id'], 'ult' => time()];
            registar('Início de sessão', '', $papel, (int) $reg['id'], $reg['nome']);
            $pdo->prepare("DELETE FROM tentativas_login WHERE ip = ? AND tipo = 'login'")->execute([$ip]);
            redirect($papel === 'admin' ? '/admin/painel.php' : '/aluno/painel.php');
        }
        $pdo->prepare('INSERT INTO tentativas_login (ip) VALUES (?)')->execute([$ip]);
        $erro = 'Número de utilizador ou senha incorrectos.';
    }
}
topo('Entrar');
?>
<div class="entrada">
  <section class="lado">
    <img class="logo-login" src="<?= url('/assets/logo.png') ?>" alt="Universidade Licungo">
    <h1><?= APP_NOME ?></h1>
    <p>Peça a sua credencial de estágio e acompanhe a resposta da secretaria, sem filas.</p>
  </section>
  <section class="form">
    <form method="post" autocomplete="off">
      <h2>Entrar</h2>
      <p class="sub"><?= INSTITUICAO ?></p>
      <?php if (isset($_GET['expirada'])): ?><div class="aviso info" role="alert">A sessão expirou por inactividade. Entre de novo.</div><?php endif; ?>
      <?php if ($erro): ?><div class="aviso erro" role="alert"><?= e($erro) ?></div><?php endif; ?>
      <?= csrf_field() ?>
      <div class="campos">
        <div><label for="u">Número de estudante ou utilizador</label><input id="u" name="utilizador" required autofocus></div>
        <div><label for="s">Senha</label><input id="s" name="senha" type="password" required></div>
      </div>
      <button class="btn" type="submit">Entrar</button>
      <p class="mt"><a href="<?= url('/esqueci.php') ?>">Esqueceu a senha?</a></p>
    </form>
  </section>
</div>
<?php rodape();
