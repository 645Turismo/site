<?php
// Autenticação do guia (CPF + senha) e do ADM (e-mail + senha), com limite de tentativas e redefinição de senha.
// As duas sessões são independentes: entrar como ADM não derruba o login de guia e vice-versa.

const LOGIN_MAX_TENTATIVAS = 5;
const LOGIN_JANELA_MIN = 15;
const RESET_VALIDADE_MIN = 60;

const PAPEIS_ADMIN = [
  'admin' => 'Administrador',
  'coordenador' => 'Coordenador',
  'financeiro' => 'Financeiro',
];

// ---------- Limite de tentativas ----------

function login_bloqueado(array $chaves): bool {
  $desde = date('Y-m-d H:i:s', time() - LOGIN_JANELA_MIN * 60);
  foreach ($chaves as $chave) {
    $n = (int) valor('SELECT COUNT(*) FROM login_tentativas WHERE chave = ? AND criado_em >= ?', [$chave, $desde]);
    if ($n >= LOGIN_MAX_TENTATIVAS) {
      return true;
    }
  }
  return false;
}

function registrar_tentativa(array $chaves): void {
  foreach ($chaves as $chave) {
    inserir('login_tentativas', ['chave' => $chave, 'criado_em' => agora()]);
  }
}

function limpar_tentativas(array $chaves): void {
  foreach ($chaves as $chave) {
    q('DELETE FROM login_tentativas WHERE chave = ?', [$chave]);
  }
  // Faxina ocasional das tentativas antigas.
  if (random_int(1, 50) === 1) {
    q('DELETE FROM login_tentativas WHERE criado_em < ?', [date('Y-m-d H:i:s', time() - 86400)]);
  }
}

// ---------- Guia ----------

/** Retorna null em caso de sucesso ou a mensagem de erro. */
function autenticar_guia(string $cpf, string $senha): ?string {
  $cpf = so_digitos($cpf);
  $chaves = ['ip:' . ip_cliente(), 'cpf:' . $cpf];
  if (login_bloqueado($chaves)) {
    return 'Muitas tentativas. Aguarde ' . LOGIN_JANELA_MIN . ' minutos e tente novamente.';
  }
  $p = $cpf !== '' ? um('SELECT * FROM guias WHERE cpf = ?', [$cpf]) : null;
  if (!$p || !$p['senha_hash'] || !password_verify($senha, $p['senha_hash'])) {
    registrar_tentativa($chaves);
    return 'CPF ou senha inválidos.';
  }
  if (in_array($p['status'], ['inativo', 'bloqueado'], true) || $p['anonimizado_em']) {
    return 'Seu acesso está suspenso. Fale com a equipe da 645 Turismo.';
  }
  limpar_tentativas($chaves);
  if (password_needs_rehash($p['senha_hash'], PASSWORD_DEFAULT)) {
    atualizar('guias', ['senha_hash' => hash_senha($senha)], 'id = ?', [$p['id']]);
  }
  session_regenerate_id(true);
  $_SESSION['guia_id'] = (int) $p['id'];
  atualizar('guias', ['ultimo_login_em' => agora()], 'id = ?', [$p['id']]);
  auditar('login', 'guia', (int) $p['id'], null, 'guia', (int) $p['id']);
  return null;
}

function guia_atual(): ?array {
  static $cache = false;
  if ($cache !== false) {
    return $cache;
  }
  $id = $_SESSION['guia_id'] ?? null;
  $p = $id ? um('SELECT * FROM guias WHERE id = ?', [$id]) : null;
  if ($p && (in_array($p['status'], ['inativo', 'bloqueado'], true) || $p['anonimizado_em'])) {
    unset($_SESSION['guia_id']);
    $p = null;
  }
  return $cache = $p;
}

function exigir_guia(): array {
  $p = guia_atual();
  if (!$p) {
    flash('aviso', 'Entre com seu CPF e senha para continuar.');
    redirecionar('/');
  }
  return $p;
}

function sair_guia(): void {
  unset($_SESSION['guia_id']);
  session_regenerate_id(true);
}

// ---------- ADM ----------

function autenticar_admin(string $email, string $senha): ?string {
  $email = mb_strtolower(trim($email));
  $chaves = ['ip:' . ip_cliente(), 'adm:' . $email];
  if (login_bloqueado($chaves)) {
    return 'Muitas tentativas. Aguarde ' . LOGIN_JANELA_MIN . ' minutos e tente novamente.';
  }
  $a = $email !== '' ? um('SELECT * FROM admins WHERE email = ?', [$email]) : null;
  if (!$a || !password_verify($senha, $a['senha_hash'])) {
    registrar_tentativa($chaves);
    return 'E-mail ou senha inválidos.';
  }
  if (!(int) $a['ativo']) {
    return 'Este acesso está desativado.';
  }
  limpar_tentativas($chaves);
  if (password_needs_rehash($a['senha_hash'], PASSWORD_DEFAULT)) {
    atualizar('admins', ['senha_hash' => hash_senha($senha)], 'id = ?', [$a['id']]);
  }
  session_regenerate_id(true);
  $_SESSION['admin_id'] = (int) $a['id'];
  atualizar('admins', ['ultimo_login_em' => agora()], 'id = ?', [$a['id']]);
  auditar('login', 'admin', (int) $a['id'], null, 'admin', (int) $a['id']);
  return null;
}

function admin_atual(): ?array {
  static $cache = false;
  if ($cache !== false) {
    return $cache;
  }
  $id = $_SESSION['admin_id'] ?? null;
  $a = $id ? um('SELECT * FROM admins WHERE id = ? AND ativo = 1', [$id]) : null;
  if (!$a) {
    unset($_SESSION['admin_id']);
  }
  return $cache = $a;
}

/** Exige ADM logado. Se $papeis for informado, só esses papéis (além de 'admin') passam. */
function exigir_admin(array $papeis = []): array {
  $a = admin_atual();
  if (!$a) {
    flash('aviso', 'Entre com seu e-mail e senha para continuar.');
    redirecionar('/admin');
  }
  if ($papeis && $a['papel'] !== 'admin' && !in_array($a['papel'], $papeis, true)) {
    abortar(403, 'Seu perfil não tem acesso a esta área.');
  }
  return $a;
}

function sair_admin(): void {
  unset($_SESSION['admin_id']);
  session_regenerate_id(true);
}

// ---------- Redefinição de senha ----------

/** Cria um token de uso único e devolve o token em texto puro para o link do e-mail. */
function criar_token_reset(string $tipo, int $usuarioId, int $validadeMin = RESET_VALIDADE_MIN): string {
  q('UPDATE password_resets SET usado_em = ? WHERE usuario_tipo = ? AND usuario_id = ? AND usado_em IS NULL',
    [agora(), $tipo, $usuarioId]);
  $token = bin2hex(random_bytes(32));
  inserir('password_resets', [
    'usuario_tipo' => $tipo,
    'usuario_id' => $usuarioId,
    'token_hash' => hash('sha256', $token),
    'expira_em' => date('Y-m-d H:i:s', time() + $validadeMin * 60),
    'criado_em' => agora(),
  ]);
  return $token;
}

function buscar_token_reset(string $token, string $tipo): ?array {
  if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    return null;
  }
  return um('SELECT * FROM password_resets WHERE token_hash = ? AND usuario_tipo = ? AND usado_em IS NULL AND expira_em > ?',
    [hash('sha256', $token), $tipo, agora()]);
}

function consumir_token_reset(int $id): void {
  atualizar('password_resets', ['usado_em' => agora()], 'id = ?', [$id]);
}
