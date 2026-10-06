<?php
$abas = ['duvidas' => 'Dúvidas', 'chamados' => 'Chamados', 'contatos' => 'Contatos'];
$abertos = count(array_filter($chamados, fn($c) => $c['status'] !== 'resolvido'));
/** Converte link do YouTube/Vimeo no endereço de incorporação (outros links abrem em nova aba). */
$embed = function (?string $url): ?string {
  if (!$url) return null;
  if (preg_match('#(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([\w-]{11})#', $url, $m)) return 'https://www.youtube-nocookie.com/embed/' . $m[1];
  if (preg_match('#vimeo\.com/(\d+)#', $url, $m)) return 'https://player.vimeo.com/video/' . $m[1];
  return null;
};
?>
<div class="pagina-topo">
  <div>
    <p class="sobretitulo">Estamos juntos</p>
    <h1>Ajuda</h1>
    <p>Respostas rápidas, passo a passo e conversa direta com a equipe da 645.</p>
  </div>
  <a href="/guia/ajuda?aba=chamados#novo" class="btn btn-primario">Abrir chamado</a>
</div>

<nav class="filtros" aria-label="Seções da ajuda">
  <?php foreach ($abas as $chave => $rotulo): ?>
    <a href="?aba=<?= e($chave) ?>" class="<?= $aba === $chave ? 'ativo' : '' ?>"><?= e($rotulo) ?><?= $chave === 'chamados' && $abertos ? ' <span>' . $abertos . '</span>' : '' ?></a>
  <?php endforeach; ?>
</nav>

<?php if ($aba === 'duvidas'): ?>
  <div class="alerta alerta-aviso"><?= icone('alerta') ?>
    <span><strong>Imprevisto e não vai poder ir?</strong> Avise o quanto antes:
      <a href="/guia/ajuda?aba=chamados&motivo=<?= (int) ($motivos[0]['id'] ?? 0) ?>#novo">abrir chamado urgente</a>.</span>
  </div>

  <section class="bloco" aria-labelledby="t-faq">
    <div class="bloco-titulo"><h2 id="t-faq">Perguntas frequentes</h2></div>
    <input type="search" class="busca-faq" placeholder="Buscar dúvida" aria-label="Buscar dúvida" data-busca-faq>
    <?php if (empty($conteudo['faq'])): ?>
      <p class="vazio">As perguntas frequentes ainda estão sendo preparadas.</p>
    <?php else: ?>
      <div class="faq" data-faq>
        <?php foreach ($conteudo['faq'] as $i => $f): ?>
          <details class="faq-item">
            <summary><span class="faq-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span><?= e($f['titulo']) ?></summary>
            <?php if ($f['categoria']): ?><p class="rotulo"><?= e($f['categoria']) ?></p><?php endif; ?>
            <div class="texto-longo"><?= nl2br(e((string) $f['conteudo'])) ?></div>
          </details>
        <?php endforeach; ?>
      </div>
      <p class="vazio" data-faq-vazio hidden>Nada encontrado. Que tal <a href="/guia/ajuda?aba=chamados#novo">perguntar para a equipe</a>?</p>
    <?php endif; ?>
  </section>

  <?php if (!empty($conteudo['guia_pratico'])): ?>
    <section class="bloco" aria-labelledby="t-pratico">
      <div class="bloco-titulo"><h2 id="t-pratico">Guia prático</h2></div>
      <ol class="passo-a-passo">
        <?php foreach ($conteudo['guia_pratico'] as $passo): ?>
          <li><strong><?= e($passo['titulo']) ?></strong><div class="texto-longo texto-2"><?= nl2br(e((string) $passo['conteudo'])) ?></div></li>
        <?php endforeach; ?>
      </ol>
    </section>
  <?php endif; ?>

  <?php if (!empty($conteudo['video'])): ?>
    <section class="bloco" aria-labelledby="t-videos">
      <div class="bloco-titulo"><h2 id="t-videos">Vídeos</h2></div>
      <div class="videos">
        <?php foreach ($conteudo['video'] as $vd): $src = $embed($vd['video_url']); ?>
          <div class="video">
            <?php if ($src): ?>
              <div class="video-quadro"><iframe src="<?= e($src) ?>" title="<?= e($vd['titulo']) ?>" loading="lazy" allowfullscreen allow="encrypted-media; picture-in-picture"></iframe></div>
            <?php endif; ?>
            <strong><?= e($vd['titulo']) ?></strong>
            <?php if (!$src && $vd['video_url']): ?><a href="<?= e($vd['video_url']) ?>" target="_blank" rel="noopener noreferrer">Assistir</a><?php endif; ?>
            <?php if ($vd['conteudo']): ?><p class="texto-2"><?= e($vd['conteudo']) ?></p><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

