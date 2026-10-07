<?php
// Guias no Painel ADM: triagem de cadastros, banco de guias com filtros e ficha completa.

const ABAS_GUIAS = [
  'triagem' => ['Para triagem', ['em_analise']],
  'pendentes' => ['Com pendência', ['pendente']],
  'aprovados' => ['Aprovados', ['aprovado']],
  'inativos' => ['Inativos e reprovados', ['inativo', 'bloqueado', 'reprovado']],
  'pre' => ['Pré-cadastro', ['pre_cadastro']],
  'rascunhos' => ['Cadastro incompleto', ['rascunho']],
  'todos' => ['Todos', []],
];

// Ações de situação: [novo status, exige motivo, rótulo, e-mail ao guia (assunto)]
const ACOES_GUIA = [
  'aprovar' => ['aprovado', false, 'Aprovar cadastro', 'Seu cadastro foi aprovado'],
  'pendencia' => ['pendente', true, 'Pedir correção', 'Seu cadastro precisa de um ajuste'],
  'reprovar' => ['reprovado', true, 'Reprovar', 'Sobre seu cadastro na 645 Turismo'],
  'inativar' => ['inativo', false, 'Inativar', null],
  'bloquear' => ['bloqueado', true, 'Bloquear', null],
  'reativar' => ['aprovado', false, 'Reativar', 'Seu acesso foi reativado'],
];

function adm_guias(): void {
  $a = exigir_admin(['coordenador']);
  $aba = (string) ($_GET['aba'] ?? 'triagem');
  if (!isset(ABAS_GUIAS[$aba])) {
    $aba = 'triagem';
  }
  $f = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'funcao' => (int) ($_GET['funcao'] ?? 0),
    'idioma' => trim((string) ($_GET['idioma'] ?? '')),
  ];
  $where = ['g.anonimizado_em IS NULL'];
  $params = [];
  if (ABAS_GUIAS[$aba][1]) {
    $where[] = 'g.status IN (' . implode(',', array_fill(0, count(ABAS_GUIAS[$aba][1]), '?')) . ')';
    $params = array_merge($params, ABAS_GUIAS[$aba][1]);
  }
  if ($f['q'] !== '') {
    $where[] = '(g.nome LIKE ? OR g.nome_social LIKE ? OR g.codigo LIKE ? OR g.email LIKE ?' . (so_digitos($f['q']) !== '' ? ' OR g.cpf LIKE ?' : '') . ')';
    array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
    if (so_digitos($f['q']) !== '') {
      $params[] = '%' . so_digitos($f['q']) . '%';
    }
  }
  if ($f['funcao']) {
    $where[] = 'EXISTS (SELECT 1 FROM guia_funcoes gf WHERE gf.guia_id = g.id AND gf.funcao_id = ?)';
    $params[] = $f['funcao'];
  }
  if ($f['idioma'] !== '') {
    $where[] = 'EXISTS (SELECT 1 FROM guia_idiomas gi WHERE gi.guia_id = g.id AND gi.idioma LIKE ?)';
    $params[] = "%{$f['idioma']}%";
  }
  $guias = todos('SELECT g.*,
      (SELECT ' . sql_lista('fn.nome') . ' FROM guia_funcoes gf JOIN funcoes fn ON fn.id = gf.funcao_id WHERE gf.guia_id = g.id) AS funcoes,
      (SELECT ' . sql_lista('gi.idioma') . ' FROM guia_idiomas gi WHERE gi.guia_id = g.id) AS idiomas,
      (SELECT COUNT(*) FROM guia_documentos d WHERE d.guia_id = g.id AND d.atual = 1 AND d.status = \'enviado\') AS docs_revisar
    FROM guias g WHERE ' . implode(' AND ', $where) . '
    ORDER BY CASE g.status WHEN \'em_analise\' THEN 0 WHEN \'pendente\' THEN 1 ELSE 2 END, g.nome LIMIT 500', $params);
  $contagem = [];
  foreach (ABAS_GUIAS as $k => [, $st]) {
    $contagem[$k] = $st ? (int) valor('SELECT COUNT(*) FROM guias WHERE anonimizado_em IS NULL AND status IN (' . implode(',', array_fill(0, count($st), '?')) . ')', $st) : null;
  }
  exibir('admin/guias', [
    'titulo' => 'Guias',
    'menu' => 'guias',
    'a' => $a,
    'aba' => $aba,
    'f' => $f,
    'guias' => $guias,
    'contagem' => $contagem,
    'cat' => guia_catalogos(),
  ], 'admin');
}

