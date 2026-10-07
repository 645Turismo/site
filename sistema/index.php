<?php
// Front controller: toda requisição que não é arquivo estático passa por aqui.
require __DIR__ . '/app/bootstrap.php';

cabecalhos_seguranca();
iniciar_sessao();

try {
  migracoes_automaticas();
  $rotas = require RAIZ . '/app/rotas.php';
  $caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
  despachar($rotas, $_SERVER['REQUEST_METHOD'] ?? 'GET', rawurldecode($caminho));
} catch (Throwable $e) {
  error_log((string) $e);
  if (em_dev()) {
    throw $e;
  }
  abortar(500, 'Algo deu errado. Tente novamente em instantes.');
}
