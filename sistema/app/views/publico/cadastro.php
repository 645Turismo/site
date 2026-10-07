<?php
$comCpf = empty($g['id']);
$preCadastro = ($g['status'] ?? '') === 'pre_cadastro';
$textos = [
  1 => 'Comece pelos seus dados. Leva uns 10 minutos e dá para voltar a qualquer etapa.',
  2 => 'Conte o que você faz: funções, idiomas, regiões e Cadastur. É assim que os convites chegam até você.',
  3 => 'Envie os documentos. Pelo celular dá para fotografar na hora.',
  4 => 'Dados para a nota fiscal e para receber os pagamentos.',
  5 => 'Crie sua senha de acesso. Você vai entrar com o CPF e essa senha.',
];
if ($preCadastro) {
  $textos[1] = 'A equipe da 645 já começou seu cadastro. Confira os dados e complete o que falta: são poucas etapas rápidas.';
  $textos[5] = 'Último passo: aceite os termos para enviar o cadastro para a equipe conferir.';
}

?>
<div class="cadastro">
<?php $etapas = etapas_cadastro(); $posicao = array_search($etapa, array_keys($etapas), true) + 1; ?>
  <p class="sobretitulo">Cadastro de guia · etapa <?= $posicao ?> de <?= count($etapas) ?></p>
  <h1 class="cadastro-titulo"><?= e($etapas[$etapa]) ?></h1>
  <p class="pub-lead"><?= e($textos[$etapa]) ?></p>

  <ol class="etapas" aria-label="Etapas do cadastro">
    <?php $i = 0; foreach ($etapas as $n => $rotulo): $i++; ?>
      <li class="<?= $n === $etapa ? 'atual' : ($n < $liberada ? 'feita' : '') ?>">
        <?php if ($n <= $liberada && $n !== $etapa): ?><a href="/cadastro/<?= $n ?>"><span><?= $n < $liberada ? '✓' : $i ?></span><em><?= e($rotulo) ?></em></a>
        <?php else: ?><span><span><?= $i ?></span><em><?= e($rotulo) ?></em></span><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>

  <form method="post" action="/cadastro/<?= $etapa ?>" class="form form-secoes" enctype="multipart/form-data" novalidate>
    <?= csrf_campo() ?>
    <?php
    if ($etapa === 1) {
      require RAIZ . '/app/views/guia_form/pessoais.php';
    } elseif ($etapa === 2) {
      require RAIZ . '/app/views/guia_form/atuacao.php';
    } elseif ($etapa === 3) {
      require RAIZ . '/app/views/guia_form/documentos.php';
    } elseif ($etapa === 4) {
      require RAIZ . '/app/views/guia_form/recebimento.php';
    } else { ?>
      <fieldset>
        <legend><span>01</span> <?= $preCadastro ? 'Termos' : 'Senha de acesso' ?></legend>
        <p class="texto-2">Seu código de guia <?= $preCadastro ? 'é' : 'será' ?> <strong class="amarelo mono"><?= e($g['codigo'] ?? '') ?></strong>.</p>
        <?php if (!$preCadastro): ?>
        <div class="grade-campos">
          <label class="campo"><span>Senha *</span>
            <span class="senha"><input type="password" name="senha" autocomplete="new-password" minlength="8" required>
            <button type="button" class="senha-alternar" data-alternar-senha aria-label="Mostrar senha"><?= icone('olho') ?></button></span></label>
          <label class="campo"><span>Confirmar senha *</span><input type="password" name="confirmacao" autocomplete="new-password" minlength="8" required></label>
        </div>
        <p class="texto-2">Pelo menos 8 caracteres, com letras e números.</p>
        <?php endif; ?>
        <label class="opcao espaco-topo"><input type="checkbox" name="termos" value="1">
          <span>Li e aceito os <a href="/termos" target="_blank">termos de uso</a> e a <a href="/privacidade" target="_blank">política de privacidade</a>, e autorizo a 645 Turismo a usar meus dados para credenciamento, escala e pagamento.</span></label>
      </fieldset>
    <?php } ?>
    <div class="form-rodape cadastro-rodape">
      <?php if ($etapa > 1): ?><a href="/cadastro/<?= etapa_anterior($etapa) ?>" class="btn btn-texto">Voltar</a><?php endif; ?>
      <button type="submit" class="btn btn-primario"><?= $etapa < 5 ? 'Salvar e continuar' : 'Concluir cadastro' ?></button>
    </div>
  </form>
</div>