function adm_guia(int $id): void {
  $a = exigir_admin(['coordenador']);
  $g = um('SELECT * FROM guias WHERE id = ?', [$id]) ?? abortar(404, 'Guia não encontrado.');
  auditar('ficha_guia_visualizada', 'guia', $id);
  exibir('admin/guia', [
    'titulo' => $g['nome'],
    'menu' => 'guias',
    'a' => $a,
    'g' => $g,
    'x' => guia_extras($id),
    'funcoesNomes' => array_column(todos('SELECT f.nome FROM guia_funcoes gf JOIN funcoes f ON f.id = gf.funcao_id WHERE gf.guia_id = ? ORDER BY f.ordem', [$id]), 'nome'),
    'historico' => todos("SELECT v.id, v.codigo, v.nome, MIN(d.data) AS inicio, COUNT(*) AS diarias, MAX(s.status) AS status
      FROM escalas s JOIN diarias d ON d.id = s.diaria_id JOIN viagens v ON v.id = d.viagem_id
      WHERE s.guia_id = ? AND s.status NOT IN ('cancelado') GROUP BY v.id, v.codigo, v.nome ORDER BY MIN(d.data) DESC LIMIT 20", [$id]),
    'faltas' => (int) valor("SELECT COUNT(*) FROM escalas WHERE guia_id = ? AND status = 'falta'", [$id]),
    'recusas' => (int) valor("SELECT COUNT(*) FROM escalas WHERE guia_id = ? AND status = 'recusado'", [$id]),
    'proximaDisponibilidade' => todos('SELECT data, tipo, periodo FROM disponibilidade WHERE guia_id = ? AND data BETWEEN ? AND ? ORDER BY data',
      [$id, hoje(), date('Y-m-d', strtotime('+30 days'))]),
    'faltam' => guia_documentos_faltando($id),
  ], 'admin');
}

function adm_guia_status(int $id): void {
  $a = exigir_admin(['coordenador']);
  $g = um('SELECT * FROM guias WHERE id = ?', [$id]) ?? abortar(404);
  $acao = entrada('acao');
  if (!isset(ACOES_GUIA[$acao])) {
    abortar(400);
  }
  [$novo, $exigeMotivo, $rotulo, $assunto] = ACOES_GUIA[$acao];
  $motivo = mb_substr(trim((string) ($_POST['motivo'] ?? '')), 0, 1000);
  if ($exigeMotivo && mb_strlen($motivo) < 5) {
    flash('erro', 'Escreva o motivo: ele aparece para o guia.');
    redirecionar("/admin/guias/$id");
  }
  $dados = ['status' => $novo, 'status_motivo' => $motivo ?: null, 'atualizado_em' => agora()];
  if ($novo === 'aprovado' && !$g['aprovado_em']) {
    $dados += ['aprovado_em' => agora(), 'aprovado_por' => $a['id']];
  }
  atualizar('guias', $dados, 'id = ?', [$id]);
  if ($acao === 'aprovar') {
    // Aprovação do cadastro aprova também os documentos que estavam em conferência.
    q("UPDATE guia_documentos SET status = 'aprovado', revisado_por = ?, revisado_em = ? WHERE guia_id = ? AND atual = 1 AND status = 'enviado'",
      [$a['id'], agora(), $id]);
  }
  if (in_array($novo, ['inativo', 'bloqueado', 'reprovado'], true)) {
    // Sai das escalas futuras ainda não realizadas.
    q("UPDATE escalas SET status = 'cancelado', atualizado_em = ? WHERE guia_id = ? AND status IN ('convidado','confirmado')
      AND diaria_id IN (SELECT id FROM diarias WHERE data >= ?)", [agora(), $id, hoje()]);
  }
  auditar('guia_' . $acao, 'guia', $id, ['motivo' => $motivo]);
  if ($assunto && $g['email']) {
    $corpo = match ($acao) {
      'aprovar', 'reativar' => '<p>Boas notícias: seu cadastro de guia na 645 Turismo está <strong>aprovado</strong>. A partir de agora você recebe convites para viagens e tours.</p>'
        . '<p>Mantenha sua disponibilidade em dia na Área do Guia.</p>',
      'pendencia' => '<p>Conferimos seu cadastro e precisamos de um ajuste:</p><p><strong>' . e($motivo) . '</strong></p>'
        . '<p>Entre na Área do Guia, corrija em Perfil e clique em "Já corrigi, enviar para análise".</p>',
      default => '<p>Agradecemos seu interesse. Neste momento seu cadastro não foi aprovado.</p><p>' . e($motivo) . '</p>',
    };
    enviar_email($g['email'], $assunto, '<p>Olá, ' . e(primeiro_nome($g)) . '.</p>' . $corpo
      . '<p><a href="' . e(url_absoluta('/')) . '" style="display:inline-block;padding:12px 22px;background:#53D9B2;color:#000;text-decoration:none;font-weight:bold;border-radius:999px">Abrir a Área do Guia</a></p>');
  }
  flash('sucesso', $rotulo . ': feito.' . ($assunto ? ' O guia foi avisado por e-mail.' : ''));
  redirecionar("/admin/guias/$id");
}

function adm_guia_notas(int $id): void {
  exigir_admin(['coordenador']);
  um('SELECT id FROM guias WHERE id = ?', [$id]) ?? abortar(404);
  atualizar('guias', ['notas_internas' => mb_substr(trim((string) ($_POST['notas_internas'] ?? '')), 0, 5000) ?: null], 'id = ?', [$id]);
  flash('sucesso', 'Notas salvas (visíveis só para a equipe).');
  redirecionar("/admin/guias/$id#notas");
}

function adm_guia_documento(int $id, int $docId): void {
  $a = exigir_admin(['coordenador']);
  $g = um('SELECT * FROM guias WHERE id = ?', [$id]) ?? abortar(404);
  $d = um('SELECT * FROM guia_documentos WHERE id = ? AND guia_id = ?', [$docId, $id]) ?? abortar(404);
  $aprovar = entrada('decisao') === 'aprovar';
  $motivo = mb_substr(trim((string) ($_POST['motivo'] ?? '')), 0, 500);
  if (!$aprovar && mb_strlen($motivo) < 3) {
    flash('erro', 'Diga o motivo para o guia reenviar o documento.');
    redirecionar("/admin/guias/$id#documentos");
  }
  atualizar('guia_documentos', ['status' => $aprovar ? 'aprovado' : 'reprovado', 'motivo' => $aprovar ? null : $motivo,
    'revisado_por' => $a['id'], 'revisado_em' => agora()], 'id = ?', [$docId]);
  auditar($aprovar ? 'documento_aprovado' : 'documento_reprovado', 'guia', $id, ['documento' => $d['tipo'], 'motivo' => $motivo]);
  if (!$aprovar && $g['email']) {
    enviar_email($g['email'], 'Reenvie um documento',
      '<p>Olá, ' . e(primeiro_nome($g)) . '.</p><p>Precisamos que você reenvie: <strong>' . e(DOCUMENTOS_GUIA[$d['tipo']][0] ?? $d['tipo']) . '</strong>.</p>'
      . '<p>Motivo: ' . e($motivo) . '</p><p>Envie pela Área do Guia, em Perfil → Documentos.</p>');
  }
  flash('sucesso', $aprovar ? 'Documento aprovado.' : 'Documento devolvido; o guia foi avisado.');
  redirecionar("/admin/guias/$id#documentos");
}

function adm_guia_senha(int $id): void {
  exigir_admin(['coordenador']);
  $g = um('SELECT * FROM guias WHERE id = ?', [$id]) ?? abortar(404);
  if (!$g['email'] || !$g['senha_hash']) {
    flash('erro', 'Este guia ainda não concluiu o cadastro.');
    redirecionar("/admin/guias/$id");
  }
  $token = criar_token_reset('guia', $id, 24 * 60);
  enviar_email($g['email'], 'Crie uma nova senha',
    '<p>Olá, ' . e(primeiro_nome($g)) . '.</p><p>A equipe da 645 Turismo enviou um link para você criar uma nova senha na Área do Guia.</p>'
    . '<p><a href="' . e(url_absoluta('/redefinir-senha?token=' . $token)) . '" style="display:inline-block;padding:12px 22px;background:#53D9B2;color:#000;text-decoration:none;font-weight:bold;border-radius:999px">Criar nova senha</a></p><p>O link vale por 24 horas.</p>');
  auditar('guia_link_senha_enviado', 'guia', $id);
  flash('sucesso', 'Link para nova senha enviado para ' . $g['email'] . '.');
  redirecionar("/admin/guias/$id");
}

/**
 * Exclui um cadastro que não chegou a ser usado: incompleto (rascunho) ou pré-cadastro sem trabalho.
 * Serve para destravar quem não consegue continuar: a pessoa recomeça do zero com o mesmo CPF.
 * Não exclui se houver convite/escala, nota, pagamento ou chamado ligados ao guia.
 */
function adm_guia_excluir_incompleto(int $id): void {
  $a = exigir_admin(['coordenador']);
  $g = um('SELECT * FROM guias WHERE id = ?', [$id]) ?? abortar(404);
  if (!in_array($g['status'], ['rascunho', 'pre_cadastro'], true)) {
    flash('erro', 'Só é possível excluir cadastro incompleto ou pré-cadastro. Para os demais, use Inativar.');
    redirecionar("/admin/guias/$id");
  }
  foreach (['escalas' => 'convites ou escalas', 'envios' => 'relatórios ou notas fiscais', 'pagamentos' => 'pagamentos', 'chamados' => 'chamados'] as $tabela => $rotulo) {
    if (valor("SELECT 1 FROM $tabela WHERE guia_id = ?", [$id])) {
      flash('erro', "Este guia já tem $rotulo no sistema e não pode ser excluído. Use Inativar.");
      redirecionar("/admin/guias/$id");
    }
  }
  transacao(function () use ($id) {
    foreach (todos('SELECT arquivo_path FROM guia_documentos WHERE guia_id = ?', [$id]) as $d) {
      apagar_arquivo($d['arquivo_path']);
    }
    apagar_arquivo(valor('SELECT foto_path FROM guias WHERE id = ?', [$id]));
    foreach (['guia_documentos', 'guia_funcoes', 'guia_idiomas', 'guia_regioes', 'guia_dados_bancarios', 'disponibilidade'] as $t) {
      q("DELETE FROM $t WHERE guia_id = ?", [$id]);
    }
    q("DELETE FROM password_resets WHERE usuario_id = ? AND usuario_tipo IN ('guia', 'cadastro')", [$id]);
    q("DELETE FROM notificacoes WHERE destinatario_tipo = 'guia' AND destinatario_id = ?", [$id]);
    q('DELETE FROM guias WHERE id = ?', [$id]);
  });
  // A auditoria guarda só o necessário para rastrear a exclusão (sem dados pessoais).
  auditar('guia_cadastro_excluido', 'guia', $id, ['status' => $g['status'], 'codigo' => $g['codigo']]);
  flash('sucesso', 'Cadastro de ' . ($g['nome_social'] ?: $g['nome']) . ' excluído. A pessoa pode se cadastrar de novo com o mesmo CPF.');
  redirecionar('/admin/guias?aba=' . ($g['status'] === 'rascunho' ? 'rascunhos' : 'pre'));
}
