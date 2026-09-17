<?php
// Credenciais de acesso ao painel de NPS.
// Senhas nunca ficam em texto puro aqui: armazenamos apenas o hash PBKDF2 (sal + chave derivada).
$NPS_USERS = [
  'TR_ADM' => [
    'salt' => '1e75ed06a9aa856be4c0f2afa2077f43',
    'iterations' => 100000,
    'key' => '738c7de3895c7ffbc8c16e54544847526dc26a67418fc9e0957b404b130dc71c',
  ],
  '645_ADM' => [
    'salt' => '495fd9bdc385daee0e083a867b06b605',
    'iterations' => 100000,
    'key' => 'beef3842edda98ff31eae716adf0c665911bd47ca8df4b6735627e75bffa0788',
  ],
];

function nps_verify($usuario, $senha, $users) {
  if (!isset($users[$usuario])) {
    return false;
  }
  $u = $users[$usuario];
  $calculado = hash_pbkdf2('sha256', $senha, $u['salt'], $u['iterations'], 64, false);
  return hash_equals($u['key'], $calculado);
}
