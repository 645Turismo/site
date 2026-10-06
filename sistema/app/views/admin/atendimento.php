<?php
$abas = ['abertos' => 'Aguardando resposta', 'andamento' => 'Em andamento', 'resolvidos' => 'Resolvidos', 'recados' => 'Recados para os guias'];
?>
<div class="pagina-topo">
  <div>
    <p class="sobretitulo">Comunicação</p>
    <h1>Atendimento</h1>
    <p>Chamados dos guias (urgentes primeiro) e recados exibidos na tela inicial deles.</p>
  </div>
</div>

<nav class="filtros" aria-label="Seção">
  <?php foreach ($abas as $chave => $rotulo): ?>
    <a href="?aba=<?= e($chave) ?>" class="<?= $aba === $chave ? 'ativo' : '' ?>"><?= e($rotulo) ?><?= isset($contagem[$chave]) ? ' <span>' . (int) $contagem[$chave] . '</span>' : '' ?></a>
  <?php endforeach; ?>
</nav>

<?php if ($aba !== 'recados'): ?>
  <?php if (!$chamados): ?>
    <p class="vazio">Nenhum chamado aqui.</p>
  <?php else: ?>
    <ul class="lista-viagens">
      <?php foreach ($chamados as $c): ?>
        <li><a href="/admin/atendimento/<?= (int) $c['id'] ?>" class="<?= (int) $c['urgente'] && $c['status'] === 'aberto' ? 'urgente' : '' ?>">
          <span class="lv-data"><strong><?= e(date('d', strtotime($c['atualizado_em']))) ?></strong><?= e(mes_curto($c['atualizado_em'])) ?></span>
          <span class="lv-corpo">
            <span class="rotulo"><?= (int) $c['urgente'] ? '<span class="vermelho">Urgente</span> · ' : '' ?><?= e($c['motivo'] ?: 'Chamado') ?><?= $c['codigo'] ? ' · <span class="mono">' . e($c['codigo']) . '</span>' : '' ?></span>
            <strong><?= e($c['titulo']) ?></strong>
            <small><?= e($c['guia']) ?> · <span class="mono"><?= e($c['guia_codigo']) ?></span></small>
          </span>
          <span class="lv-status"><?= selo(STATUS_CHAMADO_ADM, $c['status']) ?><?= icone('seta') ?></span>
        </a></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

<?php else: ?>
  <div class="colunas">
    <section class="bloco" aria-labelledby="t-recados">
      <div class="bloco-titulo"><h2 id="t-recados">Recados</h2></div>
      <?php if (!$avisos): ?>
        <p class="vazio">Nenhum recado publicado.</p>
      <?php else: ?>
        <ul class="feed feed-simples">
          <?php foreach ($avisos as $av):
            $publico = match ($av['publico']) { 'funcao' => 'Função: ' . ($av['funcao'] ?? '—'), 'viagem' => 'Viagem: ' . ($av['codigo'] ?? '—'), default => 'Todos os guias' };
            $periodo = $av['inicio'] || $av['fim'] ? ' · ' . trim(($av['inicio'] ? 'de ' . formatar_data($av['inicio']) : '') . ' ' . ($av['fim'] ? 'até ' . formatar_data($av['fim']) : '')) : ''; ?>
            <li class="<?= (int) $av['ativo'] ? '' : 'inativo' ?>">
              <span class="feed-data"><?= e(date('d/m', strtotime($av['criado_em']))) ?></span>
              <div>
                <strong><?= e($av['titulo']) ?></strong>
                <p><?= nl2br(e($av['mensagem'])) ?></p>
                <p class="rotulo"><?= e($publico . $periodo) ?><?= (int) $av['ativo'] ? '' : ' · escondido' ?></p>
                <div class="acoes-linha">
                  <form method="post" action="/admin/avisos/<?= (int) $av['id'] ?>/status" class="inline-form"><?= csrf_campo() ?>
                    <button type="submit" class="btn btn-texto btn-p"><?= (int) $av['ativo'] ? 'Esconder' : 'Publicar de novo' ?></button></form>
                  <form method="post" action="/admin/avisos/<?= (int) $av['id'] ?>/remover" class="inline-form" data-confirmar="Apagar este recado?"><?= csrf_campo() ?>
                    <button type="submit" class="btn btn-texto btn-p">Apagar</button></form>
                </div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <section class="bloco" aria-labelledby="t-novo-recado">
      <div class="bloco-titulo"><h2 id="t-novo-recado">Novo recado</h2></div>
      <form method="post" action="/admin/avisos" class="form painel" novalidate>
        <?= csrf_campo() ?>
        <label class="campo"><span>Título</span><input type="text" name="titulo" maxlength="200" required value="<?= e(antigo('titulo')) ?>"></label>
        <label class="campo"><span>Mensagem</span><textarea name="mensagem" rows="4" maxlength="3000" required><?= e(antigo('mensagem')) ?></textarea></label>
        <label class="campo"><span>Para quem</span>
          <select name="publico" data-publico-aviso>
            <option value="todos">Todos os guias</option>
            <option value="funcao">Guias de uma função</option>
            <option value="viagem">Guias de uma viagem/tour</option>
          </select></label>
        <label class="campo" data-publico="funcao" hidden><span>Função</span>
          <select name="funcao_id"><?php foreach ($funcoes as $f): ?><option value="<?= (int) $f['id'] ?>"><?= e($f['nome']) ?></option><?php endforeach; ?></select></label>
        <label class="campo" data-publico="viagem" hidden><span>Viagem/tour</span>
          <select name="viagem_id"><?php foreach ($viagensAtivas as $v): ?><option value="<?= (int) $v['id'] ?>"><?= e($v['codigo'] . ' · ' . $v['nome']) ?></option><?php endforeach; ?></select></label>
        <div class="grade-campos">
          <label class="campo"><span>Mostrar a partir de</span><input type="date" name="inicio"></label>
          <label class="campo"><span>Até</span><input type="date" name="fim"></label>
        </div>
        <button type="submit" class="btn btn-primario btn-bloco">Publicar recado</button>
      </form>
    </section>
  </div>
<?php endif; ?>
