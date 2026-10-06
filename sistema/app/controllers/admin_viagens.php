<?php
// Viagens/Tours no Painel ADM. Fluxo da operação:
// 1. cadastrar (dados do grupo, logística, roteiro) -> 2. dias de trabalho -> 3. vagas por função e valor da diária
// -> 4. publicar -> 5. convidar guias -> 6. acompanhar confirmações e relatórios -> 7. concluir.

const PAPEIS_VIAGENS = ['coordenador'];
const LIMITE_DIAS_AUTOMATICOS = 60;

function adm_viagem_carregar(int $id): array {
  return um('SELECT * FROM viagens WHERE id = ?', [$id]) ?? abortar(404, 'Viagem/tour não encontrado.');
}

/** Lê e valida o formulário da viagem/tour. Retorna [dados, erros, contatos extras]. */
function adm_viagem_ler_form(?int $id = null): array {
  $txt = fn(string $c, int $max) => ($v = mb_substr(entrada($c), 0, $max)) === '' ? null : $v;
  $longo = fn(string $c) => ($v = trim((string) ($_POST[$c] ?? ''))) === '' ? null : mb_substr($v, 0, 10000);
  $tel = fn(string $c) => substr(so_digitos(entrada($c)), 0, 13) ?: null;
  $veiculo = isset(VEICULOS[entrada('veiculo')]) ? entrada('veiculo') : null;
  $d = [
    // Código definido pela equipe (texto livre, ex.: TR.R.20261010.1); só não pode repetir.
    'codigo' => mb_substr(preg_replace('/\s+/', ' ', entrada('codigo')), 0, 40),
    'nome' => mb_substr(entrada('nome'), 0, 200),
    'tipo' => entrada('tipo'),
    'cliente' => $txt('cliente', 200),
    'data_inicio' => entrada('data_inicio'),
    'data_fim' => entrada('data_fim'),
    'com_pernoite' => entrada('com_pernoite') === '1' ? 1 : 0,
    'hospedagem' => $txt('hospedagem', 255),
    'origem' => null, // preenchida com a primeira das origens abaixo
    'destino' => $txt('destino', 160),
    'horario_apresentacao' => entrada('horario_apresentacao'),
    'horario_saida' => entrada('horario_saida'),
    'horario_saida_destino' => entrada('horario_saida_destino'),
    'previsao_chegada' => entrada('previsao_chegada'),
    'ponto_encontro' => $txt('ponto_encontro', 255),
    'ponto_encontro_mapa' => $txt('ponto_encontro_mapa', 500),
    'veiculo' => $veiculo,
    'veiculo_descricao' => $veiculo === 'outro' ? $txt('veiculo_descricao', 120) : null,
    'lugares' => $veiculo === 'outro' || entrada('lugares') !== '' ? (entrada('lugares') === '' ? null : max(1, min(90, (int) entrada('lugares')))) : null,
    'lugares_piso_inferior' => $veiculo === 'dd_64' && entrada('lugares_piso_inferior') !== '' ? max(1, min(40, (int) entrada('lugares_piso_inferior'))) : null,
    'poltronas_bloqueadas' => null,
    'transporte' => $txt('transporte', 200),
    'coordenador_nome' => $txt('coordenador_nome', 120),
    'coordenador_telefone' => $tel('coordenador_telefone'),
    'motorista_nome' => $txt('motorista_nome', 120),
    'motorista_telefone' => $tel('motorista_telefone'),
    'guia_local_nome' => $txt('guia_local_nome', 120),
    'guia_local_telefone' => $tel('guia_local_telefone'),
    'perfil_grupo' => $txt('perfil_grupo', 120),
    'idioma_grupo' => $txt('idioma_grupo', 40),
    'qtd_passageiros' => entrada('qtd_passageiros') === '' ? null : max(0, min(9999, (int) entrada('qtd_passageiros'))),
    'uniforme' => $txt('uniforme', 200),
    'alimentacao' => $txt('alimentacao', 200),
    'roteiro' => $longo('roteiro'),
    'regras' => $longo('regras'),
    'observacoes' => $longo('observacoes'),
    'instrucoes_nf' => $longo('instrucoes_nf'),
    'prazo_nf_dias_uteis' => max(0, min(30, (int) entrada('prazo_nf_dias_uteis', '3'))),
    'prazo_pagamento_dias' => max(0, min(120, (int) entrada('prazo_pagamento_dias', '30'))),
  ];
  if ($veiculo) {
    $capacidade = (int) ($d['lugares'] ?: VEICULOS[$veiculo][1]);
    $bloqueadas = ler_poltronas_bloqueadas(entrada('poltronas_bloqueadas'), max(1, $capacidade));
    $d['poltronas_bloqueadas'] = $bloqueadas ? implode(', ', $bloqueadas) : null;
  }

  // Origens (pontos de embarque): uma por linha, com horário de saída opcional; linhas vazias são ignoradas.
  $origens = [];
  $horarioOrigemInvalido = false;
  foreach ((array) ($_POST['origens'] ?? []) as $i => $local) {
    $local = mb_substr(trim(preg_replace('/\s+/', ' ', (string) $local)), 0, 160);
    $horario = trim((string) ($_POST['origens_horario'][$i] ?? ''));
    if ($local === '') {
      continue;
    }
    if ($horario !== '' && !hora_valida($horario)) {
      $horarioOrigemInvalido = true;
    }
    $origens[] = ['local' => $local, 'horario' => $horario ?: null];
  }
  $d['origem'] = $origens[0]['local'] ?? null;

  // Outros contatos: linhas do formulário (papel, nome, telefone, observação); linhas vazias são ignoradas.
  $contatos = [];
  foreach ((array) ($_POST['contato_papel'] ?? []) as $i => $papel) {
    $linha = [
      'papel' => mb_substr(trim((string) $papel), 0, 60),
      'nome' => mb_substr(trim((string) ($_POST['contato_nome'][$i] ?? '')), 0, 120) ?: null,
      'telefone' => substr(so_digitos((string) ($_POST['contato_telefone'][$i] ?? '')), 0, 13) ?: null,
      'observacao' => mb_substr(trim((string) ($_POST['contato_observacao'][$i] ?? '')), 0, 255) ?: null,
    ];
    if ($linha['nome'] || $linha['telefone']) {
      $linha['papel'] = $linha['papel'] ?: 'Contato';
      $contatos[] = $linha;
    }
  }

  $erros = [];
  if ($d['codigo'] === '') {
    $erros[] = 'Informe o código da viagem/tour.';
  } elseif (valor('SELECT 1 FROM viagens WHERE codigo = ? AND id <> ?', [$d['codigo'], $id ?? 0])) {
    $erros[] = 'Já existe uma viagem/tour com o código ' . $d['codigo'] . '.';
  }
  if (mb_strlen($d['nome']) < 3) {
    $erros[] = 'Dê um nome à viagem/tour.';
  }
  if (!isset(TIPOS_VIAGEM[$d['tipo']])) {
    $erros[] = 'Escolha o tipo.';
  }
  if (!data_valida($d['data_inicio']) || !data_valida($d['data_fim'])) {
    $erros[] = 'Informe as datas de início e fim.';
  } elseif ($d['data_fim'] < $d['data_inicio']) {
    $erros[] = 'A data de fim não pode ser antes do início.';
  }
  if (!$d['origem'] || !$d['destino']) {
    $erros[] = 'Informe pelo menos uma origem e o destino.';
  }
  if ($horarioOrigemInvalido) {
    $erros[] = 'Confira os horários das origens (formato 07:30).';
  }
  foreach (['horario_apresentacao' => 'apresentação', 'horario_saida' => 'saída', 'horario_saida_destino' => 'saída do destino',
            'previsao_chegada' => 'previsão de chegada'] as $campo => $rotulo) {
    if (!hora_valida($d[$campo])) {
      $erros[] = "Informe o horário de $rotulo.";
    }
  }
  if ($d['com_pernoite'] && $d['data_fim'] === $d['data_inicio']) {
    $erros[] = 'Viagem com pernoite precisa terminar depois do dia de início.';
  }
  if ($veiculo === 'outro' && (!$d['veiculo_descricao'] || !$d['lugares'])) {
    $erros[] = 'Para "Outro tipo" de veículo, descreva o veículo e informe a quantidade de lugares.';
  }
  if ($d['ponto_encontro_mapa'] && !preg_match('#^https://#i', $d['ponto_encontro_mapa'])) {
    $erros[] = 'O link do mapa precisa começar com https://';
  }
  return [$d, $erros, $contatos, $origens];
}

