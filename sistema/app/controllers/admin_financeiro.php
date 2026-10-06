<?php
// Conferência (relatórios e notas fiscais) e Pagamentos no Painel ADM.

// ---------- Conferência ----------

function adm_conferencia(): void {
  $a = exigir_admin(['coordenador', 'financeiro']);
  $aba = in_array($_GET['aba'] ?? '', ['nf', 'relatorio', 'historico'], true) ? $_GET['aba'] : 'nf';
  $base = "SELECT e.*, g.nome AS guia, g.codigo AS guia_codigo, v.codigo, v.nome AS viagem,
      (SELECT COALESCE(SUM(s.valor), 0) FROM escalas s JOIN diarias d ON d.id = s.diaria_id
        WHERE d.viagem_id = e.viagem_id AND s.guia_id = e.guia_id AND s.status NOT IN ('recusado','cancelado','falta','convidado')) AS total_diarias
    FROM envios e JOIN guias g ON g.id = e.guia_id JOIN viagens v ON v.id = e.viagem_id";
  $envios = $aba === 'historico'
    ? todos("$base WHERE e.status <> 'enviado' AND e.tipo IN ('nf','relatorio') ORDER BY e.revisado_em DESC LIMIT 100")
    : todos("$base WHERE e.status = 'enviado' AND e.tipo = ? ORDER BY e.enviado_em", [$aba]);
  exibir('admin/conferencia', [
    'titulo' => 'Conferência',
    'menu' => 'conferencia',
    'a' => $a,
    'aba' => $aba,
    'envios' => $envios,
    'contagem' => [
      'nf' => (int) valor("SELECT COUNT(*) FROM envios WHERE tipo = 'nf' AND status = 'enviado'"),
      'relatorio' => (int) valor("SELECT COUNT(*) FROM envios WHERE tipo = 'relatorio' AND status = 'enviado'"),
    ],
  ], 'admin');
}

