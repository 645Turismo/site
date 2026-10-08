<?php
// Pós-viagem: depois do último dia de trabalho, cada guia faz o relatório da viagem e, em seguida,
// envia a nota fiscal (quem não emite nota pula essa etapa). Tudo o que é obrigatório fica aqui.

const POS_VIAGEM_ETAPAS = [
  'relatorio' => ['Fazer o relatório', 'aviso'],
  'nf' => ['Enviar a nota fiscal', 'aviso'],
  'conferencia' => ['Em conferência', 'info'],
  'concluida' => ['Concluída', 'sucesso'],
];

/** Viagens já terminadas do guia, com a etapa do pós-viagem de cada uma (pendentes primeiro). */
function guia_pos_viagem_lista(array $g): array {
  $assumidas = "('" . implode("','", ESCALAS_ASSUMIDAS) . "')";
  $linhas = todos("SELECT v.id, v.codigo, v.nome, v.qtd_passageiros, v.instrucoes_nf, v.prazo_nf_dias_uteis,
      MIN(d.data) AS inicio, MAX(d.data) AS fim
    FROM escalas s JOIN diarias d ON d.id = s.diaria_id JOIN viagens v ON v.id = d.viagem_id
    WHERE s.guia_id = ? AND s.status IN $assumidas
    GROUP BY v.id, v.codigo, v.nome, v.qtd_passageiros, v.instrucoes_nf, v.prazo_nf_dias_uteis
    HAVING MAX(d.data) <= ?
    ORDER BY MAX(d.data) DESC", [$g['id'], hoje()]);
  $emiteNf = (int) ($g['emite_nf'] ?? 1) === 1;
  foreach ($linhas as &$v) {
    $escalas = todos("SELECT s.status, s.valor FROM escalas s JOIN diarias d ON d.id = s.diaria_id
      WHERE d.viagem_id = ? AND s.guia_id = ? AND s.status IN $assumidas", [$v['id'], $g['id']]);
    $status = array_column($escalas, 'status');
    $v['total'] = array_sum(array_map(fn($s) => (float) $s['valor'], $escalas));
    $v['relatorio'] = um("SELECT * FROM envios WHERE viagem_id = ? AND guia_id = ? AND tipo = 'relatorio' ORDER BY id DESC LIMIT 1", [$v['id'], $g['id']]);
    $v['nf'] = um("SELECT * FROM envios WHERE viagem_id = ? AND guia_id = ? AND tipo = 'nf' ORDER BY id DESC LIMIT 1", [$v['id'], $g['id']]);
    $v['prazo_nf'] = somar_dias_uteis($v['fim'], (int) $v['prazo_nf_dias_uteis']);
    if (!$v['relatorio'] || $v['relatorio']['status'] === 'reprovado') {
      $v['etapa'] = 'relatorio';
    } elseif ($emiteNf && in_array('aguardando_nf', $status, true)) {
      $v['etapa'] = 'nf';
    } elseif (in_array('nf_em_conferencia', $status, true)) {
      $v['etapa'] = 'conferencia';
    } else {
      $v['etapa'] = 'concluida';
    }
  }
  unset($v);
  usort($linhas, fn($a, $b) => [in_array($a['etapa'], ['relatorio', 'nf'], true) ? 0 : 1, -strtotime($a['fim'])]
    <=> [in_array($b['etapa'], ['relatorio', 'nf'], true) ? 0 : 1, -strtotime($b['fim'])]);
  return $linhas;
}

function guia_pos_viagem(): void {
  $g = exigir_guia();
  $viagens = guia_pos_viagem_lista($g);
  $aba = ($_GET['aba'] ?? '') === 'concluidas' ? 'concluidas' : 'pendentes';
  $pendentes = array_values(array_filter($viagens, fn($v) => in_array($v['etapa'], ['relatorio', 'nf'], true)));
  $outras = array_values(array_filter($viagens, fn($v) => !in_array($v['etapa'], ['relatorio', 'nf'], true)));
  exibir('guia/pos-viagem', [
    'titulo' => 'Pós-viagem',
    'menu' => 'pos',
    'p' => $g,
    'aba' => $aba,
    'pendentes' => $pendentes,
    'outras' => $outras,
    'emiteNf' => (int) ($g['emite_nf'] ?? 1) === 1,
    'empresa' => ['razao' => configuracao('empresa_razao_social'), 'cnpj' => configuracao('empresa_cnpj')],
  ], 'guia');
}

/** Comentário do guia durante a viagem (aviso para a equipe; não substitui o relatório). */
function guia_viagem_comentar(int $id): void {
  $g = exigir_guia();
  [$v, $escalas] = guia_carregar_viagem($g, $id);
  if (!array_filter($escalas, fn($s) => in_array($s['status'], ESCALAS_ASSUMIDAS, true))) {
    abortar(403, 'Os comentários ficam disponíveis depois que você confirma presença.');
  }
  $texto = trim((string) ($_POST['texto'] ?? ''));
  if (mb_strlen($texto) < 2 || mb_strlen($texto) > 2000) {
    flash('erro', 'Escreva o comentário (até 2.000 caracteres).');
    redirecionar("/guia/viagens/$id#comentarios");
  }
  inserir('viagem_comentarios', ['viagem_id' => $id, 'guia_id' => $g['id'], 'texto' => $texto, 'criado_em' => agora()]);
  auditar('viagem_comentario', 'viagem', $id);
  flash('sucesso', 'Comentário enviado para a equipe.');
  redirecionar("/guia/viagens/$id#comentarios");
}
