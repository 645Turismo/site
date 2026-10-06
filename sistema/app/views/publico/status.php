<?php
$mensagens = [
  'rascunho' => 'Seu cadastro foi iniciado mas não concluído. Continue no mesmo aparelho em "Fazer meu cadastro" ou comece de novo.',
  'em_analise' => 'Recebemos tudo e nossa equipe está conferindo seus dados e documentos.',
  'pendente' => 'Há algo para corrigir. Entre na Área do Guia e ajuste no seu Perfil.',
  'aprovado' => 'Cadastro aprovado! Entre na Área do Guia para receber convites.',
  'reprovado' => 'Seu cadastro não foi aprovado neste momento.',
  'inativo' => 'Seu cadastro está inativo. Fale com a equipe da 645 Turismo.',
  'bloqueado' => 'Seu cadastro está bloqueado. Fale com a equipe da 645 Turismo.',
];
?>
<section class="painel-acesso centro" aria-labelledby="titulo-status">
  <p class="sobretitulo">Cadastro de guia</p>
  <h2 id="titulo-status">Acompanhar cadastro</h2>
  <?php if ($resultado === false): ?>
    <div class="alerta alerta-erro"><?= icone('alerta') ?><span>Não encontramos cadastro com esses dados. Confira o CPF e a data de nascimento.</span></div>
  <?php elseif ($resultado): [$rotulo, $cor] = STATUS_CADASTRO[$resultado['status']] ?? [$resultado['status'], 'neutro']; ?>
    <div class="status-resultado">
      <p class="rotulo">Olá, <?= e(primeiro_nome($resultado)) ?> · código <span class="mono amarelo"><?= e($resultado['codigo']) ?></span></p>
      <p><?= selo(STATUS_CADASTRO, $resultado['status']) ?></p>
      <p><?= e($mensagens[$resultado['status']] ?? '') ?></p>
      <?php if (in_array($resultado['status'], ['pendente', 'reprovado'], true) && $resultado['status_motivo']): ?>
        <p class="texto-2">Observação da equipe: <?= e($resultado['status_motivo']) ?></p>
      <?php endif; ?>
      <a href="/" class="btn btn-primario btn-bloco espaco-topo">Entrar na Área do Guia</a>
    </div>
  <?php else: ?>
    <p>Informe seu CPF e sua data de nascimento.</p>
  <?php endif; ?>
  <?php if (!$resultado): ?>
    <form method="post" action="/status" class="form" novalidate>
      <?= csrf_campo() ?>
      <label class="campo"><span>CPF</span><input type="text" name="cpf" inputmode="numeric" data-mascara="cpf" maxlength="14" required></label>
      <label class="campo"><span>Data de nascimento</span><input type="date" name="nascimento" required></label>
      <button type="submit" class="btn btn-primario btn-bloco">Consultar</button>
    </form>
  <?php endif; ?>
  <div class="painel-acesso-rodape">
    <a href="/cadastro" class="link-sublinhado">Fazer meu cadastro</a>
    <a href="/" class="link-sublinhado">Voltar</a>
  </div>
</section>
