<?php
$abas = [
  'convites' => ['Convites', 'Nenhum convite aguardando resposta.'],
  'proximas' => ['Confirmadas', 'Nenhuma viagem/tour confirmado pela frente.'],
  'realizadas' => ['Realizadas', 'Seu histórico aparece aqui depois do primeiro trabalho.'],
];
?>
<div class="pagina-topo">
  <div>
    <p class="sobretitulo">Seus trabalhos</p>
    <h1>Viagens/Tours</h1>
    <p>Responda convites, consulte o briefing e envie o relatório depois do trabalho.</p>
  </div>
</div>

<nav class="filtros" aria-label="Filtrar">
  <?php foreach ($abas as $chave => [$rotulo]): ?>
    <a href="?aba=<?= e($chave) ?>" class="<?= $aba === $chave ? 'ativo' : '' ?>" <?= $aba === $chave ? 'aria-current="page"' : '' ?>>
      <?= e($rotulo) ?> <span><?= count($grupos[$chave]) ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<?php if (!$grupos[$aba]): ?>
  <p class="vazio"><?= e($abas[$aba][1]) ?></p>
<?php else: ?>
  <ul class="lista-viagens">
    <?php foreach ($grupos[$aba] as $v): ?>
      <li>
        <a href="/guia/viagens/<?= (int) $v['id'] ?>">
          <span class="lv-data">
            <strong><?= e(date('d', strtotime($v['primeira']))) ?></strong>
            <?= e(mes_curto($v['primeira'])) ?>
          </span>
          <span class="lv-corpo">
            <span class="rotulo"><span class="mono"><?= e($v['codigo']) ?></span><?= $v['funcao'] ? ' · ' . e($v['funcao']) : '' ?></span>
            <strong><?= e($v['nome']) ?></strong>
            <small>
              <?= $v['primeira'] === $v['ultima'] ? e(formatar_data($v['primeira'])) : e(formatar_data($v['primeira']) . ' a ' . formatar_data($v['ultima'])) ?>
              · <?= (int) $v['diarias'] ?> diária(s)
            </small>
          </span>
          <span class="lv-status">
            <?php if ($aba === 'convites'): ?>
              <?= selo(STATUS_ESCALA, 'convidado') ?>
            <?php elseif ($aba === 'proximas'): ?>
              <?= selo(STATUS_ESCALA, 'confirmado') ?>
            <?php else: ?>
              <span class="selo selo-neutro">Realizada</span>
            <?php endif; ?>
            <?= icone('seta') ?>
          </span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