function adm_conferencia_decidir(int $id): void {
  $a = exigir_admin(['coordenador', 'financeiro']);
  $e = um("SELECT e.*, g.email, g.nome, g.nome_social, v.codigo, v.nome AS viagem, v.prazo_pagamento_dias
    FROM envios e JOIN guias g ON g.id = e.guia_id JOIN viagens v ON v.id = e.viagem_id WHERE e.id = ? AND e.status = 'enviado'", [$id])
    ?? abortar(404, 'Envio não encontrado ou já conferido.');
  $aprovar = entrada('decisao') === 'aprovar';
  $motivo = mb_substr(trim((string) ($_POST['motivo'] ?? '')), 0, 1000);
  $voltar = '/admin/conferencia?aba=' . $e['tipo'];
  if (!$aprovar && mb_strlen($motivo) < 3) {
    flash('erro', 'Escreva o motivo da devolução: o guia vai ver.');
    redirecionar($voltar);
  }
  transacao(function () use ($e, $a, $aprovar, $motivo) {
    atualizar('envios', ['status' => $aprovar ? 'aprovado' : 'reprovado', 'motivo' => $aprovar ? null : $motivo,
      'revisado_por' => $a['id'], 'revisado_em' => agora()], 'id = ?', [$e['id']]);
    if ($e['tipo'] === 'nf') {
      q("UPDATE escalas SET status = ?, atualizado_em = ? WHERE guia_id = ? AND status = 'nf_em_conferencia'
        AND diaria_id IN (SELECT id FROM diarias WHERE viagem_id = ?)", [$aprovar ? 'a_pagar' : 'aguardando_nf', agora(), $e['guia_id'], $e['viagem_id']]);
    }
  });
  auditar(($e['tipo'] === 'nf' ? 'nf_' : 'relatorio_') . ($aprovar ? 'aprovado' : 'devolvido'), 'viagem', (int) $e['viagem_id'], ['envio_id' => $e['id'], 'motivo' => $motivo]);

  $rotulo = $e['tipo'] === 'nf' ? 'nota fiscal' : 'relatório';
  if ($aprovar && $e['tipo'] === 'nf') {
    $previsao = date('Y-m-d', strtotime('+' . (int) $e['prazo_pagamento_dias'] . ' days'));
    $html = '<p>Sua nota fiscal de <strong>' . e($e['codigo'] . ' · ' . $e['viagem']) . '</strong> foi aprovada. Previsão de pagamento: <strong>' . e(formatar_data($previsao)) . '</strong>.</p>';
  } elseif ($aprovar) {
    $html = null; // relatório aprovado não precisa de aviso
  } else {
    $html = '<p>Seu(sua) ' . $rotulo . ' de <strong>' . e($e['codigo'] . ' · ' . $e['viagem']) . '</strong> foi devolvido(a) para ajuste.</p><p>Motivo: ' . e($motivo) . '</p>'
      . '<p>Corrija e envie de novo pela Área do Guia' . ($e['tipo'] === 'nf' ? ', em Recebimentos' : ', na viagem/tour') . '.</p>';
  }
  if ($html && $e['email']) {
    enviar_email($e['email'], ucfirst($rotulo) . ($aprovar ? ' aprovada' : ' devolvida') . ': ' . $e['codigo'], '<p>Olá, ' . e(primeiro_nome($e)) . '.</p>' . $html);
  }
  flash('sucesso', ucfirst($rotulo) . ($aprovar ? ' aprovado(a).' : ' devolvido(a) ao guia.') . ($aprovar && $e['tipo'] === 'nf' ? ' Diárias liberadas para pagamento.' : ''));
  redirecionar($voltar);
}

// ---------- Pagamentos ----------

/** Diárias a pagar agrupadas por guia + viagem, com dados de pagamento e previsão. */
function pagamentos_pendentes(): array {
  $linhas = todos("SELECT s.guia_id, d.viagem_id, SUM(s.valor) AS total, COUNT(*) AS diarias,
      g.nome, g.nome_social, g.codigo AS guia_codigo, g.cnpj, g.razao_social, g.email,
      v.codigo, v.nome AS viagem, v.prazo_pagamento_dias,
      b.pix_tipo, b.pix_chave, b.banco_nome, b.agencia, b.conta
    FROM escalas s JOIN diarias d ON d.id = s.diaria_id JOIN guias g ON g.id = s.guia_id JOIN viagens v ON v.id = d.viagem_id
    LEFT JOIN guia_dados_bancarios b ON b.guia_id = s.guia_id
    WHERE s.status = 'a_pagar'
    GROUP BY s.guia_id, d.viagem_id, g.nome, g.nome_social, g.codigo, g.cnpj, g.razao_social, g.email, v.codigo, v.nome, v.prazo_pagamento_dias,
      b.pix_tipo, b.pix_chave, b.banco_nome, b.agencia, b.conta");
  foreach ($linhas as &$l) {
    $l['nf'] = um("SELECT * FROM envios WHERE tipo = 'nf' AND status = 'aprovado' AND guia_id = ? AND viagem_id = ? ORDER BY id DESC LIMIT 1", [$l['guia_id'], $l['viagem_id']]);
    $l['previsao'] = $l['nf'] ? date('Y-m-d', strtotime($l['nf']['revisado_em'] . ' +' . (int) $l['prazo_pagamento_dias'] . ' days')) : null;
  }
  unset($l);
  usort($linhas, fn($x, $y) => [(string) $x['previsao'], $x['nome']] <=> [(string) $y['previsao'], $y['nome']]);
  return $linhas;
}

function adm_pagamentos(): void {
  $a = exigir_admin(['financeiro']);
  $aba = ($_GET['aba'] ?? '') === 'pagos' ? 'pagos' : 'a_pagar';
  $mes = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['mes'] ?? '')) ? $_GET['mes'] : date('Y-m');
  $pendentes = pagamentos_pendentes();
  $pagos = $aba === 'pagos' ? todos('SELECT p.*, g.nome, g.codigo AS guia_codigo, v.codigo, v.nome AS viagem FROM pagamentos p
      JOIN guias g ON g.id = p.guia_id LEFT JOIN viagens v ON v.id = p.viagem_id
      WHERE p.pago_em BETWEEN ? AND ? ORDER BY p.pago_em DESC, p.id DESC', [$mes . '-01', date('Y-m-t', strtotime($mes . '-01'))]) : [];
  exibir('admin/pagamentos', [
    'titulo' => 'Pagamentos',
    'menu' => 'pagamentos',
    'a' => $a,
    'aba' => $aba,
    'mes' => $mes,
    'pendentes' => $pendentes,
    'pagos' => $pagos,
    'totalPendente' => array_sum(array_column($pendentes, 'total')),
    'vencidos' => count(array_filter($pendentes, fn($p) => $p['previsao'] && $p['previsao'] < hoje())),
  ], 'admin');
}

function adm_pagamento_registrar(): void {
  $a = exigir_admin(['financeiro']);
  $guiaId = (int) entrada('guia_id');
  $viagemId = (int) entrada('viagem_id');
  $pagoEm = entrada('pago_em');
  if (!data_valida($pagoEm) || $pagoEm > hoje()) {
    flash('erro', 'Informe a data do pagamento.');
    redirecionar('/admin/pagamentos');
  }
  $escalas = todos("SELECT s.* FROM escalas s JOIN diarias d ON d.id = s.diaria_id WHERE s.guia_id = ? AND d.viagem_id = ? AND s.status = 'a_pagar'", [$guiaId, $viagemId]);
  if (!$escalas) {
    flash('erro', 'Nada a pagar para este guia nesta viagem/tour.');
    redirecionar('/admin/pagamentos');
  }
  $comprovante = null;
  if (upload_enviado('comprovante')) {
    try {
      [$comprovante] = salvar_upload('comprovante', 'comprovantes');
    } catch (RuntimeException $e) {
      flash('erro', 'Comprovante: ' . $e->getMessage());
      redirecionar('/admin/pagamentos');
    }
  }
  $total = array_sum(array_map(fn($s) => (float) $s['valor'], $escalas));
  $pid = transacao(function () use ($guiaId, $viagemId, $pagoEm, $comprovante, $total, $escalas, $a) {
    $pid = inserir('pagamentos', ['guia_id' => $guiaId, 'viagem_id' => $viagemId, 'valor' => $total, 'pago_em' => $pagoEm,
      'comprovante_path' => $comprovante, 'observacao' => mb_substr(entrada('observacao'), 0, 255) ?: null,
      'registrado_por' => $a['id'], 'criado_em' => agora()]);
    foreach ($escalas as $s) {
      atualizar('escalas', ['status' => 'paga', 'pagamento_id' => $pid, 'atualizado_em' => agora()], 'id = ?', [$s['id']]);
    }
    return $pid;
  });
  auditar('pagamento_registrado', 'guia', $guiaId, ['pagamento_id' => $pid, 'valor' => $total, 'viagem_id' => $viagemId]);
  $g = um('SELECT email, nome, nome_social FROM guias WHERE id = ?', [$guiaId]);
  $v = um('SELECT codigo, nome FROM viagens WHERE id = ?', [$viagemId]);
  if ($g && $g['email']) {
    enviar_email($g['email'], 'Pagamento realizado: ' . $v['codigo'],
      '<p>Olá, ' . e(primeiro_nome($g)) . '.</p><p>Pagamos <strong>' . e(formatar_moeda($total)) . '</strong> referente a <strong>' . e($v['codigo'] . ' · ' . $v['nome'])
      . '</strong> em ' . e(formatar_data($pagoEm)) . '.</p><p>O comprovante fica disponível em Recebimentos, na Área do Guia.</p>');
  }
  flash('sucesso', 'Pagamento de ' . formatar_moeda($total) . ' registrado. O guia foi avisado.');
  redirecionar('/admin/pagamentos');
}

/** Planilha (CSV) do que está a pagar, para conferência ou lote no banco. */
function adm_pagamentos_exportar(): void {
  exigir_admin(['financeiro']);
  auditar('pagamentos_exportados', null, null);
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="a-pagar-' . date('Y-m-d') . '.csv"');
  $s = fopen('php://output', 'w');
  fwrite($s, "\xEF\xBB\xBF");
  fputcsv($s, ['Guia', 'Código', 'CNPJ', 'Razão social', 'Tipo PIX', 'Chave PIX', 'Banco', 'Agência', 'Conta', 'Viagem/Tour', 'Diárias', 'Valor', 'NF', 'Previsão'], ';', '"', '');
  foreach (pagamentos_pendentes() as $p) {
    fputcsv($s, [$p['nome'], $p['guia_codigo'], formatar_cnpj($p['cnpj']), $p['razao_social'], TIPOS_PIX[$p['pix_tipo']] ?? '', $p['pix_chave'],
      $p['banco_nome'], $p['agencia'], $p['conta'], $p['codigo'] . ' · ' . $p['viagem'], $p['diarias'],
      number_format((float) $p['total'], 2, ',', ''), $p['nf']['numero_nf'] ?? '', formatar_data($p['previsao'])], ';', '"', '');
  }
  fclose($s);
  exit;
}
