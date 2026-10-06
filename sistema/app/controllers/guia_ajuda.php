<?php
// Ajuda do guia: perguntas frequentes, guia prático, vídeos, contatos e chamados com a equipe.

// [rótulo, selo] — visão do guia
const STATUS_CHAMADO = [
  'aberto' => ['Aguardando a equipe', 'aviso'],
  'respondido' => ['Respondido', 'info'],
  'aguardando_confirmacao' => ['Confirme se resolveu', 'info'],
  'resolvido' => ['Resolvido', 'sucesso'],
];

function guia_ajuda(): void {
  $g = exigir_guia();
  $aba = (string) ($_GET['aba'] ?? 'duvidas');
  if (!in_array($aba, ['duvidas', 'chamados', 'contatos'], true)) {
    $aba = 'duvidas';
  }
  $conteudo = [];
  foreach (todos('SELECT * FROM orientacoes WHERE ativo = 1 ORDER BY secao, ordem, id') as $o) {
    $conteudo[$o['secao']][] = $o;
  }
  exibir('guia/ajuda', [
    'titulo' => 'Ajuda',
    'menu' => 'ajuda',
    'p' => $g,
    'aba' => $aba,
    'conteudo' => $conteudo,
    'chamados' => todos('SELECT c.*, m.nome AS motivo, v.codigo FROM chamados c LEFT JOIN chamado_motivos m ON m.id = c.motivo_id
      LEFT JOIN viagens v ON v.id = c.viagem_id WHERE c.guia_id = ? ORDER BY CASE WHEN c.status = \'resolvido\' THEN 1 ELSE 0 END, c.atualizado_em DESC', [$g['id']]),
    'motivos' => todos('SELECT * FROM chamado_motivos WHERE ativo = 1 ORDER BY ordem'),
    'viagens' => todos("SELECT DISTINCT v.id, v.codigo, v.nome, v.data_inicio FROM escalas s JOIN diarias d ON d.id = s.diaria_id
      JOIN viagens v ON v.id = d.viagem_id WHERE s.guia_id = ? AND s.status NOT IN ('recusado','cancelado') ORDER BY v.data_inicio DESC LIMIT 30", [$g['id']]),
    'viagemSelecionada' => (int) ($_GET['viagem'] ?? 0),
    'motivoSelecionado' => (int) ($_GET['motivo'] ?? 0),
  ], 'guia');
}

/** Guarda anexo opcional da mensagem. */
function chamado_anexo(): ?string {
  if (!upload_enviado('anexo')) {
    return null;
  }
  [$caminho] = salvar_upload('anexo', 'chamados');
  return $caminho;
}

function guia_chamado_criar(): void {
  $g = exigir_guia();
  $titulo = mb_substr(entrada('titulo'), 0, 200);
  $mensagem = trim((string) ($_POST['mensagem'] ?? ''));
  $motivo = (int) entrada('motivo_id');
  $viagem = (int) entrada('viagem_id') ?: null;
  if (mb_strlen($titulo) < 3 || mb_strlen($mensagem) < 5) {
    guardar_antigo($_POST);
    flash('erro', 'Escreva um título e a mensagem.');
    redirecionar('/guia/ajuda?aba=chamados#novo');
  }
  if ($viagem && !valor("SELECT 1 FROM escalas s JOIN diarias d ON d.id = s.diaria_id WHERE d.viagem_id = ? AND s.guia_id = ?", [$viagem, $g['id']])) {
    $viagem = null;
  }
  try {
    $anexo = chamado_anexo();
  } catch (RuntimeException $e) {
    guardar_antigo($_POST);
    flash('erro', 'Anexo: ' . $e->getMessage());
    redirecionar('/guia/ajuda?aba=chamados#novo');
  }
  $urgente = isset($_POST['urgente']) ? 1 : 0;
  $id = transacao(function () use ($g, $titulo, $mensagem, $motivo, $viagem, $urgente, $anexo) {
    $id = inserir('chamados', [
      'guia_id' => $g['id'], 'titulo' => $titulo, 'motivo_id' => $motivo ?: null, 'viagem_id' => $viagem, 'urgente' => $urgente,
      'status' => 'aberto', 'criado_em' => agora(), 'atualizado_em' => agora(),
    ]);
    inserir('chamado_mensagens', ['chamado_id' => $id, 'autor_tipo' => 'guia', 'autor_id' => $g['id'],
      'mensagem' => mb_substr($mensagem, 0, 5000), 'anexo_path' => $anexo, 'criado_em' => agora()]);
    return $id;
  });
  auditar('chamado_aberto', 'chamado', $id);
  enviar_email(email_equipe(), ($urgente ? 'URGENTE · ' : '') . 'Chamado de ' . primeiro_nome($g) . ': ' . $titulo,
    '<p><strong>' . e($g['nome']) . '</strong> (código ' . e($g['codigo']) . ') abriu um chamado' . ($urgente ? ' <strong>urgente</strong>' : '') . '.</p>'
    . '<p>' . nl2br(e($mensagem)) . '</p><p><a href="' . e(url_absoluta('/admin/atendimento/' . $id)) . '">Responder no Painel ADM</a></p>');
  flash('sucesso', 'Chamado aberto. A equipe responde por aqui e você recebe aviso por e-mail.');
  redirecionar('/guia/ajuda/chamados/' . $id);
}

function guia_chamado_carregar(array $g, int $id): array {
  return um('SELECT c.*, m.nome AS motivo, v.codigo, v.nome AS viagem FROM chamados c LEFT JOIN chamado_motivos m ON m.id = c.motivo_id
    LEFT JOIN viagens v ON v.id = c.viagem_id WHERE c.id = ? AND c.guia_id = ?', [$id, $g['id']]) ?? abortar(404, 'Chamado não encontrado.');
}

function guia_chamado(int $id): void {
  $g = exigir_guia();
  $c = guia_chamado_carregar($g, $id);
  exibir('comum/chamado', [
    'titulo' => $c['titulo'],
    'menu' => 'ajuda',
    'p' => $g,
    'c' => $c,
    'mensagens' => chamado_mensagens($id),
    'admin' => false,
    'base' => '/guia/ajuda/chamados/' . $id,
    'voltar' => '/guia/ajuda?aba=chamados',
  ], 'guia');
}

function chamado_mensagens(int $chamadoId): array {
  return todos("SELECT cm.*, CASE WHEN cm.autor_tipo = 'admin' THEN a.nome ELSE g.nome END AS autor
    FROM chamado_mensagens cm LEFT JOIN admins a ON cm.autor_tipo = 'admin' AND a.id = cm.autor_id
    LEFT JOIN guias g ON cm.autor_tipo = 'guia' AND g.id = cm.autor_id
    WHERE cm.chamado_id = ? ORDER BY cm.id", [$chamadoId]);
}

function guia_chamado_responder(int $id): void {
  $g = exigir_guia();
  $c = guia_chamado_carregar($g, $id);
  $mensagem = trim((string) ($_POST['mensagem'] ?? ''));
  if (mb_strlen($mensagem) < 2) {
    flash('erro', 'Escreva a mensagem.');
    redirecionar("/guia/ajuda/chamados/$id");
  }
  try {
    $anexo = chamado_anexo();
  } catch (RuntimeException $e) {
    flash('erro', 'Anexo: ' . $e->getMessage());
    redirecionar("/guia/ajuda/chamados/$id");
  }
  inserir('chamado_mensagens', ['chamado_id' => $id, 'autor_tipo' => 'guia', 'autor_id' => $g['id'],
    'mensagem' => mb_substr($mensagem, 0, 5000), 'anexo_path' => $anexo, 'criado_em' => agora()]);
  atualizar('chamados', ['status' => 'aberto', 'resolvido_em' => null, 'atualizado_em' => agora()], 'id = ?', [$id]);
  enviar_email(email_equipe(), 'Nova mensagem no chamado: ' . $c['titulo'],
    '<p><strong>' . e($g['nome']) . '</strong> respondeu:</p><p>' . nl2br(e($mensagem)) . '</p>'
    . '<p><a href="' . e(url_absoluta('/admin/atendimento/' . $id)) . '">Abrir no Painel ADM</a></p>');
  flash('sucesso', 'Mensagem enviada.');
  redirecionar("/guia/ajuda/chamados/$id");
}

function guia_chamado_resolvido(int $id): void {
  $g = exigir_guia();
  guia_chamado_carregar($g, $id);
  atualizar('chamados', ['status' => 'resolvido', 'resolvido_em' => agora(), 'atualizado_em' => agora()], 'id = ?', [$id]);
  auditar('chamado_resolvido_guia', 'chamado', $id);
  flash('sucesso', 'Obrigado! Chamado encerrado.');
  redirecionar('/guia/ajuda?aba=chamados');
}
