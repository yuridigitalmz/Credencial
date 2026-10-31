<?php
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/nav_admin.php';
$u = exigir('admin'); so_secretaria($u); $pdo = db();
$t = fn($k, $n = 120) => mb_substr(trim($_POST[$k] ?? ''), 0, $n);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    $acao = $_POST['acao'] ?? ''; $id = (int) ($_POST['id'] ?? 0);
    try {
        if ($acao === 'dep_criar') {
            if ($t('nome') === '') throw new RuntimeException('Indique o nome do departamento.');
            $pdo->prepare('INSERT INTO departamentos (nome) VALUES (?)')->execute([$t('nome')]);
            registar('Departamento criado', $t('nome')); flash('Departamento criado.');
        } elseif ($acao === 'dep_apagar') {
            $pdo->prepare('DELETE FROM departamentos WHERE id = ?')->execute([$id]);
            registar('Departamento removido', "#$id"); flash('Departamento removido.');
        } elseif ($acao === 'curso_criar') {
            $dep = (int) ($_POST['departamento_id'] ?? 0);
            if ($t('nome') === '' || $t('faculdade') === '' || !consulta('SELECT id FROM departamentos WHERE id = ?', [$dep])) throw new RuntimeException('Preencha nome, faculdade e departamento.');
            $pdo->prepare('INSERT INTO cursos (nome, faculdade, departamento_id) VALUES (?,?,?)')->execute([$t('nome'), $t('faculdade'), $dep]);
            registar('Curso criado', $t('nome')); flash('Curso criado.');
        } elseif ($acao === 'curso_apagar') {
            if (consulta('SELECT id FROM estudantes WHERE curso_id = ? LIMIT 1', [$id])) throw new RuntimeException('Não pode remover um curso que tem estudantes.');
            $pdo->prepare('DELETE FROM cursos WHERE id = ?')->execute([$id]);
            registar('Curso removido', "#$id"); flash('Curso removido, com as suas disciplinas.');
        } elseif ($acao === 'chefe_criar') {
            $us = $t('usuario', 40); $dep = (int) ($_POST['departamento_id'] ?? 0);
            if (!preg_match('/^[A-Za-z0-9._-]{3,40}$/', $us) || $t('nome') === '' || !consulta('SELECT id FROM departamentos WHERE id = ?', [$dep])) throw new RuntimeException('Utilizador (3 a 40 letras, números, . _ -), nome e departamento são obrigatórios.');
            if (consulta('SELECT id FROM administradores WHERE usuario = ?', [$us])) throw new RuntimeException('Esse utilizador já existe.');
            $tmp = substr(bin2hex(random_bytes(4)), 0, 8);
            $pdo->prepare('INSERT INTO administradores (usuario, nome, departamento_id, senha_hash, trocar_senha) VALUES (?,?,?,?,1)')->execute([$us, $t('nome'), $dep, password_hash($tmp, PASSWORD_DEFAULT)]);
            registar('Conta de chefe criada', $us); flash("Conta criada. Senha temporária (mostrada só agora): $tmp");
        } elseif ($acao === 'chefe_senha') {
            $tmp = substr(bin2hex(random_bytes(4)), 0, 8);
            $pdo->prepare('UPDATE administradores SET senha_hash = ?, trocar_senha = 1 WHERE id = ? AND departamento_id IS NOT NULL')->execute([password_hash($tmp, PASSWORD_DEFAULT), $id]);
            registar('Senha de chefe reposta', "#$id"); flash("Nova senha temporária (mostrada só agora): $tmp");
        } elseif ($acao === 'chefe_apagar') {
            $pdo->prepare('DELETE FROM administradores WHERE id = ? AND departamento_id IS NOT NULL')->execute([$id]);
            registar('Conta de chefe removida', "#$id"); flash('Conta removida.');
        }
    } catch (RuntimeException $e) { flash($e->getMessage(), 'erro'); }
    catch (PDOException) { flash('Não foi possível concluir: o registo está em uso ou já existe.', 'erro'); }
    redirect('/admin/estrutura.php');
}
$deps = consulta('SELECT d.*, (SELECT COUNT(*) FROM cursos c WHERE c.departamento_id = d.id) nc FROM departamentos d ORDER BY d.nome');
$cursos = consulta('SELECT c.*, d.nome AS dep, (SELECT COUNT(*) FROM estudantes e WHERE e.curso_id = c.id) ne FROM cursos c JOIN departamentos d ON d.id = c.departamento_id ORDER BY c.nome');
$chefes = consulta('SELECT a.*, d.nome AS dep FROM administradores a JOIN departamentos d ON d.id = a.departamento_id ORDER BY d.nome, a.nome');
$apagar = fn($acao, $id, $msg) => '<form method="post" data-confirm="' . e($msg) . '">' . csrf_field() . '<input type="hidden" name="acao" value="' . $acao . '"><input type="hidden" name="id" value="' . (int) $id . '"><button class="btn perigo peq">Remover</button></form>';

