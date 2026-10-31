<?php
function topo(string $titulo, ?array $u = null, array $nav = [], string $ativo = ''): void { ?>
<!DOCTYPE html>
<html lang="pt-MZ">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo) ?> — <?= APP_NOME ?></title>
<link rel="stylesheet" href="<?= url('/assets/style.css') ?>">
</head>
<body>
<a class="skip" href="#conteudo">Saltar para o conteúdo</a>
<?php if ($u): ?>
<header class="topo">
  <div class="marca"><img class="logo-mini" src="<?= url('/assets/logo.png') ?>" alt=""><?= APP_NOME ?></div>
  <input type="checkbox" id="mn" class="sr-only"><label for="mn" class="burger">Menu</label>
  <nav aria-label="Principal">
    <?php foreach ($nav as $k => [$href, $rot]): ?>
      <?php if ($href === null): ?>
        <span class="em-breve" aria-disabled="true"><?= e($rot) ?> <small>em breve</small></span>
      <?php elseif (str_starts_with($href, 'http')): ?>
        <a href="<?= e($href) ?>" target="_blank" rel="noopener noreferrer"><?= e($rot) ?><span class="sr-only"> (abre noutro separador)</span></a>
      <?php else: ?>
        <a href="<?= url($href) ?>"<?= $k === $ativo ? ' aria-current="page"' : '' ?>><?= e($rot) ?></a>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>
  <div class="user"><span><?= e($u['nome']) ?></span><a class="btn sec peq" href="<?= url('/logout.php') ?>">Sair</a></div>
</header>
<?php endif; ?>
<main id="conteudo"<?= $u ? ' class="wrap"' : '' ?>>
<?php if ($f = flash()): ?><div class="aviso <?= e($f[0]) ?>" role="status"><?= e($f[1]) ?></div><?php endif;
}
function rodape(array $scripts = []): void { ?>
</main>
<footer class="rod"><?= INSTITUICAO ?> · <?= APP_NOME ?></footer>
<script src="<?= url('/assets/app.js') ?>"></script>
<?php foreach ($scripts as $js): ?><script src="<?= url($js) ?>"></script><?php endforeach; ?>
</body>
</html>
<?php }

function paginacao(int $total, int $pag, int $por): void {
    $n = (int) ceil($total / $por);
    if ($n <= 1) return;
    $q = $_GET;
    $link = function (int $p) use ($q) { $q['p'] = $p; return '?' . http_build_query($q); };
    echo '<nav class="paginas" aria-label="Páginas">';
    if ($pag > 1) echo '<a class="btn sec peq" href="' . e($link($pag - 1)) . '">« Anterior</a>';
    echo '<span>Página ' . $pag . ' de ' . $n . ' · ' . $total . ' registos</span>';
    if ($pag < $n) echo '<a class="btn sec peq" href="' . e($link($pag + 1)) . '">Seguinte »</a>';
    echo '</nav>';
}