function adm_viagem_salvar_origens(int $viagemId, array $origens): void {
  q('DELETE FROM viagem_origens WHERE viagem_id = ?', [$viagemId]);
  foreach (array_values($origens) as $i => $o) {
    inserir('viagem_origens', $o + ['viagem_id' => $viagemId, 'ordem' => $i]);
  }
}

function viagem_origens(int $viagemId): array {
  return todos('SELECT local, horario FROM viagem_origens WHERE viagem_id = ? ORDER BY ordem', [$viagemId]);
}

function adm_viagem_salvar_contatos(int $viagemId, array $contatos): void {
  q('DELETE FROM viagem_contatos WHERE viagem_id = ?', [$viagemId]);
  foreach (array_values($contatos) as $i => $c) {
    inserir('viagem_contatos', $c + ['viagem_id' => $viagemId, 'ordem' => $i]);
  }
}

function adm_viagens(): void {
  $a = exigir_admin(PAPEIS_VIAGENS);
  $filtro = (string) ($_GET['status'] ?? 'ativas');
  $busca = trim((string) ($_GET['q'] ?? ''));
  $where = [];
  $params = [];
  if ($filtro === 'ativas') {
    $where[] = "v.status IN ('rascunho','publicada')";
  } elseif (isset(STATUS_VIAGEM[$filtro])) {
    $where[] = 'v.status = ?';
    $params[] = $filtro;
  }
  if ($busca !== '') {
    $where[] = '(v.codigo LIKE ? OR v.nome LIKE ? OR v.cliente LIKE ?)';
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
  }
  $viagens = todos('SELECT v.*,
      (SELECT COUNT(*) FROM diarias d WHERE d.viagem_id = v.id) AS dias,
      (SELECT COALESCE(SUM(vagas), 0) FROM viagem_vagas vv WHERE vv.viagem_id = v.id) AS vagas,
      (SELECT COUNT(DISTINCT s.guia_id) FROM escalas s JOIN diarias d ON d.id = s.diaria_id
        WHERE d.viagem_id = v.id AND s.status NOT IN (\'recusado\',\'cancelado\')) AS guias
    FROM viagens v' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . '
    ORDER BY CASE WHEN v.status IN (\'rascunho\',\'publicada\') THEN 0 ELSE 1 END, v.data_inicio', $params);
  exibir('admin/viagens', [
    'titulo' => 'Viagens/Tours',
    'menu' => 'viagens',
    'a' => $a,
    'viagens' => $viagens,
    'filtro' => $filtro,
    'busca' => $busca,
  ], 'admin');
}

function adm_viagem_nova(): void {
  $a = exigir_admin(PAPEIS_VIAGENS);
  exibir('admin/viagem-form', [
    'titulo' => 'Nova viagem/tour',
    'menu' => 'viagens',
    'a' => $a,
    'v' => [
      'tipo' => 'excursao',
      'veiculo' => 'onibus_46',
      'prazo_nf_dias_uteis' => configuracao('nf_prazo_dias_uteis_padrao', '3'),
      'prazo_pagamento_dias' => configuracao('pagamento_prazo_dias_padrao', '30'),
    ],
    'origens' => [],
    'contatos' => [],
    'nova' => true,
  ], 'admin');
}

function adm_viagem_criar(): void {
  $a = exigir_admin(PAPEIS_VIAGENS);
  [$d, $erros, $contatos, $origens] = adm_viagem_ler_form();
  $gerar = isset($_POST['gerar_diarias']);
  $totalDias = !$erros ? (int) ((strtotime($d['data_fim']) - strtotime($d['data_inicio'])) / 86400) + 1 : 0;
  if ($gerar && $totalDias > LIMITE_DIAS_AUTOMATICOS) {
    $erros[] = 'Para períodos acima de ' . LIMITE_DIAS_AUTOMATICOS . ' dias, cadastre os dias de trabalho manualmente.';
  }
  if ($erros) {
    guardar_antigo($_POST);
    flash('erro', implode(' ', $erros));
    redirecionar('/admin/viagens/nova');
  }

  $id = transacao(function () use ($d, $a, $gerar, $totalDias, $contatos, $origens) {
    $id = inserir('viagens', $d + ['status' => 'rascunho', 'criado_por' => $a['id'], 'criado_em' => agora()]);
    adm_viagem_salvar_contatos($id, $contatos);
    adm_viagem_salvar_origens($id, $origens);
    if ($gerar) {
      // Um dia de trabalho por data: apresentação e saída no primeiro dia, chegada prevista no último.
      for ($i = 0; $i < $totalDias; $i++) {
        $primeiro = $i === 0;
        $ultimo = $i === $totalDias - 1;
        inserir('diarias', [
          'viagem_id' => $id,
          'data' => date('Y-m-d', strtotime($d['data_inicio'] . " +$i days")),
          'horario_apresentacao' => $primeiro ? $d['horario_apresentacao'] : null,
          'hora_inicio' => $primeiro ? $d['horario_saida'] : null,
          'hora_fim' => $ultimo ? $d['previsao_chegada'] : null,
          'pernoite' => $d['com_pernoite'] && !$ultimo ? 1 : 0,
        ]);
      }
    }
    return $id;
  });
  auditar('viagem_criada', 'viagem', $id, ['codigo' => $d['codigo']]);
  limpar_antigo();
  flash('sucesso', 'Viagem/tour ' . $d['codigo'] . ' criado como rascunho. Agora defina as vagas por função, a lista de passageiros e publique.');
  redirecionar("/admin/viagens/$id");
}

/**
 * Situação de cada guia aprovado para os dias desta viagem/tour, para a alocação:
 * disponivel (marcou disponibilidade), conflito (escalado em outra viagem no mesmo dia),
 * indisponivel (marcou que não pode), sem_marcacao, ou ja_escalado (já está nesta).
 */
function adm_viagem_guias_para_alocar(array $v, array $datas): array {
  $guias = todos("SELECT g.id, g.nome, g.nome_social, g.codigo, g.celular,
      (SELECT " . sql_lista('f.nome') . " FROM guia_funcoes gf JOIN funcoes f ON f.id = gf.funcao_id WHERE gf.guia_id = g.id) AS funcoes,
      (SELECT " . sql_lista('gi.idioma') . " FROM guia_idiomas gi WHERE gi.guia_id = g.id) AS idiomas
    FROM guias g WHERE g.status = 'aprovado' ORDER BY g.nome");
  if (!$guias || !$datas) {
    foreach ($guias as &$g) {
      $g['situacao'] = 'sem_marcacao';
      $g['conflitos'] = '';
    }
    unset($g);
    return $guias;
  }
  $marcas = implode(',', array_fill(0, count($datas), '?'));
  $ativas = "('convidado','" . implode("','", ESCALAS_ASSUMIDAS) . "')";
  $conflitos = [];
  foreach (todos("SELECT s.guia_id, v2.codigo FROM escalas s JOIN diarias d ON d.id = s.diaria_id JOIN viagens v2 ON v2.id = d.viagem_id
      WHERE d.data IN ($marcas) AND d.viagem_id <> ? AND s.status IN $ativas", array_merge($datas, [$v['id']])) as $c) {
    $conflitos[$c['guia_id']][$c['codigo']] = true;
  }
  $nesta = array_flip(array_column(todos("SELECT DISTINCT s.guia_id FROM escalas s JOIN diarias d ON d.id = s.diaria_id
      WHERE d.viagem_id = ? AND s.status IN $ativas", [$v['id']]), 'guia_id'));
  $disp = [];
  foreach (todos("SELECT guia_id, tipo FROM disponibilidade WHERE data IN ($marcas)", $datas) as $m) {
    $disp[$m['guia_id']][$m['tipo']] = true;
  }
  $ordem = ['disponivel' => 0, 'sem_marcacao' => 1, 'ja_escalado' => 2, 'indisponivel' => 3, 'conflito' => 4];
  foreach ($guias as &$g) {
    $id = (int) $g['id'];
    $g['conflitos'] = isset($conflitos[$id]) ? implode(', ', array_keys($conflitos[$id])) : '';
    $g['situacao'] = match (true) {
      isset($nesta[$id]) => 'ja_escalado',
      $g['conflitos'] !== '' => 'conflito',
      isset($disp[$id]['indisponivel']) => 'indisponivel',
      isset($disp[$id]['disponivel']) => 'disponivel',
      default => 'sem_marcacao',
    };
  }
  unset($g);
  usort($guias, fn($x, $y) => [$ordem[$x['situacao']], $x['nome']] <=> [$ordem[$y['situacao']], $y['nome']]);
  return $guias;
}

function adm_viagem(int $id): void {
  $a = exigir_admin(PAPEIS_VIAGENS);
  $v = adm_viagem_carregar($id);
  $diarias = todos("SELECT d.*,
      (SELECT COUNT(*) FROM escalas s WHERE s.diaria_id = d.id AND s.status IN ('" . implode("','", ESCALAS_ASSUMIDAS) . "')) AS confirmados,
      (SELECT COUNT(*) FROM escalas s WHERE s.diaria_id = d.id AND s.status = 'convidado') AS pendentes
    FROM diarias d WHERE d.viagem_id = ? ORDER BY d.data", [$id]);
  $vagas = todos('SELECT vv.*, f.nome AS funcao,
      (SELECT COUNT(DISTINCT s.guia_id) FROM escalas s WHERE s.viagem_vaga_id = vv.id AND s.status NOT IN (\'recusado\',\'cancelado\',\'falta\')) AS ocupadas
    FROM viagem_vagas vv JOIN funcoes f ON f.id = vv.funcao_id WHERE vv.viagem_id = ? ORDER BY f.ordem', [$id]);

  $escalas = todos('SELECT s.*, d.data, g.nome AS guia, g.codigo, g.celular, f.nome AS funcao
    FROM escalas s JOIN diarias d ON d.id = s.diaria_id JOIN guias g ON g.id = s.guia_id
    LEFT JOIN viagem_vagas vv ON vv.id = s.viagem_vaga_id LEFT JOIN funcoes f ON f.id = vv.funcao_id
    WHERE d.viagem_id = ? ORDER BY g.nome, d.data', [$id]);
  $equipe = [];
  foreach ($escalas as $s) {
    $equipe[$s['guia_id']]['guia'] = $s;
    $equipe[$s['guia_id']]['escalas'][] = $s;
  }
  $datasFuturas = array_values(array_filter(array_column($diarias, 'data'), fn($d) => $d >= hoje()));

  exibir('admin/viagem', [
    'titulo' => $v['codigo'] . ' · ' . $v['nome'],
    'menu' => 'viagens',
    'a' => $a,
    'v' => $v,
    'diarias' => $diarias,
    'vagas' => $vagas,
    'equipe' => $equipe,
    'origens' => viagem_origens($id),
    'contatos' => todos('SELECT * FROM viagem_contatos WHERE viagem_id = ? ORDER BY ordem', [$id]),
    'passageiros' => [
      'total' => (int) valor("SELECT COUNT(*) FROM passageiros WHERE viagem_id = ? AND status = 'ativo'", [$id]),
      'ajustados' => (int) valor("SELECT COUNT(*) FROM passageiros WHERE viagem_id = ? AND status = 'ativo'
        AND (ajustes_guia IS NOT NULL OR criado_por_tipo = 'guia')", [$id]),
    ],
    'relatorios' => todos("SELECT e.*, g.nome AS guia FROM envios e JOIN guias g ON g.id = e.guia_id
      WHERE e.viagem_id = ? AND e.tipo = 'relatorio' ORDER BY e.enviado_em DESC", [$id]),
    'funcoes' => todos('SELECT * FROM funcoes WHERE ativo = 1 ORDER BY ordem, nome'),
    'guiasParaAlocar' => adm_viagem_guias_para_alocar($v, $datasFuturas),
    'hoje' => hoje(),
  ], 'admin');
}

function adm_viagem_editar(int $id): void {
  $a = exigir_admin(PAPEIS_VIAGENS);
  $v = adm_viagem_carregar($id);
  exibir('admin/viagem-form', [
    'titulo' => 'Editar ' . $v['codigo'],
    'menu' => 'viagens',
    'a' => $a,
    'v' => $v,
    'origens' => viagem_origens($id),
    'contatos' => todos('SELECT * FROM viagem_contatos WHERE viagem_id = ? ORDER BY ordem', [$id]),
    'nova' => false,
  ], 'admin');
}

function adm_viagem_salvar(int $id): void {
  exigir_admin(PAPEIS_VIAGENS);
  adm_viagem_carregar($id);
  [$d, $erros, $contatos, $origens] = adm_viagem_ler_form($id);
  if (!$erros) {
    $foraDoPeriodo = valor('SELECT COUNT(*) FROM diarias WHERE viagem_id = ? AND (data < ? OR data > ?)', [$id, $d['data_inicio'], $d['data_fim']]);
    if ($foraDoPeriodo) {
      $erros[] = 'Há dias de trabalho fora do novo período. Remova-os antes de mudar as datas.';
    }
  }
  if ($erros) {
    guardar_antigo($_POST);
    flash('erro', implode(' ', $erros));
    redirecionar("/admin/viagens/$id/editar");
  }
  transacao(function () use ($id, $d, $contatos, $origens) {
    atualizar('viagens', $d + ['atualizado_em' => agora()], 'id = ?', [$id]);
    adm_viagem_salvar_contatos($id, $contatos);
    adm_viagem_salvar_origens($id, $origens);
  });
  auditar('viagem_editada', 'viagem', $id);
  limpar_antigo();
  flash('sucesso', 'Dados salvos.');
  redirecionar("/admin/viagens/$id");
}

function adm_viagem_status(int $id): void {
  exigir_admin(PAPEIS_VIAGENS);
  $v = adm_viagem_carregar($id);
  $acao = entrada('acao');
  $destinos = ['publicar' => 'publicada', 'concluir' => 'concluida', 'cancelar' => 'cancelada', 'rascunho' => 'rascunho'];
  if (!isset($destinos[$acao])) {
    abortar(400);
  }
  if ($acao === 'publicar') {
    $faltas = [];
    if (!valor('SELECT 1 FROM diarias WHERE viagem_id = ?', [$id])) {
      $faltas[] = 'pelo menos um dia de trabalho';
    }
    if (!valor('SELECT 1 FROM viagem_vagas WHERE viagem_id = ?', [$id])) {
      $faltas[] = 'pelo menos uma vaga por função';
    }
    if ($faltas) {
      flash('erro', 'Para publicar, cadastre ' . implode(' e ', $faltas) . '.');
      redirecionar("/admin/viagens/$id");
    }
  }
  transacao(function () use ($id, $acao, $destinos) {
    atualizar('viagens', ['status' => $destinos[$acao], 'atualizado_em' => agora()], 'id = ?', [$id]);
    if ($acao === 'cancelar') {
      q("UPDATE escalas SET status = 'cancelado', atualizado_em = ? WHERE status IN ('convidado','confirmado')
        AND diaria_id IN (SELECT id FROM diarias WHERE viagem_id = ?)", [agora(), $id]);
    }
  });
  auditar('viagem_' . $destinos[$acao], 'viagem', $id);
  $mensagens = [
    'publicar' => 'Publicada. Agora convide os guias.',
    'concluir' => 'Viagem/tour concluído.',
    'cancelar' => 'Viagem/tour cancelado. Convites e confirmações foram cancelados.',
    'rascunho' => 'Voltou para rascunho: os guias deixam de vê-lo até publicar de novo.',
  ];
  flash('sucesso', $mensagens[$acao]);
  redirecionar("/admin/viagens/$id");
}

function adm_viagem_diaria_criar(int $id): void {
  exigir_admin(PAPEIS_VIAGENS);
  $v = adm_viagem_carregar($id);
  $data = entrada('data');
  $horas = ['horario_apresentacao' => entrada('horario_apresentacao'), 'hora_inicio' => entrada('hora_inicio'), 'hora_fim' => entrada('hora_fim')];
  if (!data_valida($data)) {
    flash('erro', 'Informe a data do dia de trabalho.');
    redirecionar("/admin/viagens/$id#dias");
  }
  foreach ($horas as $h) {
    if ($h !== '' && !hora_valida($h)) {
      flash('erro', 'Confira os horários (formato 08:30).');
      redirecionar("/admin/viagens/$id#dias");
    }
  }
  if (valor('SELECT 1 FROM diarias WHERE viagem_id = ? AND data = ?', [$id, $data])) {
    flash('erro', 'Já existe um dia de trabalho nesta data.');
    redirecionar("/admin/viagens/$id#dias");
  }
  inserir('diarias', array_map(fn($h) => $h ?: null, $horas) + [
    'viagem_id' => $id,
    'data' => $data,
    'pernoite' => isset($_POST['pernoite']) ? 1 : 0,
    'observacao' => mb_substr(entrada('observacao'), 0, 255) ?: null,
  ]);
  // O período da viagem acompanha os dias cadastrados.
  atualizar('viagens', [
    'data_inicio' => min($v['data_inicio'], $data),
    'data_fim' => max($v['data_fim'], $data),
    'atualizado_em' => agora(),
  ], 'id = ?', [$id]);
  auditar('diaria_criada', 'viagem', $id, ['data' => $data]);
  flash('sucesso', 'Dia de trabalho adicionado.');
  redirecionar("/admin/viagens/$id#dias");
}

function adm_viagem_diaria_remover(int $id, int $diariaId): void {
  exigir_admin(PAPEIS_VIAGENS);
  adm_viagem_carregar($id);
  $d = um('SELECT * FROM diarias WHERE id = ? AND viagem_id = ?', [$diariaId, $id]) ?? abortar(404);
  if (valor('SELECT 1 FROM escalas WHERE diaria_id = ?', [$diariaId])) {
    flash('erro', 'Este dia já tem guias convidados ou escalados. Cancele as escalas antes de removê-lo.');
    redirecionar("/admin/viagens/$id#dias");
  }
  q('DELETE FROM diarias WHERE id = ?', [$diariaId]);
  auditar('diaria_removida', 'viagem', $id, ['data' => $d['data']]);
  flash('sucesso', 'Dia de trabalho removido.');
  redirecionar("/admin/viagens/$id#dias");
}

function adm_viagem_vaga_criar(int $id): void {
  exigir_admin(PAPEIS_VIAGENS);
  adm_viagem_carregar($id);
  $funcaoId = (int) entrada('funcao_id');
  $vagas = max(1, min(99, (int) entrada('vagas', '1')));
  $valor = ler_moeda(entrada('valor_diaria'));
  if (!valor('SELECT 1 FROM funcoes WHERE id = ? AND ativo = 1', [$funcaoId])) {
    flash('erro', 'Escolha uma função ativa.');
    redirecionar("/admin/viagens/$id#vagas");
  }
  if ($valor <= 0) {
    flash('erro', 'Informe o valor da diária.');
    redirecionar("/admin/viagens/$id#vagas");
  }
  $existente = um('SELECT id FROM viagem_vagas WHERE viagem_id = ? AND funcao_id = ?', [$id, $funcaoId]);
  if ($existente) {
    atualizar('viagem_vagas', ['vagas' => $vagas, 'valor_diaria' => $valor], 'id = ?', [$existente['id']]);
  } else {
    inserir('viagem_vagas', ['viagem_id' => $id, 'funcao_id' => $funcaoId, 'vagas' => $vagas, 'valor_diaria' => $valor]);
  }
  auditar('vaga_salva', 'viagem', $id, ['funcao_id' => $funcaoId, 'vagas' => $vagas, 'valor' => $valor]);
  flash('sucesso', $existente ? 'Vaga atualizada.' : 'Vaga adicionada.');
  redirecionar("/admin/viagens/$id#vagas");
}

function adm_viagem_vaga_remover(int $id, int $vagaId): void {
  exigir_admin(PAPEIS_VIAGENS);
  adm_viagem_carregar($id);
  um('SELECT id FROM viagem_vagas WHERE id = ? AND viagem_id = ?', [$vagaId, $id]) ?? abortar(404);
  if (valor("SELECT 1 FROM escalas WHERE viagem_vaga_id = ? AND status NOT IN ('recusado','cancelado')", [$vagaId])) {
    flash('erro', 'Há guias convidados ou escalados nesta vaga. Cancele as escalas antes de removê-la.');
    redirecionar("/admin/viagens/$id#vagas");
  }
  q('UPDATE escalas SET viagem_vaga_id = NULL WHERE viagem_vaga_id = ?', [$vagaId]);
  q('DELETE FROM viagem_vagas WHERE id = ?', [$vagaId]);
  auditar('vaga_removida', 'viagem', $id);
  flash('sucesso', 'Vaga removida.');
  redirecionar("/admin/viagens/$id#vagas");
}

function adm_viagem_convidar(int $id): void {
  $a = exigir_admin(PAPEIS_VIAGENS);
  $v = adm_viagem_carregar($id);
  if ($v['status'] !== 'publicada') {
    flash('erro', 'Publique a viagem/tour antes de escalar guias.');
    redirecionar("/admin/viagens/$id#equipe");
  }
  $g = um("SELECT * FROM guias WHERE id = ? AND status = 'aprovado'", [(int) entrada('guia_id')]);
  $vaga = um('SELECT vv.*, f.nome AS funcao FROM viagem_vagas vv JOIN funcoes f ON f.id = vv.funcao_id
    WHERE vv.id = ? AND vv.viagem_id = ?', [(int) entrada('vaga_id'), $id]);
  $diariasIds = array_map('intval', (array) ($_POST['diarias'] ?? []));
  if (!$g || !$vaga || !$diariasIds) {
    flash('erro', 'Escolha o guia, a função e pelo menos um dia.');
    redirecionar("/admin/viagens/$id#equipe");
  }

  // "Alocar direto": já combinado com o guia, entra confirmado sem precisar aceitar o convite.
  $direto = isset($_POST['alocar_direto']);
  $status = $direto ? 'confirmado' : 'convidado';
  $convidados = 0;
  $lotados = [];
  foreach ($diariasIds as $diariaId) {
    $d = um('SELECT * FROM diarias WHERE id = ? AND viagem_id = ? AND data >= ?', [$diariaId, $id, hoje()]);
    if (!$d) {
      continue;
    }
    $ocupadas = (int) valor("SELECT COUNT(*) FROM escalas WHERE diaria_id = ? AND viagem_vaga_id = ? AND guia_id <> ?
      AND status NOT IN ('recusado','cancelado','falta')", [$diariaId, $vaga['id'], $g['id']]);
    if ($ocupadas >= (int) $vaga['vagas']) {
      $lotados[] = formatar_data($d['data']);
      continue;
    }
    $existente = um('SELECT * FROM escalas WHERE diaria_id = ? AND guia_id = ?', [$diariaId, $g['id']]);
    $dados = ['viagem_vaga_id' => $vaga['id'], 'status' => $status, 'valor' => $vaga['valor_diaria'],
      'convidado_por' => $a['id'], 'convidado_em' => agora(), 'respondido_em' => $direto ? agora() : null, 'motivo_recusa' => null, 'atualizado_em' => agora()];
    if ($existente) {
      if (!in_array($existente['status'], ['recusado', 'cancelado'], true)) {
        continue;
      }
      atualizar('escalas', $dados, 'id = ?', [$existente['id']]);
    } else {
      inserir('escalas', $dados + ['diaria_id' => $diariaId, 'guia_id' => $g['id']]);
    }
    $convidados++;
  }

  if ($convidados) {
    $link = url_absoluta("/guia/viagens/$id");
    $assunto = ($direto ? 'Você foi escalado(a): ' : 'Novo convite: ') . $v['codigo'] . ' · ' . $v['nome'];
    enviar_email((string) $g['email'], $assunto,
      '<p>Olá, ' . e(primeiro_nome($g)) . '.</p>'
      . '<p>' . ($direto ? 'Você foi escalado(a) para' : 'Você foi convidado(a) para') . ' <strong>'
      . e($v['codigo'] . ' · ' . $v['nome']) . '</strong> (' . e($v['origem'] . ' → ' . $v['destino']) . ') como ' . e($vaga['funcao']) . ', '
      . $convidados . ' dia(s) de trabalho, ' . e(formatar_moeda($vaga['valor_diaria'])) . ' por diária.</p>'
      . '<p><a href="' . e($link) . '" style="display:inline-block;padding:12px 22px;background:#53D9B2;color:#000;text-decoration:none;font-weight:bold;border-radius:999px">' . ($direto ? 'Ver briefing' : 'Ver convite e responder') . '</a></p>');
    inserir('notificacoes', ['destinatario_tipo' => 'guia', 'destinatario_id' => $g['id'], 'titulo' => $assunto,
      'link' => "/guia/viagens/$id", 'criado_em' => agora()]);
    auditar($direto ? 'guia_alocado' : 'guia_convidado', 'viagem', $id, ['guia_id' => $g['id'], 'vaga_id' => $vaga['id'], 'dias' => $convidados]);
  }
  $msg = $convidados
    ? ($direto ? "{$g['nome']} alocado(a) em $convidados dia(s). Já aparece como confirmado(a)." : "Convite enviado para {$g['nome']} ($convidados dia(s)).")
    : 'Nada novo: o guia já estava escalado nesses dias.';
  if ($lotados) {
    $msg .= ' Vaga já preenchida em: ' . implode(', ', $lotados) . '.';
  }
  flash($convidados ? 'sucesso' : 'aviso', $msg);
  redirecionar("/admin/viagens/$id#equipe");
}

function adm_viagem_escala_cancelar(int $id, int $escalaId): void {
  exigir_admin(PAPEIS_VIAGENS);
  $v = adm_viagem_carregar($id);
  $s = um('SELECT s.*, g.email, g.nome, g.nome_social, d.data FROM escalas s JOIN diarias d ON d.id = s.diaria_id JOIN guias g ON g.id = s.guia_id
    WHERE s.id = ? AND d.viagem_id = ?', [$escalaId, $id]) ?? abortar(404);
  if (!in_array($s['status'], ['convidado', 'confirmado'], true)) {
    flash('erro', 'Só é possível cancelar convites ou confirmações antes do trabalho.');
    redirecionar("/admin/viagens/$id#equipe");
  }
  atualizar('escalas', ['status' => 'cancelado', 'atualizado_em' => agora()], 'id = ?', [$escalaId]);
  if ($s['status'] === 'confirmado') {
    enviar_email((string) $s['email'], 'Escala cancelada: ' . $v['nome'],
      '<p>Olá, ' . e(primeiro_nome($s)) . '.</p><p>Sua escala de ' . e(formatar_data($s['data'])) . ' em <strong>'
      . e($v['nome']) . '</strong> foi cancelada pela equipe da 645 Turismo. Em caso de dúvida, abra um chamado na Ajuda.</p>');
  }
  auditar('escala_cancelada', 'viagem', $id, ['escala_id' => $escalaId]);
  flash('sucesso', 'Escala cancelada.');
  redirecionar("/admin/viagens/$id#equipe");
}
