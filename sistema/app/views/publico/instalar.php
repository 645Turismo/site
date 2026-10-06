<section class="painel-acesso centro" aria-labelledby="titulo-form">
    <p class="sobretitulo">Configuração do servidor</p>
    <h2 id="titulo-form">Instalação e atualização do banco</h2>
    <p>Cria ou atualiza as tabelas do sistema<?= $temAdmin ? '' : ' e o primeiro administrador' ?>.</p>

  <?php if ($aplicadas !== null): ?>
    <div class="form">
      <p><strong>Pronto.</strong> <?= $aplicadas ? 'Atualizações aplicadas: ' . e(implode(', ', $aplicadas)) . '.' : 'O banco já estava atualizado.' ?></p>
      <p class="texto-2">Guarde o código de configuração: ele só serve para atualizar o banco depois de uma nova versão do sistema.</p>
      <a href="/admin" class="btn btn-primario btn-bloco">Ir para o Painel ADM</a>
    </div>
  <?php else: ?>
    <form method="post" action="/instalar" class="form" novalidate autocomplete="off">
      <?= csrf_campo() ?>
      <label class="campo">
        <span>Código de instalação</span>
        <input type="password" name="token" required autofocus>
      </label>
      <?php if (!$temAdmin): ?>
        <p class="texto-2">Primeiro administrador</p>
        <label class="campo"><span>Nome</span><input type="text" name="nome" required></label>
        <label class="campo"><span>E-mail</span><input type="email" name="email" required></label>
        <label class="campo"><span>Senha</span><input type="password" name="senha" autocomplete="new-password" minlength="8" required></label>
        <label class="campo"><span>Confirmar senha</span><input type="password" name="confirmacao" autocomplete="new-password" minlength="8" required></label>
      <?php endif; ?>
      <button type="submit" class="btn btn-primario btn-bloco">Executar</button>
    </form>
  <?php endif; ?>
</section>
