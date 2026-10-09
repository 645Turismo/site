<?php
declare(strict_types=1);

define('RAIZ', dirname(__DIR__));
date_default_timezone_set('America/Sao_Paulo');
mb_internal_encoding('UTF-8');

$arquivoConfig = RAIZ . '/config.local.php';
if (!is_file($arquivoConfig)) {
  if (PHP_SAPI === 'cli') {
    exit("Sistema não configurado: crie o config.local.php a partir do config.example.php.\n");
  }
  require RAIZ . '/app/configurar.php';
  configurar_executar($arquivoConfig);
}
$GLOBALS['__config'] = require $arquivoConfig;

/** Lê uma chave da configuração usando ponto para níveis (ex.: 'db.driver'). */
function config(string $chave, $padrao = null) {
  $valor = $GLOBALS['__config'];
  foreach (explode('.', $chave) as $parte) {
    if (!is_array($valor) || !array_key_exists($parte, $valor)) {
      return $padrao;
    }
    $valor = $valor[$parte];
  }
  return $valor;
}

function em_dev(): bool {
  return config('ambiente') === 'dev';
}

error_reporting(E_ALL);
ini_set('display_errors', em_dev() ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', RAIZ . '/storage/logs/php-erros.log');

require RAIZ . '/app/lib/db.php';
require RAIZ . '/app/lib/http.php';
require RAIZ . '/app/lib/csrf.php';
require RAIZ . '/app/lib/validacao.php';
require RAIZ . '/app/lib/dominio.php';
require RAIZ . '/app/lib/view.php';
require RAIZ . '/app/lib/auditoria.php';
require RAIZ . '/app/lib/auth.php';
require RAIZ . '/app/lib/email.php';
require RAIZ . '/app/lib/passageiros.php';
require RAIZ . '/app/lib/planilha.php';
require RAIZ . '/app/lib/importacao.php';
require RAIZ . '/app/lib/arquivos.php';
require RAIZ . '/app/lib/cadastro_guia.php';
require RAIZ . '/app/lib/migracao.php';
require RAIZ . '/app/lib/lembretes.php';
