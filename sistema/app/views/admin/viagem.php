<?php
$veic = veiculo_layout($v);
$diasFuturos = array_values(array_filter($diarias, fn($d) => $d['data'] >= $hoje));
$situacoes = [
  'disponivel' => 'Disponíveis nas datas',
  'sem_marcacao' => 'Sem marcação de agenda',
  'ja_escalado' => 'Já escalados nesta viagem/tour',
  'indisponivel' => 'Marcaram indisponibilidade',
  'conflito' => 'Escalados em outra viagem no mesmo dia',
];
$porSituacao = [];
foreach ($guiasParaAlocar as $g) {
  $porSituacao[$g['situacao']][] = $g;
}
$passos = [
  ['Dados e briefing', true, '/admin/viagens/' . (int) $v['id'] . '/editar'],
  ['Dias de trabalho', (bool) $diarias, '#dias'],
  ['Vagas por função', (bool) $vagas, '#vagas'],
  ['Lista de passageiros', $passageiros['total'] > 0, '/admin/viagens/' . (int) $v['id'] . '/passageiros'],
  ['Publicar', $v['status'] !== 'rascunho', '#acoes'],
  ['Escalar guias', (bool) $equipe, '#equipe'],
];
?>
<div class="pagina-topo">
  <div>
    <p class="sobretitulo mono"><?= e($v['codigo']) ?> · <?= e(TIPOS_VIAGEM[$v['tipo']] ?? '') ?></p>
    <h1><?= e($v['nome']) ?></h1>
    <p><?= e(($v['origem'] ?? '') . ' → ' . ($v['destino'] ?? '')) ?> · <?= e(periodo_viagem($v)) ?> · <?= (int) $v['com_pernoite'] ? 'com pernoite' : 'sem pernoite' ?></p>
  </div>
  <div class="acoes-topo" id="acoes">
    <?= selo(STATUS_VIAGEM, $v['status']) ?>
    <a href="/admin/viagens/<?= (int) $v['id'] ?>/editar" class="btn btn-contorno btn-p">Editar dados</a>
    <a href="/admin/viagens/nova?copiar=<?= (int) $v['id'] ?>" class="btn btn-contorno btn-p">Copiar viagem</a>
    <?php
    $botoes = match ($v['status']) {
      'rascunho' => [['publicar', 'Publicar', 'btn-primario', '']],
      'publicada' => [['concluir', 'Concluir', 'btn-contorno', 'Marcar como concluída?'], ['rascunho', 'Voltar a rascunho', 'btn-texto', 'Os guias deixam de ver até publicar de novo. Continuar?'],
        ['cancelar', 'Cancelar', 'btn-texto', 'Cancelar a viagem/tour? Convites e confirmações serão cancelados.']],
      'cancelada', 'concluida' => [['rascunho', 'Reabrir como rascunho', 'btn-texto', '']],
      default => [],
    };
    foreach ($botoes as [$acao, $rotulo, $classe, $confirmar]): ?>
      <form method="post" action="/admin/viagens/<?= (int) $v['id'] ?>/status" <?= $confirmar ? 'data-confirmar="' . e($confirmar) . '"' : '' ?>>
        <?= csrf_campo() ?><input type="hidden" name="acao" value="<?= e($acao) ?>">
        <button type="submit" class="btn <?= e($classe) ?> btn-p"><?= e($rotulo) ?></button>
      </form>
    <?php endforeach; ?>
  </div>
</div>

<ol class="passos" aria-label="Etapas">
  <?php foreach ($passos as $i => [$rotulo, $feito, $href]): ?>
    <li class="<?= $feito ? 'feito' : '' ?>"><a href="<?= e($href) ?>"><span><?= $feito ? '✓' : str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span><?= e($rotulo) ?></a></li>
  <?php endforeach; ?>
</ol>

