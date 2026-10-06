<section class="painel-acesso centro" aria-labelledby="titulo-form">
  <p class="sobretitulo">Primeiro acesso</p>
  <h2 id="titulo-form">Olá, <?= e($nome) ?>. Crie sua senha</h2>
  <p>Você entrou com a senha temporária enviada por e-mail. Crie agora a sua senha: pelo menos 8 caracteres, com letras e números.<?= $completar ? ' Em seguida, complete seu cadastro.' : '' ?></p>
  <form method="post" action="/primeiro-acesso" class="form" novalidate>
    <?= csrf_campo() ?>
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
    <button type="submit" class="btn btn-primario btn-bloco">Salvar senha e continuar</button>
  </form>
  <form method="post" action="/guia/sair" class="espaco-topo"><?= csrf_campo() ?><button type="submit" class="btn btn-texto">Sair</button></form>
</section>
