<?php $f = $f ?? []; ?>
<label class="campo"><span>Nome *</span>
  <input type="text" name="nome" maxlength="120" required value="<?= e(antigo('nome', (string) ($f['nome'] ?? ''))) ?>" placeholder="Ex.: Guia de Turismo Regional"></label>
<label class="campo"><span>Descrição</span>
  <input type="text" name="descricao" maxlength="255" value="<?= e(antigo('descricao', (string) ($f['descricao'] ?? ''))) ?>" placeholder="O que essa pessoa faz na viagem"></label>
<label class="campo campo-curto"><span>Ordem na lista</span>
  <input type="number" name="ordem" min="0" max="999" value="<?= e(antigo('ordem', (string) ($f['ordem'] ?? '0'))) ?>"></label>
<label class="opcao"><input type="checkbox" name="exige_cadastur" value="1" <?= (int) ($f['exige_cadastur'] ?? 0) ? 'checked' : '' ?>> <span>Exige Cadastur de guia</span></label>
<label class="opcao"><input type="checkbox" name="exige_idioma" value="1" <?= (int) ($f['exige_idioma'] ?? 0) ? 'checked' : '' ?>> <span>Exige idioma estrangeiro</span></label>