<?php elseif ($aba === 'chamados'): ?>
  <section class="bloco" aria-labelledby="t-chamados">
    <div class="bloco-titulo"><h2 id="t-chamados">Seus chamados</h2></div>
    <?php if (!$chamados): ?>
      <p class="vazio">Nenhum chamado ainda.</p>
    <?php else: ?>
      <ul class="lista-viagens">
        <?php foreach ($chamados as $c): ?>
          <li><a href="/guia/ajuda/chamados/<?= (int) $c['id'] ?>">
            <span class="lv-data"><strong><?= e(date('d', strtotime($c['criado_em']))) ?></strong><?= e(mes_curto($c['criado_em'])) ?></span>
            <span class="lv-corpo">
              <span class="rotulo"><?= e($c['motivo'] ?: 'Chamado') ?><?= $c['codigo'] ? ' · <span class="mono">' . e($c['codigo']) . '</span>' : '' ?><?= (int) $c['urgente'] ? ' · <span class="vermelho">urgente</span>' : '' ?></span>
              <strong><?= e($c['titulo']) ?></strong>
              <small>Atualizado em <?= e(formatar_data_hora($c['atualizado_em'])) ?></small>
            </span>
            <span class="lv-status"><?= selo(STATUS_CHAMADO, $c['status']) ?><?= icone('seta') ?></span>
          </a></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <section class="bloco" id="novo" aria-labelledby="t-novo">
    <div class="bloco-titulo"><h2 id="t-novo">Novo chamado</h2></div>
    <form method="post" action="/guia/ajuda/chamados" class="form painel" enctype="multipart/form-data" novalidate>
      <?= csrf_campo() ?>
      <div class="grade-campos">
        <label class="campo"><span>Assunto</span>
          <select name="motivo_id" data-motivo-chamado>
            <?php foreach ($motivos as $i => $m): ?>
              <option value="<?= (int) $m['id'] ?>" <?= (int) antigo('motivo_id', (string) ($motivoSelecionado ?: '')) === (int) $m['id'] ? 'selected' : '' ?>><?= e($m['nome']) ?></option>
            <?php endforeach; ?>
          </select></label>
        <label class="campo"><span>Viagem/tour (opcional)</span>
          <select name="viagem_id"><option value="">Nenhuma</option>
            <?php foreach ($viagens as $v): ?>
              <option value="<?= (int) $v['id'] ?>" <?= (int) antigo('viagem_id', (string) $viagemSelecionada) === (int) $v['id'] ? 'selected' : '' ?>><?= e($v['codigo'] . ' · ' . $v['nome']) ?></option>
            <?php endforeach; ?>
          </select></label>
        <label class="campo campo-largo"><span>Título</span><input type="text" name="titulo" maxlength="200" required value="<?= e(antigo('titulo')) ?>" placeholder="Resumo em poucas palavras"></label>
        <label class="campo campo-largo"><span>Mensagem</span><textarea name="mensagem" rows="5" maxlength="5000" required><?= e(antigo('mensagem')) ?></textarea></label>
        <label class="campo campo-largo"><span>Anexo (opcional)</span><input type="file" name="anexo" accept="application/pdf,image/jpeg,image/png,image/webp"></label>
      </div>
      <label class="opcao"><input type="checkbox" name="urgente" value="1" <?= $motivoSelecionado && $motivoSelecionado === (int) ($motivos[0]['id'] ?? -1) ? 'checked' : '' ?>>
        <span>É urgente (imprevisto para um trabalho próximo)</span></label>
      <div class="form-rodape"><button type="submit" class="btn btn-primario">Enviar chamado</button></div>
    </form>
  </section>

<?php else: ?>
  <section class="bloco" aria-labelledby="t-contatos">
    <div class="bloco-titulo"><h2 id="t-contatos">Contatos da equipe</h2></div>
    <?php if (empty($conteudo['contato'])): ?>
      <p class="vazio">Use os chamados para falar com a equipe.</p>
    <?php else: ?>
      <ul class="contatos">
        <?php foreach ($conteudo['contato'] as $ct): ?>
          <li><span class="rotulo"><?= e($ct['categoria'] ?: 'Contato') ?></span><strong><?= e($ct['titulo']) ?></strong>
            <?php if ($ct['conteudo']): ?><small class="texto-longo"><?= nl2br(e($ct['conteudo'])) ?></small><?php endif; ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
<?php endif; ?>
