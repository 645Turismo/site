<?php
// Conversa de um chamado (guia e ADM). Variáveis: $c, $mensagens, $admin, $base, $voltar.
$statusVisao = $admin ? STATUS_CHAMADO_ADM : STATUS_CHAMADO;
?>
<div class="pagina-topo">
  <div>
    <p class="sobretitulo"><?= e($c['motivo'] ?: 'Chamado') ?><?= $c['codigo'] ? ' · ' . e($c['codigo']) : '' ?><?= (int) $c['urgente'] ? ' · urgente' : '' ?></p>
    <h1><?= e($c['titulo']) ?></h1>
    <p><?= $admin ? e($c['guia'] . ' · código ' . $c['guia_codigo']) . ' · ' : '' ?>Aberto em <?= e(formatar_data_hora($c['criado_em'])) ?></p>
  </div>
  <div class="acoes-topo">
    <?= selo($statusVisao, $c['status']) ?>
    <a href="<?= e($voltar) ?>" class="link-sublinhado">Voltar</a>
  </div>
</div>

<?php if ($admin && $c['celular']): ?>
  <p class="texto-2">Contato do guia: <a href="<?= e(link_whatsapp($c['celular'])) ?>" target="_blank" rel="noopener noreferrer">WhatsApp <?= e(formatar_celular($c['celular'])) ?></a>
    <?= $c['viagem'] ? ' · Viagem/tour: <a href="/admin/viagens/' . (int) $c['viagem_id'] . '">' . e($c['codigo'] . ' · ' . $c['viagem']) . '</a>' : '' ?></p>
<?php endif; ?>

<ol class="conversa">
  <?php foreach ($mensagens as $m): $daEquipe = $m['autor_tipo'] === 'admin'; ?>
    <li class="msg <?= $daEquipe ? 'msg-equipe' : 'msg-guia' ?>">
      <span class="msg-autor"><?= e($daEquipe ? ($admin ? $m['autor'] : 'Equipe 645') : ($admin ? $m['autor'] : 'Você')) ?> · <?= e(formatar_data_hora($m['criado_em'])) ?></span>
      <div class="msg-texto"><?= nl2br(e($m['mensagem'])) ?></div>
      <?php if ($m['anexo_path']): ?><a href="<?= e(arquivo_url('anexo', $m)) ?>" target="_blank" rel="noopener" class="msg-anexo">📎 Ver anexo</a><?php endif; ?>
    </li>
  <?php endforeach; ?>
</ol>

<?php if (!$admin && in_array($c['status'], ['respondido', 'aguardando_confirmacao'], true)): ?>
  <div class="alerta alerta-sucesso"><?= icone('check') ?>
    <span>A equipe respondeu. Se resolveu, encerre o chamado.
      <form method="post" action="<?= e($base) ?>/resolvido" class="inline-form"><?= csrf_campo() ?>
        <button type="submit" class="btn btn-primario btn-p">Resolveu, encerrar</button></form></span>
  </div>
<?php endif; ?>

<form method="post" action="<?= e($base) ?>/responder" class="form painel espaco-topo" enctype="multipart/form-data" novalidate>
  <?= csrf_campo() ?>
  <label class="campo"><span><?= $c['status'] === 'resolvido' ? 'Reabrir com uma nova mensagem' : 'Responder' ?></span>
    <textarea name="mensagem" rows="4" maxlength="5000" required></textarea></label>
  <div class="grade-campos">
    <label class="campo"><span>Anexo (opcional)</span><input type="file" name="anexo" accept="application/pdf,image/jpeg,image/png,image/webp"></label>
    <?php if ($admin): ?>
      <label class="campo"><span>Depois de enviar</span>
        <select name="status">
          <option value="respondido">Aguardar o guia</option>
          <option value="aguardando_confirmacao">Pedir confirmação de que resolveu</option>
          <option value="resolvido">Encerrar como resolvido</option>
        </select></label>
    <?php endif; ?>
  </div>
  <div class="form-rodape"><button type="submit" class="btn btn-primario">Enviar</button></div>
</form>
