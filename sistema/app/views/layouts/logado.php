<?php
// Casca das áreas logadas (guia e ADM). Os layouts guia.php e admin.php definem:
// $area, $inicioHref, $itensMenu [chave => [rótulo, link, ícone]], $abas (chaves que vão na barra do celular),
// $perfilNome, $perfilLinha, $acaoSair, $comTour.
$menuAtivo = $menu ?? '';
$noMais = array_diff_key($itensMenu, array_flip($abas));
$maisAtivo = isset($noMais[$menuAtivo]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<?php require RAIZ . '/app/views/comum/head.php'; ?>
</head>
<body class="app">
  <aside class="lateral" aria-label="Menu">
    <div class="lateral-topo">
      <a href="<?= e($inicioHref) ?>" class="marca"><img src="<?= e(asset('img/logo-branco.png')) ?>" alt="645 Turismo" width="80" height="32"></a>
      <p class="sobretitulo"><?= e($area) ?></p>
    </div>
    <nav class="lateral-nav">
      <?php foreach ($itensMenu as $chave => [$rotulo, $href, $ic]): ?>
        <a href="<?= e($href) ?>" class="<?= $menuAtivo === $chave ? 'ativo' : '' ?>" <?= $menuAtivo === $chave ? 'aria-current="page"' : '' ?>><?= icone($ic) ?><?= e($rotulo) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="lateral-perfil">
      <strong><?= e($perfilNome) ?></strong>
      <span class="codigo"><?= e($perfilLinha) ?></span>
      <div class="lateral-perfil-acoes">
        <?php if ($comTour): ?>
          <button type="button" data-tour-abrir><?= icone('brilho') ?> Rever introdução</button>
        <?php endif; ?>
        <form method="post" action="<?= e($acaoSair) ?>">
          <?= csrf_campo() ?>
          <button type="submit" class="sair"><?= icone('sair') ?> Sair</button>
        </form>
      </div>
    </div>
  </aside>

  <div class="conteudo">
    <header class="barra-movel">
      <a href="<?= e($inicioHref) ?>" class="marca"><img src="<?= e(asset('img/logo-branco.png')) ?>" alt="645 Turismo" width="65" height="26"></a>
      <div class="barra-movel-id">
        <strong><?= e(explode(' ', trim($perfilNome))[0]) ?></strong>
        <span><?= e($perfilLinha) ?></span>
      </div>
    </header>

    <main class="app-main">
      <?php require RAIZ . '/app/views/comum/flashes.php'; ?>
      <?= $conteudo ?>
    </main>
  </div>

  <nav class="abas" aria-label="Menu rápido">
    <?php foreach ($abas as $chave): [$rotulo, $href, $ic] = $itensMenu[$chave]; $curto = $itensMenu[$chave][3] ?? $rotulo; ?>
      <a href="<?= e($href) ?>" class="<?= $menuAtivo === $chave ? 'ativo' : '' ?>" <?= $menuAtivo === $chave ? 'aria-current="page"' : '' ?> aria-label="<?= e($rotulo) ?>"><?= icone($ic) ?><?= e($curto) ?></a>
    <?php endforeach; ?>
    <button type="button" class="<?= $maisAtivo ? 'ativo' : '' ?>" aria-expanded="false" aria-controls="folha" data-folha><?= icone('mais') ?>Mais</button>
  </nav>
  <div class="folha" id="folha" hidden>
    <?php foreach ($noMais as $chave => [$rotulo, $href, $ic]): ?>
      <a href="<?= e($href) ?>" class="<?= $menuAtivo === $chave ? 'ativo' : '' ?>"><?= icone($ic) ?><?= e($rotulo) ?></a>
    <?php endforeach; ?>
    <?php if ($comTour): ?>
      <button type="button" data-tour-abrir><?= icone('brilho') ?> Rever introdução</button>
    <?php endif; ?>
    <form method="post" action="<?= e($acaoSair) ?>">
      <?= csrf_campo() ?>
      <button type="submit" class="sair"><?= icone('sair') ?> Sair</button>
    </form>
  </div>

  <?php if ($comTour) require RAIZ . '/app/views/guia/tour.php'; ?>
  <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
