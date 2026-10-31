<?php
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/nav_admin.php';
$u = exigir('admin'); so_secretaria($u); $pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    $novos = []; $erro = false;
    foreach (['horario', 'jornal'] as $k) {
        $v = trim($_POST[$k] ?? '');
        if ($v !== '' && (mb_strlen($v) > 500 || !filter_var($v, FILTER_VALIDATE_URL) || !in_array(parse_url($v, PHP_URL_SCHEME), ['http', 'https'], true))) $erro = true;
        $novos[$k] = $v;
    }
    if ($erro) flash('Cada ligação deve começar por http:// ou https://. Deixe em branco para desactivar o botão.', 'erro');
    else {
        foreach ($novos as $k => $v) $pdo->prepare('UPDATE ligacoes SET url = ? WHERE chave = ?')->execute([$v, $k]);
        registar('Ligações actualizadas'); flash('Ligações guardadas. Os botões já aparecem no painel dos estudantes.');
    }
    redirect('/admin/ligacoes.php');
}
$lig = array_column(consulta('SELECT chave, titulo, url FROM ligacoes'), null, 'chave');
topo('Horário e Jornal', $u, nav_admin($u), 'l');
?>
<h1>Horário e Jornal da faculdade</h1>
<p class="sub">Cole o endereço de cada sistema. Os estudantes verão um botão que o abre noutro separador.</p>
<section class="card"><form method="post"><?= csrf_field() ?>
  <div class="campos">
    <?php foreach (['horario', 'jornal'] as $k): ?>
      <div class="full"><label for="<?= $k ?>"><?= e($lig[$k]['titulo'] ?? $k) ?></label>
        <input id="<?= $k ?>" name="<?= $k ?>" type="url" maxlength="500" placeholder="https://" value="<?= e($lig[$k]['url'] ?? '') ?>"></div>
    <?php endforeach; ?>
  </div>
  <button class="btn" type="submit">Guardar ligações</button>
</form></section>
<?php rodape();
