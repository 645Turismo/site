<?php
// Área pública: login do guia, recuperação de senha, cadastro e consulta de status.

function pub_inicio(): void {
  if (guia_atual()) {
    redirecionar('/guia/hoje');
  }
  exibir('publico/inicio', ['titulo' => 'Área do Guia'], 'publico');
}

function pub_entrar(): void {
  $cpf = entrada('cpf');
  $erro = autenticar_guia($cpf, (string) ($_POST['senha'] ?? ''));
  if ($erro) {
    guardar_antigo(['cpf' => $cpf]);
    flash('erro', $erro);
    redirecionar('/');
  }
  redirecionar('/guia/hoje');
}

function pub_esqueci_senha(): void {
  exibir('publico/esqueci-senha', [
    'titulo' => 'Recuperar senha',
    'acao' => '/esqueci-senha',
    'rotulo' => 'CPF do guia',
    'campo' => 'cpf',
    'placeholder' => '000.000.000-00',
    'voltar' => '/',
  ], 'publico');
}

function pub_esqueci_senha_enviar(): void {
  $cpf = so_digitos(entrada('cpf'));
  $chaves = ['reset-ip:' . ip_cliente()];
  if (login_bloqueado($chaves)) {
    flash('erro', 'Muitas solicitações. Aguarde alguns minutos e tente de novo.');
    redirecionar('/esqueci-senha');
  }
  registrar_tentativa($chaves);
  $p = $cpf ? um("SELECT * FROM guias WHERE cpf = ? AND senha_hash IS NOT NULL AND anonimizado_em IS NULL", [$cpf]) : null;
  if ($p && $p['email']) {
    $token = criar_token_reset('guia', (int) $p['id']);
    $link = url_absoluta('/redefinir-senha?token=' . $token);
    enviar_email($p['email'], 'Redefinição de senha',
      '<p>Olá, ' . e(primeiro_nome($p)) . '.</p><p>Recebemos um pedido para redefinir sua senha na Área do Guia da 645 Turismo.</p>'
      . '<p><a href="' . e($link) . '" style="display:inline-block;padding:12px 22px;background:#53D9B2;color:#000;text-decoration:none;font-weight:bold;border-radius:999px">Criar nova senha</a></p>'
      . '<p>O link vale por 1 hora. Se não foi você, ignore este e-mail.</p>');
    auditar('reset_senha_solicitado', 'guia', (int) $p['id'], null, 'guia', (int) $p['id']);
  }
  // Mesma resposta exista ou não o CPF, para não revelar quem tem cadastro.
  flash('sucesso', 'Se o CPF estiver cadastrado, enviamos um link para o e-mail do cadastro. Confira também o spam.');
  redirecionar('/');
}

function pub_redefinir_senha(): void {
  $token = (string) ($_GET['token'] ?? '');
  if (!buscar_token_reset($token, 'guia')) {
    flash('erro', 'Link inválido ou expirado. Peça um novo.');
    redirecionar('/esqueci-senha');
  }
  exibir('publico/redefinir-senha', ['titulo' => 'Nova senha', 'token' => $token, 'acao' => '/redefinir-senha'], 'publico');
}

function pub_redefinir_senha_salvar(): void {
  $token = entrada('token');
  $reset = buscar_token_reset($token, 'guia');
  if (!$reset) {
    flash('erro', 'Link inválido ou expirado. Peça um novo.');
    redirecionar('/esqueci-senha');
  }
  $erro = erro_senha((string) ($_POST['senha'] ?? ''), (string) ($_POST['confirmacao'] ?? ''));
  if ($erro) {
    flash('erro', $erro);
    redirecionar('/redefinir-senha?token=' . urlencode($token));
  }
  atualizar('guias', ['senha_hash' => hash_senha($_POST['senha']), 'atualizado_em' => agora()], 'id = ?', [$reset['usuario_id']]);
  consumir_token_reset((int) $reset['id']);
  auditar('reset_senha_concluido', 'guia', (int) $reset['usuario_id'], null, 'guia', (int) $reset['usuario_id']);
  flash('sucesso', 'Senha alterada. Entre com seu CPF e a nova senha.');
  redirecionar('/');
}

function pub_termos(): void {
  exibir('publico/legal', ['titulo' => 'Termos de uso', 'documento' => 'termos'], 'publico');
}

function pub_privacidade(): void {
  exibir('publico/legal', ['titulo' => 'Política de privacidade', 'documento' => 'privacidade'], 'publico');
}

function primeiro_nome(array $pessoa): string {
  $nome = trim((string) ($pessoa['nome_social'] ?: $pessoa['nome']));
  return explode(' ', $nome)[0];
}
