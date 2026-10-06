<?php
// Menu na ordem da rotina do guia.
$area = 'Área do Guia';
$inicioHref = '/guia/hoje';
$itensMenu = [
  'hoje' => ['Hoje', '/guia/hoje', 'inicio'],
  'viagens' => ['Viagens/Tours', '/guia/viagens', 'eventos', 'Viagens'],
  'disponibilidade' => ['Disponibilidade', '/guia/disponibilidade', 'agenda', 'Agenda'],
  'recebimentos' => ['Recebimentos', '/guia/recebimentos', 'financeiro', 'Ganhos'],
  'perfil' => ['Perfil', '/guia/perfil', 'usuario'],
  'ajuda' => ['Ajuda', '/guia/ajuda', 'suporte'],
];
$abas = ['hoje', 'viagens', 'disponibilidade', 'recebimentos'];
$perfilNome = $p['nome_social'] ?: $p['nome'];
$perfilLinha = 'Guia ' . $p['codigo'];
$acaoSair = '/guia/sair';
$comTour = true;
require __DIR__ . '/logado.php';
