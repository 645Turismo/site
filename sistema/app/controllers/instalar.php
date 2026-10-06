<?php
// Instalação e atualização do banco pelo navegador (a hospedagem não tem terminal).
// Só funciona com 'setup_token' preenchido no config.local.php. Apague o token depois de usar.

function instalar_liberado(): void {
  if ((string) config('setup_token') === '') {
    abortar(404, 'O endereço acessado não existe.');
  }
}

function instalar_tem_admin(): bool {
  try {
    return (int) valor('SELECT COUNT(*) FROM admins') > 0;
  } catch (PDOException $e) {
    return false;
  }
}

function instalar_form(): void {
  instalar_liberado();
  exibir('publico/instalar', ['titulo' => 'Instalação', 'temAdmin' => instalar_tem_admin(), 'aplicadas' => null], 'publico');
}

function instalar_executar(): void {
  instalar_liberado();
  // Limite de tentativas guardado na sessão, porque a tabela de tentativas pode ainda não existir.
  $_SESSION['instalar_tentativas'] = ($_SESSION['instalar_tentativas'] ?? 0) + 1;
  if ($_SESSION['instalar_tentativas'] > 5) {
    abortar(403, 'Tentativas demais. Feche o navegador e tente mais tarde.');
  }
  if (!hash_equals((string) config('setup_token'), (string) ($_POST['token'] ?? ''))) {
    flash('erro', 'Código de instalação incorreto.');
    redirecionar('/instalar');
  }
  unset($_SESSION['instalar_tentativas']);

  $aplicadas = executar_migracoes();

  if (!instalar_tem_admin()) {
    $nome = entrada('nome');
    $email = mb_strtolower(entrada('email'));
    $erroSenha = erro_senha((string) ($_POST['senha'] ?? ''), (string) ($_POST['confirmacao'] ?? ''));
    if (mb_strlen($nome) < 3 || !email_valido($email) || $erroSenha) {
      flash('erro', $erroSenha ?: 'Informe nome e e-mail válidos para o primeiro administrador.');
      redirecionar('/instalar');
    }
    $id = inserir('admins', [
      'nome' => $nome,
      'email' => $email,
      'senha_hash' => hash_senha($_POST['senha']),
      'papel' => 'admin',
      'ativo' => 1,
      'criado_em' => agora(),
    ]);
    auditar('instalacao_admin_criado', 'admin', $id, null, 'sistema');
    flash('sucesso', 'Primeiro administrador criado.');
  }

  exibir('publico/instalar', ['titulo' => 'Instalação', 'temAdmin' => true, 'aplicadas' => $aplicadas], 'publico');
}
