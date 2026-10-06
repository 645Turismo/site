<?php
// Entrega de arquivos com conferência de permissão: ADM vê tudo; o guia vê só os próprios
// (durante o cadastro, ainda sem login, vale o rascunho da sessão).

/** Id do guia "dono" desta sessão (logado ou com cadastro em andamento). */
function arquivo_guia_da_sessao(): ?int {
  if ($g = guia_atual()) {
    return (int) $g['id'];
  }
  return isset($_SESSION['cadastro_guia_id']) ? (int) $_SESSION['cadastro_guia_id'] : null;
}

function arquivo_autorizar(?int $donoGuiaId): void {
  if (admin_atual()) {
    return;
  }
  $guia = arquivo_guia_da_sessao();
  if (!$guia || $guia !== $donoGuiaId) {
    abortar(403, 'Você não tem acesso a este arquivo.');
  }
}

function arq_foto(int $guiaId): void {
  $g = um('SELECT id, foto_path, codigo FROM guias WHERE id = ?', [$guiaId]) ?? abortar(404);
  arquivo_autorizar((int) $g['id']);
  servir_arquivo($g['foto_path'], 'foto-' . $g['codigo']);
}

function arq_documento(int $id): void {
  $d = um('SELECT * FROM guia_documentos WHERE id = ?', [$id]) ?? abortar(404);
  arquivo_autorizar((int) $d['guia_id']);
  if (admin_atual()) {
    auditar('documento_visualizado', 'guia', (int) $d['guia_id'], ['documento_id' => $id]);
  }
  servir_arquivo($d['arquivo_path'], $d['nome_original'] ?: $d['tipo']);
}

function arq_envio(int $id): void {
  $e = um('SELECT * FROM envios WHERE id = ?', [$id]) ?? abortar(404);
  arquivo_autorizar((int) $e['guia_id']);
  servir_arquivo($e['arquivo_path'], $e['nome_original'] ?: $e['tipo']);
}

function arq_comprovante(int $id): void {
  $p = um('SELECT * FROM pagamentos WHERE id = ?', [$id]) ?? abortar(404);
  arquivo_autorizar((int) $p['guia_id']);
  servir_arquivo($p['comprovante_path'], 'comprovante-pagamento-' . $id);
}

function arq_anexo(int $id): void {
  $m = um('SELECT cm.anexo_path, c.guia_id FROM chamado_mensagens cm JOIN chamados c ON c.id = cm.chamado_id WHERE cm.id = ?', [$id]) ?? abortar(404);
  arquivo_autorizar((int) $m['guia_id']);
  servir_arquivo($m['anexo_path'], 'anexo-' . $id);
}
