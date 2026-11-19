<?php
// Proteção CSRF: todo POST precisa do token da sessão (o roteador verifica automaticamente).

function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}

function csrf_campo(): string {
  return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verificar(): void {
  // Arquivo maior que o post_max_size do PHP: o servidor descarta o formulário inteiro (sem token e sem campos).
  $tamanho = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
  if ($_POST === [] && $_FILES === [] && $tamanho > 0 && $tamanho > ini_para_bytes((string) ini_get('post_max_size'))) {
    abortar(413, 'O arquivo é grande demais (o limite é ' . upload_limite_texto() . '). Reduza a foto ou envie o PDF e tente de novo.');
  }
  $enviado = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
  if (!is_string($enviado) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $enviado)) {
    abortar(419, 'O formulário expirou. Volte, recarregue a página e tente de novo.');
  }
}
