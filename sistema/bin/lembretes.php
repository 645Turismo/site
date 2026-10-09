<?php
// Envia os lembretes de embarque (12 h antes) pela linha de comando, para usar num agendador (cron):
// php bin/lembretes.php — sem agendador, o sistema faz a mesma verificação sozinho durante os acessos.
if (PHP_SAPI !== 'cli') {
  exit(1);
}
require dirname(__DIR__) . '/app/bootstrap.php';
migracoes_automaticas();
echo 'Lembretes enviados: ' . lembretes_embarque_processar() . PHP_EOL;
