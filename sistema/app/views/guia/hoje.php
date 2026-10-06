<div class="pagina-topo">
  <div>
    <p class="sobretitulo"><?= e(data_extenso(hoje())) ?></p>
    <h1><?= e(saudacao()) ?>, <?= e(primeiro_nome($p)) ?>.</h1>
  </div>
</div>

<?php if ($p['status'] !== 'aprovado'):
  [$rotuloStatus, $corStatus] = STATUS_CADASTRO[$p['status']] ?? [$p['status'], 'neutro']; ?>
  <div class="alerta alerta-<?= e($corStatus === 'sucesso' ? 'sucesso' : ($corStatus === 'erro' ? 'erro' : 'aviso')) ?>">
    <?= icone('alerta') ?>
    <span><strong><?= e($rotuloStatus) ?>.</strong>
      <?php if ($p['status'] === 'em_analise'): ?>Estamos conferindo seus dados e documentos. Você recebe um e-mail assim que terminarmos. Enquanto isso, já pode marcar sua disponibilidade.
      <?php elseif ($p['status'] === 'pendente'): ?><?= e($p['status_motivo'] ?: 'Há itens para corrigir no seu cadastro.') ?> Ajuste no seu Perfil.
      <?php elseif ($p['status'] === 'reprovado'): ?><?= e($p['status_motivo'] ?: '') ?> Em caso de dúvida, fale com a gente pela Ajuda.
      <?php endif; ?>
    </span>
  </div>
<?php endif; ?>

<section class="bloco" aria-labelledby="t-proxima">
  <div class="bloco-titulo"><h2 id="t-proxima">Próximo trabalho</h2></div>
  <?php if (!$proxima): ?>
    <p class="vazio">Nenhum trabalho marcado. Mantenha sua disponibilidade em dia para receber convites.
      <a href="/guia/disponibilidade">Atualizar disponibilidade</a></p>
  <?php else: ?>
    <a class="destaque" href="/guia/viagens/<?= (int) $proxima['viagem_id'] ?>">
      <div class="destaque-data">
        <strong><?= e(date('d', strtotime($proxima['data']))) ?></strong>
        <span><?= e(mes_curto($proxima['data'])) ?> · <?= e(dia_semana_curto($proxima['data'])) ?></span>
      </div>
      <div>
        <p class="rotulo mono"><?= e($proxima['codigo']) ?><?= $proxima['data'] === hoje() ? ' · hoje' : '' ?></p>
        <h3><?= e($proxima['nome']) ?></h3>
      </div>
      <p class="destaque-info"><?= e($proxima['origem'] . ' → ' . $proxima['destino']) ?><br>
        <?php if ($proxima['horario_apresentacao']): ?>Apresentação às <b><?= e($proxima['horario_apresentacao']) ?></b><?php endif; ?>
        <?php if ($proxima['ponto_encontro']): ?> · <?= e($proxima['ponto_encontro']) ?><?php endif; ?>
      </p>
      <div class="destaque-rodape">
        <?= selo(STATUS_ESCALA, $proxima['escala_status']) ?>
        <span class="link-sublinhado"><?= $proxima['escala_status'] === 'convidado' ? 'Responder convite' : 'Ver briefing' ?></span>
      </div>
    </a>
  <?php endif; ?>
</section>

<div class="colunas">
  <div>
    <section class="bloco" aria-labelledby="t-resolver">
      <div class="bloco-titulo"><h2 id="t-resolver">Para resolver</h2></div>
      <ul class="pendencias">
        <?php foreach ($pendencias as [$n, $tituloItem, $descricao, $href]): ?>
          <li>
            <a href="<?= e($href) ?>">
              <span class="num <?= $n ? '' : 'zero' ?>"><?= str_pad((string) $n, 2, '0', STR_PAD_LEFT) ?></span>
              <span><strong><?= e($tituloItem) ?></strong><small><?= e($descricao) ?></small></span>
              <?= icone('seta') ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
      <p class="resumo">
        <span>Neste mês: <b><?= (int) $mes['diarias'] ?></b> diária(s)</span>
        <span>A receber: <b><?= e(formatar_moeda($mes['a_receber'])) ?></b></span>
      </p>
    </section>

    <section class="bloco" aria-labelledby="t-agenda">
      <div class="bloco-titulo"><h2 id="t-agenda">Próximos dias</h2><a href="/guia/viagens" class="link-p">Ver todas</a></div>
      <?php if (!$proximosDias): ?>
        <p class="vazio">Sem dias de trabalho pela frente.</p>
      <?php else: ?>
        <ul class="feed">
          <?php foreach ($proximosDias as $d): ?>
            <li>
              <span class="feed-data"><?= e(dia_semana_curto($d['data'])) ?> <?= e(date('d/m', strtotime($d['data']))) ?></span>
              <div>
                <strong><a href="/guia/viagens/<?= (int) $d['viagem_id'] ?>" class="link-limpo"><?= e($d['nome']) ?></a></strong>
                <p><?= e(trim(($d['horario_apresentacao'] ? 'Apresentação ' . $d['horario_apresentacao'] . ' · ' : '') . (string) $d['ponto_encontro'], ' ·')) ?></p>
              </div>
              <?= selo(STATUS_ESCALA, $d['status']) ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>

  <section class="bloco" aria-labelledby="t-recados">
    <div class="bloco-titulo"><h2 id="t-recados">Recados da 645</h2></div>
    <?php if (!$recados): ?>
      <p class="vazio">Nenhum recado no momento.</p>
    <?php else: ?>
      <ul class="feed feed-simples">
        <?php foreach ($recados as $r): ?>
          <li>
            <span class="feed-data"><?= e(date('d/m', strtotime($r['criado_em']))) ?></span>
            <div>
              <strong><?= e($r['titulo']) ?></strong>
              <p><?= nl2br(e($r['mensagem'])) ?></p>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>
