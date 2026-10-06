<?php
// Funções, idiomas, especialidades, regiões, Cadastur. Variáveis: $g, $x (guia_extras), $cat (guia_catalogos).
$val = fn(string $c) => antigo($c, (string) ($g[$c] ?? ''));
$funcoesMarcadas = array_map('intval', antigo_lista('funcoes', $x['funcoes']));
$regioesMarcadas = array_map('intval', antigo_lista('regioes', $x['regioes']));
$especialidades = antigo_lista('especialidades', array_filter(array_map('trim', explode(',', (string) ($g['especialidades'] ?? '')))));
$categorias = antigo_lista('cadastur_categorias', array_filter(array_map('trim', explode(',', (string) ($g['cadastur_categorias'] ?? '')))));
$idiomas = [];
if (isset($_SESSION['antigo']['idioma'])) {
  foreach ((array) $_SESSION['antigo']['idioma'] as $i => $idioma) {
    $idiomas[] = ['idioma' => $idioma, 'nivel' => $_SESSION['antigo']['idioma_nivel'][$i] ?? 'fluente'];
  }
} else {
  $idiomas = $x['idiomas'];
}
$idiomas = array_values(array_filter($idiomas, fn($i) => trim((string) $i['idioma']) !== ''));
while (count($idiomas) < 3) {
  $idiomas[] = ['idioma' => '', 'nivel' => 'fluente'];
}
?>
<fieldset>
  <legend><span>01</span> Funções *</legend>
  <div class="opcoes-cartao">
    <?php foreach ($cat['funcoes'] as $f): ?>
      <label class="opcao-cartao">
        <input type="checkbox" name="funcoes[]" value="<?= (int) $f['id'] ?>" <?= in_array((int) $f['id'], $funcoesMarcadas, true) ? 'checked' : '' ?>>
        <span><strong><?= e($f['nome']) ?></strong>
          <?php if ($f['descricao']): ?><small><?= e($f['descricao']) ?></small><?php endif; ?>
          <?php if ((int) $f['exige_cadastur'] || (int) $f['exige_idioma']): ?>
            <small class="amarelo">Exige <?= e(implode(' e ', array_filter([(int) $f['exige_cadastur'] ? 'Cadastur' : null, (int) $f['exige_idioma'] ? 'idioma estrangeiro' : null]))) ?></small>
          <?php endif; ?></span>
      </label>
    <?php endforeach; ?>
  </div>
</fieldset>

<fieldset>
  <legend><span>02</span> Cadastur</legend>
  <div class="grade-campos">
    <label class="campo"><span>Número do Cadastur</span><input type="text" name="cadastur_numero" maxlength="40" value="<?= e($val('cadastur_numero')) ?>"></label>
    <label class="campo"><span>Estado de registro</span>
      <select name="cadastur_uf"><option value="">UF</option>
        <?php foreach (UFS as $uf): ?><option <?= $val('cadastur_uf') === $uf ? 'selected' : '' ?>><?= $uf ?></option><?php endforeach; ?>
      </select></label>
    <label class="campo"><span>Validade</span><input type="date" name="cadastur_validade" value="<?= e($val('cadastur_validade')) ?>"></label>
    <div class="campo campo-largo"><span>Categorias</span>
      <div class="opcoes-linha">
        <?php foreach (CATEGORIAS_CADASTUR as $c): ?>
          <label class="opcao"><input type="checkbox" name="cadastur_categorias[]" value="<?= e($c) ?>" <?= in_array($c, $categorias, true) ? 'checked' : '' ?>> <span><?= e($c) ?></span></label>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</fieldset>

<fieldset>
  <legend><span>03</span> Idiomas</legend>
  <p class="texto-2">Além do português. Deixe em branco se não conduz grupos em outro idioma.</p>
  <div class="idiomas-linhas">
    <?php foreach ($idiomas as $i): ?>
      <div class="idioma-linha">
        <label class="campo"><span>Idioma</span><input type="text" name="idioma[]" maxlength="40" list="idiomas-sugeridos" value="<?= e($i['idioma']) ?>"></label>
        <label class="campo"><span>Nível</span>
          <select name="idioma_nivel[]">
            <?php foreach (NIVEIS_IDIOMA as $k => $r): ?><option value="<?= $k ?>" <?= $i['nivel'] === $k ? 'selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?>
          </select></label>
      </div>
    <?php endforeach; ?>
  </div>
  <datalist id="idiomas-sugeridos"><?php foreach (IDIOMAS_SUGERIDOS as $s): ?><option value="<?= e($s) ?>"><?php endforeach; ?></datalist>
</fieldset>

<fieldset>
  <legend><span>04</span> Onde e como trabalha</legend>
  <div class="campo"><span>Regiões em que aceita trabalhar *</span>
    <div class="opcoes-linha">
      <?php foreach ($cat['regioes'] as $r): ?>
        <label class="opcao"><input type="checkbox" name="regioes[]" value="<?= (int) $r['id'] ?>" <?= in_array((int) $r['id'], $regioesMarcadas, true) ? 'checked' : '' ?>> <span><?= e($r['nome']) ?></span></label>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="campo espaco-topo"><span>Especialidades</span>
    <div class="opcoes-linha">
      <?php foreach (ESPECIALIDADES as $esp): ?>
        <label class="opcao"><input type="checkbox" name="especialidades[]" value="<?= e($esp) ?>" <?= in_array($esp, $especialidades, true) ? 'checked' : '' ?>> <span><?= e($esp) ?></span></label>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="campo espaco-topo"><span>Aceita viagens com pernoite?</span>
    <div class="opcoes-linha">
      <label class="opcao"><input type="radio" name="aceita_pernoite" value="1" <?= $val('aceita_pernoite') === '1' ? 'checked' : '' ?>> <span>Sim</span></label>
      <label class="opcao"><input type="radio" name="aceita_pernoite" value="0" <?= $val('aceita_pernoite') !== '1' ? 'checked' : '' ?>> <span>Não</span></label>
    </div>
  </div>
  <label class="campo espaco-topo"><span>Apresentação</span>
    <textarea name="apresentacao" rows="4" maxlength="1500" placeholder="Sua experiência como guia: roteiros, grupos, tempo de atuação"><?= e($val('apresentacao')) ?></textarea></label>
</fieldset>
