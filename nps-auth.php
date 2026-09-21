<?php
// Credenciais de acesso ao painel de NPS.
// Senhas nunca ficam em texto puro aqui: armazenamos apenas o hash PBKDF2 (sal + chave derivada).
$NPS_USERS = [
  'TR_ADM' => [
    'salt' => '09de3ae5fb338810e5410166919d425c',
    'iterations' => 100000,
    'key' => '624ea9d9c521ff435d780708fd7c6e06553a1af4f4fa2b30c415472ac486254c',
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
