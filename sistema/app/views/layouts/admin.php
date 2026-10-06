<?php
// Menu na ordem da operação. [rótulo, link, ícone, papéis com acesso além do admin]
$todos = [
  'hoje' => ['Hoje', '/admin/hoje', 'painel', ['coordenador', 'financeiro']],
  'viagens' => ['Viagens/Tours', '/admin/viagens', 'eventos', ['coordenador'], 'Viagens'],
  'guias' => ['Guias', '/admin/guias', 'usuarios', ['coordenador']],
  'conferencia' => ['Conferência', '/admin/conferencia', 'check', ['coordenador', 'financeiro'], 'Conferir'],
  'pagamentos' => ['Pagamentos', '/admin/pagamentos', 'financeiro', ['financeiro']],
  'atendimento' => ['Atendimento', '/admin/atendimento', 'suporte', ['coordenador', 'financeiro']],
  'conteudo' => ['Conteúdo', '/admin/conteudo', 'orientacoes', ['coordenador']],
  'funcoes' => ['Funções', '/admin/funcoes', 'config', ['coordenador']],
  'equipe' => ['Equipe', '/admin/equipe', 'cadeado', []],
];
$itensMenu = [];
foreach ($todos as $chave => $item) {
  [$rotulo, $href, $ic, $papeis] = $item;
  if ($a['papel'] === 'admin' || in_array($a['papel'], $papeis, true)) {
    $itensMenu[$chave] = [$rotulo, $href, $ic, $item[4] ?? $rotulo];
  }
}
$area = 'Painel ADM';
$inicioHref = '/admin/hoje';
$abas = array_slice(array_keys($itensMenu), 0, 4);
$perfilNome = $a['nome'];
$perfilLinha = PAPEIS_ADMIN[$a['papel']] ?? $a['papel'];
$acaoSair = '/admin/sair';
$comTour = false;
require __DIR__ . '/logado.php';
