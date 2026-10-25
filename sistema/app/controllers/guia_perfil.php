<?php
// Perfil do guia: mesmos formulários do cadastro, em abas.

const ABAS_PERFIL = [
  'dados' => 'Dados',
  'atuacao' => 'Atuação',
  'documentos' => 'Documentos',
  'recebimento' => 'Recebimento',
  'senha' => 'Senha',
  'privacidade' => 'Privacidade',
];

/** Abas em uso (sem Documentos enquanto o envio estiver desligado). */
function abas_perfil(): array {
  return DOCUMENTOS_ATIVOS ? ABAS_PERFIL : array_diff_key(ABAS_PERFIL, ['documentos' => true]);
}

function guia_perfil(): void {
  $g = exigir_guia();
  $aba = (string) ($_GET['aba'] ?? 'dados');
  if (!isset(abas_perfil()[$aba])) {
    $aba = 'dados';
  }
  exibir('guia/perfil', [
    'titulo' => 'Perfil',
    'menu' => 'perfil',
    'p' => $g,
    'g' => $g,
    'aba' => $aba,
    'x' => guia_extras((int) $g['id']),
    'cat' => guia_catalogos(),
    'exigeCadastur' => (bool) valor('SELECT 1 FROM guia_funcoes gf JOIN funcoes f ON f.id = gf.funcao_id WHERE gf.guia_id = ? AND f.exige_cadastur = 1', [$g['id']]),
    'faltam' => guia_documentos_faltando((int) $g['id']),
  ], 'guia');
}

function guia_perfil_volta(string $aba, array $erros): never {
  guardar_antigo($_POST);
  flash('erro', implode(' ', $erros));
  redirecionar('/guia/perfil?aba=' . $aba);
}

function guia_perfil_ok(string $aba, string $msg): never {
  limpar_antigo();
  flash('sucesso', $msg);
  redirecionar('/guia/perfil?aba=' . $aba);
}

function guia_perfil_pessoais(): void {
  $g = exigir_guia();
  [$d, $erros] = guia_ler_pessoais(false, (int) $g['id']);
  if ($erros) {
    guia_perfil_volta('dados', $erros);
  }
  atualizar('guias', $d + ['atualizado_em' => agora()], 'id = ?', [$g['id']]);
  auditar('perfil_dados_editados', 'guia', (int) $g['id']);
  guia_perfil_ok('dados', 'Dados atualizados.');
}

function guia_perfil_atuacao(): void {
  $g = exigir_guia();
  [$d, $funcoes, $idiomas, $regioes, $erros] = guia_ler_atuacao();
  if ($erros) {
    guia_perfil_volta('atuacao', $erros);
  }
  atualizar('guias', $d + ['atualizado_em' => agora()], 'id = ?', [$g['id']]);
  guia_salvar_atuacao((int) $g['id'], $funcoes, $idiomas, $regioes);
  auditar('perfil_atuacao_editada', 'guia', (int) $g['id']);
  guia_perfil_ok('atuacao', 'Atuação atualizada.');
}

function guia_perfil_documentos(): void {
  $g = exigir_guia();
  if (!DOCUMENTOS_ATIVOS) {
    abortar(404);
  }
  [$salvos, $erros] = guia_salvar_documentos((int) $g['id'], 'guia');
  if ($erros) {
    guia_perfil_volta('documentos', $erros);
  }
  if (!$salvos) {
    guia_perfil_volta('documentos', ['Escolha pelo menos um arquivo para enviar.']);
  }
  if ($g['status'] !== 'rascunho') {
    enviar_email(email_equipe(), 'Documento atualizado: ' . $g['nome'],
      '<p><strong>' . e($g['nome']) . '</strong> (código ' . e($g['codigo']) . ') enviou ' . $salvos . ' documento(s) novo(s) para conferência.</p>'
      . '<p><a href="' . e(url_absoluta('/admin/guias/' . (int) $g['id'])) . '">Abrir ficha no Painel ADM</a></p>');
  }
  guia_perfil_ok('documentos', 'Documento(s) enviado(s). A equipe vai conferir; seu cadastro continua ativo.');
}

function guia_perfil_recebimento(): void {
  $g = exigir_guia();
  [$d, $b, $erros] = guia_ler_recebimento();
  if ($erros) {
    guia_perfil_volta('recebimento', $erros);
  }
  atualizar('guias', $d + ['atualizado_em' => agora()], 'id = ?', [$g['id']]);
  guia_salvar_bancarios((int) $g['id'], $b);
  auditar('perfil_recebimento_editado', 'guia', (int) $g['id']);
  // Pagamento em andamento: a equipe precisa saber da troca de conta/PIX.
  if (valor("SELECT 1 FROM escalas WHERE guia_id = ? AND status IN ('aguardando_nf','nf_em_conferencia','a_pagar')", [$g['id']])) {
    enviar_email(email_equipe(), 'Dados de pagamento alterados: ' . $g['nome'],
      '<p><strong>' . e($g['nome']) . '</strong> (código ' . e($g['codigo']) . ') alterou CNPJ/PIX/conta e tem pagamentos em andamento. Confira antes de pagar.</p>');
  }
  guia_perfil_ok('recebimento', 'Dados de recebimento atualizados.');
}

function guia_perfil_senha(): void {
  $g = exigir_guia();
  if (!password_verify((string) ($_POST['senha_atual'] ?? ''), (string) $g['senha_hash'])) {
    flash('erro', 'A senha atual não confere.');
    redirecionar('/guia/perfil?aba=senha');
  }
  $erro = erro_senha((string) ($_POST['senha'] ?? ''), (string) ($_POST['confirmacao'] ?? ''));
  if ($erro) {
    flash('erro', $erro);
    redirecionar('/guia/perfil?aba=senha');
  }
  atualizar('guias', ['senha_hash' => hash_senha($_POST['senha']), 'atualizado_em' => agora()], 'id = ?', [$g['id']]);
  auditar('senha_alterada', 'guia', (int) $g['id']);
  flash('sucesso', 'Senha alterada.');
  redirecionar('/guia/perfil?aba=senha');
}

