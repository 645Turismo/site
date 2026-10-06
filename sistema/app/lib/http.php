<?php
// Utilitários de requisição/resposta: sessão, escape, redirecionamento, mensagens e cabeçalhos.

const SESSAO_INATIVIDADE_SEG = 2 * 60 * 60;

function iniciar_sessao(): void {
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  session_name('sis645');
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  ini_set('session.use_strict_mode', '1');
  session_start();

  $ultima = $_SESSION['ultima_atividade'] ?? null;
  if ($ultima && time() - $ultima > SESSAO_INATIVIDADE_SEG) {
    $_SESSION = [];
    session_regenerate_id(true);
    flash('aviso', 'Sua sessão expirou por inatividade. Entre novamente.');
  }
  $_SESSION['ultima_atividade'] = time();
}

function cabecalhos_seguranca(): void {
  // O .htaccess também envia o X-Robots-Tag; repetir aqui protege caso ele não seja aplicado.
  header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');
  header('X-Frame-Options: DENY');
  header('X-Content-Type-Options: nosniff');
  header('Referrer-Policy: same-origin');
  header('Permissions-Policy: camera=(self), geolocation=(self), microphone=()');
  header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; "
    . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; "
    . "script-src 'self'; connect-src 'self' https://viacep.com.br; "
    . "frame-src https://www.youtube-nocookie.com https://www.youtube.com https://player.vimeo.com; "
    . "form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
}

function e($valor): string {
  return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function url_absoluta(string $caminho): string {
  return rtrim((string) config('url_base'), '/') . $caminho;
}

function asset(string $caminho): string {
  $arquivo = RAIZ . '/assets/' . $caminho;
  $versao = is_file($arquivo) ? filemtime($arquivo) : 0;
  return '/assets/' . $caminho . '?v=' . $versao;
}

function redirecionar(string $caminho): never {
  header('Location: ' . $caminho, true, 303);
  exit;
}

function e_post(): bool {
  return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function entrada(string $campo, string $padrao = ''): string {
  $v = $_POST[$campo] ?? $padrao;
  return is_string($v) ? trim($v) : $padrao;
}

function ip_cliente(): string {
  return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
}

function flash(string $tipo, string $mensagem): void {
  $_SESSION['flash'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function flashes(): array {
  $lista = $_SESSION['flash'] ?? [];
  unset($_SESSION['flash']);
  return $lista;
}

/** Guarda os campos de um formulário para reexibir após erro de validação. */
function guardar_antigo(array $dados): void {
  $_SESSION['antigo'] = $dados;
}

function antigo(string $campo, string $padrao = ''): string {
  return (string) ($_SESSION['antigo'][$campo] ?? $padrao);
}

/** Lista (checkboxes, linhas repetidas) do envio anterior, ou o padrão quando não houve erro. */
function antigo_lista(string $campo, array $padrao = []): array {
  return isset($_SESSION['antigo']) ? (array) ($_SESSION['antigo'][$campo] ?? []) : $padrao;
}

function limpar_antigo(): void {
  unset($_SESSION['antigo']);
}

function abortar(int $codigo, string $mensagem = ''): never {
  http_response_code($codigo);
  $titulos = [403 => 'Acesso negado', 404 => 'Página não encontrada', 419 => 'Sessão expirada', 500 => 'Erro interno'];
  echo render('erro', [
    'titulo' => $titulos[$codigo] ?? 'Erro',
    'codigo' => $codigo,
    'mensagem' => $mensagem,
  ], 'publico');
  exit;
}

/**
 * Roteador simples. Cada rota: [método, padrão, função].
 * Padrões aceitam parâmetros numéricos no formato {id}.
 */
function despachar(array $rotas, string $metodo, string $caminho): void {
  $caminho = '/' . trim($caminho, '/');
  foreach ($rotas as [$m, $padrao, $funcao]) {
    if ($m !== $metodo && !($m === 'GET' && $metodo === 'HEAD')) {
      continue;
    }
    $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>\d+)', $padrao) . '$#';
    if (preg_match($regex, $caminho, $m2)) {
      $params = array_filter($m2, 'is_string', ARRAY_FILTER_USE_KEY);
      if ($metodo === 'POST') {
        csrf_verificar();
      }
      $funcao(...array_map('intval', array_values($params)));
      return;
    }
  }
  abortar(404, 'O endereço acessado não existe.');
}
