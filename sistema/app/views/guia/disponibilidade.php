<?php
$meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
$t = strtotime($inicio);
$anterior = date('Y-m', strtotime('-1 month', $t));
$seguinte = date('Y-m', strtotime('+1 month', $t));
$primeiroDiaSemana = (int) date('w', $t);
$totalDias = (int) date('t', $t);
$selecionada = data_valida($dataSelecionada) ? $dataSelecionada : (substr(hoje(), 0, 7) === $mes ? hoje() : $inicio);
?>
<div class="pagina-topo">
  <div>
    <p class="sobretitulo">Sua agenda</p>
    <h1>Disponibilidade</h1>
    <p>Marque quando pode (e quando não pode) trabalhar. A equipe vê isso na hora de escalar.</p>
  </div>
</div>

<div class="agenda">
  <section class="calendario" aria-label="Calendário">
    <div class="calendario-topo">
      <a href="?mes=<?= e($anterior) ?>" class="btn btn-contorno btn-p" aria-label="Mês anterior">‹</a>
      <h2><?= e($meses[(int) date('n', $t) - 1] . ' ' . date('Y', $t)) ?></h2>
      <a href="?mes=<?= e($seguinte) ?>" class="btn btn-contorno btn-p" aria-label="Próximo mês">›</a>
    </div>
    <div class="calendario-grade">
      <?php foreach (['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'] as $d): ?><span class="cal-semana"><?= $d ?></span><?php endforeach; ?>
      <?php for ($i = 0; $i < $primeiroDiaSemana; $i++): ?><span></span><?php endfor; ?>
      <?php for ($dia = 1; $dia <= $totalDias; $dia++):
        $data = $mes . '-' . str_pad((string) $dia, 2, '0', STR_PAD_LEFT);
        $ms = $marcas[$data] ?? [];
        $ts = $trabalhos[$data] ?? [];
        $classes = ['cal-dia'];
        if ($data < hoje()) $classes[] = 'passado';
        if ($data === hoje()) $classes[] = 'hoje';
        if ($data === $selecionada) $classes[] = 'selecionado';
        foreach ($ms as $m) $classes[] = 'm-' . $m['tipo'];
        foreach ($ts as $tr) $classes[] = $tr['status'] === 'convidado' ? 't-convite' : 't-trabalho'; ?>
        <button type="button" class="<?= e(implode(' ', array_unique($classes))) ?>" data-dia="<?= e($data) ?>" <?= $data < hoje() ? 'disabled' : '' ?>
                aria-label="<?= e(data_extenso($data)) ?>">
          <span class="cal-num"><?= $dia ?></span>
          <span class="cal-marcas" aria-hidden="true"></span>
        </button>
      <?php endfor; ?>
    </div>
    <ul class="cal-legenda">
      <li><span class="leg-cal disp"></span>Disponível</li>
      <li><span class="leg-cal indisp"></span>Indisponível</li>
      <li><span class="leg-cal trab"></span>Trabalho</li>
      <li><span class="leg-cal conv"></span>Convite</li>
    </ul>
  </section>

  <section class="painel agenda-form" id="marcar" aria-labelledby="t-marcar">
    <h2 id="t-marcar" class="subtitulo" style="margin-top:0">Marcar</h2>
    <form method="post" action="/guia/disponibilidade" class="form" novalidate>
      <?= csrf_campo() ?>
      <div class="grade-campos">
        <label class="campo"><span>Dia</span><input type="date" name="data" min="<?= e(hoje()) ?>" value="<?= e($selecionada >= hoje() ? $selecionada : hoje()) ?>" required data-agenda-data></label>
        <label class="campo"><span>Até (opcional)</span><input type="date" name="ate" min="<?= e(hoje()) ?>" data-agenda-ate></label>
      </div>
      <div class="campo"><span>Situação</span>
        <div class="opcoes-botao">
          <label><input type="radio" name="tipo" value="disponivel" checked><span class="disp">Disponível</span></label>
          <label><input type="radio" name="tipo" value="indisponivel"><span class="indisp">Indisponível</span></label>
        </div>
      </div>
      <div class="campo"><span>Período</span>
        <div class="opcoes-botao">
          <?php foreach (PERIODOS as $k => $r): ?><label><input type="radio" name="periodo" value="<?= $k ?>" <?= $k === 'dia' ? 'checked' : '' ?>><span><?= e($r) ?></span></label><?php endforeach; ?>
        </div>
      </div>
      <details class="adicionar" data-so-intervalo>
        <summary>Só em alguns dias da semana</summary>
        <div class="opcoes-linha espaco-topo">
          <?php foreach (['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'] as $i => $d): ?>
            <label class="opcao"><input type="checkbox" name="dias_semana[]" value="<?= $i ?>"> <span><?= $d ?></span></label>
          <?php endforeach; ?>
        </div>
      </details>
      <label class="campo"><span>Observação</span><input type="text" name="observacao" maxlength="255" placeholder="Ex.: só a partir das 14h"></label>
      <button type="submit" class="btn btn-primario btn-bloco">Salvar marcação</button>
    </form>
  </section>
</div>

<section class="bloco espaco-topo" aria-labelledby="t-lista">
  <div class="bloco-titulo"><h2 id="t-lista">Neste mês</h2></div>
  <?php if (!$marcas && !$trabalhos): ?>
    <p class="vazio">Nenhuma marcação neste mês. Toque num dia do calendário para começar.</p>
  <?php else: ?>
    <ul class="feed">
      <?php
      $dias = array_unique(array_merge(array_keys($marcas), array_keys($trabalhos)));
      sort($dias);
      foreach ($dias as $data): ?>
        <li>
          <span class="feed-data"><?= e(dia_semana_curto($data) . ' ' . date('d/m', strtotime($data))) ?></span>
          <div>
            <?php foreach ($trabalhos[$data] ?? [] as $tr): ?>
              <strong><a class="link-limpo" href="/guia/viagens/<?= (int) $tr['viagem_id'] ?>"><span class="mono codigo"><?= e($tr['codigo']) ?></span> <?= e($tr['nome']) ?></a></strong>
              <p><?= selo(STATUS_ESCALA, $tr['status']) ?></p>
            <?php endforeach; ?>
            <?php foreach ($marcas[$data] ?? [] as $m): ?>
              <p class="marcacao">
                <span class="selo <?= $m['tipo'] === 'disponivel' ? 'selo-sucesso' : 'selo-erro' ?>"><?= $m['tipo'] === 'disponivel' ? 'Disponível' : 'Indisponível' ?></span>
                <?= e(PERIODOS[$m['periodo']] ?? '') ?><?= $m['observacao'] ? ' · ' . e($m['observacao']) : '' ?>
                <?php if ($data >= hoje()): ?>
                  <form method="post" action="/guia/disponibilidade/<?= (int) $m['id'] ?>/remover" class="inline-form"><?= csrf_campo() ?>
                    <button type="submit" class="btn btn-texto btn-p" aria-label="Remover marcação">remover</button></form>
                <?php endif; ?>
              </p>
            <?php endforeach; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
