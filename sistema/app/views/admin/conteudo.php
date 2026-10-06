<?php
$item = $editar ?? null;
$v = fn(string $c, string $padrao = '') => antigo($c, (string) ($item[$c] ?? $padrao));
$rotuloTitulo = ['faq' => 'Pergunta', 'guia_pratico' => 'Passo', 'video' => 'Título do vídeo', 'contato' => 'Nome / setor'][$secao];
$rotuloConteudo = ['faq' => 'Resposta', 'guia_pratico' => 'Explicação', 'video' => 'Descrição (opcional)', 'contato' => 'Telefone, e-mail e horário'][$secao];
?>
<div class="pagina-topo">
  <div>
    <p class="sobretitulo">Ajuda dos guias</p>
    <h1>Conteúdo</h1>
    <p>O que os guias encontram na Ajuda. Mudanças aparecem na hora.</p>
  </div>
</div>

<nav class="filtros" aria-label="Seção">
  <?php foreach (SECOES_CONTEUDO as $chave => $rotulo): ?>
    <a href="?secao=<?= e($chave) ?>" class="<?= $secao === $chave ? 'ativo' : '' ?>"><?= e($rotulo) ?> <span><?= (int) ($contagem[$chave] ?? 0) ?></span></a>
  <?php endforeach; ?>
</nav>

<div class="colunas">
  <section class="bloco" aria-labelledby="t-itens">
    <div class="bloco-titulo"><h2 id="t-itens"><?= e(SECOES_CONTEUDO[$secao]) ?></h2></div>
    <?php if (!$itens): ?>
      <p class="vazio">Nada publicado nesta seção.</p>
    <?php else: ?>
      <ul class="feed feed-simples">
        <?php foreach ($itens as $o): ?>
          <li class="<?= (int) $o['ativo'] ? '' : 'inativo' ?>">
            <span class="feed-data"><?= str_pad((string) $o['ordem'], 2, '0', STR_PAD_LEFT) ?></span>
            <div>
              <strong><?= e($o['titulo']) ?></strong>
              <?php if ($o['categoria']): ?><p class="rotulo"><?= e($o['categoria']) ?></p><?php endif; ?>
              <?php if ($o['conteudo']): ?><p><?= e(mb_strimwidth($o['conteudo'], 0, 180, '…')) ?></p><?php endif; ?>
              <div class="acoes-linha">
                <a href="?secao=<?= e($secao) ?>&editar=<?= (int) $o['id'] ?>#form-conteudo" class="btn btn-texto btn-p">Editar</a>
                <form method="post" action="/admin/conteudo/<?= (int) $o['id'] ?>/status" class="inline-form"><?= csrf_campo() ?>
                  <button type="submit" class="btn btn-texto btn-p"><?= (int) $o['ativo'] ? 'Esconder' : 'Publicar' ?></button></form>
                <form method="post" action="/admin/conteudo/<?= (int) $o['id'] ?>/remover" class="inline-form" data-confirmar="Apagar este item?"><?= csrf_campo() ?>
                  <button type="submit" class="btn btn-texto btn-p">Apagar</button></form>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <section class="bloco" id="form-conteudo" aria-labelledby="t-form">
    <div class="bloco-titulo"><h2 id="t-form"><?= $item ? 'Editar' : 'Adicionar' ?></h2><?php if ($item): ?><a href="?secao=<?= e($secao) ?>" class="link-p">Cancelar</a><?php endif; ?></div>
    <form method="post" action="<?= $item ? '/admin/conteudo/' . (int) $item['id'] : '/admin/conteudo' ?>" class="form painel" novalidate>
      <?= csrf_campo() ?>
      <input type="hidden" name="secao" value="<?= e($secao) ?>">
      <label class="campo"><span><?= e($rotuloTitulo) ?></span><input type="text" name="titulo" maxlength="255" required value="<?= e($v('titulo')) ?>"></label>
      <?php if ($secao === 'video'): ?>
        <label class="campo"><span>Link do vídeo (YouTube ou Vimeo)</span><input type="url" name="video_url" maxlength="255" required value="<?= e($v('video_url')) ?>" placeholder="https://www.youtube.com/watch?v=..."></label>
      <?php endif; ?>
      <label class="campo"><span><?= e($rotuloConteudo) ?></span><textarea name="conteudo" rows="6" maxlength="10000"><?= e($v('conteudo')) ?></textarea></label>
      <div class="grade-campos">
        <label class="campo"><span><?= $secao === 'contato' ? 'Função' : 'Categoria' ?></span><input type="text" name="categoria" maxlength="80" value="<?= e($v('categoria')) ?>" placeholder="<?= $secao === 'contato' ? 'Ex.: Coordenação' : 'Ex.: Pagamentos' ?>"></label>
        <label class="campo"><span>Ordem</span><input type="number" name="ordem" min="0" max="999" value="<?= e($v('ordem', '0')) ?>"></label>
      </div>
      <button type="submit" class="btn btn-primario btn-bloco"><?= $item ? 'Salvar' : 'Publicar' ?></button>
    </form>
  </section>
</div>
