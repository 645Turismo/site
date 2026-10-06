<section class="painel-acesso centro" aria-labelledby="titulo-form">
    <h2 id="titulo-form"><?= e($titulo) ?></h2>
    <p>Use pelo menos 8 caracteres, com letras e números.</p>
  <form method="post" action="<?= e($acao) ?>" class="form" novalidate>
    <?= csrf_campo() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <label class="campo">
      <span>Nova senha</span>
      <span class="senha">
        <input type="password" name="senha" autocomplete="new-password" minlength="8" required autofocus>
        <button type="button" class="senha-alternar" data-alternar-senha aria-label="Mostrar senha"><?= icone('olho') ?></button>
      </span>
    </label>
    <label class="campo">
      <span>Confirmar nova senha</span>
      <input type="password" name="confirmacao" autocomplete="new-password" minlength="8" required>
    </label>
    <button type="submit" class="btn btn-primario btn-bloco">Salvar nova senha</button>
  </form>
</section>
