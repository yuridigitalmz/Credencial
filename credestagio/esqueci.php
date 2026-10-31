<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';
require_once __DIR__ . '/inc/notificar.php';
if (utilizador()) redirect('/index.php');
$enviado = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $n = (int) consulta("SELECT COUNT(*) n FROM tentativas_login WHERE ip = ? AND tipo = 'reset' AND criado_em > NOW() - INTERVAL 1 HOUR", [$ip])[0]['n'];
    if ($n < 5) {
        db()->prepare("INSERT INTO tentativas_login (ip, tipo) VALUES (?, 'reset')")->execute([$ip]);
        $id = trim($_POST['identificador'] ?? '');
        $est = $id !== '' ? (consulta('SELECT id, nome, email FROM estudantes WHERE numero = ? OR email = ? LIMIT 1', [$id, $id])[0] ?? null) : null;
        if ($est && $est['email']) {
            $token = bin2hex(random_bytes(32));
            db()->prepare('INSERT INTO reset_tokens (estudante_id, token_hash, expira_em) VALUES (?,?, DATE_ADD(NOW(), INTERVAL 1 HOUR))')->execute([$est['id'], hash('sha256', $token)]);
            enviar_email($est['email'], 'Recuperação de senha — ' . APP_NOME,
                "Olá {$est['nome']},\n\nPara definir uma nova senha abra a ligação abaixo (válida durante 1 hora):\n" . site_url('/redefinir.php?token=' . $token) . "\n\nSe não pediu esta recuperação, ignore esta mensagem.");
            registar('Pedido de recuperação de senha', '', 'estudante', (int) $est['id'], $est['nome']);
        }
    }
    $enviado = true; // resposta igual quer a conta exista ou não
}
topo('Recuperar senha');
?>
<div class="centro"><section class="card">
  <h1>Recuperar senha</h1>
  <?php if ($enviado): ?>
    <div class="aviso ok" role="status">Se os dados corresponderem a uma conta com e-mail registado, enviámos uma ligação para definir uma nova senha. Se não receber nada, fale com a secretaria.</div>
  <?php else: ?>
    <p class="sub">Indique o seu número de estudante ou o e-mail registado.</p>
    <form method="post"><?= csrf_field() ?>
      <div class="campos"><div class="full"><label for="i">Número de estudante ou e-mail</label><input id="i" name="identificador" required autofocus></div></div>
      <button class="btn" type="submit">Enviar ligação</button>
    </form>
  <?php endif; ?>
  <p class="mt"><a href="<?= url('/index.php') ?>">Voltar a entrar</a></p>
</section></div>
<?php rodape();