<div class="colunas">
  <div>
    <section class="bloco" aria-labelledby="t-horarios">
      <div class="bloco-titulo"><h2 id="t-horarios">Horários</h2></div>
      <div class="horarios">
        <div><span class="rotulo">Apresentação</span><strong><?= e($v['horario_apresentacao'] ?: '—') ?></strong></div>
        <div><span class="rotulo">Saída</span><strong><?= e($v['horario_saida'] ?: '—') ?></strong></div>
        <div><span class="rotulo">Saída do destino</span><strong><?= e($v['horario_saida_destino'] ?: '—') ?></strong></div>
        <div><span class="rotulo">Chegada prevista</span><strong><?= e($v['previsao_chegada'] ?: '—') ?></strong></div>
      </div>
      <dl class="ficha">
        <?php if ($origens): ?>
          <dt>Origens</dt>
          <dd><ul class="origens-lista"><?php foreach ($origens as $o): ?><li><?= e($o['local']) ?><?= $o['horario'] ? '<b>' . e($o['horario']) . '</b>' : '' ?></li><?php endforeach; ?></ul></dd>
        <?php endif; ?>
        <?php if ($v['ponto_encontro']): ?><dt>Ponto de encontro</dt><dd><?= e($v['ponto_encontro']) ?></dd><?php endif; ?>
        <?php if ((int) $v['com_pernoite']): ?><dt>Hospedagem</dt><dd><?= e($v['hospedagem'] ?: 'Não informada') ?></dd><?php endif; ?>
        <dt>Veículo</dt>
        <dd><?= $veic ? e($veic['rotulo']) . ($veic['bloqueadas'] ? '<br><span class="texto-2">Bloqueadas: ' . e(implode(', ', $veic['bloqueadas'])) . '</span>' : '') : 'Sem mapa de poltronas' ?><?= $v['transporte'] ? '<br><span class="texto-2">' . e($v['transporte']) . '</span>' : '' ?></dd>
        <?php if ($v['cliente'] || $v['perfil_grupo']): ?><dt>Grupo</dt><dd><?= e(implode(' · ', array_filter([$v['cliente'], $v['perfil_grupo'], $v['qtd_passageiros'] ? $v['qtd_passageiros'] . ' passageiros previstos' : null]))) ?></dd><?php endif; ?>
      </dl>
    </section>

    <section class="bloco" id="dias" aria-labelledby="t-dias">
      <div class="bloco-titulo"><h2 id="t-dias">Dias de trabalho</h2></div>
      <?php if (!$diarias): ?>
        <p class="vazio">Nenhum dia cadastrado.</p>
      <?php else: ?>
        <div class="tabela-rolagem">
          <table class="tabela">
            <thead><tr><th>Data</th><th>Apresentação</th><th>Início–fim</th><th>Guias</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($diarias as $d): ?>
                <tr>
                  <td><strong><?= e(dia_semana_curto($d['data']) . ' ' . formatar_data($d['data'])) ?></strong><?= (int) $d['pernoite'] ? ' <span class="selo selo-info">pernoite</span>' : '' ?></td>
                  <td><?= e($d['horario_apresentacao'] ?: '—') ?></td>
                  <td><?= e(($d['hora_inicio'] ?: '—') . ' – ' . ($d['hora_fim'] ?: '—')) ?></td>
                  <td><?= (int) $d['confirmados'] ?> confirmado(s)<?= (int) $d['pendentes'] ? ' · ' . (int) $d['pendentes'] . ' pendente(s)' : '' ?></td>
                  <td class="acoes">
                    <form method="post" action="/admin/viagens/<?= (int) $v['id'] ?>/diarias/<?= (int) $d['id'] ?>/remover" data-confirmar="Remover o dia <?= e(formatar_data($d['data'])) ?>?">
                      <?= csrf_campo() ?><button type="submit" class="btn btn-texto btn-p">Remover</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
      <details class="adicionar">
        <summary>Adicionar dia de trabalho</summary>
        <form method="post" action="/admin/viagens/<?= (int) $v['id'] ?>/diarias" class="form">
          <?= csrf_campo() ?>
          <div class="grade-campos grade-4">
            <label class="campo"><span>Data</span><input type="date" name="data" required></label>
            <label class="campo"><span>Apresentação</span><input type="time" name="horario_apresentacao" value="<?= e($v['horario_apresentacao']) ?>"></label>
            <label class="campo"><span>Início</span><input type="time" name="hora_inicio" value="<?= e($v['horario_saida']) ?>"></label>
            <label class="campo"><span>Fim</span><input type="time" name="hora_fim" value="<?= e($v['previsao_chegada']) ?>"></label>
          </div>
          <label class="opcao"><input type="checkbox" name="pernoite" value="1"> <span>Com pernoite</span></label>
          <div class="form-rodape"><button type="submit" class="btn btn-primario btn-p">Adicionar dia</button></div>
        </form>
      </details>
    </section>

    <section class="bloco" id="vagas" aria-labelledby="t-vagas">
      <div class="bloco-titulo"><h2 id="t-vagas">Vagas por função</h2><a href="/admin/funcoes" class="link-p">Gerenciar funções</a></div>
      <?php if (!$vagas): ?>
        <p class="vazio">Nenhuma vaga definida. Diga quantos guias de cada função são necessários e o valor da diária.</p>
      <?php else: ?>
        <div class="tabela-rolagem">
          <table class="tabela">
            <thead><tr><th>Função</th><th>Vagas</th><th>Ocupadas</th><th>Diária</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($vagas as $vg): ?>
                <tr>
                  <td><strong><?= e($vg['funcao']) ?></strong></td>
                  <td><?= (int) $vg['vagas'] ?></td>
                  <td><?= (int) $vg['ocupadas'] ?></td>
                  <td><?= e(formatar_moeda($vg['valor_diaria'])) ?></td>
                  <td class="acoes">
                    <form method="post" action="/admin/viagens/<?= (int) $v['id'] ?>/vagas/<?= (int) $vg['id'] ?>/remover" data-confirmar="Remover a vaga de <?= e($vg['funcao']) ?>?">
                      <?= csrf_campo() ?><button type="submit" class="btn btn-texto btn-p">Remover</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
      <details class="adicionar" <?= $vagas ? '' : 'open' ?>>
        <summary>Adicionar ou alterar vaga</summary>
        <form method="post" action="/admin/viagens/<?= (int) $v['id'] ?>/vagas" class="form">
          <?= csrf_campo() ?>
          <div class="grade-campos grade-3">
            <label class="campo"><span>Função</span>
              <select name="funcao_id" required>
                <?php foreach ($funcoes as $f): ?><option value="<?= (int) $f['id'] ?>"><?= e($f['nome']) ?></option><?php endforeach; ?>
              </select></label>
            <label class="campo"><span>Quantidade</span><input type="number" name="vagas" min="1" max="99" value="1" required></label>
            <label class="campo"><span>Valor da diária (R$)</span><input type="text" name="valor_diaria" inputmode="decimal" placeholder="250,00" required></label>
          </div>
          <div class="form-rodape"><button type="submit" class="btn btn-primario btn-p">Salvar vaga</button></div>
        </form>
      </details>
    </section>
  </div>

  <aside>
    <section class="bloco" aria-labelledby="t-passageiros">
      <div class="bloco-titulo"><h2 id="t-passageiros">Passageiros</h2></div>
      <a class="destaque destaque-simples" href="/admin/viagens/<?= (int) $v['id'] ?>/passageiros">
        <div class="destaque-data"><strong><?= (int) $passageiros['total'] ?></strong><span><?= $veic ? 'de ' . (int) $veic['lugares'] : 'na lista' ?></span></div>
        <div>
          <h3>Lista e mapa do carro</h3>
          <p class="destaque-info"><?= $passageiros['ajustados'] ? '<span class="amarelo">' . (int) $passageiros['ajustados'] . ' com ajuste do guia</span> · ' : '' ?>check-in ao vivo, importação da planilha</p>
        </div>
      </a>
      <p class="espaco-topo"><a href="/admin/viagens/<?= (int) $v['id'] ?>/passageiros/modelo.xlsx" class="btn btn-contorno btn-p">Baixar planilha padrão</a>
        <a href="/admin/viagens/<?= (int) $v['id'] ?>/passageiros#importar" class="btn btn-texto btn-p">Importar planilha</a></p>
    </section>

    <section class="bloco" id="comentarios" aria-labelledby="t-comentarios">
      <div class="bloco-titulo"><h2 id="t-comentarios">Comentários dos guias</h2></div>
      <?php if (!$comentarios): ?>
        <p class="vazio">Nenhum comentário durante a viagem.</p>
      <?php else: ?>
        <ul class="comentarios-lista">
          <?php foreach ($comentarios as $cm): ?>
            <li><small><?= e(($cm['nome_social'] ?: $cm['nome']) . ' · ' . formatar_data_hora($cm['criado_em'])) ?></small><span class="texto-longo"><?= nl2br(e($cm['texto'])) ?></span></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <section class="bloco" aria-labelledby="t-contatos">
      <div class="bloco-titulo"><h2 id="t-contatos">Contatos</h2></div>
      <ul class="contatos">
        <?php foreach ([['Coordenação', $v['coordenador_nome'], $v['coordenador_telefone']], ['Motorista', $v['motorista_nome'], $v['motorista_telefone']],
                        ['Guia local', $v['guia_local_nome'], $v['guia_local_telefone']]] as [$papel, $nome, $tel]):
          if (!$nome && !$tel) continue; ?>
          <li><span class="rotulo"><?= e($papel) ?></span><strong><?= e($nome ?: '—') ?></strong><?php if ($tel): ?><a href="<?= e(link_whatsapp($tel)) ?>" target="_blank" rel="noopener noreferrer">WhatsApp <?= e(formatar_celular($tel)) ?></a><?php endif; ?></li>
        <?php endforeach; ?>
        <?php foreach ($contatos as $c): ?>
          <li><span class="rotulo"><?= e($c['papel']) ?></span><strong><?= e($c['nome'] ?: '—') ?></strong><?php if ($c['telefone']): ?><a href="<?= e(link_whatsapp($c['telefone'])) ?>" target="_blank" rel="noopener noreferrer">WhatsApp <?= e(formatar_celular($c['telefone'])) ?></a><?php endif; ?><?php if ($c['observacao']): ?><small><?= e($c['observacao']) ?></small><?php endif; ?></li>
        <?php endforeach; ?>
      </ul>
    </section>
  </aside>
