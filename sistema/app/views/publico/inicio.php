<div class="pub-grade">
  <section class="pub-hero">
    <p class="sobretitulo">Área do Prestador · 645 Turismo</p>
    <h1>Do convite ao pagamento, seu trabalho organizado</h1>
    <p class="pub-lead">Para guias, recepcionistas, comissários(as) de bordo e demais prestadores. Receba convites para viagens, tours e serviços, consulte o briefing, envie o relatório depois do trabalho e acompanhe seus recebimentos.</p>
    <ul class="pub-recursos">
      <li><span class="num">01</span><strong>Convite e briefing</strong><span>Ponto de encontro, horário de apresentação, roteiro e contatos de cada viagem, tour ou serviço.</span></li>
      <li><span class="num">02</span><strong>Relatório do trabalho</strong><span>Conte como foi: público atendido, ocorrências e observações.</span></li>
      <li><span class="num">03</span><strong>Recebimentos claros</strong><span>Diárias ou valor fechado, nota fiscal, previsão de pagamento e comprovantes.</span></li>
      <li><span class="num">04</span><strong>Sua disponibilidade</strong><span>Diga quando pode trabalhar e receba convites compatíveis.</span></li>
    </ul>
  </section>

  <section class="painel-acesso" aria-labelledby="titulo-login">
    <p class="sobretitulo">Já trabalho com a 645</p>
    <h2 id="titulo-login">Entrar</h2>
    <p>Use o CPF e a senha do seu cadastro.</p>
    <form method="post" action="/entrar" class="form" novalidate>
      <?= csrf_campo() ?>
      <label class="campo">
        <span>CPF</span>
        <input type="text" name="cpf" inputmode="numeric" autocomplete="username" placeholder="000.000.000-00"
               data-mascara="cpf" maxlength="14" value="<?= e(antigo('cpf')) ?>" required autofocus>
      </label>
      <label class="campo">
        <span class="campo-linha">Senha <a href="/esqueci-senha">Esqueci a senha</a></span>
        <span class="senha">
          <input type="password" name="senha" autocomplete="current-password" required>
          <button type="button" class="senha-alternar" data-alternar-senha aria-label="Mostrar senha"><?= icone('olho') ?></button>
        </span>
      </label>
      <button type="submit" class="btn btn-primario btn-bloco">Entrar</button>
    </form>
    <div class="painel-acesso-rodape">
      <span>Ainda não trabalha com a gente?</span>
      <a href="/cadastro" class="link-sublinhado">Fazer meu cadastro de prestador</a>
      <a href="/cadastro/continuar" class="link-sublinhado">Continuar um cadastro que comecei</a>
      <a href="/status" class="link-sublinhado">Acompanhar meu cadastro</a>
    </div>
  </section>
</div>
