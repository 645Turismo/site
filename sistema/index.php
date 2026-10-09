<?php
// Front controller: toda requisição que não é arquivo estático passa por aqui.
require __DIR__ . '/app/bootstrap.php';

cabecalhos_seguranca();
iniciar_sessao();
register_shutdown_function('tarefas_agendadas');

try {
  migracoes_automaticas();
  $rotas = require RAIZ . '/app/rotas.php';
  $caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
  despachar($rotas, $_SERVER['REQUEST_METHOD'] ?? 'GET', rawurldecode($caminho));
} catch (Throwable $e) {
  // Código curto na tela e no registro, para achar o erro exato em /admin/diagnostico.
  $ref = strtoupper(bin2hex(random_bytes(3)));
  error_log("[erro $ref] " . ($_SERVER['REQUEST_METHOD'] ?? '') . ' ' . ($_SERVER['REQUEST_URI'] ?? '') . "\n" . $e);
  if (em_dev()) {
    throw $e;
  }
  abortar(500, 'Algo deu errado. Tente novamente em instantes. Se continuar, informe à equipe o código ' . $ref . '.');
}
