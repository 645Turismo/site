<?php
// Área do Guia. O fluxo segue a rotina do guia:
// Hoje (o que vem pela frente e o que falta resolver) -> Viagens/Tours (convite, briefing, relatório)
// -> Disponibilidade -> Recebimentos -> Perfil -> Ajuda.

function guia_raiz(): void {
  redirecionar('/guia/hoje');
}

function guia_hoje(): void {
  $g = exigir_guia();
  $id = (int) $g['id'];
  $hoje = hoje();
  $assumidas = "('" . implode("','", ESCALAS_ASSUMIDAS) . "')";

  $proxima = um("SELECT d.*, v.codigo, v.nome, v.tipo, v.origem, v.destino, v.ponto_encontro, v.cliente, s.status AS escala_status
      FROM escalas s JOIN diarias d ON d.id = s.diaria_id JOIN viagens v ON v.id = d.viagem_id
      WHERE s.guia_id = ? AND s.status IN ('convidado','confirmado','em_campo') AND d.data >= ? AND v.status = 'publicada'
      ORDER BY d.data, d.horario_apresentacao LIMIT 1", [$id, $hoje]);

  $pendencias = [
    [
      (int) valor("SELECT COUNT(DISTINCT d.viagem_id) FROM escalas s JOIN diarias d ON d.id = s.diaria_id JOIN viagens v ON v.id = d.viagem_id
        WHERE s.guia_id = ? AND s.status = 'convidado' AND d.data >= ? AND v.status = 'publicada'", [$id, $hoje]),
      'Convites para responder', 'Aceite ou recuse para a equipe fechar a escala.', '/guia/viagens?aba=convites',
    ],
    [
      (int) valor("SELECT COUNT(*) FROM (SELECT d.viagem_id FROM escalas s JOIN diarias d ON d.id = s.diaria_id
        WHERE s.guia_id = ? AND s.status IN ('confirmado','em_campo','realizada')
        GROUP BY d.viagem_id HAVING MAX(d.data) <= ?) t
        WHERE NOT EXISTS (SELECT 1 FROM envios e WHERE e.viagem_id = t.viagem_id AND e.guia_id = ? AND e.tipo = 'relatorio' AND e.status <> 'reprovado')",
        [$id, $hoje, $id]),
      'Relatórios de viagem para enviar', 'Conte como foi: passageiros, ocorrências e observações.', '/guia/viagens?aba=realizadas',
    ],
    [
      (int) valor("SELECT COUNT(DISTINCT d.viagem_id) FROM escalas s JOIN diarias d ON d.id = s.diaria_id
        WHERE s.guia_id = ? AND s.status = 'aguardando_nf'", [$id]),
      'Notas fiscais para emitir', 'Sem a NF o pagamento não entra no lote.', '/guia/recebimentos',
    ],
    [
      (int) valor("SELECT COUNT(*) FROM chamados WHERE guia_id = ? AND status IN ('respondido','aguardando_confirmacao')", [$id]),
      'Respostas da equipe', 'Chamados com novidade para você ler.', '/guia/ajuda?aba=chamados',
    ],
  ];

  $proximosDias = todos("SELECT d.data, d.horario_apresentacao, d.hora_inicio, v.id AS viagem_id, v.codigo, v.nome, v.origem, v.destino, v.ponto_encontro, s.status
      FROM escalas s JOIN diarias d ON d.id = s.diaria_id JOIN viagens v ON v.id = d.viagem_id
      WHERE s.guia_id = ? AND s.status IN ('convidado','confirmado','em_campo') AND d.data >= ? AND v.status = 'publicada'
      ORDER BY d.data, d.horario_apresentacao LIMIT 6", [$id, $hoje]);

  $recados = todos("SELECT * FROM avisos a WHERE a.ativo = 1
      AND (a.inicio IS NULL OR a.inicio <= ?) AND (a.fim IS NULL OR a.fim >= ?)
      AND (a.publico = 'todos'
        OR (a.publico = 'funcao' AND a.funcao_id IN (SELECT funcao_id FROM guia_funcoes WHERE guia_id = ?))
        OR (a.publico = 'viagem' AND a.viagem_id IN (SELECT d.viagem_id FROM escalas s JOIN diarias d ON d.id = s.diaria_id WHERE s.guia_id = ?)))
      ORDER BY a.criado_em DESC LIMIT 4", [$hoje, $hoje, $id, $id]);

  $inicioMes = date('Y-m-01');
  $fimMes = date('Y-m-t');
  $mes = [
    'diarias' => (int) valor("SELECT COUNT(*) FROM escalas s JOIN diarias d ON d.id = s.diaria_id
      WHERE s.guia_id = ? AND s.status IN $assumidas AND d.data BETWEEN ? AND ?", [$id, $inicioMes, $fimMes]),
    'a_receber' => (float) valor("SELECT COALESCE(SUM(valor), 0) FROM escalas
      WHERE guia_id = ? AND status IN ('realizada','aguardando_nf','nf_em_conferencia','a_pagar')", [$id]),
  ];

  exibir('guia/hoje', [
    'titulo' => 'Hoje',
    'menu' => 'hoje',
    'p' => $g,
    'proxima' => $proxima,
    'pendencias' => $pendencias,
    'proximosDias' => $proximosDias,
    'recados' => $recados,
    'mes' => $mes,
  ], 'guia');
}

function guia_viagens(): void {
  $g = exigir_guia();
  $hoje = hoje();
  $assumidas = "('" . implode("','", ESCALAS_ASSUMIDAS) . "')";
  $linhas = todos("SELECT v.id, v.codigo, v.nome, v.tipo, v.cliente, v.origem, v.destino, v.status AS viagem_status,
        MIN(d.data) AS primeira, MAX(d.data) AS ultima, COUNT(s.id) AS diarias,
        SUM(CASE WHEN s.status = 'convidado' AND d.data >= ? THEN 1 ELSE 0 END) AS convites,
        SUM(CASE WHEN s.status IN $assumidas THEN 1 ELSE 0 END) AS assumidas,
        MAX(f.nome) AS funcao
      FROM escalas s
      JOIN diarias d ON d.id = s.diaria_id
      JOIN viagens v ON v.id = d.viagem_id
      LEFT JOIN viagem_vagas vv ON vv.id = s.viagem_vaga_id
      LEFT JOIN funcoes f ON f.id = vv.funcao_id
      WHERE s.guia_id = ? AND v.status IN ('publicada','concluida') AND s.status <> 'cancelado'
      GROUP BY v.id, v.codigo, v.nome, v.tipo, v.cliente, v.origem, v.destino, v.status
      ORDER BY MIN(d.data)", [$hoje, $g['id']]);

  $grupos = ['convites' => [], 'proximas' => [], 'realizadas' => []];
  foreach ($linhas as $l) {
    if ((int) $l['convites'] > 0) {
      $grupos['convites'][] = $l;
    }
    if ((int) $l['assumidas'] > 0) {
      $grupos[$l['ultima'] >= $hoje ? 'proximas' : 'realizadas'][] = $l;
    }
  }
  $grupos['realizadas'] = array_reverse($grupos['realizadas']);

  $aba = (string) ($_GET['aba'] ?? '');
  if (!isset($grupos[$aba])) {
    $aba = $grupos['convites'] ? 'convites' : 'proximas';
  }
  exibir('guia/viagens', [
    'titulo' => 'Viagens/Tours',
    'menu' => 'viagens',
    'p' => $g,
    'grupos' => $grupos,
    'aba' => $aba,
  ], 'guia');
}

/** Carrega a viagem/tour e as escalas do guia nela; 404 se o guia não estiver envolvido. */
function guia_carregar_viagem(array $g, int $id): array {
  $v = um("SELECT * FROM viagens WHERE id = ? AND status IN ('publicada','concluida')", [$id]);
  $escalas = $v ? todos("SELECT s.*, d.data, d.horario_apresentacao, d.hora_inicio, d.hora_fim, d.pernoite, d.observacao,
        f.nome AS funcao
      FROM escalas s JOIN diarias d ON d.id = s.diaria_id
      LEFT JOIN viagem_vagas vv ON vv.id = s.viagem_vaga_id LEFT JOIN funcoes f ON f.id = vv.funcao_id
      WHERE d.viagem_id = ? AND s.guia_id = ? AND s.status <> 'cancelado'
      ORDER BY d.data, d.horario_apresentacao", [$id, $g['id']]) : [];
  if (!$v || !$escalas) {
    abortar(404, 'Viagem/tour não encontrado.');
  }
  return [$v, $escalas];
}

function guia_viagem(int $id): void {
  $g = exigir_guia();
  [$v, $escalas] = guia_carregar_viagem($g, $id);
  $hoje = hoje();

  $convites = array_values(array_filter($escalas, fn($s) => $s['status'] === 'convidado' && $s['data'] >= $hoje));
  $assumidas = array_values(array_filter($escalas, fn($s) => in_array($s['status'], ESCALAS_ASSUMIDAS, true)));
  $ultimaAssumida = $assumidas ? end($assumidas)['data'] : null;
  $relatorio = um("SELECT * FROM envios WHERE viagem_id = ? AND guia_id = ? AND tipo = 'relatorio' ORDER BY id DESC LIMIT 1", [$id, $g['id']]);

  exibir('guia/viagem', [
    'titulo' => $v['nome'],
    'menu' => 'viagens',
    'p' => $g,
    'v' => $v,
    'escalas' => $escalas,
    'convites' => $convites,
    'confirmado' => (bool) $assumidas,
    'relatorio' => $relatorio,
    'relatorioLiberado' => $ultimaAssumida !== null && $ultimaAssumida <= $hoje,
    'relatorioEditavel' => !$relatorio || in_array($relatorio['status'], ['enviado', 'reprovado'], true),
    'anexos' => $assumidas ? todos('SELECT * FROM viagem_anexos WHERE viagem_id = ? ORDER BY id', [$id]) : [],
    'origens' => viagem_origens($id),
    'contatos' => $assumidas ? todos('SELECT * FROM viagem_contatos WHERE viagem_id = ? ORDER BY ordem', [$id]) : [],
  ], 'guia');
}

function guia_viagem_responder(int $id): void {
  $g = exigir_guia();
  [$v] = guia_carregar_viagem($g, $id);
  $decisao = entrada('decisao');
  if (!in_array($decisao, ['aceitar', 'recusar'], true)) {
    abortar(400);
  }
  if ($decisao === 'aceitar' && $g['status'] !== 'aprovado') {
    flash('erro', 'Seu cadastro precisa estar aprovado para aceitar convites.');
    redirecionar("/guia/viagens/$id");
  }
  $ids = array_column(todos("SELECT s.id FROM escalas s JOIN diarias d ON d.id = s.diaria_id
      WHERE d.viagem_id = ? AND s.guia_id = ? AND s.status = 'convidado' AND d.data >= ?", [$id, $g['id'], hoje()]), 'id');
  if (!$ids) {
    flash('aviso', 'Não há convite pendente nesta viagem/tour.');
    redirecionar("/guia/viagens/$id");
  }
  $novo = $decisao === 'aceitar' ? 'confirmado' : 'recusado';
  $motivo = mb_substr(entrada('motivo'), 0, 255) ?: null;
  foreach ($ids as $escalaId) {
    atualizar('escalas', ['status' => $novo, 'motivo_recusa' => $novo === 'recusado' ? $motivo : null,
      'respondido_em' => agora(), 'atualizado_em' => agora()], 'id = ?', [$escalaId]);
  }
  auditar($decisao === 'aceitar' ? 'convite_aceito' : 'convite_recusado', 'viagem', $id, ['escalas' => $ids, 'motivo' => $motivo]);
  flash('sucesso', $decisao === 'aceitar'
    ? 'Presença confirmada! O briefing completo e os contatos já estão liberados abaixo.'
    : 'Convite recusado. Obrigado por avisar.');
  redirecionar("/guia/viagens/$id");
}

function guia_viagem_relatorio(int $id): void {
  $g = exigir_guia();
  [$v, $escalas] = guia_carregar_viagem($g, $id);
  $assumidas = array_values(array_filter($escalas, fn($s) => in_array($s['status'], ESCALAS_ASSUMIDAS, true)));
  if (!$assumidas || end($assumidas)['data'] > hoje()) {
    flash('erro', 'O relatório fica disponível a partir do último dia de trabalho.');
    redirecionar("/guia/viagens/$id");
  }
  $texto = trim((string) ($_POST['texto'] ?? ''));
  $pax = entrada('qtd_passageiros');
  if (mb_strlen($texto) < 20 || mb_strlen($texto) > 5000) {
    guardar_antigo(['texto' => $texto, 'qtd_passageiros' => $pax]);
    flash('erro', 'Escreva o relatório com pelo menos 20 caracteres (máximo de 5.000).');
    redirecionar("/guia/viagens/$id#relatorio");
  }
  $pax = $pax === '' ? null : max(0, min(9999, (int) $pax));
  $existente = um("SELECT * FROM envios WHERE viagem_id = ? AND guia_id = ? AND tipo = 'relatorio' ORDER BY id DESC LIMIT 1", [$id, $g['id']]);
  if ($existente && $existente['status'] === 'aprovado') {
    flash('aviso', 'Este relatório já foi conferido pela equipe.');
    redirecionar("/guia/viagens/$id");
  }
  if ($existente && $existente['status'] === 'enviado') {
    atualizar('envios', ['texto' => $texto, 'qtd_passageiros' => $pax, 'enviado_em' => agora()], 'id = ?', [$existente['id']]);
  } else {
    inserir('envios', [
      'tipo' => 'relatorio', 'viagem_id' => $id, 'guia_id' => $g['id'], 'texto' => $texto, 'qtd_passageiros' => $pax,
      'enviado_por_tipo' => 'guia', 'enviado_por_id' => $g['id'], 'status' => 'enviado', 'enviado_em' => agora(),
    ]);
  }
  // Com o relatório entregue, as diárias realizadas seguem para a nota fiscal; quem não emite nota vai direto para pagamento.
  $emiteNf = (int) ($g['emite_nf'] ?? 1) === 1;
  foreach ($assumidas as $s) {
    if (in_array($s['status'], ['confirmado', 'em_campo', 'realizada'], true) && $s['data'] <= hoje()) {
      atualizar('escalas', ['status' => $emiteNf ? 'aguardando_nf' : 'a_pagar', 'atualizado_em' => agora()], 'id = ?', [$s['id']]);
    }
  }
  auditar('relatorio_enviado', 'viagem', $id);
  flash('sucesso', $emiteNf ? 'Relatório enviado. Obrigado! O próximo passo é a nota fiscal.' : 'Relatório enviado. Obrigado! O pagamento segue para programação.');
  redirecionar("/guia/viagens/$id");
}

// ---------- Lista de passageiros (check-in e check-out em campo) ----------

/** Dias de trabalho assumidos pelo guia na viagem; só quem confirmou presença acessa a lista. */
function guia_dias_da_lista(array $g, int $viagemId): array {
  [$v, $escalas] = guia_carregar_viagem($g, $viagemId);
  $dias = [];
  foreach ($escalas as $s) {
    if (in_array($s['status'], ESCALAS_ASSUMIDAS, true)) {
      $dias[(int) $s['diaria_id']] = $s;
    }
  }
  if (!$dias) {
    abortar(403, 'A lista de passageiros fica disponível depois que você confirma presença.');
  }
  return [$v, $dias];
}

/** O guia marca no dia do trabalho e no dia seguinte (para viagens que terminam de madrugada). */
function guia_pode_marcar(array $dia): bool {
  return $dia['data'] === hoje() || $dia['data'] === date('Y-m-d', strtotime('-1 day'));
}

function guia_passageiros(int $id): void {
  $g = exigir_guia();
  [$v, $dias] = guia_dias_da_lista($g, $id);
  $escolhido = null;
  foreach ($dias as $diariaId => $d) {
    if ($d['data'] >= hoje() || $escolhido === null) {
      $escolhido = $diariaId;
      if ($d['data'] >= hoje()) {
        break;
      }
    }
  }
  if (isset($_GET['diaria'], $dias[(int) $_GET['diaria']])) {
    $escolhido = (int) $_GET['diaria'];
  }
  exibir('comum/passageiros', [
    'titulo' => 'Passageiros · ' . $v['codigo'],
    'menu' => 'viagens',
    'p' => $g,
    'v' => $v,
    'dias' => array_map(fn($d) => ['id' => (int) $d['diaria_id'], 'data' => $d['data']], array_values($dias)),
    'escolhido' => $escolhido,
    'base' => "/guia/viagens/$id/passageiros",
    'origens' => viagem_origens($id),
    'voltar' => "/guia/viagens/$id",
    'admin' => false,
  ], 'guia');
}

function guia_passageiros_dados(int $id): void {
  $g = exigir_guia();
  [$v, $dias] = guia_dias_da_lista($g, $id);
  $dia = $dias[(int) ($_GET['diaria'] ?? 0)] ?? resposta_json(['erro' => 'Dia de trabalho inválido.'], 404);
  resposta_json(passageiros_estado($v, ['id' => $dia['diaria_id'], 'data' => $dia['data']], guia_pode_marcar($dia)));
}

function guia_passageiro_marcar(int $id, int $passageiroId): void {
  $g = exigir_guia();
  [$v, $dias] = guia_dias_da_lista($g, $id);
  $dia = $dias[(int) entrada('diaria')] ?? resposta_json(['erro' => 'Dia de trabalho inválido.'], 404);
  if (!guia_pode_marcar($dia)) {
    resposta_json(['erro' => 'O check-in só pode ser feito no dia do trabalho.'], 403);
  }
  $erro = passageiro_marcar($id, $passageiroId, (int) $dia['diaria_id'], entrada('tipo'), entrada('desfazer') === '1', 'guia', (int) $g['id']);
  if ($erro) {
    resposta_json(['erro' => $erro], 422);
  }
  resposta_json(passageiros_estado($v, ['id' => $dia['diaria_id'], 'data' => $dia['data']], true));
}

/**
 * Ajuste do guia num passageiro. O dado do ADM é preservado; o ajuste aparece destacado
 * e a coordenação recebe e-mail com o antes e o depois.
 */
function guia_passageiro_editar(int $id, int $passageiroId): void {
  $g = exigir_guia();
  [$v] = guia_dias_da_lista($g, $id);
  $p = um("SELECT * FROM passageiros WHERE id = ? AND viagem_id = ? AND status = 'ativo'", [$passageiroId, $id])
    ?? resposta_json(['erro' => 'Passageiro não encontrado.'], 404);
  [$dados, $erro] = passageiro_ler_form();
  if (!$erro) {
    [$dados, $erro] = passageiro_aplicar_regras($v, $dados, passageiros_ocupacao($id, $passageiroId));
  }
  if ($erro) {
    resposta_json(['erro' => $erro], 422);
  }
  $alteracoes = passageiro_ajustar_pelo_guia($p, $dados, (int) $g['id']);
  if ($alteracoes) {
    auditar('passageiro_ajustado_guia', 'passageiro', $passageiroId, $alteracoes);
    notificar_alteracao_lista($v, $g, $dados['nome'] ?? $p['nome'], $alteracoes, false);
  }
  resposta_json(['ok' => true, 'alterado' => (bool) $alteracoes]);
}

/** Inclusão de passageiro em campo (ex.: troca de última hora). Aparece destacado e avisa a coordenação. */
function guia_passageiro_incluir(int $id): void {
  $g = exigir_guia();
  [$v] = guia_dias_da_lista($g, $id);
  [$dados, $erro] = passageiro_ler_form();
  if (!$erro) {
    [$dados, $erro] = passageiro_aplicar_regras($v, $dados, passageiros_ocupacao($id));
  }
  if ($erro) {
    resposta_json(['erro' => $erro], 422);
  }
  $pid = passageiro_incluir($id, $dados + ['ajustado_por_guia_id' => $g['id'], 'ajustado_em' => agora()], 'guia', (int) $g['id']);
  $itens = [];
  foreach ($dados as $campo => $valor) {
    if ($valor !== null) {
      $itens[] = [CAMPOS_PASSAGEIRO[$campo][0], '', passageiro_exibir($campo, $valor)];
    }
  }
  auditar('passageiro_incluido_guia', 'passageiro', $pid);
  notificar_alteracao_lista($v, $g, $dados['nome'], $itens, true);
  resposta_json(['ok' => true]);
}

function guia_tour_visto(): void {
  $g = exigir_guia();
  atualizar('guias', ['tour_visto' => 1], 'id = ?', [$g['id']]);
  http_response_code(204);
}

function guia_sair(): void {
  sair_guia();
  flash('sucesso', 'Você saiu da Área do Guia.');
  redirecionar('/');
}
