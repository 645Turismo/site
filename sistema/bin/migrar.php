<?php
// Aplica as migrations pendentes pela linha de comando: php bin/migrar.php
// (No servidor da Locaweb, use a página /instalar.)
if (PHP_SAPI !== 'cli') {
  exit(1);
}
require dirname(__DIR__) . '/app/bootstrap.php';

$aplicadas = executar_migracoes();
echo $aplicadas ? 'Aplicadas: ' . implode(', ', $aplicadas) . PHP_EOL : 'Banco já atualizado.' . PHP_EOL;
