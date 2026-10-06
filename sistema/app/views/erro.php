<section class="painel-acesso centro">
    <p class="sobretitulo">Erro <?= (int) $codigo ?></p>
    <h2><?= e($titulo) ?></h2>
    <?php if ($mensagem): ?><p><?= e($mensagem) ?></p><?php endif; ?>
  <div class="painel-acesso-rodape">
    <a href="/" class="btn btn-contorno btn-bloco">Voltar ao início</a>
  </div>
</section>