/** Guia com cadastro "com pendência" avisa que corrigiu: volta para análise. */
function guia_perfil_reenviar(): void {
  $g = exigir_guia();
  if ($g['status'] !== 'pendente') {
    redirecionar('/guia/perfil');
  }
  $faltam = guia_documentos_faltando((int) $g['id']);
  if ($faltam) {
    flash('erro', 'Antes de reenviar, envie: ' . implode(', ', $faltam) . '.');
    redirecionar('/guia/perfil?aba=documentos');
  }
  atualizar('guias', ['status' => 'em_analise', 'atualizado_em' => agora()], 'id = ?', [$g['id']]);
  auditar('cadastro_reenviado', 'guia', (int) $g['id']);
  enviar_email(email_equipe(), 'Cadastro corrigido: ' . $g['nome'],
    '<p><strong>' . e($g['nome']) . '</strong> corrigiu a pendência e reenviou o cadastro para análise.</p>'
    . '<p><a href="' . e(url_absoluta('/admin/guias/' . (int) $g['id'])) . '">Abrir ficha no Painel ADM</a></p>');
  flash('sucesso', 'Cadastro reenviado para análise.');
  redirecionar('/guia/hoje');
}

// ---------- Privacidade (LGPD): cópia dos dados, pedidos ao titular e revogação ----------

const PEDIDOS_PRIVACIDADE = [
  'acesso' => 'Acesso aos meus dados',
  'correcao' => 'Correção de dados',
  'exclusao' => 'Exclusão ou anonimização dos meus dados',
  'portabilidade' => 'Portabilidade dos meus dados',
  'informacoes' => 'Informações sobre uso e compartilhamento',
];

/** Baixa uma cópia dos dados do próprio prestador (JSON). */
function guia_perfil_privacidade_baixar(): void {
  $g = exigir_guia();
  auditar('lgpd_copia_dados', 'guia', (int) $g['id']);
  header('Content-Type: application/json; charset=utf-8');
  header('Content-Disposition: attachment; filename="meus-dados-645turismo.json"');
  header('Cache-Control: no-store');
  echo json_encode(lgpd_exportar_guia((int) $g['id']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
  exit;
}

/** Pedido do titular: vira um chamado para a equipe, que responde pelo próprio sistema. */
function guia_perfil_privacidade_solicitar(): void {
  $g = exigir_guia();
  $tipo = entrada('tipo');
  if (!isset(PEDIDOS_PRIVACIDADE[$tipo])) {
    flash('erro', 'Escolha o tipo de pedido.');
    redirecionar('/guia/perfil?aba=privacidade');
  }
  $detalhe = mb_substr(trim((string) ($_POST['detalhe'] ?? '')), 0, 2000);
  $titulo = 'Privacidade (LGPD): ' . PEDIDOS_PRIVACIDADE[$tipo];
  $motivo = valor("SELECT id FROM chamado_motivos WHERE nome LIKE 'Privacidade (LGPD)%'");
  $mensagem = 'Pedido do titular de dados: ' . PEDIDOS_PRIVACIDADE[$tipo] . '.' . ($detalhe !== '' ? "\n\n" . $detalhe : '');
  $id = transacao(function () use ($g, $titulo, $motivo, $mensagem) {
    $id = inserir('chamados', [
      'guia_id' => $g['id'], 'titulo' => $titulo, 'motivo_id' => $motivo ?: null, 'urgente' => 0,
      'status' => 'aberto', 'criado_em' => agora(), 'atualizado_em' => agora(),
    ]);
    inserir('chamado_mensagens', ['chamado_id' => $id, 'autor_tipo' => 'guia', 'autor_id' => $g['id'],
      'mensagem' => $mensagem, 'criado_em' => agora()]);
    return $id;
  });
  auditar('lgpd_pedido_titular', 'chamado', $id, ['tipo' => $tipo]);
  enviar_email(email_equipe(), 'Pedido de privacidade (LGPD) de ' . primeiro_nome($g),
    '<p><strong>' . e($g['nome']) . '</strong> (código ' . e($g['codigo']) . ') fez um pedido como titular de dados: <strong>' . e(PEDIDOS_PRIVACIDADE[$tipo]) . '</strong>.</p>'
    . ($detalhe !== '' ? '<p>' . nl2br(e($detalhe)) . '</p>' : '')
    . '<p>O prazo para responder é de 15 dias. <a href="' . e(url_absoluta('/admin/atendimento/' . $id)) . '">Responder no Painel ADM</a></p>');
  flash('sucesso', 'Pedido recebido. Respondemos pelo chamado em até 15 dias.');
  redirecionar('/guia/ajuda/chamados/' . $id);
}

/** Revoga a autorização para dados de saúde: apaga os campos de saúde do cadastro. */
function guia_perfil_privacidade_revogar_saude(): void {
  $g = exigir_guia();
  atualizar('guias', [
    'pcd' => 0, 'pcd_descricao' => null, 'restricao_alimentar' => null, 'doencas_preexistentes' => null,
    'consentimento_saude_em' => null, 'atualizado_em' => agora(),
  ], 'id = ?', [$g['id']]);
  auditar('lgpd_saude_revogada', 'guia', (int) $g['id']);
  flash('sucesso', 'Autorização revogada. Seus dados de saúde foram apagados do cadastro.');
  redirecionar('/guia/perfil?aba=privacidade');
}
