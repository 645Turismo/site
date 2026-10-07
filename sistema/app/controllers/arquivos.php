<?php
// Entrega de arquivos com conferência de permissão: ADM vê tudo; o guia vê só os próprios
// (durante o cadastro, ainda sem login, vale o rascunho da sessão).
// O endereço usa uma chave aleatória (arquivo_url), nunca o id: trocar números na URL não leva a nada.

/** Id do guia "dono" desta sessão (logado ou com cadastro em andamento). */
function arquivo_guia_da_sessao(): ?int {
  if ($g = guia_atual()) {
    return (int) $g['id'];
  }
  return isset($_SESSION['cadastro_guia_id']) ? (int) $_SESSION['cadastro_guia_id'] : null;
}

/** Mesma resposta (404) para "não existe" e "não é seu": não revela se o arquivo existe. */
function arquivo_autorizar(?array $linha, string $colunaGuia = 'guia_id'): array {
  if (!$linha) {
    abortar(404, 'Arquivo não encontrado.');
  }
  if (admin_atual()) {
    return $linha;
  }
  $guia = arquivo_guia_da_sessao();
  if (!$guia || $guia !== (int) $linha[$colunaGuia]) {
    abortar(404, 'Arquivo não encontrado.');
  }
  return $linha;
}

function arq_foto(string $chave): void {
  $g = arquivo_autorizar(um('SELECT id, foto_path, codigo FROM guias WHERE foto_chave = ?', [$chave]), 'id');
  servir_arquivo($g['foto_path'], 'foto-' . $g['codigo']);
}

function arq_documento(string $chave): void {
  $d = arquivo_autorizar(um('SELECT * FROM guia_documentos WHERE chave = ?', [$chave]));
  if (admin_atual()) {
    auditar('documento_visualizado', 'guia', (int) $d['guia_id'], ['documento_id' => (int) $d['id']]);
  }
  servir_arquivo($d['arquivo_path'], $d['nome_original'] ?: $d['tipo']);
}

function arq_envio(string $chave): void {
  $e = arquivo_autorizar(um('SELECT * FROM envios WHERE chave = ?', [$chave]));
  servir_arquivo($e['arquivo_path'], $e['nome_original'] ?: $e['tipo']);
}

function arq_comprovante(string $chave): void {
  $p = arquivo_autorizar(um('SELECT * FROM pagamentos WHERE chave = ?', [$chave]));
  servir_arquivo($p['comprovante_path'], 'comprovante-pagamento');
}

function arq_anexo(string $chave): void {
  $m = arquivo_autorizar(um('SELECT cm.anexo_path, cm.chamado_id, c.guia_id FROM chamado_mensagens cm JOIN chamados c ON c.id = cm.chamado_id WHERE cm.chave = ?', [$chave]));
  servir_arquivo($m['anexo_path'], 'anexo-chamado-' . $m['chamado_id']);
}
