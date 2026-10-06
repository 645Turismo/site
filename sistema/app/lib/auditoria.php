<?php
// Registro de auditoria: quem fez o quê, em qual registro, de qual IP.

function auditar(string $acao, ?string $entidade = null, ?int $entidadeId = null, $detalhes = null,
                 ?string $atorTipo = null, ?int $atorId = null): void {
  if ($atorTipo === null) {
    if (!empty($_SESSION['admin_id'])) {
      $atorTipo = 'admin';
      $atorId = (int) $_SESSION['admin_id'];
    } elseif (!empty($_SESSION['guia_id'])) {
      $atorTipo = 'guia';
      $atorId = (int) $_SESSION['guia_id'];
    } else {
      $atorTipo = 'sistema';
    }
  }
  inserir('auditoria', [
    'ator_tipo' => $atorTipo,
    'ator_id' => $atorId,
    'acao' => $acao,
    'entidade' => $entidade,
    'entidade_id' => $entidadeId,
    'detalhes' => is_string($detalhes) || $detalhes === null ? $detalhes : json_encode($detalhes, JSON_UNESCAPED_UNICODE),
    'ip' => ip_cliente(),
    'criado_em' => agora(),
  ]);
}
