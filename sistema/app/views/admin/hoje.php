<div class="pagina-topo">
  <div>
    <p class="sobretitulo"><?= e(data_extenso(hoje())) ?></p>
    <h1>Operação de hoje</h1>
  </div>
  <a href="/admin/viagens/nova" class="btn btn-primario">Nova viagem/tour</a>
</div>

<section class="bloco" aria-labelledby="t-campo">
  <div class="bloco-titulo"><h2 id="t-campo">Em campo hoje</h2></div>
  <?php if (!$emCampo): ?>
    <p class="vazio">Nenhuma viagem/tour com trabalho hoje.</p>
  <?php else: ?>
    <ul class="feed">
      <?php foreach ($emCampo as $c): ?>
        <li>
          <span class="feed-data"><?= e($c['horario_apresentacao'] ?: $c['hora_inicio'] ?: '—') ?></span>
          <div>
            <strong><a href="/admin/viagens/<?= (int) $c['id'] ?>" class="link-limpo"><span class="mono codigo"><?= e($c['codigo']) ?></span> <?= e($c['nome']) ?></a></strong>
            <p><?= e($c['ponto_encontro'] ?: 'Ponto de encontro não informado') ?></p>
          </div>
          <span class="selo <?= (int) $c['confirmados'] >= (int) $c['vagas'] ? 'selo-sucesso' : 'selo-aviso' ?>">
            <?= (int) $c['confirmados'] ?>/<?= (int) $c['vagas'] ?> guias<?= (int) $c['pendentes'] ? ' · ' . (int) $c['pendentes'] . ' sem resposta' : '' ?>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<div class="colunas">
  <section class="bloco" aria-labelledby="t-fila">
    <div class="bloco-titulo"><h2 id="t-fila">Fila de trabalho</h2></div>
    <ul class="pendencias">
      <?php foreach ($fila as [$n, $tituloItem, $descricao, $href]): ?>
        <li>
          <a href="<?= e($href) ?>">
            <span class="num <?= $n ? '' : 'zero' ?>"><?= str_pad((string) $n, 2, '0', STR_PAD_LEFT) ?></span>
            <span><strong><?= e($tituloItem) ?></strong><small><?= e($descricao) ?></small></span>
            <?= icone('seta') ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <div>
    <section class="bloco" aria-labelledby="t-pagar">
      <div class="bloco-titulo"><h2 id="t-pagar">A pagar</h2></div>
      <div class="painel">
        <p class="numero-grande"><?= e(formatar_moeda($aPagar)) ?></p>
        <p class="texto-2">Diárias com nota fiscal conferida aguardando pagamento.</p>
      </div>
    </section>

    <section class="bloco" aria-labelledby="t-proximas">
      <div class="bloco-titulo"><h2 id="t-proximas">Próximas viagens/tours</h2><a href="/admin/viagens" class="link-p">Ver todas</a></div>
      <?php if (!$proximas): ?>
        <p class="vazio">Nada programado. <a href="/admin/viagens/nova">Cadastrar viagem/tour</a></p>
      <?php else: ?>
        <ul class="feed">
          <?php foreach ($proximas as $v): ?>
            <li>
              <span class="feed-data"><?= e(date('d/m', strtotime($v['data_inicio']))) ?></span>
              <div>
                <strong><a href="/admin/viagens/<?= (int) $v['id'] ?>" class="link-limpo"><span class="mono codigo"><?= e($v['codigo']) ?></span> <?= e($v['nome']) ?></a></strong>
                <p><?= e(TIPOS_VIAGEM[$v['tipo']] ?? '') ?> · <?= (int) $v['dias'] ?> dia(s)</p>
              </div>
              <?= selo(STATUS_VIAGEM, $v['status']) ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>
</div>
