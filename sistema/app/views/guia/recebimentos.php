<?php
$etapas = [
  'realizada' => ['Aguardando relatório', 'aviso', 'Envie o relatório da viagem para liberar a nota fiscal.'],
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
    <p>Do relatório entregue até o pagamento, viagem por viagem.</p>
  </div>
  <?php if (count($anos) > 1): ?>
    <nav class="filtros" aria-label="Ano">
      <?php foreach ($anos as $a): ?><a href="?ano=<?= e($a) ?>" class="<?= $a === $ano ? 'ativo' : '' ?>"><?= e($a) ?></a><?php endforeach; ?>
    </nav>
  <?php endif; ?>
</div>

<div class="lp-contadores resumo-financeiro">
  <div><span class="rotulo">Recebido em <?= e($ano) ?></span><strong><b><?= e(formatar_moeda($resumo['recebido'])) ?></b></strong></div>
  <div><span class="rotulo">A receber</span><strong class="amarelo"><b><?= e(formatar_moeda($resumo['a_receber'])) ?></b></strong></div>
  <div><span class="rotulo">Diárias em <?= e($ano) ?></span><strong><b><?= (int) $resumo['diarias'] ?></b></strong></div>
</div>

<?php if ($resumo['nf_pendentes']): ?>
  <div class="alerta alerta-aviso espaco-topo"><?= icone('alerta') ?><span><strong><?= (int) $resumo['nf_pendentes'] ?> nota(s) fiscal(is) para enviar.</strong> Sem a nota, o pagamento não entra no lote.</span></div>
<?php endif; ?>

<?php if (!$viagens): ?>
  <p class="vazio espaco-topo">Nenhuma diária realizada em <?= e($ano) ?>. Os valores aparecem aqui depois do relatório de cada viagem.</p>
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
            <strong><?= e(formatar_moeda($v['total'])) ?></strong>
            <span class="selo selo-<?= e($cor) ?>"><?= e($rotulo) ?></span>
          </div>
        </div>

        <?php if ($dica): ?><p class="texto-2"><?= e($dica) ?></p><?php endif; ?>

        <?php if ($v['etapa'] === 'aguardando_nf'): ?>
          <?php if ($v['nf'] && $v['nf']['status'] === 'reprovado'): ?>
            <div class="alerta alerta-erro"><?= icone('alerta') ?><span><strong>A nota anterior foi devolvida:</strong> <?= e($v['nf']['motivo'] ?: 'confira e envie de novo.') ?></span></div>
          <?php endif; ?>
          <div class="nf-instrucoes">
            <p><strong>Emita a nota para:</strong> <?= e($empresa['razao']) ?> · CNPJ <?= e(formatar_cnpj($empresa['cnpj'])) ?></p>
            <p>Valor: <strong><?= e(formatar_moeda($v['total'])) ?></strong> · prazo: <strong class="<?= $v['prazo_nf'] < hoje() ? 'vermelho' : '' ?>"><?= e(formatar_data($v['prazo_nf'])) ?></strong><?= $v['prazo_nf'] < hoje() ? ' (vencido)' : '' ?></p>
            <?php if ($v['instrucoes_nf']): ?><p class="texto-2"><?= nl2br(e($v['instrucoes_nf'])) ?></p><?php endif; ?>
          </div>
          <form method="post" action="/guia/recebimentos/<?= (int) $v['id'] ?>/nf" class="form nf-form" enctype="multipart/form-data" novalidate>
            <?= csrf_campo() ?>
            <div class="grade-campos">
              <label class="campo"><span>Número da NF</span><input type="text" name="numero_nf" maxlength="40" required inputmode="numeric"></label>
              <label class="campo"><span>Valor da NF (R$)</span><input type="text" name="valor_nf" inputmode="decimal" required value="<?= e(number_format($v['total'], 2, ',', '.')) ?>"></label>
              <label class="campo campo-largo"><span>Arquivo da nota (PDF ou foto)</span>
                <input type="file" name="arquivo_nf" accept="application/pdf,image/jpeg,image/png,image/webp" required></label>
            </div>
            <button type="submit" class="btn btn-primario btn-bloco">Enviar nota fiscal</button>
          </form>
        <?php endif; ?>

        <?php if ($v['nf'] && $v['etapa'] !== 'aguardando_nf'): ?>
          <p class="texto-2">NF <?= e($v['nf']['numero_nf']) ?> · <?= e(formatar_moeda($v['nf']['valor_nf'])) ?> · enviada em <?= e(formatar_data($v['nf']['enviado_em'])) ?>
            · <a href="/arquivos/envio/<?= (int) $v['nf']['id'] ?>" target="_blank" rel="noopener">ver nota</a></p>
        <?php endif; ?>
        <?php if ($v['previsao'] && $v['etapa'] === 'a_pagar'): ?>
          <p><strong>Previsão de pagamento:</strong> <?= e(formatar_data($v['previsao'])) ?></p>
        <?php endif; ?>
        <?php foreach ($v['pagamentos'] as $pg): ?>
          <p class="pago">Pago <?= e(formatar_moeda($pg['valor'])) ?> em <?= e(formatar_data($pg['pago_em'])) ?>
            <?php if ($pg['comprovante_path']): ?> · <a href="/arquivos/comprovante/<?= (int) $pg['id'] ?>" target="_blank" rel="noopener">comprovante</a><?php endif; ?></p>
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
