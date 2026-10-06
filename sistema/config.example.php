<?php
// Modelo de configuração. Copie para config.local.php e preencha.
// O config.local.php NUNCA vai para o Git nem é enviado pelo deploy: no servidor ele é criado à mão.

return [
  // 'dev' (local) ou 'prod' (Locaweb). Em dev os e-mails vão para storage/logs/mail.log e erros aparecem na tela.
  'ambiente' => 'dev',

  // Endereço público do sistema, sem barra no final.
  'url_base' => 'http://localhost:8080',

  'db' => [
    // 'sqlite' para testes locais; 'mysql' em produção.
    'driver' => 'sqlite',
    'sqlite_path' => __DIR__ . '/storage/dev.sqlite',
    'host' => '',
    'porta' => 3306,
    'nome' => '',
    'usuario' => '',
    'senha' => '',
  ],

  'smtp' => [
    'host' => 'email-ssl.com.br',
    'porta' => 465,
    'seguranca' => 'ssl', // 'ssl' (porta 465) ou 'tls' (STARTTLS, porta 587)
    'usuario' => 'nao-responda@645turismo.com.br',
    'senha' => '',
    'remetente' => 'nao-responda@645turismo.com.br',
    'remetente_nome' => '645 Turismo',
  ],

  // Código que libera a página /instalar (cria as tabelas e o primeiro ADM).
  // Use um valor longo e aleatório e APAGUE depois que a instalação terminar.
  'setup_token' => '',
];
