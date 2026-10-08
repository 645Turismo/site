<?php
$etapas = [
  'agendada' => ['Agendada', 'neutro', 'Valor previsto. Depois da viagem, envie o relatório para liberar o pagamento.'],
  'realizada' => ['Aguardando relatório', 'aviso', 'Faça o relatório da viagem no Pós-viagem para liberar o pagamento.'],
  'aguardando_nf' => ['Envie a nota fiscal', 'aviso', ''],
  'nf_em_conferencia' => ['NF em conferência', 'info', 'A equipe está conferindo sua nota fiscal.'],
  'a_pagar' => ['Pagamento programado', 'info', ''],
  'paga' => ['Pago', 'sucesso', ''],
  'falta' => ['Falta (não remunerada)', 'erro', ''],
];
?>
<div class="pagina-topo">
  <div>
    <p class="sobretitulo">Seus valores</p>
    <h1>Recebimentos</h1>
    <p>Do convite aceito até o pagamento, viagem por viagem.</p>
  </div>
  <nav class="filtros" aria-label="Filtrar por ano">
    <?php foreach ($anos as $a): ?><a href="?ano=<?= e($a) ?>" class="<?= (string) $a === (string) $ano ? 'ativo' : '' ?>"<?= (string) $a === (string) $ano ? ' aria-current="page"' : '' ?>><?= e($a) ?></a><?php endforeach; ?>
  </nav>
</div>

<div class="lp-contadores resumo-financeiro">
  <div><span class="rotulo">Recebido em <?= e($ano) ?></span><strong><b><?= e(formatar_moeda($resumo['recebido'])) ?></b></strong></div>
  <div><span class="rotulo">A receber</span><strong class="amarelo"><b><?= e(formatar_moeda($resumo['a_receber'])) ?></b></strong></div>
  <div><span class="rotulo">Previsto em <?= e($ano) ?></span><strong><b><?= e(formatar_moeda($resumo['previsto'])) ?></b></strong></div>
  <div><span class="rotulo">Diárias em <?= e($ano) ?></span><strong><b><?= (int) $resumo['diarias'] ?></b></strong></div>
</div>

<?php if ($resumo['nf_pendentes']): ?>
  <div class="alerta alerta-aviso espaco-topo"><?= icone('alerta') ?><span><strong><?= (int) $resumo['nf_pendentes'] ?> nota(s) fiscal(is) para enviar.</strong> Sem a nota, o pagamento não entra no lote. <a href="/guia/pos-viagem">Enviar no Pós-viagem</a>.</span></div>
<?php endif; ?>

<?php if (!$viagens): ?>
  <p class="vazio espaco-topo">Nenhum trabalho em <?= e($ano) ?>. O valor aparece aqui assim que você aceita um convite.</p>
<?php else: ?>
  <ul class="lista-financeiro">
    <?php foreach ($viagens as $v): [$rotulo, $cor, $dica] = $etapas[$v['etapa']] ?? [$v['etapa'], 'neutro', '']; ?>
      <li class="financeiro-item" id="viagem-<?= (int) $v['id'] ?>">
        <div class="financeiro-cabeca">
          <div>
            <span class="mono codigo"><?= e($v['codigo']) ?></span>
            <strong><a href="/guia/viagens/<?= (int) $v['id'] ?>" class="link-limpo"><?= e($v['nome']) ?></a></strong>
            <small class="texto-2"><?= count($v['diarias']) ?> diária(s) · último dia <?= e(formatar_data($v['ultima'])) ?></small>
          </div>
          <div class="financeiro-valor">
            <strong><?= e(formatar_moeda($v['total'] + $v['previsto'])) ?></strong>
            <?php if ($v['previsto'] > 0 && $v['total'] > 0): ?><small class="texto-2"><?= e(formatar_moeda($v['previsto'])) ?> previsto</small><?php endif; ?>
            <span class="selo selo-<?= e($cor) ?>"><?= e($rotulo) ?></span>
          </div>
        </div>

        <?php if ($dica): ?><p class="texto-2"><?= e($dica) ?></p><?php endif; ?>

        <?php if ($v['etapa'] === 'aguardando_nf'): ?>
          <p><a href="/guia/pos-viagem#viagem-<?= (int) $v['id'] ?>" class="btn btn-primario btn-p">Enviar a nota fiscal no Pós-viagem</a></p>
        <?php elseif ($v['etapa'] === 'realizada'): ?>
          <p><a href="/guia/pos-viagem#viagem-<?= (int) $v['id'] ?>" class="btn btn-primario btn-p">Fazer o relatório no Pós-viagem</a></p>
        <?php endif; ?>

        <?php if ($v['nf'] && $v['etapa'] !== 'aguardando_nf'): ?>
          <p class="texto-2">NF <?= e($v['nf']['numero_nf']) ?> · <?= e(formatar_moeda($v['nf']['valor_nf'])) ?> · enviada em <?= e(formatar_data($v['nf']['enviado_em'])) ?>
            · <a href="<?= e(arquivo_url('envio', $v['nf'])) ?>" target="_blank" rel="noopener">ver nota</a></p>
        <?php endif; ?>
        <?php if ($v['previsao'] && $v['etapa'] === 'a_pagar'): ?>
          <p><strong>Previsão de pagamento:</strong> <?= e(formatar_data($v['previsao'])) ?></p>
        <?php endif; ?>
        <?php foreach ($v['pagamentos'] as $pg): ?>
          <p class="pago">Pago <?= e(formatar_moeda($pg['valor'])) ?> em <?= e(formatar_data($pg['pago_em'])) ?>
            <?php if ($pg['comprovante_path']): ?> · <a href="<?= e(arquivo_url('comprovante', $pg)) ?>" target="_blank" rel="noopener">comprovante</a><?php endif; ?></p>
        <?php endforeach; ?>

        <details class="adicionar">
          <summary>Ver diárias</summary>
          <ul class="sem-poltrona">
            <?php foreach ($v['diarias'] as $d): ?>
              <li><?= e(formatar_data($d['data'])) ?> · <?= e(formatar_moeda($d['valor'])) ?> · <?= selo(STATUS_ESCALA, $d['status']) ?></li>
            <?php endforeach; ?>
          </ul>
        </details>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