</div>

<section class="bloco" id="equipe" aria-labelledby="t-equipe">
  <div class="bloco-titulo"><h2 id="t-equipe">Guias escalados</h2></div>
  <?php if (!$equipe): ?>
    <p class="vazio">Ninguém escalado ainda.</p>
  <?php else: ?>
    <div class="tabela-rolagem">
      <table class="tabela">
        <thead><tr><th>Guia</th><th>Função</th><th>Dias</th><th>Contato</th></tr></thead>
        <tbody>
          <?php foreach ($equipe as $item): $g = $item['guia']; ?>
            <tr>
              <td><strong><?= e($g['guia']) ?></strong><br><span class="texto-2 mono"><?= e($g['codigo']) ?></span></td>
              <td><?= e($g['funcao'] ?: '—') ?></td>
              <td>
                <ul class="dias-escala">
                  <?php foreach ($item['escalas'] as $s): ?>
                    <li><?= e(date('d/m', strtotime($s['data']))) ?> <?= selo(STATUS_ESCALA, $s['status']) ?>
                      <?php if (in_array($s['status'], ['convidado', 'confirmado'], true)): ?>
                        <form method="post" action="/admin/viagens/<?= (int) $v['id'] ?>/escalas/<?= (int) $s['id'] ?>/cancelar" data-confirmar="Cancelar a escala de <?= e($g['guia']) ?> em <?= e(formatar_data($s['data'])) ?>?">
                          <?= csrf_campo() ?><button type="submit" class="btn btn-texto btn-p">cancelar</button>
                        </form>
                      <?php endif; ?>
                      <?php if ($s['status'] === 'recusado' && $s['motivo_recusa']): ?><small class="texto-2">“<?= e($s['motivo_recusa']) ?>”</small><?php endif; ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </td>
              <td><?php if ($g['celular']): ?><a href="<?= e(link_whatsapp($g['celular'])) ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a><?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <div class="painel espaco-topo">
    <h3 class="subtitulo">Alocar guia</h3>
    <?php if ($v['status'] !== 'publicada'): ?>
      <p class="texto-2">Publique a viagem/tour para escalar guias.</p>
    <?php elseif (!$vagas || !$diasFuturos): ?>
      <p class="texto-2">Cadastre as vagas por função e pelo menos um dia de trabalho a partir de hoje.</p>
    <?php elseif (!$guiasParaAlocar): ?>
      <p class="texto-2">Nenhum guia aprovado ou pré-cadastrado ainda.</p>
    <?php else: ?>
      <form method="post" action="/admin/viagens/<?= (int) $v['id'] ?>/convidar" class="form">
        <?= csrf_campo() ?>
        <div class="grade-campos">
          <label class="campo campo-largo"><span>Guia</span>
            <select name="guia_id" required>
              <?php foreach ($situacoes as $chave => $rotulo): if (empty($porSituacao[$chave])) continue; ?>
                <optgroup label="<?= e($rotulo) ?>">
                  <?php foreach ($porSituacao[$chave] as $g): ?>
                    <option value="<?= (int) $g['id'] ?>"><?= e(($g['nome_social'] ?: $g['nome']) . ' · ' . $g['codigo']
                      . ($g['funcoes'] ? ' · ' . $g['funcoes'] : '') . ($g['idiomas'] ? ' · ' . $g['idiomas'] : '')
                      . ($g['conflitos'] ? ' · em ' . $g['conflitos'] : '')
                      . ($g['status'] === 'pre_cadastro' ? ' · pré-cadastro' : ($g['status'] === 'em_analise' ? ' · cadastro em análise' : ''))) ?></option>
                  <?php endforeach; ?>
                </optgroup>
              <?php endforeach; ?>
            </select></label>
          <label class="campo"><span>Função</span>
            <select name="vaga_id" required>
              <?php foreach ($vagas as $vg): ?>
                <option value="<?= (int) $vg['id'] ?>"><?= e($vg['funcao'] . ' · ' . formatar_moeda($vg['valor_diaria'])) ?> (<?= (int) $vg['ocupadas'] ?>/<?= (int) $vg['vagas'] ?>)</option>
              <?php endforeach; ?>
            </select></label>
        </div>
        <div class="campo">
          <span>Dias</span>
          <div class="opcoes-linha">
            <?php foreach ($diasFuturos as $d): ?>
              <label class="opcao"><input type="checkbox" name="diarias[]" value="<?= (int) $d['id'] ?>" checked> <span><?= e(dia_semana_curto($d['data']) . ' ' . date('d/m', strtotime($d['data']))) ?></span></label>
            <?php endforeach; ?>
          </div>
        </div>
        <label class="opcao"><input type="checkbox" name="alocar_direto" value="1"> <span>Já combinado com o guia: alocar direto, sem pedir confirmação</span></label>
        <div class="form-rodape">
          <button type="submit" class="btn btn-primario">Escalar guia</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</section>

<section class="bloco" aria-labelledby="t-relatorios">
  <div class="bloco-titulo"><h2 id="t-relatorios">Relatórios dos guias</h2></div>
  <?php if (!$relatorios): ?>
    <p class="vazio">Os relatórios aparecem aqui depois do último dia de trabalho.</p>
  <?php else: ?>
    <ul class="feed feed-simples">
      <?php foreach ($relatorios as $r): ?>
        <li>
          <span class="feed-data"><?= e(date('d/m H:i', strtotime($r['enviado_em']))) ?></span>
          <div>
            <strong><?= e($r['guia']) ?><?= $r['qtd_passageiros'] !== null ? ' · ' . (int) $r['qtd_passageiros'] . ' passageiros' : '' ?></strong>
            <p class="texto-longo"><?= nl2br(e($r['texto'])) ?></p>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
