<?php
// Renderização de templates PHP (app/views) dentro de um layout (app/views/layouts).

function render(string $view, array $dados = [], ?string $layout = null): string {
  extract($dados, EXTR_SKIP);
  ob_start();
  require RAIZ . '/app/views/' . $view . '.php';
  $conteudo = ob_get_clean();
  if ($layout === null) {
    return $conteudo;
  }
  ob_start();
  require RAIZ . '/app/views/layouts/' . $layout . '.php';
  return ob_get_clean();
}

function exibir(string $view, array $dados = [], ?string $layout = null): void {
  echo render($view, $dados, $layout);
  limpar_antigo();
}

/** Ícones SVG em linha (traço), no estilo Lucide. */
function icone(string $nome, string $classe = 'ic'): string {
  static $caminhos = [
    'inicio' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/>',
    'agenda' => '<rect x="3" y="4.5" width="18" height="16.5" rx="2"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4"/>',
    'usuario' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
    'usuarios' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18.5 14.5c1.9.8 3 2.8 3 5.5"/>',
    'eventos' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 13h18"/>',
    'financeiro' => '<path d="M12 2v20"/><path d="M17 6.5c0-1.9-2.2-3-5-3s-5 1.3-5 3.3S9 9.8 12 10.5s5 1.6 5 3.7-2.2 3.3-5 3.3-5-1.1-5-3"/>',
    'suporte' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3.5"/><path d="m5.6 5.6 3.9 3.9M14.5 14.5l3.9 3.9M18.4 5.6l-3.9 3.9M9.5 14.5l-3.9 3.9"/>',
    'orientacoes' => '<path d="M2 5h6a4 4 0 0 1 4 4v12a3 3 0 0 0-3-3H2z"/><path d="M22 5h-6a4 4 0 0 0-4 4v12a3 3 0 0 1 3-3h7z"/>',
    'sair' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
    'sino' => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/>',
    'brilho' => '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/><path d="M19 3v4M17 5h4"/>',
    'olho' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
    'envios' => '<path d="m22 2-7 20-4-9-9-4z"/><path d="M22 2 11 13"/>',
    'painel' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
    'avisos' => '<path d="M3 11v2a1 1 0 0 0 1 1h3l6 4V6L7 10H4a1 1 0 0 0-1 1z"/><path d="M17 8a5 5 0 0 1 0 8"/>',
    'config' => '<path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="18" r="2"/>',
    'check' => '<path d="M20 6 9 17l-5-5"/>',
    'alerta' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v4.5M12 16h.01"/>',
    'seta' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    'cadeado' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
    'mais' => '<circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/>',
    'documento' => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>',
  ];
  $svg = $caminhos[$nome] ?? $caminhos['alerta'];
  return '<svg class="' . e($classe) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" '
    . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $svg . '</svg>';
}
