<?php
// Envio de documentos. Variáveis: $x (guia_extras), $exigeCadastur (bool).
$statusDoc = ['enviado' => ['Em conferência', 'info'], 'aprovado' => ['Aprovado', 'sucesso'], 'reprovado' => ['Reenviar', 'erro']];
?>
<fieldset>
  <legend><span>01</span> Documentos</legend>
  <p class="texto-2">PDF ou foto (JPG, PNG, WebP), até 20 MB cada. Um novo envio substitui o anterior e é conferido pela equipe.</p>
  <div class="docs-lista">
    <?php foreach (DOCUMENTOS_GUIA as $tipo => [$rotulo, $obrigatorio]):
      $obrigatorio = $obrigatorio || ($tipo === 'cadastur' && !empty($exigeCadastur));
      $atuais = $x['documentos'][$tipo] ?? []; ?>
      <div class="doc-item">
        <div class="doc-cabeca">
          <strong><?= e($rotulo) ?><?= $obrigatorio ? ' *' : '' ?></strong>
          <?php foreach ($atuais as $d): [$r, $c] = $statusDoc[$d['status']] ?? [$d['status'], 'neutro']; ?>
            <span class="doc-atual">
              <span class="selo selo-<?= e($c) ?>"><?= e($r) ?></span>
              <a href="/arquivos/documento/<?= (int) $d['id'] ?>" target="_blank" rel="noopener">Ver arquivo</a>
            </span>
            <?php if ($d['status'] === 'reprovado' && $d['motivo']): ?><small class="vermelho">Motivo: <?= e($d['motivo']) ?></small><?php endif; ?>
          <?php endforeach; ?>
        </div>
        <input type="file" name="doc_<?= e($tipo) ?>" accept="application/pdf,image/jpeg,image/png,image/webp" aria-label="<?= e($rotulo) ?>">
      </div>
    <?php endforeach; ?>
  </div>
</fieldset>
