<?php
// Validação e formatação de documentos e contatos brasileiros.

function so_digitos(?string $valor): string {
  return preg_replace('/\D+/', '', (string) $valor);
}

function cpf_valido(string $cpf): bool {
  $cpf = so_digitos($cpf);
  if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
    return false;
  }
  for ($t = 9; $t < 11; $t++) {
    $soma = 0;
    for ($i = 0; $i < $t; $i++) {
      $soma += (int) $cpf[$i] * (($t + 1) - $i);
    }
    $digito = ((10 * $soma) % 11) % 10;
    if ((int) $cpf[$t] !== $digito) {
      return false;
    }
  }
  return true;
}

function cnpj_valido(string $cnpj): bool {
  $cnpj = so_digitos($cnpj);
  if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
    return false;
  }
  $pesos = [[5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]];
  foreach ([12, 13] as $k => $pos) {
    $soma = 0;
    for ($i = 0; $i < $pos; $i++) {
      $soma += (int) $cnpj[$i] * $pesos[$k][$i];
    }
    $resto = $soma % 11;
    $digito = $resto < 2 ? 0 : 11 - $resto;
    if ((int) $cnpj[$pos] !== $digito) {
      return false;
    }
  }
  return true;
}

function email_valido(string $email): bool {
  return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 190;
}

/** Senha com pelo menos 8 caracteres, uma letra e um número. Retorna a mensagem de erro ou null. */
function erro_senha(string $senha, string $confirmacao): ?string {
  if (mb_strlen($senha) < 8) {
    return 'A senha precisa ter pelo menos 8 caracteres.';
  }
  if (!preg_match('/[A-Za-z]/', $senha) || !preg_match('/\d/', $senha)) {
    return 'A senha precisa ter letras e números.';
  }
  if ($senha !== $confirmacao) {
    return 'A confirmação não confere com a nova senha.';
  }
  return null;
}

function hash_senha(string $senha): string {
  return password_hash($senha, PASSWORD_DEFAULT);
}

function formatar_cpf(?string $cpf): string {
  $d = so_digitos($cpf);
  return strlen($d) === 11 ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $d) : (string) $cpf;
}

/**
 * CPF parcialmente oculto para listagens (LGPD). Mostra só os dígitos que o código do guia já revela
 * (início e fim); exibir o miolo, junto com o código, permitiria reconstruir o CPF inteiro.
 */
function mascarar_cpf(?string $cpf): string {
  $d = so_digitos($cpf);
  return strlen($d) === 11 ? substr($d, 0, 3) . '.***.***-' . substr($d, 9, 2) : '';
}

function formatar_cnpj(?string $cnpj): string {
  $d = so_digitos($cnpj);
  return strlen($d) === 14 ? preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $d) : (string) $cnpj;
}

function formatar_celular(?string $cel): string {
  $d = so_digitos($cel);
  if (strlen($d) === 11) {
    return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $d);
  }
  if (strlen($d) === 10) {
    return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $d);
  }
  return (string) $cel;
}

function formatar_data(?string $data): string {
  if (!$data) {
    return '';
  }
  $t = strtotime($data);
  return $t ? date('d/m/Y', $t) : '';
}

function formatar_data_hora(?string $data): string {
  if (!$data) {
    return '';
  }
  $t = strtotime($data);
  return $t ? date('d/m/Y H:i', $t) : '';
}

function formatar_moeda($valor): string {
  return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

/**
 * Código do guia: 3 primeiros + 3 últimos dígitos do CPF (529.982.247-25 → 529725).
 * Se outro guia já tiver a mesma combinação, recebe sufixo (529725-2, 529725-3...).
 */
function gerar_codigo_guia(string $cpf): string {
  $d = so_digitos($cpf);
  $base = substr($d, 0, 3) . substr($d, -3);
  $codigo = $base;
  for ($n = 2; valor('SELECT 1 FROM guias WHERE codigo = ?', [$codigo]); $n++) {
    $codigo = $base . '-' . $n;
  }
  return $codigo;
}
