<?php
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/nav_admin.php';
$u = exigir('admin'); so_secretaria($u); $pdo = db();
const COLUNAS = ['numero', 'nome', 'curso', 'ano', 'regime', 'financeiro', 'pai', 'mae', 'bi', 'bi_emissao', 'contacto', 'email'];
const MAX_LINHAS = 300;

if (isset($_GET['modelo'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="modelo_estudantes.csv"');
    $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, COLUNAS, ';', '"', '');
    fputcsv($o, ['04.0100.2026', 'Maria Exemplo Silva', 'Matemática', 3, 'Laboral', 'Regularizada', 'João Silva', 'Ana Silva', '110000000000A', '01/01/2020 — Beira', '84 000 0000', 'maria@exemplo.ac.mz'], ';', '"', '');
    exit;
}

$res = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    $f = $_FILES['csv'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 2 * 1024 * 1024) {
        flash('Envie um ficheiro CSV com menos de 2 MB.', 'erro');
    } else {
        set_time_limit(180);
        $utf = fn($c) => mb_check_encoding((string) $c, 'UTF-8') ? (string) $c : mb_convert_encoding((string) $c, 'UTF-8', 'Windows-1252');
        $h = fopen($f['tmp_name'], 'r');
        $primeira = (string) fgets($h); rewind($h);
        $sep = substr_count($primeira, ';') >= substr_count($primeira, ',') ? ';' : ',';
        $linhas = [];
        while (($r = fgetcsv($h, 0, $sep, '"', '')) !== false) {
            if ($r === [null] || !array_filter($r, fn($c) => trim((string) $c) !== '')) continue;
            $linhas[] = array_map($utf, $r);
        }
        fclose($h);
        $cab = $linhas ? array_map(fn($c) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $c))), array_shift($linhas)) : [];
        if (array_diff(['numero', 'nome', 'curso', 'ano'], $cab)) {
            flash('O ficheiro precisa das colunas numero, nome, curso e ano na primeira linha. Descarregue o modelo.', 'erro');
        } elseif (count($linhas) > MAX_LINHAS) {
            flash('Máximo de ' . MAX_LINHAS . ' estudantes por importação. Divida o ficheiro.', 'erro');
        } else {
            $cursos = []; foreach (consulta('SELECT id, nome FROM cursos') as $c) $cursos[mb_strtolower($c['nome'])] = (int) $c['id'];
            $ins = $pdo->prepare('INSERT INTO estudantes (numero, nome, nome_pai, nome_mae, bi, bi_emissao, contacto, email, curso_id, ano, regime, financeiro, senha_hash, trocar_senha) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1)');
            $res = ['criados' => [], 'erros' => []];
            foreach ($linhas as $i => $r) {
                $l = $i + 2; $d = [];
                foreach ($cab as $k => $nome) $d[$nome] = trim($r[$k] ?? '');
                $num = mb_substr($d['numero'], 0, 30); $nome = mb_substr($d['nome'], 0, 120); $ano = (int) $d['ano'];
                $curso = $cursos[mb_strtolower($d['curso'])] ?? 0; $email = $d['email'] ?? '';
                if ($num === '' || $nome === '') $res['erros'][] = [$l, 'Falta o número ou o nome.'];
                elseif (!$curso) $res['erros'][] = [$l, "Curso não encontrado: «{$d['curso']}»."];
                elseif ($ano < 1 || $ano > 4) $res['erros'][] = [$l, 'O ano deve ser de 1 a 4.'];
                elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $res['erros'][] = [$l, 'E-mail inválido.'];
                elseif (consulta('SELECT id FROM estudantes WHERE numero = ?', [$num])) $res['erros'][] = [$l, "O número $num já existe."];
                else {
                    $tmp = substr(bin2hex(random_bytes(4)), 0, 8);
                    $ins->execute([$num, $nome, mb_substr($d['pai'] ?? '', 0, 120) ?: null, mb_substr($d['mae'] ?? '', 0, 120) ?: null, mb_substr($d['bi'] ?? '', 0, 30) ?: null,
                        mb_substr($d['bi_emissao'] ?? '', 0, 120) ?: null, mb_substr($d['contacto'] ?? '', 0, 30) ?: null, $email ?: null, $curso, $ano,
                        ($d['regime'] ?? '') === 'Pós-laboral' ? 'Pós-laboral' : 'Laboral', mb_strtolower($d['financeiro'] ?? '') === 'em dívida' ? 'Em dívida' : 'Regularizada',
                        password_hash($tmp, PASSWORD_DEFAULT)]);
                    $res['criados'][] = [$num, $nome, $tmp];
                }
            }
            registar('Importação de estudantes', count($res['criados']) . ' criados, ' . count($res['erros']) . ' com erro');
        }
    }
}
$csv = '';
if ($res && $res['criados']) {
    $csv = "\xEF\xBB\xBFnumero;nome;senha_temporaria\r\n";
    foreach ($res['criados'] as $c) $csv .= implode(';', $c) . "\r\n";
}
topo('Importar estudantes', $u, nav_admin($u), 'e');
?>
<h1>Importar estudantes</h1>
<p class="sub">Carregue uma folha de Excel guardada como CSV. Colunas: <?= e(implode(', ', COLUNAS)) ?>. São obrigatórias numero, nome, curso (igual ao nome registado) e ano. Máximo <?= MAX_LINHAS ?> por vez.</p>
<section class="card">
  <p><a href="<?= url('/admin/importar.php?modelo=1') ?>">Descarregar ficheiro modelo</a></p>
  <form method="post" enctype="multipart/form-data"><?= csrf_field() ?>
    <div class="campos"><div class="full"><label for="f">Ficheiro CSV</label><input id="f" type="file" name="csv" accept=".csv,text/csv" required></div></div>
    <button class="btn">Importar</button>
  </form>
</section>
<?php if ($res): ?>
<section class="card"><h2>Resultado: <?= count($res['criados']) ?> criados, <?= count($res['erros']) ?> com erro</h2>
  <?php if ($res['criados']): ?>
    <div class="aviso info">As senhas temporárias só aparecem agora. Cada estudante terá de a mudar no primeiro acesso.
      <a href="data:text/csv;charset=utf-8;base64,<?= base64_encode($csv) ?>" download="senhas_temporarias.csv">Descarregar lista de senhas (CSV)</a></div>
    <div class="tabela"><table><tr><th>Número</th><th>Nome</th><th>Senha temporária</th></tr>
    <?php foreach ($res['criados'] as $c): ?><tr><td><?= e($c[0]) ?></td><td><?= e($c[1]) ?></td><td><code><?= e($c[2]) ?></code></td></tr><?php endforeach; ?></table></div>
  <?php endif; ?>
  <?php if ($res['erros']): ?><h3 class="mt">Linhas com erro (não importadas)</h3><div class="tabela"><table><tr><th>Linha</th><th>Motivo</th></tr>
    <?php foreach ($res['erros'] as $e_): ?><tr><td><?= (int) $e_[0] ?></td><td><?= e($e_[1]) ?></td></tr><?php endforeach; ?></table></div><?php endif; ?>
</section>
<?php endif; rodape();
