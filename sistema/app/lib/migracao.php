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

/**
 * Aplica sozinho as migrations novas depois de cada publicação (a hospedagem não tem terminal e o
 * código novo não pode rodar com o banco antigo). Custo normal: ler um arquivo de marca.
 * As migrations só acrescentam tabelas e colunas; um bloqueio de arquivo evita duas execuções juntas.
 */
function migracoes_automaticas(): void {
  $arquivos = glob(RAIZ . '/database/migrations/*.sql') ?: [];
  if (!$arquivos) {
    return;
  }
  sort($arquivos);
  $ultima = basename(end($arquivos), '.sql');
  $marca = RAIZ . '/storage/logs/.migracao-aplicada';
  if (is_file($marca) && trim((string) file_get_contents($marca)) === $ultima) {
    return;
  }
  $trava = @fopen(RAIZ . '/storage/logs/.migracao.lock', 'c');
  if (!$trava) {
    executar_migracoes(); // pasta sem permissão de escrita: aplica sem trava (cada migration só roda uma vez)
    return;
  }
  flock($trava, LOCK_EX);
  try {
    clearstatcache();
    if (!is_file($marca) || trim((string) file_get_contents($marca)) !== $ultima) {
      $novas = executar_migracoes();
      if ($novas) {
        error_log('Migrations aplicadas automaticamente: ' . implode(', ', $novas));
      }
      file_put_contents($marca, $ultima);
    }
  } finally {
    flock($trava, LOCK_UN);
    fclose($trava);
  }
}
