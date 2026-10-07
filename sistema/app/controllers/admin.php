<?php
// Painel ADM: acesso, tela Hoje, equipe e módulos ainda em construção.
// Viagens/Tours e Funções ficam em admin_viagens.php e admin_funcoes.php.

const CONVITE_VALIDADE_MIN = 72 * 60;

function adm_login(): void {
  if (admin_atual()) {
    redirecionar('/admin/hoje');
  }
  exibir('admin/login', ['titulo' => 'Painel ADM'], 'publico');
}

function adm_entrar(): void {
  $email = entrada('email');
  $erro = autenticar_admin($email, (string) ($_POST['senha'] ?? ''));
  if ($erro) {
    guardar_antigo(['email' => $email]);
    flash('erro', $erro);
    redirecionar('/admin');
  }
  redirecionar('/admin/hoje');
}

function adm_esqueci_senha(): void {
  exibir('publico/esqueci-senha', [
    'titulo' => 'Recuperar senha do ADM',
    'acao' => '/admin/esqueci-senha',
    'rotulo' => 'E-mail',
    'campo' => 'email',
    'placeholder' => 'voce@645turismo.com.br',
    'voltar' => '/admin',
  ], 'publico');
}

function adm_esqueci_senha_enviar(): void {
  $email = mb_strtolower(entrada('email'));
  $chaves = ['reset-ip:' . ip_cliente()];
  if (login_bloqueado($chaves)) {
    flash('erro', 'Muitas solicitações. Aguarde alguns minutos e tente de novo.');
    redirecionar('/admin/esqueci-senha');
  }
  registrar_tentativa($chaves);
  $a = $email ? um('SELECT * FROM admins WHERE email = ? AND ativo = 1', [$email]) : null;
  if ($a) {
    adm_enviar_link_senha($a, 'Redefinição de senha do Painel ADM', 'Recebemos um pedido para redefinir sua senha do Painel ADM.', RESET_VALIDADE_MIN);
  }
  flash('sucesso', 'Se o e-mail estiver cadastrado, enviamos um link para criar uma nova senha.');
  redirecionar('/admin');
}

function adm_enviar_link_senha(array $a, string $assunto, string $intro, int $validadeMin): void {
  $token = criar_token_reset('admin', (int) $a['id'], $validadeMin);
  $link = url_absoluta('/admin/redefinir-senha?token=' . $token);
  $validade = $validadeMin >= 120 ? intdiv($validadeMin, 60) . ' horas' : $validadeMin . ' minutos';
  enviar_email($a['email'], $assunto,
    '<p>Olá, ' . e(explode(' ', $a['nome'])[0]) . '.</p><p>' . e($intro) . '</p>'
    . '<p><a href="' . e($link) . '" style="display:inline-block;padding:12px 22px;background:#53D9B2;color:#000;text-decoration:none;font-weight:bold;border-radius:999px">Definir senha</a></p>'
    . '<p>O link vale por ' . $validade . '.</p>');
}

function adm_redefinir_senha(): void {
  $token = (string) ($_GET['token'] ?? '');
  if (!buscar_token_reset($token, 'admin')) {
    flash('erro', 'Link inválido ou expirado. Peça um novo.');
    redirecionar('/admin/esqueci-senha');
  }
  exibir('publico/redefinir-senha', ['titulo' => 'Nova senha do ADM', 'token' => $token, 'acao' => '/admin/redefinir-senha'], 'publico');
}