topo('Estrutura', $u, nav_admin($u), 'x');
?>
<h1>Departamentos, cursos e chefes</h1>
<p class="sub">Um curso pertence a um departamento; a assinatura da credencial é a do chefe desse departamento.</p>

<section class="card"><h2>Departamentos</h2>
  <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="acao" value="dep_criar"><label class="sr-only" for="dn">Nome</label><input id="dn" name="nome" required maxlength="120" placeholder="Departamento de…"><button class="btn peq">Adicionar</button></form>
  <div class="tabela mt"><table><tr><th>Departamento</th><th>Cursos</th><th></th></tr>
  <?php foreach ($deps as $d): ?><tr><td><?= e($d['nome']) ?></td><td><?= (int) $d['nc'] ?></td><td><?= $d['nc'] ? '' : $apagar('dep_apagar', $d['id'], 'Remover este departamento?') ?></td></tr><?php endforeach; ?></table></div></section>

<section class="card"><h2>Cursos</h2>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="acao" value="curso_criar">
    <div class="campos">
      <div><label for="cn">Nome do curso</label><input id="cn" name="nome" required maxlength="120"></div>
      <div><label for="cf">Faculdade</label><input id="cf" name="faculdade" required maxlength="120" value="Faculdade de Ciências e Tecnologia"></div>
      <div><label for="cd">Departamento</label><select id="cd" name="departamento_id"><?php foreach ($deps as $d): ?><option value="<?= (int) $d['id'] ?>"><?= e($d['nome']) ?></option><?php endforeach; ?></select></div>
    </div><button class="btn">Adicionar curso</button></form>
  <div class="tabela mt"><table><tr><th>Curso</th><th>Departamento</th><th>Estudantes</th><th></th></tr>
  <?php foreach ($cursos as $c): ?><tr><td><?= e($c['nome']) ?></td><td><?= e($c['dep']) ?></td><td><?= (int) $c['ne'] ?></td><td><?= $c['ne'] ? '' : $apagar('curso_apagar', $c['id'], 'Remover este curso e as suas disciplinas?') ?></td></tr><?php endforeach; ?></table></div></section>

<section class="card"><h2>Contas de chefes de departamento</h2>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="acao" value="chefe_criar">
    <div class="campos">
      <div><label for="cu">Utilizador</label><input id="cu" name="usuario" required maxlength="40" placeholder="chefe.exemplo"></div>
      <div><label for="cnm">Nome</label><input id="cnm" name="nome" required maxlength="120"></div>
      <div><label for="cdp">Departamento</label><select id="cdp" name="departamento_id"><?php foreach ($deps as $d): ?><option value="<?= (int) $d['id'] ?>"><?= e($d['nome']) ?></option><?php endforeach; ?></select></div>
    </div><button class="btn">Criar conta</button></form>
  <div class="tabela mt"><table><tr><th>Utilizador</th><th>Nome</th><th>Departamento</th><th>Acções</th></tr>
  <?php foreach ($chefes as $c): ?><tr><td><?= e($c['usuario']) ?></td><td><?= e($c['nome']) ?></td><td><?= e($c['dep']) ?></td>
    <td class="inline"><form method="post" data-confirm="Gerar nova senha temporária?"><?= csrf_field() ?><input type="hidden" name="acao" value="chefe_senha"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="btn sec peq">Repor senha</button></form><?= $apagar('chefe_apagar', $c['id'], 'Remover esta conta?') ?></td></tr><?php endforeach; ?></table></div></section>
<?php rodape();
