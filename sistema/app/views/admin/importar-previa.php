<?php
$novos = array_values(array_filter($imp['passageiros'], fn($p) => !$p['repetido']));
$repetidos = array_values(array_filter($imp['passageiros'], fn($p) => $p['repetido']));
$comAviso = array_filter($novos, fn($p) => $p['avisos']);
$limite = 200;
$campos = CAMPOS_PASSAGEIRO;
?>
<div class="pagina-topo">
  <div>
    <p class="sobretitulo mono"><?= e($v['codigo']) ?> · Importação</p>
    <h1>Conferir antes de importar</h1>
    <p>Origem: <?= e($imp['origem']) ?>. Nada foi gravado ainda.</p>
  </div>
  <a href="/admin/viagens/<?= (int) $v['id'] ?>/passageiros" class="link-sublinhado">Cancelar</a>
</div>

<div class="lp-contadores importar-resumo">
  <div><span class="rotulo">Vão entrar</span><strong><b><?= count($novos) ?></b></strong></div>
  <div><span class="rotulo">Já estão na lista</span><strong class="amarelo"><b><?= count($repetidos) ?></b></strong></div>
  <div><span class="rotulo">Linhas ignoradas</span><strong class="<?= $imp['ignoradas'] ? 'vermelho' : '' ?>"><b><?= count($imp['ignoradas']) ?></b></strong></div>
</div>

<p class="texto-2 espaco-topo">
  <?php if ($imp['colunas']): ?>
    Colunas reconhecidas: <b><?= e(implode(', ', array_map(fn($c) => $campos[$c][0], $imp['colunas']))) ?></b>.
    <?php if (!empty($imp['poltrona_sem_titulo'])): ?>
      <br><strong>Poltrona:</strong> a coluna <?= e($imp['poltrona_sem_titulo']) ?> não tem título, mas tem os números de poltrona de cada passageiro, por isso foi usada como Poltrona.
    <?php endif; ?>
    <?php $faltam = array_diff(array_keys($campos), $imp['colunas']); if ($faltam): ?>
      Sem coluna para: <?= e(implode(', ', array_map(fn($c) => $campos[$c][0], $faltam))) ?> (ficam em branco).
    <?php endif; ?>
  <?php else: ?>
    Sem cabeçalho reconhecido: as colunas foram lidas na ordem padrão da planilha da 645.
  <?php endif; ?>
</p>

<?php if ($imp['ignoradas']): ?>
  <div class="alerta alerta-erro"><?= icone('alerta') ?>
    <span><strong>Linhas ignoradas:</strong>
      <?= e(implode(' · ', array_map(fn($i) => 'linha ' . $i[0] . ' (' . $i[1] . ')', array_slice($imp['ignoradas'], 0, 20)))) ?><?= count($imp['ignoradas']) > 20 ? '…' : '' ?></span>
  </div>
<?php endif; ?>
<?php if ($comAviso): ?>
  <div class="alerta alerta-aviso"><?= icone('alerta') ?>
    <span><strong>Ajustes antes de importar</strong> (o passageiro entra mesmo assim):
      <?php foreach (array_slice($comAviso, 0, 30) as $p): ?>
        <br>Linha <?= (int) $p['linha'] ?> · <?= e($p['dados']['nome']) ?>: <?= e(implode('; ', $p['avisos'])) ?>
      <?php endforeach; ?><?= count($comAviso) > 30 ? '<br>…' : '' ?></span>
  </div>
<?php endif; ?>

<?php if ($novos): ?>
  <form method="post" action="/admin/viagens/<?= (int) $v['id'] ?>/passageiros/importar/confirmar" class="importar-confirmar">
    <?= csrf_campo() ?>
    <button type="submit" class="btn btn-primario">Importar <?= count($novos) ?> passageiro(s)</button>
    <a href="/admin/viagens/<?= (int) $v['id'] ?>/passageiros#importar" class="btn btn-texto">Enviar outro arquivo</a>
  </form>

  <section class="bloco" aria-labelledby="t-novos">
    <div class="bloco-titulo"><h2 id="t-novos">Vão entrar (<?= count($novos) ?>)</h2></div>
    <div class="tabela-rolagem">
      <table class="tabela tabela-previa">
        <thead><tr><th>Linha</th><?php foreach ($campos as [$rotulo]): ?><th><?= e($rotulo) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
          <?php foreach (array_slice($novos, 0, $limite) as $p): ?>
            <tr>
              <td class="texto-2"><?= (int) $p['linha'] ?></td>
              <?php foreach (array_keys($campos) as $c): ?>
                <td><?= e(passageiro_exibir($c, $p['dados'][$c] ?? null)) ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (count($novos) > $limite): ?><p class="texto-2">… e mais <?= count($novos) - $limite ?> passageiro(s).</p><?php endif; ?>
  </section>
<?php else: ?>
  <p class="vazio">Todos os passageiros da planilha já estão na lista. <a href="/admin/viagens/<?= (int) $v['id'] ?>/passageiros#importar">Enviar outro arquivo</a></p>
<?php endif; ?>

<?php if ($repetidos): ?>
  <details class="bloco adicionar">
    <summary>Já estão na lista e serão pulados (<?= count($repetidos) ?>)</summary>
    <ul class="sem-poltrona">
      <?php foreach (array_slice($repetidos, 0, 100) as $p): ?>
        <li>Linha <?= (int) $p['linha'] ?>: <?= e($p['dados']['nome']) ?><?= $p['dados']['documento'] ? ' · ' . e($p['dados']['documento']) : '' ?></li>
      <?php endforeach; ?>
    </ul>
  </details>
<?php endif; ?>
