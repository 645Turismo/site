<div class="pagina-topo">
  <div>
    <p class="sobretitulo">Servidor</p>
    <h1>Diagnóstico</h1>
    <p>Situação do banco, das pastas e os últimos erros registrados. Use quando algo não estiver funcionando.</p>
  </div>
  <a href="/admin/equipe" class="link-sublinhado">Voltar</a>
</div>

<section class="bloco">
  <div class="bloco-titulo"><h2>Resumo</h2></div>
  <dl class="ficha">
    <dt>PHP</dt><dd><?= e($php) ?> · banco <?= e($banco) ?></dd>
    <dt>Atualizações do banco</dt>
    <dd><?= $pendentes ? '<span class="vermelho">Pendentes: ' . e(implode(', ', $pendentes)) . '</span>' : 'Em dia (' . e((string) end($aplicadas)) . ')' ?></dd>
    <dt>Pastas</dt>
    <dd><?php foreach ($pastas as [$p, $ok]): ?><?= e($p) ?>: <?= $ok ? 'grava' : '<span class="vermelho">sem permissão de escrita</span>' ?><br><?php endforeach; ?></dd>
    <dt>E-mail</dt><dd><?= e($smtp) ?></dd>
  </dl>
</section>

<section class="bloco" id="email">
  <div class="bloco-titulo"><h2>Testar envio de e-mail</h2></div>
  <form method="post" action="/admin/diagnostico/email" class="form filtros-form">
    <?= csrf_campo() ?>
    <label class="campo"><span>Enviar para</span><input type="email" name="para" value="<?= e($teste['para'] ?? $emailAdmin) ?>" required></label>
    <button type="submit" class="btn btn-primario btn-p">Enviar e-mail de teste</button>
  </form>
  <?php if ($teste): ?>
    <div class="alerta alerta-<?= $teste['ok'] ? 'sucesso' : 'erro' ?> espaco-topo"><?= icone($teste['ok'] ? 'check' : 'alerta') ?>
      <span><?= $teste['ok'] ? 'O servidor de e-mail aceitou a mensagem para ' . e($teste['para']) . '. Se não chegar em alguns minutos, confira o spam: o problema passa a ser de entrega, não do sistema.' : 'O envio falhou. O motivo está na última linha abaixo.' ?></span></div>
    <pre class="log-erros"><?= e(implode("
", $teste['conversa'])) ?></pre>
  <?php endif; ?>
</section>

<section class="bloco">
  <div class="bloco-titulo"><h2>Últimos erros</h2></div>
  <?php if (!$linhas): ?>
    <p class="vazio">Nenhum erro registrado.</p>
  <?php else: ?>
    <pre class="log-erros"><?= e(implode("\n", $linhas)) ?></pre>
  <?php endif; ?>
</section>
