<div class="pagina-topo">
  <div>
    <p class="sobretitulo">Funções</p>
    <h1><?= e($f['nome']) ?></h1>
  </div>
  <a href="/admin/funcoes" class="link-sublinhado">Voltar</a>
</div>
<form method="post" action="/admin/funcoes/<?= (int) $f['id'] ?>" class="form painel form-estreito" novalidate>
  <?= csrf_campo() ?>
  <?php require RAIZ . '/app/views/admin/funcao-campos.php'; ?>
  <div class="form-rodape"><button type="submit" class="btn btn-primario">Salvar</button></div>
</form>
