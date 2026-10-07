<?php
// Dados pessoais e endereço. Variáveis: $g (guia ou []), $comCpf (cadastro público).
$val = fn(string $c) => antigo($c, (string) ($g[$c] ?? ''));
?>
<fieldset>
  <legend><span>01</span> Dados pessoais</legend>
  <div class="grade-campos">
    <label class="campo campo-largo"><span>Nome completo *</span>
      <input type="text" name="nome" maxlength="160" autocomplete="name" required value="<?= e($val('nome')) ?>"></label>
    <label class="campo"><span>Como prefere ser chamado(a)</span>
      <input type="text" name="nome_social" maxlength="120" value="<?= e($val('nome_social')) ?>" placeholder="Nome social ou apelido"></label>
    <?php if ($comCpf): ?>
      <label class="campo"><span>CPF *</span>
        <input type="text" name="cpf" inputmode="numeric" data-mascara="cpf" maxlength="14" required value="<?= e(formatar_cpf($val('cpf'))) ?>"></label>
    <?php endif; ?>
    <label class="campo"><span>Data de nascimento *</span>
      <input type="date" name="nascimento" required value="<?= e($val('nascimento')) ?>"></label>
    <label class="campo"><span>Celular (WhatsApp) *</span>
      <input type="tel" name="celular" inputmode="tel" autocomplete="tel" maxlength="20" required value="<?= e(formatar_celular($val('celular'))) ?>"></label>
    <label class="campo"><span>E-mail *</span>
      <input type="email" name="email" autocomplete="email" maxlength="190" required value="<?= e($val('email')) ?>"></label>
    <label class="campo"><span>Gênero</span>
      <select name="genero"><option value="">Selecione</option>
        <?php foreach (GENEROS as $op): ?><option <?= $val('genero') === $op ? 'selected' : '' ?>><?= e($op) ?></option><?php endforeach; ?>
      </select></label>
    <label class="campo"><span>Nacionalidade</span>
      <input type="text" name="nacionalidade" maxlength="30" value="<?= e($val('nacionalidade') ?: 'Brasileira') ?>"></label>
    <label class="campo"><span>Tamanho de camiseta</span>
      <select name="camiseta"><option value="">Selecione</option>
        <?php foreach (CAMISETAS as $op): ?><option <?= $val('camiseta') === $op ? 'selected' : '' ?>><?= e($op) ?></option><?php endforeach; ?>
      </select></label>
    <div class="campo">
      <span>Pessoa com deficiência?</span>
      <div class="opcoes-linha">
        <label class="opcao"><input type="radio" name="pcd" value="0" <?= $val('pcd') !== '1' ? 'checked' : '' ?>> <span>Não</span></label>
        <label class="opcao"><input type="radio" name="pcd" value="1" <?= $val('pcd') === '1' ? 'checked' : '' ?>> <span>Sim</span></label>
      </div>
    </div>
    <label class="campo campo-largo"><span>Se sim, conte o que precisamos saber</span>
      <input type="text" name="pcd_descricao" maxlength="255" value="<?= e($val('pcd_descricao')) ?>"></label>
    <label class="campo"><span>Restrição alimentar</span>
      <select name="restricao_alimentar"><option value="">Selecione</option>
        <?php foreach (RESTRICOES_ALIMENTARES as $chave => $rotulo): ?><option value="<?= e($chave) ?>" <?= $val('restricao_alimentar') === $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option><?php endforeach; ?>
      </select></label>
    <label class="campo campo-largo"><span>Doença preexistente ou cuidado de saúde</span>
      <input type="text" name="doencas_preexistentes" maxlength="500" value="<?= e($val('doencas_preexistentes')) ?>" placeholder="Ex.: diabetes, pressão alta, alergia a frutos do mar. Deixe em branco se não tiver.">
      <small class="texto-2">Só a equipe da 645 vê. Ajuda a cuidar de você durante as viagens.</small></label>
    <div class="campo campo-largo">
      <span>Foto de rosto <?= empty($g['foto_path']) ? '*' : '' ?></span>
      <div class="foto-envio">
        <?php if (!empty($g['foto_path']) && !empty($g['id'])): ?>
          <img src="<?= e(arquivo_url('foto', $g)) ?>" alt="Sua foto atual" class="foto-atual" width="72" height="72">
        <?php endif; ?>
        <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" capture="user">
      </div>
      <small>Rosto visível, de frente e sem óculos escuros. Pelo celular dá para tirar na hora.</small>
    </div>
  </div>
</fieldset>

<fieldset>
  <legend><span>02</span> Endereço</legend>
  <div class="grade-campos">
    <label class="campo"><span>CEP</span>
      <input type="text" name="cep" inputmode="numeric" maxlength="9" data-cep value="<?= e($val('cep')) ?>" placeholder="00000-000"></label>
    <label class="campo"><span>Rua</span><input type="text" name="logradouro" maxlength="200" data-cep-logradouro value="<?= e($val('logradouro')) ?>"></label>
    <label class="campo"><span>Número</span><input type="text" name="numero" maxlength="20" value="<?= e($val('numero')) ?>"></label>
    <label class="campo"><span>Complemento</span><input type="text" name="complemento" maxlength="120" value="<?= e($val('complemento')) ?>"></label>
    <label class="campo"><span>Bairro</span><input type="text" name="bairro" maxlength="120" data-cep-bairro value="<?= e($val('bairro')) ?>"></label>
    <label class="campo"><span>Cidade *</span><input type="text" name="cidade" maxlength="120" required data-cep-cidade value="<?= e($val('cidade')) ?>"></label>
    <label class="campo"><span>Estado *</span>
      <select name="uf" required data-cep-uf><option value="">UF</option>
        <?php foreach (UFS as $uf): ?><option <?= $val('uf') === $uf ? 'selected' : '' ?>><?= $uf ?></option><?php endforeach; ?>
      </select></label>
  </div>
</fieldset>
