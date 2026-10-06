<?php
// Recebimentos do guia: por viagem/tour, do relatório entregue até o pagamento, com envio da nota fiscal.

// Ordem do ciclo financeiro (a viagem fica no estágio mais atrasado entre suas diárias).
const ETAPAS_FINANCEIRO = ['realizada', 'aguardando_nf', 'nf_em_conferencia', 'a_pagar', 'paga'];

/** Diárias do guia agrupadas por viagem, com status financeiro, NF, prazos e pagamentos. */
function guia_recebimentos_por_viagem(int $guiaId, ?string $ano = null): array {
  $params = [$guiaId];
  $filtroAno = '';
  if ($ano) {
    $filtroAno = ' AND d.data BETWEEN ? AND ?';
    $params[] = "$ano-01-01";
    $params[] = "$ano-12-31";
  }
  $linhas = todos("SELECT s.*, d.data, v.id AS viagem_id, v.codigo, v.nome, v.prazo_nf_dias_uteis, v.prazo_pagamento_dias, v.instrucoes_nf
      FROM escalas s JOIN diarias d ON d.id = s.diaria_id JOIN viagens v ON v.id = d.viagem_id
      WHERE s.guia_id = ? AND s.status IN ('realizada','aguardando_nf','nf_em_conferencia','a_pagar','paga','falta')$filtroAno
      ORDER BY d.data DESC", $params);
  $viagens = [];
  foreach ($linhas as $l) {
    $id = (int) $l['viagem_id'];
    $viagens[$id] ??= ['id' => $id, 'codigo' => $l['codigo'], 'nome' => $l['nome'], 'instrucoes_nf' => $l['instrucoes_nf'],
      'prazo_nf_dias_uteis' => (int) $l['prazo_nf_dias_uteis'], 'prazo_pagamento_dias' => (int) $l['prazo_pagamento_dias'],
      'diarias' => [], 'total' => 0.0, 'ultima' => $l['data'], 'etapa' => 'paga'];
    $viagens[$id]['diarias'][] = $l;
    if ($l['status'] !== 'falta') {
      $viagens[$id]['total'] += (float) $l['valor'];
      $pos = array_search($l['status'], ETAPAS_FINANCEIRO, true);
      if ($pos !== false && $pos < array_search($viagens[$id]['etapa'], ETAPAS_FINANCEIRO, true)) {
        $viagens[$id]['etapa'] = $l['status'];
      }
    }
    $viagens[$id]['ultima'] = max($viagens[$id]['ultima'], $l['data']);
  }
  foreach ($viagens as &$v) {
    if (!array_filter($v['diarias'], fn($d) => $d['status'] !== 'falta')) {
      $v['etapa'] = 'falta';
    }
    $v['nf'] = um("SELECT * FROM envios WHERE viagem_id = ? AND guia_id = ? AND tipo = 'nf' ORDER BY id DESC LIMIT 1", [$v['id'], $guiaId]);
    $v['prazo_nf'] = somar_dias_uteis($v['ultima'], $v['prazo_nf_dias_uteis']);
    $v['previsao'] = $v['nf'] && $v['nf']['status'] === 'aprovado'
      ? date('Y-m-d', strtotime($v['nf']['revisado_em'] . ' +' . $v['prazo_pagamento_dias'] . ' days')) : null;
    $v['pagamentos'] = todos('SELECT * FROM pagamentos WHERE guia_id = ? AND viagem_id = ? ORDER BY pago_em', [$guiaId, $v['id']]);
  }
  unset($v);
  return array_values($viagens);
}

function guia_recebimentos(): void {
  $g = exigir_guia();
  $ano = preg_match('/^\d{4}$/', (string) ($_GET['ano'] ?? '')) ? $_GET['ano'] : date('Y');
  $viagens = guia_recebimentos_por_viagem((int) $g['id'], $ano);
  $resumo = [
    'recebido' => (float) valor('SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE guia_id = ? AND pago_em BETWEEN ? AND ?', [$g['id'], "$ano-01-01", "$ano-12-31"]),
    'a_receber' => (float) valor("SELECT COALESCE(SUM(valor), 0) FROM escalas WHERE guia_id = ? AND status IN ('realizada','aguardando_nf','nf_em_conferencia','a_pagar')", [$g['id']]),
    'diarias' => count(array_merge(...array_map(fn($v) => array_filter($v['diarias'], fn($d) => $d['status'] !== 'falta'), $viagens ?: [['diarias' => []]]))),
    'nf_pendentes' => count(array_filter($viagens, fn($v) => $v['etapa'] === 'aguardando_nf')),
  ];
  $anos = array_column(todos('SELECT DISTINCT substr(d.data, 1, 4) AS ano FROM escalas s JOIN diarias d ON d.id = s.diaria_id WHERE s.guia_id = ? ORDER BY ano DESC', [$g['id']]), 'ano');
  exibir('guia/recebimentos', [
    'titulo' => 'Recebimentos',
    'menu' => 'recebimentos',
    'p' => $g,
    'ano' => $ano,
    'anos' => $anos ?: [date('Y')],
    'viagens' => $viagens,
    'resumo' => $resumo,
    'empresa' => [
      'razao' => configuracao('empresa_razao_social'),
      'cnpj' => configuracao('empresa_cnpj'),
    ],
  ], 'guia');
}

/** Envio (ou reenvio) da nota fiscal de uma viagem/tour. */
function guia_recebimentos_nf(int $viagemId): void {
  $g = exigir_guia();
  $escalas = todos("SELECT s.* FROM escalas s JOIN diarias d ON d.id = s.diaria_id
      WHERE d.viagem_id = ? AND s.guia_id = ? AND s.status = 'aguardando_nf'", [$viagemId, $g['id']]);
  if (!$escalas) {
    flash('erro', 'Esta viagem/tour não está aguardando nota fiscal.');
    redirecionar('/guia/recebimentos');
  }
  $numero = mb_substr(entrada('numero_nf'), 0, 40);
  $valorNf = ler_moeda(entrada('valor_nf'));
  if ($numero === '' || $valorNf <= 0) {
    flash('erro', 'Informe o número e o valor da nota fiscal.');
    redirecionar('/guia/recebimentos#viagem-' . $viagemId);
  }
  try {
    [$caminho, $nome, $mime, $tamanho] = salvar_upload('arquivo_nf', 'notas');
  } catch (RuntimeException $e) {
    flash('erro', 'Nota fiscal: ' . $e->getMessage());
    redirecionar('/guia/recebimentos#viagem-' . $viagemId);
  }
  $total = array_sum(array_map(fn($s) => (float) $s['valor'], $escalas));
  transacao(function () use ($g, $viagemId, $caminho, $nome, $mime, $tamanho, $numero, $valorNf, $escalas) {
    inserir('envios', [
      'tipo' => 'nf', 'viagem_id' => $viagemId, 'guia_id' => $g['id'], 'arquivo_path' => $caminho, 'nome_original' => $nome,
      'mime' => $mime, 'tamanho' => $tamanho, 'numero_nf' => $numero, 'valor_nf' => $valorNf,
      'enviado_por_tipo' => 'guia', 'enviado_por_id' => $g['id'], 'status' => 'enviado', 'enviado_em' => agora(),
    ]);
    foreach ($escalas as $s) {
      atualizar('escalas', ['status' => 'nf_em_conferencia', 'atualizado_em' => agora()], 'id = ?', [$s['id']]);
    }
  });
  auditar('nf_enviada', 'viagem', $viagemId, ['numero' => $numero, 'valor' => $valorNf]);
  $aviso = abs($valorNf - $total) > 0.009 ? ' Atenção: o valor informado (' . formatar_moeda($valorNf) . ') é diferente do total das diárias (' . formatar_moeda($total) . ').' : '';
  flash('sucesso', 'Nota fiscal enviada para conferência.' . $aviso);
  redirecionar('/guia/recebimentos#viagem-' . $viagemId);
}
