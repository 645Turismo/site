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
        migracao_executar_instrucao($pdo, $instrucao);
      }
    }
    inserir('migracoes', ['versao' => $versao, 'aplicada_em' => agora()]);
    $novas[] = $versao;
  }
  return $novas;
}

/**
 * Executa uma instrução da migration tolerando o que já existe. No MySQL, ALTER/CREATE não voltam atrás
 * numa falha: se uma migration parou no meio, a próxima tentativa encontra a coluna/índice já criado.
 * Ignorar "já existe" torna a repetição segura (as migrations só acrescentam).
 */
function migracao_executar_instrucao(PDO $pdo, string $instrucao): void {
  try {
    $pdo->exec($instrucao);
  } catch (PDOException $e) {
    $codigo = (int) ($e->errorInfo[1] ?? 0);
    $jaExiste = in_array($codigo, [1050, 1060, 1061, 1062], true) // MySQL: tabela, coluna, índice, registro
      || preg_match('/duplicate column name|already exists|UNIQUE constraint failed/i', $e->getMessage()); // SQLite
    if (!$jaExiste) {
      throw $e;
    }
  }
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
    try {
      executar_migracoes(); // pasta sem permissão de escrita: aplica sem trava (cada migration só roda uma vez)
    } catch (Throwable $e) {
      error_log('Falha ao aplicar migrations: ' . $e->getMessage());
    }
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
  } catch (Throwable $e) {
    // Uma falha aqui não pode tirar o site do ar: registra e tenta de novo na próxima requisição.
    error_log('Falha ao aplicar migrations: ' . $e->getMessage());
  } finally {
    flock($trava, LOCK_UN);
    fclose($trava);
  }
}
