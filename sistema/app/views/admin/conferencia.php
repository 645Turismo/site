<?php
$abas = ['nf' => 'Notas fiscais', 'relatorio' => 'Relatórios', 'historico' => 'Já conferidos'];
$statusEnvio = ['enviado' => ['Para conferir', 'aviso'], 'aprovado' => ['Aprovado', 'sucesso'], 'reprovado' => ['Devolvido', 'erro']];
?>
<div class="pagina-topo">
  <div>
    <p class="sobretitulo">Depois do trabalho</p>
    <h1>Conferência</h1>
    <p>Relatórios das viagens e notas fiscais dos guias. A nota aprovada libera o pagamento.</p>
  </div>
</div>

<nav class="filtros" aria-label="Tipo">
  <?php foreach ($abas as $chave => $rotulo): ?>
    <a href="?aba=<?= e($chave) ?>" class="<?= $aba === $chave ? 'ativo' : '' ?>"><?= e($rotulo) ?><?= isset($contagem[$chave]) ? ' <span>' . (int) $contagem[$chave] . '</span>' : '' ?></a>
  <?php endforeach; ?>
</nav>

<?php if (!$envios): ?>
  <p class="vazio"><?= $aba === 'historico' ? 'Nada conferido ainda.' : 'Nada para conferir agora.' ?></p>
<?php else: ?>
  <ul class="lista-conferencia">
    <?php foreach ($envios as $e): ?>
      <li class="painel conferencia-item">
        <div class="conferencia-cabeca">
          <div>
            <p class="rotulo"><?= $e['tipo'] === 'nf' ? 'Nota fiscal' : 'Relatório' ?> · enviado em <?= e(formatar_data_hora($e['enviado_em'])) ?></p>
            <strong><?= e($e['guia']) ?> <span class="mono amarelo"><?= e($e['guia_codigo']) ?></span></strong>
            <p class="texto-2"><a href="/admin/viagens/<?= (int) $e['viagem_id'] ?>" class="link-limpo"><span class="mono codigo"><?= e($e['codigo']) ?></span> <?= e($e['viagem']) ?></a></p>
          </div>
          <?= selo($statusEnvio, $e['status']) ?>
        </div>

        <?php if ($e['tipo'] === 'nf'): $difere = abs((float) $e['valor_nf'] - (float) $e['total_diarias']) > 0.009; ?>
          <dl class="ficha ficha-compacta">
            <dt>NF nº</dt><dd><?= e($e['numero_nf']) ?></dd>
            <dt>Valor da NF</dt><dd><?= e(formatar_moeda($e['valor_nf'])) ?></dd>
            <dt>Diárias</dt><dd class="<?= $difere ? 'vermelho' : '' ?>"><?= e(formatar_moeda($e['total_diarias'])) ?><?= $difere ? ' · valor diferente da NF' : ' · confere' ?></dd>
          </dl>
          <a href="<?= e(arquivo_url('envio', $e)) ?>" target="_blank" rel="noopener" class="btn btn-contorno btn-p">Abrir a nota</a>
          <a href="<?= e(arquivo_url('envio', $e)) ?>?baixar=1" class="btn btn-texto btn-p">Baixar</a>
        <?php else: ?>
          <p class="texto-2"><?= $e['qtd_passageiros'] !== null ? (int) $e['qtd_passageiros'] . ' passageiros atendidos' : '' ?></p>
          <div class="texto-longo relatorio-texto"><?= nl2br(e($e['texto'])) ?></div>
        <?php endif; ?>

        <?php if ($e['status'] === 'reprovado' && $e['motivo']): ?><p class="vermelho">Motivo da devolução: <?= e($e['motivo']) ?></p><?php endif; ?>

        <?php if ($e['status'] === 'enviado'): ?>
          <div class="conferencia-acoes">
            <form method="post" action="/admin/conferencia/<?= (int) $e['id'] ?>" class="inline-form"><?= csrf_campo() ?>
              <input type="hidden" name="decisao" value="aprovar"><button type="submit" class="btn btn-primario btn-p">Aprovar</button></form>
            <details class="acao-motivo"><summary class="btn btn-texto btn-p">Devolver ao guia</summary>
              <form method="post" action="/admin/conferencia/<?= (int) $e['id'] ?>" class="form"><?= csrf_campo() ?>
                <input type="hidden" name="decisao" value="devolver">
                <label class="campo"><span>Motivo (o guia vê)</span><textarea name="motivo" rows="2" required></textarea></label>
                <button type="submit" class="btn btn-contorno btn-p">Devolver</button></form>
            </details>
          </div>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
