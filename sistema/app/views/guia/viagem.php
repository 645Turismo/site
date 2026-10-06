<?php
$assumidas = array_values(array_filter($escalas, fn($s) => in_array($s['status'], ESCALAS_ASSUMIDAS, true)));
$ultimoDia = $assumidas ? end($assumidas)['data'] : null;
$valorDiaria = $convites ? (float) $convites[0]['valor'] : 0;
$grupo = array_filter([$v['cliente'], $v['perfil_grupo'], $v['idioma_grupo'] ? 'Idioma: ' . $v['idioma_grupo'] : null,
  $v['qtd_passageiros'] ? $v['qtd_passageiros'] . ' passageiros' : null]);
?>
<div class="pagina-topo">
  <div>
    <p class="sobretitulo mono"><?= e($v['codigo']) ?> · <?= e(TIPOS_VIAGEM[$v['tipo']] ?? 'Viagem/Tour') ?></p>
    <h1><?= e($v['nome']) ?></h1>
    <p><?= e(($v['origem'] ?? '') . ' → ' . ($v['destino'] ?? '')) ?> · <?= e(periodo_viagem($v)) ?> · <?= (int) $v['com_pernoite'] ? 'com pernoite' : 'bate e volta' ?></p>
  </div>
  <a href="/guia/viagens" class="link-sublinhado">Voltar</a>
</div>

<?php if ($confirmado): ?>
  <a class="atalho-lista" href="/guia/viagens/<?= (int) $v['id'] ?>/passageiros">
    <span><?= icone('usuarios') ?></span>
    <strong>Lista de passageiros e mapa do carro</strong>
    <small>Check-in, check-out e telefones dos passageiros</small>
    <?= icone('seta') ?>
  </a>
<?php endif; ?>

<div class="horarios">
  <div><span class="rotulo">Apresentação</span><strong><?= e($v['horario_apresentacao'] ?: '—') ?></strong></div>
  <div><span class="rotulo">Saída</span><strong><?= e($v['horario_saida'] ?: '—') ?></strong></div>
  <div><span class="rotulo">Saída do destino</span><strong><?= e($v['horario_saida_destino'] ?: '—') ?></strong></div>
  <div><span class="rotulo">Chegada prevista</span><strong><?= e($v['previsao_chegada'] ?: '—') ?></strong></div>
</div>

<?php if ($convites): ?>
  <section class="convite" aria-labelledby="t-convite">
    <div>
      <p class="sobretitulo">Convite</p>
      <h2 id="t-convite">Você foi convidado(a) como <?= e($convites[0]['funcao'] ?: 'guia') ?></h2>
      <p class="texto-2">
        <?= count($convites) ?> dia(s) de trabalho · <?= e(formatar_moeda($valorDiaria)) ?> por diária ·
        total de <b><?= e(formatar_moeda($valorDiaria * count($convites))) ?></b>
      </p>
      <ul class="convite-dias">
        <?php foreach ($convites as $c): ?>
          <li><?= e(data_extenso($c['data'])) ?><?= $c['horario_apresentacao'] ? ' · apresentação ' . e($c['horario_apresentacao']) : '' ?><?= (int) $c['pernoite'] ? ' · com pernoite' : '' ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="convite-acoes">
      <form method="post" action="/guia/viagens/<?= (int) $v['id'] ?>/responder">
        <?= csrf_campo() ?>
        <input type="hidden" name="decisao" value="aceitar">
        <button type="submit" class="btn btn-primario btn-bloco"><?= icone('check') ?> Aceitar convite</button>
      </form>
      <details class="recusar">
        <summary>Não posso ir</summary>
        <form method="post" action="/guia/viagens/<?= (int) $v['id'] ?>/responder" class="form">
          <?= csrf_campo() ?>
          <input type="hidden" name="decisao" value="recusar">
          <label class="campo"><span>Quer contar o motivo? (opcional)</span>
            <textarea name="motivo" rows="2" maxlength="255"></textarea></label>
          <button type="submit" class="btn btn-contorno btn-bloco">Recusar convite</button>
        </form>
      </details>
    </div>
  </section>
<?php endif; ?>