function adm_redefinir_senha_salvar(): void {
  $token = entrada('token');
  $reset = buscar_token_reset($token, 'admin');
  if (!$reset) {
    flash('erro', 'Link inválido ou expirado. Peça um novo.');
    redirecionar('/admin/esqueci-senha');
  }
  $erro = erro_senha((string) ($_POST['senha'] ?? ''), (string) ($_POST['confirmacao'] ?? ''));
  if ($erro) {
    flash('erro', $erro);
    redirecionar('/admin/redefinir-senha?token=' . urlencode($token));
  }
  atualizar('admins', ['senha_hash' => hash_senha($_POST['senha']), 'atualizado_em' => agora()], 'id = ?', [$reset['usuario_id']]);
  consumir_token_reset((int) $reset['id']);
  auditar('reset_senha_concluido', 'admin', (int) $reset['usuario_id'], null, 'admin', (int) $reset['usuario_id']);
  flash('sucesso', 'Senha definida. Entre com seu e-mail e a nova senha.');
  redirecionar('/admin');
}

function adm_hoje(): void {
  $a = exigir_admin();
  $hoje = hoje();
  $em7dias = date('Y-m-d', strtotime('+7 days'));

  $emCampo = todos("SELECT v.id, v.codigo, v.nome, v.ponto_encontro, v.origem, v.destino, d.horario_apresentacao, d.hora_inicio,
        (SELECT COALESCE(SUM(vagas), 0) FROM viagem_vagas WHERE viagem_id = v.id) AS vagas,
        (SELECT COUNT(*) FROM escalas s WHERE s.diaria_id = d.id AND s.status IN ('confirmado','em_campo','realizada','aguardando_nf')) AS confirmados,
        (SELECT COUNT(*) FROM escalas s WHERE s.diaria_id = d.id AND s.status = 'convidado') AS pendentes
      FROM diarias d JOIN viagens v ON v.id = d.viagem_id
      WHERE d.data = ? AND v.status = 'publicada' ORDER BY d.horario_apresentacao, d.hora_inicio", [$hoje]);

  $vagasAbertas = (int) valor("SELECT COALESCE(SUM(CASE WHEN x.vagas > x.ocupadas THEN x.vagas - x.ocupadas ELSE 0 END), 0) FROM (
      SELECT (SELECT COALESCE(SUM(vagas), 0) FROM viagem_vagas WHERE viagem_id = d.viagem_id) AS vagas,
             (SELECT COUNT(*) FROM escalas s WHERE s.diaria_id = d.id AND s.status NOT IN ('recusado','cancelado','falta')) AS ocupadas
      FROM diarias d JOIN viagens v ON v.id = d.viagem_id
      WHERE v.status = 'publicada' AND d.data BETWEEN ? AND ?) x", [$hoje, $em7dias]);

  $fila = [
    [$vagasAbertas, 'Vagas em aberto nos próximos 7 dias', 'Diárias que ainda precisam de guia.', '/admin/viagens'],
    [(int) valor("SELECT COUNT(DISTINCT s.guia_id) FROM escalas s WHERE s.status = 'convidado' AND s.convidado_em < ?",
      [date('Y-m-d H:i:s', time() - 86400)]), 'Guias sem responder há mais de 24 h', 'Vale um lembrete por WhatsApp.', '/admin/viagens'],
    [(int) valor("SELECT COUNT(*) FROM guias WHERE status = 'em_analise'"), 'Cadastros para triagem', 'Novos guias aguardando aprovação.', '/admin/guias'],
    [(int) valor("SELECT COUNT(*) FROM envios WHERE tipo = 'relatorio' AND status = 'enviado'"), 'Relatórios de viagem para ler', 'Enviados pelos guias após o trabalho.', '/admin/conferencia?aba=relatorio'],
    [(int) valor("SELECT COUNT(*) FROM envios WHERE tipo = 'nf' AND status = 'enviado'"), 'Notas fiscais para conferir', 'Liberam o pagamento das diárias.', '/admin/conferencia?aba=nf'],
    [(int) valor("SELECT COUNT(*) FROM guia_documentos WHERE atual = 1 AND status = 'enviado'"), 'Documentos atualizados', 'Cadastur, identidade e comprovantes novos.', '/admin/guias?aba=todos'],
    [(int) valor("SELECT COUNT(*) FROM chamados WHERE status = 'aberto'"), 'Chamados aguardando resposta', 'Inclui avisos de imprevisto.', '/admin/atendimento'],
  ];

  $proximas = todos("SELECT v.*, (SELECT COUNT(*) FROM diarias WHERE viagem_id = v.id) AS dias
      FROM viagens v WHERE v.status IN ('publicada','rascunho') AND v.data_fim >= ?
      ORDER BY v.data_inicio LIMIT 6", [$hoje]);

  exibir('admin/hoje', [
    'titulo' => 'Hoje',
    'menu' => 'hoje',
    'a' => $a,
    'emCampo' => $emCampo,
    'fila' => $fila,
    'aPagar' => (float) valor("SELECT COALESCE(SUM(valor), 0) FROM escalas WHERE status = 'a_pagar'"),
    'proximas' => $proximas,
  ], 'admin');
}

function adm_equipe(): void {
  $a = exigir_admin(['admin']);
  exibir('admin/equipe', [
    'titulo' => 'Equipe',
    'menu' => 'equipe',
    'a' => $a,
    'usuarios' => todos('SELECT * FROM admins ORDER BY ativo DESC, nome'),
  ], 'admin');
}

function adm_equipe_criar(): void {
  exigir_admin(['admin']);
  $nome = entrada('nome');
  $email = mb_strtolower(entrada('email'));
  $papel = entrada('papel');
  guardar_antigo(compact('nome', 'email', 'papel'));
  if (mb_strlen($nome) < 3 || !email_valido($email) || !isset(PAPEIS_ADMIN[$papel])) {
    flash('erro', 'Preencha nome, e-mail válido e perfil.');
    redirecionar('/admin/equipe');
  }
  if (valor('SELECT 1 FROM admins WHERE email = ?', [$email])) {
    flash('erro', 'Já existe um usuário com este e-mail.');
    redirecionar('/admin/equipe');
  }
  $id = inserir('admins', [
    'nome' => $nome,
    'email' => $email,
    // Senha aleatória descartada: a pessoa define a dela pelo link do convite.
    'senha_hash' => hash_senha(bin2hex(random_bytes(24))),
    'papel' => $papel,
    'ativo' => 1,
    'criado_em' => agora(),
  ]);
  auditar('admin_criado', 'admin', $id, ['email' => $email, 'papel' => $papel]);
  adm_enviar_link_senha(um('SELECT * FROM admins WHERE id = ?', [$id]), 'Seu acesso ao Painel ADM da 645 Turismo',
    'Você recebeu acesso ao Painel ADM do sistema de guias da 645 Turismo. Clique abaixo para criar sua senha.', CONVITE_VALIDADE_MIN);
  limpar_antigo();
  flash('sucesso', "Acesso criado. Enviamos para $email o link para definir a senha.");
  redirecionar('/admin/equipe');
}

function adm_equipe_status(int $id): void {
  $a = exigir_admin(['admin']);
  if ($id === (int) $a['id']) {
    flash('erro', 'Você não pode desativar o seu próprio acesso.');
    redirecionar('/admin/equipe');
  }
  $u = um('SELECT * FROM admins WHERE id = ?', [$id]);
  if (!$u) {
    abortar(404);
  }
  $novo = (int) $u['ativo'] ? 0 : 1;
  atualizar('admins', ['ativo' => $novo, 'atualizado_em' => agora()], 'id = ?', [$id]);
  auditar($novo ? 'admin_ativado' : 'admin_desativado', 'admin', $id);
  flash('sucesso', $novo ? 'Acesso reativado.' : 'Acesso desativado.');
  redirecionar('/admin/equipe');
}

function adm_sair(): void {
  sair_admin();
  flash('sucesso', 'Você saiu do Painel ADM.');
  redirecionar('/admin');
}

/** Diagnóstico do servidor (só administrador): banco, pastas graváveis e últimos erros registrados. */
function adm_diagnostico(): void {
  $a = exigir_admin();
  if ($a['papel'] !== 'admin') {
    abortar(403, 'Seu perfil não tem acesso a esta área.');
  }
  $arquivos = array_map(fn($f) => basename($f, '.sql'), glob(RAIZ . '/database/migrations/*.sql') ?: []);
  sort($arquivos);
  try {
    $aplicadas = array_column(todos('SELECT versao FROM migracoes ORDER BY versao'), 'versao');
  } catch (Throwable $e) {
    $aplicadas = [];
  }
  $log = RAIZ . '/storage/logs/php-erros.log';
  $linhas = [];
  if (is_file($log)) {
    $fp = fopen($log, 'r');
    fseek($fp, max(0, filesize($log) - 20000));
    $linhas = array_slice(array_filter(explode("\n", (string) stream_get_contents($fp))), -60);
    fclose($fp);
  }
  exibir('admin/diagnostico', [
    'titulo' => 'Diagnóstico',
    'menu' => 'equipe',
    'a' => $a,
    'pendentes' => array_values(array_diff($arquivos, $aplicadas)),
    'aplicadas' => $aplicadas,
    'pastas' => array_map(fn($p) => [$p, is_dir(RAIZ . '/' . $p) && is_writable(RAIZ . '/' . $p)],
      ['storage/logs', 'storage/uploads', '.']),
    'php' => PHP_VERSION,
    'banco' => db_driver(),
    'smtp' => config('smtp.host') . ':' . config('smtp.porta') . ' · ' . config('smtp.usuario'),
    'linhas' => $linhas,
    'teste' => $_SESSION['teste_email'] ?? null,
    'emails' => (function () { try { return todos('SELECT * FROM emails_enviados ORDER BY id DESC LIMIT 50'); } catch (Throwable $e) { return []; } })(),
    // Falhas de envio anteriores ao histórico ficam só no registro de erros.
    'falhasSmtp' => array_values(array_filter($linhas, fn($l) => str_contains($l, 'SMTP'))),
    'emailAdmin' => $a['email'],
  ], 'admin');
  unset($_SESSION['teste_email']);
}

/** Diagnóstico: envia um e-mail de teste e mostra a conversa com o servidor SMTP (sem usuário e senha). */
function adm_diagnostico_email(): void {
  $a = exigir_admin();
  if ($a['papel'] !== 'admin') {
    abortar(403, 'Seu perfil não tem acesso a esta área.');
  }
  $para = mb_strtolower(entrada('para')) ?: (string) $a['email'];
  if (!email_valido($para)) {
    flash('erro', 'Informe um e-mail válido para o teste.');
    redirecionar('/admin/diagnostico#email');
  }
  $html = '<p>Teste de envio do sistema de guias, feito por ' . e($a['nome']) . ' em ' . e(formatar_data_hora(agora())) . '.</p>'
    . '<p>Se esta mensagem chegou, o envio de e-mails está funcionando.</p>';
  if (em_dev() || !config('smtp.host')) {
    enviar_email($para, 'Teste de e-mail · 645 Turismo', $html);
    $ok = true;
    $conversa = ['Ambiente de testes: a mensagem foi gravada em storage/logs/mail.log (nenhum e-mail real é enviado).'];
  } else {
    $ok = smtp_enviar($para, 'Teste de e-mail · 645 Turismo', email_layout('Teste de e-mail · 645 Turismo', $html),
      'Teste de envio do sistema de guias. Se esta mensagem chegou, o envio de e-mails está funcionando.', $conversa);
    email_registrar($para, 'Teste de e-mail · 645 Turismo', $ok ? 'enviado' : 'falhou', $ok ? null : (string) end($conversa));
  }
  $_SESSION['teste_email'] = ['para' => $para, 'ok' => $ok, 'conversa' => $conversa, 'em' => agora()];
  redirecionar('/admin/diagnostico#email');
}
