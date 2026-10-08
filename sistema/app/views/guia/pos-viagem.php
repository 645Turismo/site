<?php
$lista = $aba === 'concluidas' ? $outras : $pendentes;
$passos = $emiteNf ? ['relatorio' => 'Relatório', 'nf' => 'Nota fiscal'] : ['relatorio' => 'Relatório'];
?>
<div class="pagina-topo">
  <div>
    <p class="sobretitulo">Depois da viagem</p>
    <h1>Pós-viagem</h1>
    <p>Em cada viagem que você terminou: faça o relatório<?= $emiteNf ? ' e envie a nota fiscal' : '' ?>. É isso que libera o seu pagamento.</p>
  </div>
</div>

<nav class="filtros" aria-label="Situação">
  <a href="?aba=pendentes" class="<?= $aba === 'pendentes' ? 'ativo' : '' ?>">Para fazer <span><?= count($pendentes) ?></span></a>
  <a href="?aba=concluidas" class="<?= $aba === 'concluidas' ? 'ativo' : '' ?>">Enviadas <span><?= count($outras) ?></span></a>
</nav>

<?php if (!$lista): ?>
  <p class="vazio"><?= $aba === 'pendentes' ? 'Nada pendente. Quando uma viagem sua terminar, ela aparece aqui.' : 'Nenhuma viagem enviada ainda.' ?></p>
<?php endif; ?>

