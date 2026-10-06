<!DOCTYPE html>
<html lang="pt-BR">
<head>
<?php require RAIZ . '/app/views/comum/head.php'; ?>
</head>
<body class="pub">
  <header class="pub-topo">
    <a href="/" class="marca" aria-label="645 Turismo, Área do Guia">
      <img src="<?= e(asset('img/logo-branco.png')) ?>" alt="645 Turismo" width="75" height="30">
      <span class="marca-rotulo">Área do Guia</span>
    </a>
    <nav class="pub-nav">
      <a href="/cadastro" class="btn btn-contorno btn-p">Quero ser guia 645</a>
    </nav>
  </header>

  <main class="pub-main">
    <?php require RAIZ . '/app/views/comum/flashes.php'; ?>
    <?= $conteudo ?>
  </main>

  <footer class="pub-rodape">
    <span>© <?= date('Y') ?> 645 Turismo · Cadastur 48.925.512/0001-95</span>
    <span class="pub-rodape-links">
      <a href="https://645turismo.com.br" rel="noopener">645turismo.com.br</a>
      <a href="/admin">Acesso ADM</a>
    </span>
  </footer>
  <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
