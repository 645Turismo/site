<section class="painel-acesso centro" aria-labelledby="titulo-form">
    <h2 id="titulo-form"><?= e($titulo) ?></h2>
    <p>Enviaremos um link para criar uma nova senha no e-mail do cadastro.</p>
  <form method="post" action="<?= e($acao) ?>" class="form" novalidate>
    <?= csrf_campo() ?>
    <label class="campo">
      <span><?= e($rotulo) ?></span>
      <?php if ($campo === 'cpf'): ?>
        <input type="text" name="cpf" inputmode="numeric" placeholder="<?= e($placeholder) ?>" data-mascara="cpf" maxlength="14" required autofocus>
      <?php else: ?>
        <input type="email" name="email" placeholder="<?= e($placeholder) ?>" required autofocus>
      <?php endif; ?>
    </label>
    <button type="submit" class="btn btn-primario btn-bloco">Enviar link</button>
  </form>
  <div class="painel-acesso-rodape">
    <a href="<?= e($voltar) ?>" class="link-sublinhado">Voltar</a>
  </div>
</section>