<ul class="lista-financeiro">
  <?php foreach ($lista as $v): [$rotuloEtapa, $corEtapa] = POS_VIAGEM_ETAPAS[$v['etapa']]; $rel = $v['relatorio']; $nf = $v['nf']; ?>
    <li class="financeiro-item" id="viagem-<?= (int) $v['id'] ?>">
      <div class="financeiro-cabeca">
        <div>
          <span class="mono codigo"><?= e($v['codigo']) ?></span>
          <strong><a href="/guia/viagens/<?= (int) $v['id'] ?>" class="link-limpo"><?= e($v['nome']) ?></a></strong>
          <small class="texto-2"><?= e($v['inicio'] === $v['fim'] ? formatar_data($v['fim']) : formatar_data($v['inicio']) . ' a ' . formatar_data($v['fim'])) ?></small>
        </div>
        <div class="financeiro-valor"><span class="selo selo-<?= e($corEtapa) ?>"><?= e($rotuloEtapa) ?></span></div>
      </div>

      <ol class="pos-passos">
        <?php foreach ($passos as $chave => $rotulo):
          $feito = $chave === 'relatorio' ? $v['etapa'] !== 'relatorio' : in_array($v['etapa'], ['conferencia', 'concluida'], true);
          $atual = $v['etapa'] === $chave; ?>
          <li class="<?= $feito ? 'feito' : ($atual ? 'atual' : '') ?>"><span><?= $feito ? '✓' : array_search($chave, array_keys($passos), true) + 1 ?></span><?= e($rotulo) ?></li>
        <?php endforeach; ?>
      </ol>

      <?php if ($v['etapa'] === 'relatorio'): ?>
        <?php if ($rel && $rel['status'] === 'reprovado'): ?>
          <div class="alerta alerta-erro"><?= icone('alerta') ?><span><strong>A equipe pediu ajustes:</strong> <?= e($rel['motivo'] ?: 'revise e envie de novo.') ?></span></div>
        <?php endif; ?>
        <form method="post" action="/guia/viagens/<?= (int) $v['id'] ?>/relatorio" class="form painel">
          <?= csrf_campo() ?>
          <label class="campo"><span>Relatório: como foi o trabalho?</span>
            <textarea name="texto" rows="7" maxlength="5000" required
              placeholder="Pontualidade do grupo e do transporte, roteiro cumprido ou alterado, ocorrências (atrasos, saúde, perdas), feedback dos passageiros e sugestões."><?= e(antigo('texto', $rel['texto'] ?? '')) ?></textarea></label>
          <label class="campo campo-curto"><span>Passageiros atendidos</span>
            <input type="number" name="qtd_passageiros" min="0" max="9999" inputmode="numeric" value="<?= e((string) ($rel['qtd_passageiros'] ?? $v['qtd_passageiros'] ?? '')) ?>"></label>
          <div class="form-rodape"><button type="submit" class="btn btn-primario">Enviar relatório</button></div>
        </form>
      <?php else: ?>
        <details class="adicionar">
          <summary>Ver relatório enviado<?= $rel['status'] === 'aprovado' ? ' (conferido pela equipe)' : '' ?></summary>
          <div class="texto-longo"><?= nl2br(e($rel['texto'])) ?></div>
          <p class="texto-2">Passageiros: <?= e($rel['qtd_passageiros'] ?? '—') ?> · enviado em <?= e(formatar_data_hora($rel['enviado_em'])) ?></p>
          <?php if ($rel['status'] === 'enviado'): ?>
            <form method="post" action="/guia/viagens/<?= (int) $v['id'] ?>/relatorio" class="form">
              <?= csrf_campo() ?>
              <label class="campo"><span>Ajustar relatório (até a equipe conferir)</span>
                <textarea name="texto" rows="5" maxlength="5000" required><?= e($rel['texto']) ?></textarea></label>
              <input type="hidden" name="qtd_passageiros" value="<?= e((string) ($rel['qtd_passageiros'] ?? '')) ?>">
              <button type="submit" class="btn btn-contorno btn-p">Atualizar relatório</button>
            </form>
          <?php endif; ?>
        </details>
      <?php endif; ?>

      <?php if ($v['etapa'] === 'nf'): ?>
        <?php if ($nf && $nf['status'] === 'reprovado'): ?>
          <div class="alerta alerta-erro"><?= icone('alerta') ?><span><strong>A nota anterior foi devolvida:</strong> <?= e($nf['motivo'] ?: 'confira e envie de novo.') ?></span></div>
        <?php endif; ?>
        <div class="nf-instrucoes">
          <p><strong>Emita a nota para:</strong> <?= e($empresa['razao']) ?> · CNPJ <?= e(formatar_cnpj($empresa['cnpj'])) ?></p>
          <p>Valor: <strong><?= e(formatar_moeda($v['total'])) ?></strong> · prazo: <strong class="<?= $v['prazo_nf'] < hoje() ? 'vermelho' : '' ?>"><?= e(formatar_data($v['prazo_nf'])) ?></strong><?= $v['prazo_nf'] < hoje() ? ' (vencido)' : '' ?></p>
          <?php if ($v['instrucoes_nf']): ?><p class="texto-2"><?= nl2br(e($v['instrucoes_nf'])) ?></p><?php endif; ?>
        </div>
        <form method="post" action="/guia/recebimentos/<?= (int) $v['id'] ?>/nf" class="form nf-form painel" enctype="multipart/form-data" novalidate>
          <?= csrf_campo() ?>
          <div class="grade-campos">
            <label class="campo"><span>Número da NF</span><input type="text" name="numero_nf" maxlength="40" required inputmode="numeric"></label>
            <label class="campo"><span>Valor da NF (R$)</span><input type="text" name="valor_nf" inputmode="decimal" required value="<?= e(number_format($v['total'], 2, ',', '.')) ?>"></label>
            <label class="campo campo-largo"><span>Arquivo da nota (PDF ou foto)</span>
              <input type="file" name="arquivo_nf" accept="application/pdf,image/jpeg,image/png,image/webp" required></label>
          </div>
          <button type="submit" class="btn btn-primario btn-bloco">Enviar nota fiscal</button>
        </form>
      <?php elseif ($nf && in_array($v['etapa'], ['conferencia', 'concluida'], true)): ?>
        <p class="texto-2">NF <?= e($nf['numero_nf']) ?> · <?= e(formatar_moeda($nf['valor_nf'])) ?> · enviada em <?= e(formatar_data($nf['enviado_em'])) ?>
          · <a href="<?= e(arquivo_url('envio', $nf)) ?>" target="_blank" rel="noopener">ver nota</a></p>
      <?php endif; ?>

      <?php if ($v['etapa'] === 'concluida'): ?>
        <p class="texto-2">Tudo enviado. Acompanhe o pagamento em <a href="/guia/recebimentos">Ganhos</a>.</p>
      <?php elseif ($v['etapa'] === 'conferencia'): ?>
        <p class="texto-2">A equipe está conferindo sua nota fiscal.</p>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
</ul>
