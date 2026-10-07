<?php
$comCpf = false;
$acoes = ['dados' => 'pessoais', 'atuacao' => 'atuacao', 'documentos' => 'documentos', 'recebimento' => 'recebimento', 'senha' => 'senha'];
?>
<div class="pagina-topo">
  <div>
    <p class="sobretitulo">Código <?= e($g['codigo']) ?> · CPF <?= e(mascarar_cpf($g['cpf'])) ?></p>
    <h1>Perfil</h1>
    <p>Mantenha seus dados em dia: é com eles que a equipe monta as escalas e faz os pagamentos.</p>
  </div>
  <?= selo(STATUS_CADASTRO, $g['status']) ?>
</div>

<?php if ($g['status'] === 'pendente'): ?>
  <div class="alerta alerta-erro"><?= icone('alerta') ?>
    <span><strong>Seu cadastro tem uma pendência:</strong> <?= e($g['status_motivo'] ?: 'confira seus dados e documentos.') ?>
      <form method="post" action="/guia/perfil/reenviar" class="inline-form"><?= csrf_campo() ?>
        <button type="submit" class="btn btn-primario btn-p">Já corrigi, enviar para análise</button></form></span>
  </div>
<?php endif; ?>
<?php if ($faltam): ?>
  <div class="alerta alerta-aviso"><?= icone('alerta') ?><span>Faltam documentos: <?= e(implode(', ', $faltam)) ?>.</span></div>
<?php endif; ?>

<nav class="filtros" aria-label="Seções do perfil">
  <?php foreach (abas_perfil() as $chave => $rotulo): ?>
    <a href="?aba=<?= e($chave) ?>" class="<?= $aba === $chave ? 'ativo' : '' ?>" <?= $aba === $chave ? 'aria-current="page"' : '' ?>><?= e($rotulo) ?></a>
  <?php endforeach; ?>
</nav>

<form method="post" action="/guia/perfil/<?= e($acoes[$aba]) ?>" class="form form-secoes" enctype="multipart/form-data" novalidate>
  <?= csrf_campo() ?>
  <?php if ($aba === 'dados'): ?>
    <?php require RAIZ . '/app/views/guia_form/pessoais.php'; ?>
  <?php elseif ($aba === 'atuacao'): ?>
    <?php require RAIZ . '/app/views/guia_form/atuacao.php'; ?>
  <?php elseif ($aba === 'documentos'): ?>
    <?php require RAIZ . '/app/views/guia_form/documentos.php'; ?>
  <?php elseif ($aba === 'recebimento'): ?>
    <?php require RAIZ . '/app/views/guia_form/recebimento.php'; ?>
  <?php else: ?>
    <fieldset>
      <legend><span>01</span> Trocar senha</legend>
      <div class="grade-campos">
        <label class="campo campo-largo"><span>Senha atual</span><input type="password" name="senha_atual" autocomplete="current-password" required></label>
        <label class="campo"><span>Nova senha</span>
          <span class="senha"><input type="password" name="senha" autocomplete="new-password" minlength="8" required>
          <button type="button" class="senha-alternar" data-alternar-senha aria-label="Mostrar senha"><?= icone('olho') ?></button></span></label>
        <label class="campo"><span>Confirmar nova senha</span><input type="password" name="confirmacao" autocomplete="new-password" minlength="8" required></label>
      </div>
    </fieldset>
  <?php endif; ?>
  <div class="form-rodape">
    <button type="submit" class="btn btn-primario"><?= $aba === 'documentos' ? 'Enviar documentos' : 'Salvar' ?></button>
  </div>
</form>