<div class="colunas">
  <div>
    <section class="bloco" aria-labelledby="t-briefing">
      <div class="bloco-titulo"><h2 id="t-briefing">Briefing</h2></div>
      <dl class="ficha">
        <?php if ($origens): ?>
          <dt>Origens</dt>
          <dd><ul class="origens-lista"><?php foreach ($origens as $o): ?><li><?= e($o['local']) ?><?= $o['horario'] ? '<b>' . e($o['horario']) . '</b>' : '' ?></li><?php endforeach; ?></ul></dd>
        <?php endif; ?>
        <?php if ($v['ponto_encontro']): ?>
          <dt>Ponto de encontro</dt>
          <dd><?= e($v['ponto_encontro']) ?>
            <?php if ($v['ponto_encontro_mapa']): ?><br><a href="<?= e($v['ponto_encontro_mapa']) ?>" target="_blank" rel="noopener noreferrer">Abrir no mapa</a><?php endif; ?></dd>
        <?php endif; ?>
        <?php if ($grupo): ?><dt>Grupo</dt><dd><?= e(implode(' · ', $grupo)) ?></dd><?php endif; ?>
        <?php $veic = veiculo_layout($v); if ($veic || $v['transporte']): ?>
          <dt>Veículo</dt><dd><?= e(implode(' · ', array_filter([$veic ? $veic['rotulo'] : null, $v['transporte']]))) ?></dd>
        <?php endif; ?>
        <?php if ((int) $v['com_pernoite']): ?><dt>Hospedagem</dt><dd><?= e($v['hospedagem'] ?: 'A confirmar com a coordenação') ?></dd><?php endif; ?>
        <?php if ($v['uniforme']): ?><dt>Uniforme</dt><dd><?= e($v['uniforme']) ?></dd><?php endif; ?>
        <?php if ($v['alimentacao']): ?><dt>Alimentação</dt><dd><?= e($v['alimentacao']) ?></dd><?php endif; ?>
      </dl>
      <?php foreach (['roteiro' => 'Roteiro', 'regras' => 'Regras e orientações', 'observacoes' => 'Observações'] as $campo => $rotulo):
        if ($v[$campo]): ?>
          <h3 class="subtitulo"><?= e($rotulo) ?></h3>
          <div class="texto-longo"><?= nl2br(e($v[$campo])) ?></div>
      <?php endif; endforeach; ?>
    </section>

    <?php if ($confirmado): ?>
      <section class="bloco" id="relatorio" aria-labelledby="t-relatorio">
        <div class="bloco-titulo"><h2 id="t-relatorio">Relatório da viagem/tour</h2>
          <?php if ($relatorio): ?><?= selo(['enviado' => ['Enviado', 'info'], 'aprovado' => ['Conferido', 'sucesso'], 'reprovado' => ['Devolvido', 'erro']], $relatorio['status']) ?><?php endif; ?>
        </div>
        <?php if (!$relatorioLiberado): ?>
          <p class="vazio">O relatório abre no último dia de trabalho (<?= e(formatar_data($ultimoDia)) ?>). Anote durante a viagem o que for importante.</p>
        <?php elseif (!$relatorioEditavel): ?>
          <div class="painel"><p class="texto-longo"><?= nl2br(e($relatorio['texto'])) ?></p>
            <p class="texto-2">Passageiros: <?= e($relatorio['qtd_passageiros'] ?? '—') ?> · conferido pela equipe.</p></div>
        <?php else: ?>
          <?php if ($relatorio && $relatorio['status'] === 'reprovado'): ?>
            <div class="alerta alerta-erro"><?= icone('alerta') ?><span><strong>A equipe pediu ajustes:</strong> <?= e($relatorio['motivo'] ?: 'revise e envie de novo.') ?></span></div>
          <?php endif; ?>
          <form method="post" action="/guia/viagens/<?= (int) $v['id'] ?>/relatorio" class="form painel">
            <?= csrf_campo() ?>
            <label class="campo">
              <span>Como foi o trabalho?</span>
              <textarea name="texto" rows="8" maxlength="5000" required
                placeholder="Pontualidade do grupo e do transporte, roteiro cumprido ou alterado, ocorrências (atrasos, saúde, perdas), feedback dos passageiros e sugestões."><?= e(antigo('texto', $relatorio['texto'] ?? '')) ?></textarea>
            </label>
            <label class="campo campo-curto">
              <span>Passageiros atendidos</span>
              <input type="number" name="qtd_passageiros" min="0" max="9999" inputmode="numeric"
                     value="<?= e(antigo('qtd_passageiros', (string) ($relatorio['qtd_passageiros'] ?? $v['qtd_passageiros'] ?? ''))) ?>">
            </label>
            <div class="form-rodape">
              <?php if ($relatorio && $relatorio['status'] === 'enviado'): ?>
                <span class="texto-2">Enviado em <?= e(formatar_data_hora($relatorio['enviado_em'])) ?>. Você pode ajustar até a equipe conferir.</span>
              <?php endif; ?>
              <button type="submit" class="btn btn-primario"><?= $relatorio ? 'Atualizar relatório' : 'Enviar relatório' ?></button>
            </div>
          </form>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  </div>

  <aside>
    <section class="bloco" aria-labelledby="t-dias">
      <div class="bloco-titulo"><h2 id="t-dias">Seus dias</h2></div>
      <ul class="feed">
        <?php foreach ($escalas as $s): ?>
          <li>
            <span class="feed-data"><?= e(dia_semana_curto($s['data'])) ?> <?= e(date('d/m', strtotime($s['data']))) ?></span>
            <div>
              <strong><?= $s['horario_apresentacao'] ? 'Apresentação ' . e($s['horario_apresentacao']) : 'Horário a confirmar' ?></strong>
              <p><?= e(trim(($s['hora_inicio'] ? $s['hora_inicio'] . '–' . $s['hora_fim'] : '') . ((int) $s['pernoite'] ? ' · pernoite' : '') . ($s['observacao'] ? ' · ' . $s['observacao'] : ''), ' ·')) ?></p>
            </div>
            <?= selo(STATUS_ESCALA, $s['status']) ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>

    <section class="bloco" aria-labelledby="t-contatos">
      <div class="bloco-titulo"><h2 id="t-contatos">Contatos e materiais</h2></div>
      <?php if (!$confirmado): ?>
        <p class="vazio">Contatos do coordenador e do motorista e os materiais do grupo aparecem quando você confirma presença.</p>
      <?php else: ?>
        <ul class="contatos">
          <?php if ($v['coordenador_nome'] || $v['coordenador_telefone']): ?>
            <li><span class="rotulo">Coordenação</span><strong><?= e($v['coordenador_nome'] ?: 'Coordenação 645') ?></strong>
              <?php if ($v['coordenador_telefone']): ?><a href="<?= e(link_whatsapp($v['coordenador_telefone'])) ?>" target="_blank" rel="noopener noreferrer">WhatsApp <?= e(formatar_celular($v['coordenador_telefone'])) ?></a><?php endif; ?></li>
          <?php endif; ?>
          <?php if ($v['motorista_nome'] || $v['motorista_telefone']): ?>
            <li><span class="rotulo">Motorista</span><strong><?= e($v['motorista_nome'] ?: 'Motorista') ?></strong>
              <?php if ($v['motorista_telefone']): ?><a href="tel:+55<?= e(so_digitos($v['motorista_telefone'])) ?>">Ligar <?= e(formatar_celular($v['motorista_telefone'])) ?></a><?php endif; ?></li>
          <?php endif; ?>
          <?php if ($v['guia_local_nome'] || $v['guia_local_telefone']): ?>
            <li><span class="rotulo">Guia local</span><strong><?= e($v['guia_local_nome'] ?: 'Guia local') ?></strong>
              <?php if ($v['guia_local_telefone']): ?><a href="tel:+55<?= e(so_digitos($v['guia_local_telefone'])) ?>">Ligar <?= e(formatar_celular($v['guia_local_telefone'])) ?></a><?php endif; ?></li>
          <?php endif; ?>
          <?php foreach ($contatos as $c): ?>
            <li><span class="rotulo"><?= e($c['papel']) ?></span><strong><?= e($c['nome'] ?: $c['papel']) ?></strong>
              <?php if ($c['telefone']): ?><a href="tel:+55<?= e(so_digitos($c['telefone'])) ?>">Ligar <?= e(formatar_celular($c['telefone'])) ?></a><?php endif; ?>
              <?php if ($c['observacao']): ?><small><?= e($c['observacao']) ?></small><?php endif; ?></li>
          <?php endforeach; ?>
          <?php foreach ($anexos as $an): ?>
            <li><span class="rotulo">Material</span><strong><?= e($an['titulo']) ?></strong></li>
          <?php endforeach; ?>
        </ul>
        <?php if ($v['instrucoes_nf']): ?>
          <h3 class="subtitulo">Nota fiscal</h3>
          <div class="texto-longo texto-2"><?= nl2br(e($v['instrucoes_nf'])) ?></div>
        <?php endif; ?>
      <?php endif; ?>
    </section>
  </aside>
</div>
