<?php
// Acesso ao banco via PDO. Funciona com MySQL (produção) e SQLite (testes locais).
// Nomes de tabela e coluna passados para inserir()/atualizar() vêm sempre do código, nunca do usuário.

function db(): PDO {
  static $pdo = null;
  if ($pdo) {
    return $pdo;
  }
  $opcoes = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ];
  if (db_driver() === 'sqlite') {
    $pdo = new PDO('sqlite:' . config('db.sqlite_path'), null, null, $opcoes);
    $pdo->exec('PRAGMA foreign_keys = ON');
  } else {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
      config('db.host'), (int) config('db.porta', 3306), config('db.nome'));
    $pdo = new PDO($dsn, config('db.usuario'), config('db.senha'), $opcoes);
    $pdo->exec("SET time_zone = '-03:00'");
    // Mesmo comportamento do SQLite usado nos testes: sem o modo estrito, que transforma em erro (500)
    // casos como texto vazio em campo de data ou agrupamento com coluna não agregada.
    $pdo->exec("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");
  }
  return $pdo;
}

function db_driver(): string {
  return config('db.driver') === 'sqlite' ? 'sqlite' : 'mysql';
}

/** GROUP_CONCAT com separador ", " na sintaxe do banco em uso. */
function sql_lista(string $expressao): string {
  return db_driver() === 'sqlite' ? "GROUP_CONCAT($expressao, ', ')" : "GROUP_CONCAT($expressao SEPARATOR ', ')";
}

function q(string $sql, array $params = []): PDOStatement {
  $stmt = db()->prepare($sql);
  $stmt->execute($params);
  return $stmt;
}

function um(string $sql, array $params = []): ?array {
  $linha = q($sql, $params)->fetch();
  return $linha === false ? null : $linha;
}

function todos(string $sql, array $params = []): array {
  return q($sql, $params)->fetchAll();
}

function valor(string $sql, array $params = []) {
  $v = q($sql, $params)->fetchColumn();
  return $v === false ? null : $v;
}

function inserir(string $tabela, array $dados): int {
  $colunas = array_keys($dados);
  $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $tabela, implode(', ', $colunas),
    implode(', ', array_map(fn($c) => ':' . $c, $colunas)));
  q($sql, $dados);
  return (int) db()->lastInsertId();
}

function atualizar(string $tabela, array $dados, string $where, array $params = []): int {
  $sets = [];
  $valores = [];
  foreach ($dados as $coluna => $v) {
    $sets[] = "$coluna = ?";
    $valores[] = $v;
  }
  $sql = sprintf('UPDATE %s SET %s WHERE %s', $tabela, implode(', ', $sets), $where);
  return q($sql, array_merge($valores, $params))->rowCount();
}

function transacao(callable $fn) {
  $pdo = db();
  $pdo->beginTransaction();
  try {
    $resultado = $fn();
    $pdo->commit();
    return $resultado;
  } catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
  }
}

function agora(): string {
  return date('Y-m-d H:i:s');
}

function hoje(): string {
  return date('Y-m-d');
}

function configuracao(string $chave, ?string $padrao = null): ?string {
  $v = valor('SELECT valor FROM configuracoes WHERE chave = ?', [$chave]);
  return $v === null ? $padrao : (string) $v;
}
