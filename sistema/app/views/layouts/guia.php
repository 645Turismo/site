<?php
// Menu na ordem da rotina do prestador.
$area = 'Área do Prestador';
$inicioHref = '/guia/hoje';
$itensMenu = [
  'hoje' => ['Hoje', '/guia/hoje', 'inicio'],
  'viagens' => ['Viagens/Tours', '/guia/viagens', 'eventos', 'Viagens'],
  'servicos' => ['Serviços', '/guia/servicos', 'cracha'],
  'consultoria' => ['Consultoria', '/guia/consultoria', 'grafico'],
  'pos' => ['Pós-viagem', '/guia/pos-viagem', 'documento', 'Pós'],
  'disponibilidade' => ['Disponibilidade', '/guia/disponibilidade', 'agenda', 'Agenda'],
  'recebimentos' => ['Recebimentos', '/guia/recebimentos', 'financeiro', 'Ganhos'],
  'perfil' => ['Perfil', '/guia/perfil', 'usuario'],
  'ajuda' => ['Ajuda', '/guia/ajuda', 'suporte'],
];
$abas = ['hoje', 'viagens', 'servicos', 'recebimentos'];
$perfilNome = $p['nome_social'] ?: $p['nome'];
$perfilLinha = 'Código ' . $p['codigo'];
$acaoSair = '/guia/sair';
$comTour = true;
require __DIR__ . '/logado.php';
