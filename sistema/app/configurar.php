<?php
// Configuração inicial pelo navegador: só roda enquanto o servidor NÃO tem config.local.php.
// Pede as senhas do banco e do e-mail, testa a conexão e grava o config.local.php no servidor,
// para que nenhuma senha precise passar por repositório ou FTP.
// O código de configuração é entregue à equipe fora do sistema; aqui fica só o hash dele.

const CONFIGURAR_HASH_CODIGO = 'da8cfeb0e64f5e1ec3c4109de85fcccd648bd0179362924268a46371ce5f9956';
const CONFIGURAR_MAX_TENTATIVAS = 10;

function configurar_html(string $texto): string {
  return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

function configurar_pagina(string $erro = '', array $dados = []): void {
  header('Content-Type: text/html; charset=utf-8');
  header('X-Robots-Tag: noindex, nofollow');
  header('Cache-Control: no-store');
  $v = fn(string $campo, string $padrao = '') => configurar_html((string) ($dados[$campo] ?? $padrao));
  ?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Configuração · 645 Turismo</title>
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400;6..72,500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css">
  <link rel="stylesheet" href="/assets/css/componentes.css">
  <link rel="stylesheet" href="/assets/css/paginas.css">
</head>
<body class="pub">
  <main class="pub-main">
    <section class="painel-acesso centro">
      <p class="sobretitulo">Primeiro acesso ao servidor</p>
      <h2>Configurar o sistema</h2>
      <p>Informe as senhas criadas no painel da Locaweb. Elas ficam gravadas só no servidor.</p>
      <?php if ($erro): ?><div class="alerta alerta-erro" role="alert"><span><?= configurar_html($erro) ?></span></div><?php endif; ?>
      <form method="post" action="/configurar" class="form" autocomplete="off">
        <label class="campo"><span>Código de configuração</span><input type="text" name="codigo" required autocomplete="off"></label>
        <fieldset class="campo">
          <legend>Banco de dados MySQL</legend>
          <label class="campo"><span>Servidor</span><input type="text" name="db_host" required value="<?= $v('db_host', 'guias645.mysql.dbaas.com.br') ?>"></label>
          <label class="campo"><span>Nome do banco</span><input type="text" name="db_nome" required value="<?= $v('db_nome', 'guias645') ?>"></label>
          <label class="campo"><span>Usuário</span><input type="text" name="db_usuario" required value="<?= $v('db_usuario', 'guias645') ?>"></label>
          <label class="campo"><span>Senha do banco</span><input type="password" name="db_senha" required autocomplete="new-password"></label>
        </fieldset>
        <fieldset class="campo">
          <legend>E-mail que envia os avisos</legend>
          <label class="campo"><span>Conta de e-mail</span><input type="email" name="smtp_usuario" required value="<?= $v('smtp_usuario', 'operacional@645turismo.com.br') ?>"></label>
          <label class="campo"><span>Senha do e-mail</span><input type="password" name="smtp_senha" autocomplete="new-password"></label>
        </fieldset>
        <div class="form-rodape"><button type="submit" class="btn btn-primario">Salvar e continuar</button></div>
      </form>
    </section>
  </main>
</body>
</html>
  <?php
  exit;
}

/** Conta tentativas erradas num arquivo (ainda não há banco nem sessão). */
function configurar_tentativas(bool $somar = false): int {
  $arquivo = RAIZ . '/storage/logs/.configurar-tentativas';
  $total = is_file($arquivo) ? (int) file_get_contents($arquivo) : 0;
  if ($somar) {
    $total++;
    @file_put_contents($arquivo, (string) $total, LOCK_EX);
  }
  return $total;
}

function configurar_executar(string $arquivoConfig): void {
  $caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
  if ($caminho !== '/configurar') {
    header('Location: /configurar', true, 302);
    exit;
  }
  if (configurar_tentativas() >= CONFIGURAR_MAX_TENTATIVAS) {
    http_response_code(403);
    exit('Configuração bloqueada por excesso de tentativas. Apague storage/logs/.configurar-tentativas no servidor.');
  }
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    configurar_pagina();
  }

  $dados = array_map(fn($x) => is_string($x) ? trim($x) : '', $_POST);
  $codigo = $dados['codigo'] ?? '';
  if (!hash_equals(CONFIGURAR_HASH_CODIGO, hash('sha256', $codigo))) {
    configurar_tentativas(true);
    configurar_pagina('Código de configuração incorreto.', $dados);
  }

  $db = [
    'driver' => 'mysql',
    'sqlite_path' => '',
    'host' => $dados['db_host'] ?? '',
    'porta' => 3306,
    'nome' => $dados['db_nome'] ?? '',
    'usuario' => $dados['db_usuario'] ?? '',
    'senha' => (string) ($_POST['db_senha'] ?? ''),
  ];
  try {
    new PDO(
      sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $db['host'], $db['porta'], $db['nome']),
      $db['usuario'], $db['senha'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]
    );
  } catch (PDOException $e) {
    configurar_pagina('Não foi possível conectar ao banco com esses dados. Confira a senha e tente de novo.', $dados);
  }

  $config = [
    'ambiente' => 'prod',
    'url_base' => 'https://sistema.645turismo.com.br',
    'db' => $db,
    'smtp' => [
      'host' => 'email-ssl.com.br',
      'porta' => 465,
      'seguranca' => 'ssl',
      'usuario' => $dados['smtp_usuario'] ?? '',
      'senha' => (string) ($_POST['smtp_senha'] ?? ''),
      'remetente' => $dados['smtp_usuario'] ?? '',
      'remetente_nome' => '645 Turismo',
    ],
    // O mesmo código libera a página /instalar, que cria as tabelas e o primeiro ADM.
    'setup_token' => $codigo,
  ];
  $conteudo = "<?php\n// Gerado pela página /configurar em " . date('d/m/Y H:i') . ".\nreturn " . var_export($config, true) . ";\n";
  if (@file_put_contents($arquivoConfig, $conteudo, LOCK_EX) === false) {
    configurar_pagina('O servidor não deixou gravar o config.local.php. Verifique a permissão de escrita da pasta do sistema.', $dados);
  }
  @chmod($arquivoConfig, 0640);
  @unlink(RAIZ . '/storage/logs/.configurar-tentativas');
  header('Location: /instalar', true, 303);
  exit;
}
