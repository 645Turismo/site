<?php
// Roteador do servidor embutido do PHP, só para testes locais (imita o .htaccess):
//   php -S localhost:8080 bin/servidor.php
$caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$raiz = dirname(__DIR__);

if (preg_match('#^/(assets/|robots\.txt$)#', $caminho) && is_file($raiz . $caminho)) {
  header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');
  return false;
}
require $raiz . '/index.php';
