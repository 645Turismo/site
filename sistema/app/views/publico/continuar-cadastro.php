<section class="painel-acesso centro" aria-labelledby="titulo-form">
  <p class="sobretitulo">Cadastro de guia</p>
  <h2 id="titulo-form">Continuar meu cadastro</h2>
  <p>Começou o cadastro e não terminou? Informe seu CPF: enviamos para o seu e-mail um link que abre o cadastro de onde você parou, em qualquer aparelho.</p>
  <form method="post" action="/cadastro/continuar" class="form" novalidate>
    <?= csrf_campo() ?>
    <label class="campo"><span>CPF</span>
      <input type="text" name="cpf" inputmode="numeric" data-mascara="cpf" maxlength="14" required placeholder="000.000.000-00" autofocus></label>
    <button type="submit" class="btn btn-primario btn-bloco">Enviar link para continuar</button>
  </form>
  <p class="texto-2 espaco-topo">Já terminou o cadastro? <a href="/">Entre com seu CPF e senha</a>. Ainda não começou? <a href="/cadastro">Faça seu cadastro</a>.</p>
</section>
