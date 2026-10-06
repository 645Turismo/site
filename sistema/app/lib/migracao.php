<?php
// Executor de migrations: aplica, em ordem, os arquivos de database/migrations ainda não aplicados.

function executar_migracoes(): array {
  $pdo = db();
  $sqlite = db_driver() === 'sqlite';
  $pdo->exec('CREATE TABLE IF NOT EXISTS migracoes (versao VARCHAR(100) NOT NULL PRIMARY KEY, aplicada_em DATETIME NOT NULL)'
    . ($sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'));
  $aplicadas = array_column(todos('SELECT versao FROM migracoes'), 'versao');

  $tokens = $sqlite
    ? ['{{PK}}' => 'INTEGER PRIMARY KEY AUTOINCREMENT', '{{TABELA}}' => '']
    : ['{{PK}}' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
       '{{TABELA}}' => 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'];

  $arquivos = glob(RAIZ . '/database/migrations/*.sql');
  sort($arquivos);
  $novas = [];
  foreach ($arquivos as $arquivo) {
    $versao = basename($arquivo, '.sql');
    if (in_array($versao, $aplicadas, true)) {
      continue;
    }
    $sql = strtr(file_get_contents($arquivo), $tokens);
    if (!$sqlite) {
      // No MySQL as FKs precisam de colunas do mesmo tipo da PK (INT UNSIGNED).
      $sql = preg_replace('/\b(\w+_id|aprovado_por|revisado_por|criado_por|registrado_por|convidado_por) INTEGER\b/',
        '$1 INT UNSIGNED', $sql);
    }
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (preg_split('/;\s*(\r?\n|$)/', $sql) as $instrucao) {
      if (trim($instrucao) !== '') {
        $pdo->exec($instrucao);
      }
    }
    inserir('migracoes', ['versao' => $versao, 'aplicada_em' => agora()]);
    $novas[] = $versao;
  }
  return $novas;
}
