<?php
$servico = e_servico($v);
$valorRotulo = e_empreitada($v) ? 'Valor fechado' : 'Diária';
$veic = $servico ? null : veiculo_layout($v);
$diasFuturos = array_values(array_filter($diarias, fn($d) => $d['data'] >= $hoje));
$situacoes = [
  'disponivel' => 'Disponíveis nas datas',
  'sem_marcacao' => 'Sem marcação de agenda',
  'ja_escalado' => 'Já escalados neste trabalho',
  'indisponivel' => 'Marcaram indisponibilidade',
  'conflito' => 'Escalados em outro trabalho no mesmo dia',
];
$porSituacao = [];
foreach ($guiasParaAlocar as $g) {
  $porSituacao[$g['situacao']][] = $g;
}
$passos = [
  ['Dados e briefing', true, '/admin/viagens/' . (int) $v['id'] . '/editar'],
  ['Dias de trabalho', (bool) $diarias, '#dias'],
  ['Vagas por função', (bool) $vagas, '#vagas'],
  $servico ? null : ['Lista de passageiros', $passageiros['total'] > 0, '/admin/viagens/' . (int) $v['id'] . '/passageiros'],
  ['Publicar', $v['status'] !== 'rascunho', '#acoes'],
  ['Escalar prestadores', (bool) $equipe, '#equipe'],
];
$passos = array_values(array_filter($passos));
?>
<div class="pagina-topo">
  <div>
    <p class="sobretitulo mono"><?= e($v['codigo']) ?> · <?= $servico ? 'Serviço · ' : '' ?><?= e(tipo_trabalho($v)) ?></p>
    <h1><?= e($v['nome']) ?></h1>
    <p><?= e($servico ? (string) $v['destino'] : ($v['origem'] ?? '') . ' → ' . ($v['destino'] ?? '')) ?> · <?= e(periodo_viagem($v)) ?> · <?= (int) $v['com_pernoite'] ? 'com pernoite' : 'sem pernoite' ?><?= $servico ? ' · ' . e(MODALIDADES[$v['modalidade']] ?? '') : '' ?></p>
  </div>
  <div class="acoes-topo" id="acoes">
    <?= selo(STATUS_VIAGEM, $v['status']) ?>
    <a href="/admin/viagens/<?= (int) $v['id'] ?>/editar" class="btn btn-contorno btn-p">Editar dados</a>
    <a href="<?= e(CATEGORIAS[categoria($v)][2]) ?>/nova?copiar=<?= (int) $v['id'] ?>" class="btn btn-contorno btn-p">Copiar <?= $servico ? 'serviço' : 'viagem' ?></a>
    <?php
    $botoes = match ($v['status']) {
      'rascunho' => [['publicar', 'Publicar', 'btn-primario', '']],
      'publicada' => [['concluir', 'Concluir', 'btn-contorno', 'Marcar como concluído?'], ['rascunho', 'Voltar a rascunho', 'btn-texto', 'Os prestadores deixam de ver até publicar de novo. Continuar?'],
        ['cancelar', 'Cancelar', 'btn-texto', 'Cancelar? Convites e confirmações serão cancelados.']],
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
      <div class="horarios<?= $servico ? ' horarios-3' : '' ?>">
        <div><span class="rotulo">Apresentação</span><strong><?= e($v['horario_apresentacao'] ?: '—') ?></strong></div>
        <?php if ($servico): ?>
          <div><span class="rotulo">Início</span><strong><?= e($v['horario_saida'] ?: '—') ?></strong></div>
          <div><span class="rotulo">Término</span><strong><?= e($v['previsao_chegada'] ?: '—') ?></strong></div>
        <?php else: ?>
          <div><span class="rotulo">Saída</span><strong><?= e($v['horario_saida'] ?: '—') ?></strong></div>
          <div><span class="rotulo">Saída do destino</span><strong><?= e($v['horario_saida_destino'] ?: '—') ?></strong></div>
          <div><span class="rotulo">Chegada prevista</span><strong><?= e($v['previsao_chegada'] ?: '—') ?></strong></div>
        <?php endif; ?>
      </div>
      <dl class="ficha">
        <?php if ($origens): ?>
          <dt>Origens</dt>
          <dd><ul class="origens-lista"><?php foreach ($origens as $o): ?><li><?= e($o['local']) ?><?= $o['horario'] ? '<b>' . e($o['horario']) . '</b>' : '' ?></li><?php endforeach; ?></ul></dd>
        <?php endif; ?>
        <?php if ($v['ponto_encontro']): ?><dt>Ponto de encontro</dt><dd><?= e($v['ponto_encontro']) ?></dd><?php endif; ?>
        <?php if ((int) $v['com_pernoite']): ?><dt>Hospedagem</dt><dd><?= e($v['hospedagem'] ?: 'Não informada') ?></dd><?php endif; ?>
        <?php if (!$servico): ?>
          <dt>Veículo</dt>
          <dd><?= $veic ? e($veic['rotulo']) . ($veic['bloqueadas'] ? '<br><span class="texto-2">Bloqueadas: ' . e(implode(', ', $veic['bloqueadas'])) . '</span>' : '') : 'Sem mapa de poltronas' ?><?= $v['transporte'] ? '<br><span class="texto-2">' . e($v['transporte']) . '</span>' : '' ?></dd>
        <?php endif; ?>
        <?php if ($v['cliente'] || $v['perfil_grupo']): ?><dt><?= $servico ? 'Público' : 'Grupo' ?></dt><dd><?= e(implode(' · ', array_filter([$v['cliente'], $v['perfil_grupo'], $v['qtd_passageiros'] ? $v['qtd_passageiros'] . ($servico ? ' pessoas previstas' : ' passageiros previstos') : null]))) ?></dd><?php endif; ?>
      </dl>
    </section>

    <section class="bloco" id="dias" aria-labelledby="t-dias">
      <div class="bloco-titulo"><h2 id="t-dias">Dias de trabalho</h2></div>
      <?php if (!$diarias): ?>
        <p class="vazio">Nenhum dia cadastrado.</p>
      <?php else: ?>
        <p class="texto-2">Ajuste os horários de cada dia e marque os dias sem trabalho ou de sobreaviso. Depois clique em "Salvar dias".</p>
        <form method="post" action="/admin/viagens/<?= (int) $v['id'] ?>/diarias/salvar" id="form-dias" data-dias><?= csrf_campo() ?></form>
        <?php if (count($diarias) > 1): ?>
          <p><button type="button" class="btn btn-contorno btn-p" data-replicar-horarios>Replicar horários do 1º dia para todos</button></p>
        <?php endif; ?>
        <ul class="dias-lista">
          <?php foreach ($diarias as $d): $n = 'dia[' . (int) $d['id'] . ']'; $sit = $d['situacao'] ?? 'trabalho'; ?>
            <li class="dia-cartao<?= $sit === 'folga' ? ' dia-folga' : '' ?>" data-dia>
              <div class="dia-cabeca">
                <strong><?= e(dia_semana_curto($d['data']) . ' ' . formatar_data($d['data'])) ?></strong>
                <span class="texto-2"><?= (int) $d['confirmados'] ?> confirmado(s)<?= (int) $d['pendentes'] ? ' · ' . (int) $d['pendentes'] . ' pendente(s)' : '' ?></span>
                <form method="post" action="/admin/viagens/<?= (int) $v['id'] ?>/diarias/<?= (int) $d['id'] ?>/remover" data-confirmar="Remover o dia <?= e(formatar_data($d['data'])) ?>?">
                  <?= csrf_campo() ?><button type="submit" class="btn btn-texto btn-p">Remover</button>
                </form>
              </div>
              <div class="dia-campos">
                <label class="campo"><span>Situação</span><select name="<?= $n ?>[situacao]" form="form-dias" data-situacao>
                  <?php foreach (SITUACOES_DIA as $chave => [$rotulo]): ?>
                    <option value="<?= e($chave) ?>" <?= $sit === $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option>
                  <?php endforeach; ?>
                </select></label>
                <label class="campo"><span>Apresentação</span><input type="time" name="<?= $n ?>[horario_apresentacao]" value="<?= e((string) $d['horario_apresentacao']) ?>" form="form-dias" data-hora="apresentacao"></label>
                <label class="campo"><span>Início</span><input type="time" name="<?= $n ?>[hora_inicio]" value="<?= e((string) $d['hora_inicio']) ?>" form="form-dias" data-hora="inicio"></label>
                <label class="campo"><span>Término</span><input type="time" name="<?= $n ?>[hora_fim]" value="<?= e((string) $d['hora_fim']) ?>" form="form-dias" data-hora="fim"></label>
              </div>
              <label class="opcao"><input type="checkbox" name="<?= $n ?>[pernoite]" value="1" form="form-dias" <?= (int) $d['pernoite'] ? 'checked' : '' ?>> <span>Pernoite</span></label>
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="form-rodape"><button type="submit" form="form-dias" class="btn btn-primario btn-p">Salvar dias</button></div>
      <?php endif; ?>
      <details class="adicionar">
        <summary>Adicionar dia de trabalho</summary>
        <form method="post" action="/admin/viagens/<?= (int) $v['id'] ?>/diarias" class="form">
          <?= csrf_campo() ?>
          <div class="grade-campos grade-4">
            <label class="campo"><span>Data</span><input type="date" name="data" required></label>
            <label class="campo"><span>Apresentação</span><input type="time" name="horario_apresentacao" value="<?= e($v['horario_apresentacao']) ?>"></label>
            <label class="campo"><span>Início</span><input type="time" name="hora_inicio" value="<?= e($v['horario_saida']) ?>"></label>
            <label class="campo"><span>Término</span><input type="time" name="hora_fim" value="<?= e($v['previsao_chegada']) ?>"></label>
            <label class="campo"><span>Situação</span><select name="situacao">
              <?php foreach (SITUACOES_DIA as $chave => [$rotulo]): ?><option value="<?= e($chave) ?>"><?= e($rotulo) ?></option><?php endforeach; ?>
            </select></label>
          </div>
          <label class="opcao"><input type="checkbox" name="pernoite" value="1"> <span>Com pernoite</span></label>
          <div class="form-rodape"><button type="submit" class="btn btn-primario btn-p">Adicionar dia</button></div>
        </form>
      </details>
    </section>

    <section class="bloco" id="vagas" aria-labelledby="t-vagas">
      <div class="bloco-titulo"><h2 id="t-vagas">Vagas por função</h2><a href="/admin/funcoes" class="link-p">Gerenciar funções</a></div>
      <?php if (!$vagas): ?>
        <p class="vazio">Nenhuma vaga definida. Diga quantas pessoas de cada função são necessárias e o valor <?= e(unidade_valor($v)) ?>.</p>
      <?php else: ?>
        <div class="tabela-rolagem">
          <table class="tabela">
            <thead><tr><th>Função</th><th>Vagas</th><th>Ocupadas</th><th><?= e($valorRotulo) ?></th><th></th></tr></thead>
            <tbody>
              <?php foreach ($vagas as $vg): ?>
                <tr>
                  <td><strong><?= e($vg['funcao']) ?></strong></td>
                  <td><?= (int) $vg['vagas'] ?></td>
                  <td><?= (int) $vg['ocupadas'] ?></td>
                  <td><?= (float) $vg['valor_diaria'] > 0 ? e(formatar_moeda($vg['valor_diaria'])) : 'Sem custo' ?></td>
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
            <label class="campo"><span><?= e_empreitada($v) ? 'Valor fechado do serviço (R$)' : 'Valor da diária (R$)' ?></span><input type="text" name="valor_diaria" inputmode="decimal" placeholder="250,00"></label>
          </div>
          <p class="texto-2">Funções sem custo, como Coordenador(a), podem ficar sem valor.</p>
          <div class="form-rodape"><button type="submit" class="btn btn-primario btn-p">Salvar vaga</button></div>
        </form>
      </details>
    </section>
  </div>

  <aside>
    <?php if (!$servico): ?>
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
    <?php endif; ?>

    <section class="bloco" id="comentarios" aria-labelledby="t-comentarios">
      <div class="bloco-titulo"><h2 id="t-comentarios">Comentários dos prestadores</h2></div>
      <?php if (!$comentarios): ?>
        <p class="vazio">Nenhum comentário durante o trabalho.</p>
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
  <div class="bloco-titulo"><h2 id="t-equipe">Prestadores escalados</h2></div>
  <?php if (!$equipe): ?>
    <p class="vazio">Ninguém escalado ainda.</p>
  <?php else: ?>
    <div class="tabela-rolagem">
      <table class="tabela">
        <thead><tr><th>Prestador</th><th>Função</th><th>Dias</th><th>Contato</th></tr></thead>
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
    <h3 class="subtitulo">Alocar prestador</h3>
    <?php if ($v['status'] !== 'publicada'): ?>
      <p class="texto-2">Publique para escalar prestadores.</p>
    <?php elseif (!$vagas || !$diasFuturos): ?>
      <p class="texto-2">Cadastre as vagas por função e pelo menos um dia de trabalho a partir de hoje.</p>
    <?php elseif (!$guiasParaAlocar): ?>
      <p class="texto-2">Nenhum prestador aprovado ou pré-cadastrado ainda.</p>
    <?php else: ?>
      <form method="post" action="/admin/viagens/<?= (int) $v['id'] ?>/convidar" class="form">
        <?= csrf_campo() ?>
        <div class="grade-campos">
          <label class="campo campo-largo"><span>Prestador</span>
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
                <option value="<?= (int) $vg['id'] ?>"><?= e($vg['funcao'] . ' · ' . ((float) $vg['valor_diaria'] > 0 ? formatar_moeda($vg['valor_diaria']) : 'sem custo')) ?> (<?= (int) $vg['ocupadas'] ?>/<?= (int) $vg['vagas'] ?>)</option>
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
        <?php if (e_empreitada($v)): ?><p class="texto-2">Valor fechado: o valor da vaga entra uma vez só para o prestador, no primeiro dia escolhido.</p><?php endif; ?>
        <label class="opcao"><input type="checkbox" name="alocar_direto" value="1"> <span>Já combinado com o prestador: alocar direto, sem pedir confirmação</span></label>
        <div class="form-rodape">
          <button type="submit" class="btn btn-primario">Escalar prestador</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</section>

<section class="bloco" aria-labelledby="t-relatorios">
  <div class="bloco-titulo"><h2 id="t-relatorios">Relatórios dos prestadores</h2></div>
  <?php if (!$relatorios): ?>
    <p class="vazio">Os relatórios aparecem aqui depois do último dia de trabalho.</p>
  <?php else: ?>
    <ul class="feed feed-simples">
      <?php foreach ($relatorios as $r): ?>
        <li>
          <span class="feed-data"><?= e(date('d/m H:i', strtotime($r['enviado_em']))) ?></span>
          <div>
            <strong><?= e($r['guia']) ?><?= $r['qtd_passageiros'] !== null ? ' · ' . (int) $r['qtd_passageiros'] . ($servico ? ' pessoas atendidas' : ' passageiros') : '' ?></strong>
            <p class="texto-longo"><?= nl2br(e($r['texto'])) ?></p>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
