<?php foreach (flashes() as $f): ?>
  <div class="alerta alerta-<?= e($f['tipo']) ?>" role="<?= $f['tipo'] === 'erro' ? 'alert' : 'status' ?>">
    <?= icone($f['tipo'] === 'sucesso' ? 'check' : 'alerta') ?>
    <span><?= e($f['mensagem']) ?></span>
  </div>
<?php endforeach; ?>
