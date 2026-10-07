<div class="pagina-topo">
  <div>
    <p class="sobretitulo">Financeiro</p>
    <h1>Pagamentos</h1>
    <p>Diárias com nota fiscal aprovada, prontas para pagar.</p>
  </div>
  <?php if ($pendentes): ?><a href="/admin/pagamentos/exportar.csv" class="btn btn-contorno">Baixar planilha do que está a pagar</a><?php endif; ?>
</div>

<nav class="filtros" aria-label="Situação">
  <a href="?aba=a_pagar" class="<?= $aba === 'a_pagar' ? 'ativo' : '' ?>">A pagar <span><?= count($pendentes) ?></span></a>
  <a href="?aba=pagos" class="<?= $aba === 'pagos' ? 'ativo' : '' ?>">Pagos</a>
</nav>

<?php if ($aba === 'a_pagar'): ?>
  <div class="lp-contadores resumo-financeiro">
    <div><span class="rotulo">Total a pagar</span><strong><b><?= e(formatar_moeda($totalPendente)) ?></b></strong></div>
    <div><span class="rotulo">Pagamentos</span><strong><b><?= count($pendentes) ?></b></strong></div>
    <div><span class="rotulo">Previsão vencida</span><strong class="<?= $vencidos ? 'vermelho' : '' ?>"><b><?= (int) $vencidos ?></b></strong></div>
  </div>
  <?php if (!$pendentes): ?>
    <p class="vazio espaco-topo">Nada a pagar agora.</p>
  <?php else: ?>
    <ul class="lista-conferencia espaco-topo">
      <?php foreach ($pendentes as $p): ?>
        <li class="painel conferencia-item">
          <div class="conferencia-cabeca">
            <div>
              <strong><?= e($p['nome_social'] ?: $p['nome']) ?> <span class="mono amarelo"><?= e($p['guia_codigo']) ?></span></strong>
              <p class="texto-2"><span class="mono codigo"><?= e($p['codigo']) ?></span> <?= e($p['viagem']) ?> · <?= (int) $p['diarias'] ?> diária(s)</p>
            </div>
            <div class="financeiro-valor">
              <strong><?= e(formatar_moeda($p['total'])) ?></strong>
              <?php if ($p['previsao']): ?><span class="selo <?= $p['previsao'] < hoje() ? 'selo-erro' : 'selo-info' ?>">até <?= e(formatar_data($p['previsao'])) ?></span><?php endif; ?>
            </div>
          </div>
          <dl class="ficha ficha-compacta">
            <dt>PIX</dt><dd><?= $p['pix_chave'] ? e((TIPOS_PIX[$p['pix_tipo']] ?? '') . ': ' . $p['pix_chave']) : '<span class="vermelho">não cadastrado</span>' ?></dd>
            <dt>Conta</dt><dd><?= e(implode(' · ', array_filter([$p['banco_nome'], $p['agencia'] ? 'ag. ' . $p['agencia'] : null, $p['conta'] ? 'cc ' . $p['conta'] : null])) ?: '—') ?></dd>
            <dt>CNPJ</dt><dd><?= e(formatar_cnpj($p['cnpj'])) ?> <?= e($p['razao_social']) ?></dd>
            <dt>NF</dt><dd><?php if ($p['nf']): ?>nº <?= e($p['nf']['numero_nf']) ?> · <a href="<?= e(arquivo_url('envio', $p['nf'])) ?>" target="_blank" rel="noopener">abrir</a><?php endif; ?></dd>
          </dl>
          <details class="acao-motivo"><summary class="btn btn-primario btn-p">Registrar pagamento</summary>
            <form method="post" action="/admin/pagamentos/registrar" class="form" enctype="multipart/form-data"><?= csrf_campo() ?>
              <input type="hidden" name="guia_id" value="<?= (int) $p['guia_id'] ?>"><input type="hidden" name="viagem_id" value="<?= (int) $p['viagem_id'] ?>">
              <div class="grade-campos">
                <label class="campo"><span>Pago em</span><input type="date" name="pago_em" max="<?= e(hoje()) ?>" value="<?= e(hoje()) ?>" required></label>
                <label class="campo"><span>Comprovante</span><input type="file" name="comprovante" accept="application/pdf,image/jpeg,image/png,image/webp"></label>
                <label class="campo campo-largo"><span>Observação</span><input type="text" name="observacao" maxlength="255"></label>
              </div>
              <button type="submit" class="btn btn-primario btn-p">Confirmar pagamento de <?= e(formatar_moeda($p['total'])) ?></button>
            </form>
          </details>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
<?php else: ?>
  <form method="get" class="filtros-form"><input type="hidden" name="aba" value="pagos">
    <label class="campo"><span>Mês</span><input type="month" name="mes" value="<?= e($mes) ?>"></label>
    <button type="submit" class="btn btn-contorno btn-p">Ver</button></form>
  <?php if (!$pagos): ?>
    <p class="vazio">Nenhum pagamento neste mês.</p>
  <?php else: ?>
    <p class="texto-2">Total do mês: <strong><?= e(formatar_moeda(array_sum(array_column($pagos, 'valor')))) ?></strong></p>
    <div class="tabela-rolagem">
      <table class="tabela">
        <thead><tr><th>Data</th><th>Guia</th><th>Viagem/Tour</th><th>Valor</th><th>Comprovante</th></tr></thead>
        <tbody>
          <?php foreach ($pagos as $pg): ?>
            <tr>
              <td><?= e(formatar_data($pg['pago_em'])) ?></td>
              <td><?= e($pg['nome']) ?> <span class="mono amarelo"><?= e($pg['guia_codigo']) ?></span></td>
              <td><span class="mono codigo"><?= e($pg['codigo']) ?></span> <?= e($pg['viagem']) ?></td>
              <td><?= e(formatar_moeda($pg['valor'])) ?></td>
              <td><?php if ($pg['comprovante_path']): ?><a href="<?= e(arquivo_url('comprovante', $pg)) ?>" target="_blank" rel="noopener">abrir</a><?php else: ?>—<?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
<?php endif; ?>
