<?php
// Atendimento (chamados e recados para os guias) e Conteúdo da Ajuda, no Painel ADM.

// [rótulo, selo] — visão da equipe
const STATUS_CHAMADO_ADM = [
  'aberto' => ['Aguardando resposta', 'aviso'],
  'respondido' => ['Aguardando o guia', 'info'],
  'aguardando_confirmacao' => ['Aguardando confirmação', 'info'],
  'resolvido' => ['Resolvido', 'sucesso'],
];

const SECOES_CONTEUDO = [
  'faq' => 'Perguntas frequentes',
  'guia_pratico' => 'Guia prático',
  'video' => 'Vídeos',
  'contato' => 'Contatos da equipe',
];

// ---------- Chamados ----------

function adm_atendimento(): void {
  $a = exigir_admin(['coordenador', 'financeiro']);
  $aba = in_array($_GET['aba'] ?? '', ['abertos', 'andamento', 'resolvidos', 'recados'], true) ? $_GET['aba'] : 'abertos';
  $filtros = ['abertos' => "c.status = 'aberto'", 'andamento' => "c.status IN ('respondido','aguardando_confirmacao')", 'resolvidos' => "c.status = 'resolvido'"];
  $chamados = $aba === 'recados' ? [] : todos('SELECT c.*, g.nome AS guia, g.codigo AS guia_codigo, m.nome AS motivo, v.codigo
    FROM chamados c JOIN guias g ON g.id = c.guia_id LEFT JOIN chamado_motivos m ON m.id = c.motivo_id LEFT JOIN viagens v ON v.id = c.viagem_id
    WHERE ' . $filtros[$aba] . ' ORDER BY c.urgente DESC, c.atualizado_em ' . ($aba === 'resolvidos' ? 'DESC LIMIT 100' : 'ASC'));
  exibir('admin/atendimento', [
    'titulo' => 'Atendimento',
    'menu' => 'atendimento',
    'a' => $a,
    'aba' => $aba,
    'chamados' => $chamados,
    'contagem' => [
      'abertos' => (int) valor("SELECT COUNT(*) FROM chamados WHERE status = 'aberto'"),
      'andamento' => (int) valor("SELECT COUNT(*) FROM chamados WHERE status IN ('respondido','aguardando_confirmacao')"),
    ],
    'avisos' => $aba === 'recados' ? todos('SELECT av.*, f.nome AS funcao, v.codigo FROM avisos av LEFT JOIN funcoes f ON f.id = av.funcao_id
      LEFT JOIN viagens v ON v.id = av.viagem_id ORDER BY av.ativo DESC, av.criado_em DESC') : [],
    'funcoes' => todos('SELECT id, nome FROM funcoes WHERE ativo = 1 ORDER BY ordem'),
    'viagensAtivas' => todos("SELECT id, codigo, nome FROM viagens WHERE status = 'publicada' ORDER BY data_inicio"),
  ], 'admin');
}

function adm_chamado_carregar(int $id): array {
  return um('SELECT c.*, g.nome AS guia, g.codigo AS guia_codigo, g.celular, g.email, g.nome_social, g.nome AS nome_guia,
      m.nome AS motivo, v.codigo, v.nome AS viagem
    FROM chamados c JOIN guias g ON g.id = c.guia_id LEFT JOIN chamado_motivos m ON m.id = c.motivo_id LEFT JOIN viagens v ON v.id = c.viagem_id
    WHERE c.id = ?', [$id]) ?? abortar(404, 'Chamado não encontrado.');
}

function adm_chamado(int $id): void {
  $a = exigir_admin(['coordenador', 'financeiro']);
  $c = adm_chamado_carregar($id);
  exibir('comum/chamado', [
    'titulo' => $c['titulo'],
    'menu' => 'atendimento',
    'a' => $a,
    'c' => $c,
    'mensagens' => chamado_mensagens($id),
    'admin' => true,
    'base' => '/admin/atendimento/' . $id,
    'voltar' => '/admin/atendimento',
  ], 'admin');
}

function adm_chamado_responder(int $id): void {
  $a = exigir_admin(['coordenador', 'financeiro']);
  $c = adm_chamado_carregar($id);
  $mensagem = trim((string) ($_POST['mensagem'] ?? ''));
  $status = isset(STATUS_CHAMADO_ADM[entrada('status')]) && entrada('status') !== 'aberto' ? entrada('status') : 'respondido';
  if (mb_strlen($mensagem) < 2) {
    flash('erro', 'Escreva a resposta.');
    redirecionar("/admin/atendimento/$id");
  }
  try {
    $anexo = chamado_anexo();
  } catch (RuntimeException $e) {
    flash('erro', 'Anexo: ' . $e->getMessage());
    redirecionar("/admin/atendimento/$id");
  }
  inserir('chamado_mensagens', ['chamado_id' => $id, 'autor_tipo' => 'admin', 'autor_id' => $a['id'],
    'mensagem' => mb_substr($mensagem, 0, 5000), 'anexo_path' => $anexo, 'criado_em' => agora()]);
  atualizar('chamados', ['status' => $status, 'responsavel_admin_id' => $a['id'], 'atualizado_em' => agora(),
    'resolvido_em' => $status === 'resolvido' ? agora() : null], 'id = ?', [$id]);
  auditar('chamado_respondido', 'chamado', $id, ['status' => $status]);
  if ($c['email']) {
    enviar_email($c['email'], 'Resposta da equipe: ' . $c['titulo'],
      '<p>Olá, ' . e(primeiro_nome(['nome' => $c['nome_guia'], 'nome_social' => $c['nome_social']])) . '.</p><p>A equipe respondeu seu chamado:</p>'
      . '<p>' . nl2br(e($mensagem)) . '</p><p><a href="' . e(url_absoluta('/guia/ajuda/chamados/' . $id)) . '">Ver conversa</a></p>');
  }
  flash('sucesso', 'Resposta enviada. O guia foi avisado por e-mail.');
  redirecionar($status === 'resolvido' ? '/admin/atendimento' : "/admin/atendimento/$id");
}

// ---------- Recados (avisos na tela inicial do guia) ----------

function adm_aviso_criar(): void {
  $a = exigir_admin(['coordenador']);
  $titulo = mb_substr(entrada('titulo'), 0, 200);
  $mensagem = trim((string) ($_POST['mensagem'] ?? ''));
  $publico = in_array(entrada('publico'), ['todos', 'funcao', 'viagem'], true) ? entrada('publico') : 'todos';
  if (mb_strlen($titulo) < 3 || mb_strlen($mensagem) < 3) {
    guardar_antigo($_POST);
    flash('erro', 'Escreva o título e a mensagem do recado.');
    redirecionar('/admin/atendimento?aba=recados');
  }
  inserir('avisos', [
    'titulo' => $titulo, 'mensagem' => mb_substr($mensagem, 0, 3000), 'publico' => $publico,
    'funcao_id' => $publico === 'funcao' ? ((int) entrada('funcao_id') ?: null) : null,
    'viagem_id' => $publico === 'viagem' ? ((int) entrada('viagem_id') ?: null) : null,
    'inicio' => data_valida(entrada('inicio')) ? entrada('inicio') : null,
    'fim' => data_valida(entrada('fim')) ? entrada('fim') : null,
    'ativo' => 1, 'criado_por' => $a['id'], 'criado_em' => agora(),
  ]);
  auditar('aviso_criado', 'aviso', null, ['titulo' => $titulo]);
  limpar_antigo();
  flash('sucesso', 'Recado publicado na tela inicial dos guias.');
  redirecionar('/admin/atendimento?aba=recados');
}

function adm_aviso_status(int $id): void {
  exigir_admin(['coordenador']);
  $av = um('SELECT * FROM avisos WHERE id = ?', [$id]) ?? abortar(404);
  atualizar('avisos', ['ativo' => (int) $av['ativo'] ? 0 : 1], 'id = ?', [$id]);
  flash('sucesso', (int) $av['ativo'] ? 'Recado escondido.' : 'Recado publicado de novo.');
  redirecionar('/admin/atendimento?aba=recados');
}

function adm_aviso_remover(int $id): void {
  exigir_admin(['coordenador']);
  q('DELETE FROM avisos WHERE id = ?', [$id]);
  auditar('aviso_removido', 'aviso', $id);
  flash('sucesso', 'Recado apagado.');
  redirecionar('/admin/atendimento?aba=recados');
}

// ---------- Conteúdo da Ajuda ----------

function adm_conteudo(): void {
  $a = exigir_admin(['coordenador']);
  $secao = isset(SECOES_CONTEUDO[$_GET['secao'] ?? '']) ? $_GET['secao'] : 'faq';
  exibir('admin/conteudo', [
    'titulo' => 'Conteúdo',
    'menu' => 'conteudo',
    'a' => $a,
    'secao' => $secao,
    'itens' => todos('SELECT * FROM orientacoes WHERE secao = ? ORDER BY ativo DESC, ordem, id', [$secao]),
    'contagem' => array_column(todos('SELECT secao, COUNT(*) AS n FROM orientacoes GROUP BY secao'), 'n', 'secao'),
    'editar' => isset($_GET['editar']) ? um('SELECT * FROM orientacoes WHERE id = ?', [(int) $_GET['editar']]) : null,
  ], 'admin');
}

function adm_conteudo_ler(): array {
  $secao = isset(SECOES_CONTEUDO[entrada('secao')]) ? entrada('secao') : 'faq';
  $d = [
    'secao' => $secao,
    'categoria' => mb_substr(entrada('categoria'), 0, 80) ?: null,
    'titulo' => mb_substr(entrada('titulo'), 0, 255),
    'conteudo' => mb_substr(trim((string) ($_POST['conteudo'] ?? '')), 0, 10000) ?: null,
    'video_url' => mb_substr(entrada('video_url'), 0, 255) ?: null,
    'ordem' => max(0, min(999, (int) entrada('ordem', '0'))),
    'atualizado_em' => agora(),
  ];
  $erros = [];
  if (mb_strlen($d['titulo']) < 3) {
    $erros[] = $secao === 'faq' ? 'Escreva a pergunta.' : 'Escreva o título.';
  }
  if ($secao === 'video' && (!$d['video_url'] || !preg_match('#^https://#i', $d['video_url']))) {
    $erros[] = 'Cole o link do vídeo (YouTube ou Vimeo, começando com https://).';
  }
  return [$d, $erros];
}

function adm_conteudo_criar(): void {
  exigir_admin(['coordenador']);
  [$d, $erros] = adm_conteudo_ler();
  if ($erros) {
    guardar_antigo($_POST);
    flash('erro', implode(' ', $erros));
    redirecionar('/admin/conteudo?secao=' . $d['secao']);
  }
  $id = inserir('orientacoes', $d + ['ativo' => 1]);
  auditar('conteudo_criado', 'orientacao', $id);
  limpar_antigo();
  flash('sucesso', 'Publicado na Ajuda dos guias.');
  redirecionar('/admin/conteudo?secao=' . $d['secao']);
}

function adm_conteudo_salvar(int $id): void {
  exigir_admin(['coordenador']);
  um('SELECT id FROM orientacoes WHERE id = ?', [$id]) ?? abortar(404);
  [$d, $erros] = adm_conteudo_ler();
  if ($erros) {
    flash('erro', implode(' ', $erros));
    redirecionar('/admin/conteudo?secao=' . $d['secao'] . '&editar=' . $id);
  }
  atualizar('orientacoes', $d, 'id = ?', [$id]);
  auditar('conteudo_editado', 'orientacao', $id);
  flash('sucesso', 'Conteúdo atualizado.');
  redirecionar('/admin/conteudo?secao=' . $d['secao']);
}

function adm_conteudo_status(int $id): void {
  exigir_admin(['coordenador']);
  $o = um('SELECT * FROM orientacoes WHERE id = ?', [$id]) ?? abortar(404);
  atualizar('orientacoes', ['ativo' => (int) $o['ativo'] ? 0 : 1], 'id = ?', [$id]);
  flash('sucesso', (int) $o['ativo'] ? 'Escondido da Ajuda.' : 'Publicado de novo.');
  redirecionar('/admin/conteudo?secao=' . $o['secao']);
}

function adm_conteudo_remover(int $id): void {
  exigir_admin(['coordenador']);
  $o = um('SELECT * FROM orientacoes WHERE id = ?', [$id]) ?? abortar(404);
  q('DELETE FROM orientacoes WHERE id = ?', [$id]);
  auditar('conteudo_removido', 'orientacao', $id);
  flash('sucesso', 'Apagado.');
  redirecionar('/admin/conteudo?secao=' . $o['secao']);
}
