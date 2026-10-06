<section class="painel-acesso centro" aria-labelledby="titulo-login">
    <p class="sobretitulo">Acesso restrito</p>
    <h2 id="titulo-login">Painel ADM</h2>
    <p>Somente pessoas autorizadas da equipe 645 Turismo.</p>
  <form method="post" action="/admin/entrar" class="form" novalidate>
    <?= csrf_campo() ?>
    <label class="campo">
      <span>E-mail</span>
      <input type="email" name="email" autocomplete="username" value="<?= e(antigo('email')) ?>" required autofocus>
    </label>
    <label class="campo">
      <span class="campo-linha">Senha <a href="/admin/esqueci-senha">Esqueci a senha</a></span>
      <span class="senha">
        <input type="password" name="senha" autocomplete="current-password" required>
        <button type="button" class="senha-alternar" data-alternar-senha aria-label="Mostrar senha"><?= icone('olho') ?></button>
      </span>
    </label>
    <button type="submit" class="btn btn-primario btn-bloco"><?= icone('cadeado') ?> Entrar no painel</button>
  </form>
  <div class="painel-acesso-rodape">
    <a href="/" class="link-sublinhado">Voltar para a Área do Guia</a>
  </div>
</section>
