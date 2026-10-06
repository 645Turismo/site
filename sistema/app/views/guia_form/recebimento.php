<?php
// CNPJ e dados para pagamento. Variáveis: $g, $x.
$b = $x['bancarios'] ?? [];
$val = fn(string $c) => antigo($c, (string) ($g[$c] ?? ''));
$vb = fn(string $c) => antigo($c, (string) ($b[$c] ?? ''));
?>
<?php $naoEmite = antigo('nao_emite_nf', isset($g['emite_nf']) && !(int) $g['emite_nf'] ? '1' : '') === '1'; ?>
<fieldset>
  <legend><span>01</span> Empresa para a nota fiscal</legend>
  <p class="texto-2">A 645 paga as diárias mediante nota fiscal. Use o CNPJ do seu MEI ou empresa.</p>
  <label class="opcao opcao-destaque"><input type="checkbox" name="nao_emite_nf" value="1" data-alterna="#dados-nf" <?= $naoEmite ? 'checked' : '' ?>>
    <span><strong>Não emito nota fiscal</strong> (não tenho MEI ou empresa). Pule esta parte: você recebe pelo PIX sem enviar nota.</span></label>
  <div class="grade-campos" id="dados-nf"<?= $naoEmite ? ' hidden' : '' ?>>
    <label class="campo"><span>CNPJ *</span><input type="text" name="cnpj" inputmode="numeric" maxlength="18" data-mascara="cnpj" value="<?= e(formatar_cnpj($val('cnpj'))) ?>"></label>
    <label class="campo"><span>Razão social *</span><input type="text" name="razao_social" maxlength="200" value="<?= e($val('razao_social')) ?>"></label>
    <label class="campo campo-largo"><span>Nome fantasia</span><input type="text" name="nome_fantasia" maxlength="200" value="<?= e($val('nome_fantasia')) ?>"></label>
  </div>
</fieldset>

<fieldset>
  <legend><span>02</span> Pagamento</legend>
  <div class="grade-campos">
    <label class="campo"><span>Tipo de chave PIX *</span>
      <select name="pix_tipo" required><option value="">Selecione</option>
        <?php foreach (TIPOS_PIX as $k => $r): ?><option value="<?= $k ?>" <?= ($vb('pix_tipo') ?: ($naoEmite ? 'cpf' : 'cnpj')) === $k ? 'selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?>
      </select></label>
    <label class="campo"><span>Chave PIX *</span><input type="text" name="pix_chave" maxlength="140" required value="<?= e($vb('pix_chave')) ?>"></label>
    <label class="campo campo-largo"><span>Banco</span>
      <input type="text" name="banco" maxlength="120" list="bancos-sugeridos" value="<?= e($vb('banco_nome') ?: antigo('banco')) ?>" placeholder="Ex.: 260 - Nubank"></label>
    <label class="campo"><span>Tipo de conta</span>
      <select name="tipo_conta"><option value="">Selecione</option>
        <?php foreach (['corrente' => 'Corrente', 'poupanca' => 'Poupança', 'pagamento' => 'Conta de pagamento'] as $k => $r): ?>
          <option value="<?= $k ?>" <?= $vb('tipo_conta') === $k ? 'selected' : '' ?>><?= $r ?></option>
        <?php endforeach; ?>
      </select></label>
    <label class="campo"><span>Agência</span><input type="text" name="agencia" maxlength="10" inputmode="numeric" value="<?= e($vb('agencia')) ?>"></label>
    <label class="campo"><span>Conta com dígito</span><input type="text" name="conta" maxlength="20" value="<?= e($vb('conta')) ?>"></label>
  </div>
  <datalist id="bancos-sugeridos"><?php foreach (BANCOS_SUGERIDOS as $s): ?><option value="<?= e($s) ?>"><?php endforeach; ?></datalist>
</fieldset>
