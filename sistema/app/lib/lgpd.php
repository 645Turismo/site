<?php
// LGPD (Lei 13.709/2018): versão da política, direitos do titular (cópia dos dados e anonimização)
// e rotina de retenção (apagar ou anonimizar o que não é mais necessário).

/** Versão do texto da política de privacidade/termos que o prestador aceita. Mude ao revisar o texto. */
const POLITICA_VERSAO = '2026-10';

/** Prazos de guarda, em dias. Os textos de /privacidade citam estes valores. */
const RETENCAO_PASSAGEIROS_DIAS = 180;       // dados dos passageiros, depois do fim da viagem/tour
const RETENCAO_RASCUNHO_DIAS = 90;           // cadastro começado e abandonado
const RETENCAO_LOCALIZACAO_DIAS = 180;       // localização registrada no envio de check-in/check-out
const RETENCAO_TOKENS_DIAS = 30;             // links de redefinição de senha e tentativas de login
const RETENCAO_EMAILS_DIAS = 180;            // histórico de e-mails enviados (destinatário e assunto)
const RETENCAO_AUDITORIA_DIAS = 730;         // trilha de auditoria (24 meses)

/** O prestador informou algum dado de saúde (inclui restrição alimentar, que pode revelar saúde ou crença)? */
function lgpd_informou_dado_sensivel(array $d): bool {
  return !empty($d['pcd']) || !empty($d['pcd_descricao']) || (!empty($d['restricao_alimentar']) && $d['restricao_alimentar'] !== 'padrao') || !empty($d['doencas_preexistentes']);
}

/**
 * Cópia dos dados do prestador (direito de acesso, art. 18 II e V). Não inclui hash de senha nem os arquivos em si:
 * os documentos aparecem pelo nome e tipo, e a equipe envia o arquivo se o titular pedir.
 */
function lgpd_exportar_guia(int $id): array {
  $g = um('SELECT * FROM guias WHERE id = ?', [$id]) ?? [];
  unset($g['senha_hash']);
  $por = fn(string $sql) => todos($sql, [$id]);
  return [
    'gerado_em' => agora(),
    'controlador' => configuracao('empresa_razao_social', '645 TURISMO CONSULTORIA E SERVICOS LTDA'),
    'versao_politica_aceita' => $g['termos_versao'] ?? null,
    'cadastro' => $g,
    'funcoes' => $por('SELECT f.nome AS funcao FROM guia_funcoes gf JOIN funcoes f ON f.id = gf.funcao_id WHERE gf.guia_id = ?'),
    'idiomas' => $por('SELECT idioma, nivel FROM guia_idiomas WHERE guia_id = ?'),
    'dados_bancarios' => um('SELECT * FROM guia_dados_bancarios WHERE guia_id = ?', [$id]) ?? [],
    'documentos' => $por('SELECT tipo, nome_original, criado_em FROM guia_documentos WHERE guia_id = ?'),
    'disponibilidade' => $por('SELECT data, periodo, tipo FROM disponibilidade WHERE guia_id = ?'),
    'escalas' => $por('SELECT d.data, v.codigo AS viagem, s.status, s.valor FROM escalas s JOIN diarias d ON d.id = s.diaria_id JOIN viagens v ON v.id = d.viagem_id WHERE s.guia_id = ? ORDER BY d.data'),
    'envios' => $por('SELECT tipo, numero_nf, valor_nf, status, enviado_em FROM envios WHERE guia_id = ? ORDER BY enviado_em'),
    'pagamentos' => $por('SELECT valor, pago_em FROM pagamentos WHERE guia_id = ? ORDER BY pago_em'),
    'chamados' => $por('SELECT titulo, status, criado_em FROM chamados WHERE guia_id = ? ORDER BY criado_em'),
  ];
}

/** Motivo que impede a anonimização agora, ou null se pode seguir. */
function lgpd_impedimento_anonimizar(int $id): ?string {
  $g = um('SELECT status, anonimizado_em FROM guias WHERE id = ?', [$id]);
  if (!$g) {
    return 'Prestador não encontrado.';
  }
  if ($g['anonimizado_em']) {
    return 'Este cadastro já foi anonimizado.';
  }
  if (valor("SELECT 1 FROM escalas WHERE guia_id = ? AND status IN ('convidado','confirmado','em_campo','realizada','aguardando_nf','nf_em_conferencia','a_pagar')", [$id])) {
    return 'Há convites, escalas ou pagamentos em andamento. Conclua ou cancele antes de anonimizar.';
  }
  return null;
}

/**
 * Anonimiza o prestador (direito de eliminação, art. 18 VI): remove identificação, contatos, endereço, saúde,
 * documentos, foto, dados bancários e acesso. Mantém o que a lei manda guardar (escalas, relatórios, notas fiscais
 * e pagamentos, com o CNPJ/razão social usados na nota) pelo prazo fiscal, sem ligação com a pessoa física.
 */
function lgpd_anonimizar_guia(int $id): void {
  transacao(function () use ($id) {
    foreach (todos('SELECT arquivo_path FROM guia_documentos WHERE guia_id = ?', [$id]) as $d) {
      apagar_arquivo($d['arquivo_path']);
    }
    apagar_arquivo(valor('SELECT foto_path FROM guias WHERE id = ?', [$id]));
    foreach (['guia_documentos', 'guia_regioes', 'guia_idiomas', 'guia_dados_bancarios', 'disponibilidade'] as $t) {
      q("DELETE FROM $t WHERE guia_id = ?", [$id]);
    }
    q("DELETE FROM password_resets WHERE usuario_id = ? AND usuario_tipo IN ('guia', 'cadastro')", [$id]);
    q("DELETE FROM notificacoes WHERE destinatario_tipo = 'guia' AND destinatario_id = ?", [$id]);
    atualizar('guias', [
      'nome' => 'Prestador anonimizado ' . $id,
      'nome_social' => null, 'cpf' => 'ANON' . str_pad((string) $id, 7, '0', STR_PAD_LEFT),
      'celular' => null, 'email' => null, 'nascimento' => null, 'genero' => null, 'nacionalidade' => null,
      'camiseta' => null, 'pcd' => 0, 'pcd_descricao' => null, 'restricao_alimentar' => null, 'doencas_preexistentes' => null,
      'foto_path' => null, 'foto_chave' => null, 'apresentacao' => null, 'especialidades' => null,
      'cep' => null, 'logradouro' => null, 'numero' => null, 'complemento' => null, 'bairro' => null, 'cidade' => null, 'uf' => null,
      'cadastur_numero' => null, 'cadastur_situacao' => null, 'cadastur_uf' => null, 'cadastur_categorias' => null, 'cadastur_validade' => null,
      'senha_hash' => null, 'notas_internas' => null, 'consentimento_saude_em' => null,
      'status' => 'inativo', 'status_motivo' => 'Dados anonimizados a pedido do titular (LGPD).',
      'anonimizado_em' => agora(), 'atualizado_em' => agora(),
    ], 'id = ?', [$id]);
  });
}

/** Passageiros: apaga os dados pessoais depois do prazo, mantendo só a contagem e o histórico de check-in. */
function lgpd_anonimizar_passageiros(): int {
  $limite = date('Y-m-d', strtotime('-' . RETENCAO_PASSAGEIROS_DIAS . ' days'));
  $ids = array_column(todos(
    'SELECT p.id FROM passageiros p JOIN viagens v ON v.id = p.viagem_id WHERE p.anonimizado_em IS NULL AND v.data_fim < ?', [$limite]), 'id');
  foreach (array_chunk($ids, 200) as $lote) {
    $marcas = implode(',', array_fill(0, count($lote), '?'));
    q("UPDATE passageiros SET nome = 'Passageiro anonimizado', tipo_documento = NULL, documento = NULL, nascimento = NULL, venda = NULL,
         observacao = NULL, telefone = NULL, ajustes_guia = NULL, anonimizado_em = ? WHERE id IN ($marcas)", array_merge([agora()], $lote));
  }
  // Listas de passageiros anexadas às viagens (planilha/PDF) também saem do servidor.
  foreach (todos('SELECT a.id, a.titulo, a.arquivo_path FROM viagem_anexos a JOIN viagens v ON v.id = a.viagem_id WHERE v.data_fim < ?', [$limite]) as $a) {
    if (preg_match('/passageir|pax|manifesto|lista/iu', (string) $a['titulo'])) {
      apagar_arquivo($a['arquivo_path']);
      q('DELETE FROM viagem_anexos WHERE id = ?', [$a['id']]);
    }
  }
  return count($ids);
}

/** Rascunhos de cadastro abandonados (nunca enviados): apaga, com documentos e foto. */
function lgpd_excluir_rascunhos_antigos(): int {
  $limite = date('Y-m-d H:i:s', strtotime('-' . RETENCAO_RASCUNHO_DIAS . ' days'));
  $n = 0;
  foreach (todos("SELECT id FROM guias WHERE status = 'rascunho' AND COALESCE(atualizado_em, criado_em) < ?", [$limite]) as $r) {
    $id = (int) $r['id'];
    if (valor('SELECT 1 FROM escalas WHERE guia_id = ?', [$id]) || valor('SELECT 1 FROM chamados WHERE guia_id = ?', [$id])) {
      continue;
    }
    transacao(function () use ($id) {
      foreach (todos('SELECT arquivo_path FROM guia_documentos WHERE guia_id = ?', [$id]) as $d) {
        apagar_arquivo($d['arquivo_path']);
      }
      apagar_arquivo(valor('SELECT foto_path FROM guias WHERE id = ?', [$id]));
      foreach (['guia_documentos', 'guia_funcoes', 'guia_idiomas', 'guia_regioes', 'guia_dados_bancarios', 'disponibilidade'] as $t) {
        q("DELETE FROM $t WHERE guia_id = ?", [$id]);
      }
      q("DELETE FROM password_resets WHERE usuario_id = ? AND usuario_tipo IN ('guia', 'cadastro')", [$id]);
      q("DELETE FROM guias WHERE id = ? AND status = 'rascunho'", [$id]);
    });
    $n++;
  }
  return $n;
}

/** Roda todas as rotinas de retenção. Retorna o que foi feito, para o log. */
function lgpd_retencao_processar(): array {
  $dias = fn(int $n) => date('Y-m-d H:i:s', strtotime("-$n days"));
  $feito = [];
  $feito['passageiros_anonimizados'] = lgpd_anonimizar_passageiros();
  $feito['rascunhos_excluidos'] = lgpd_excluir_rascunhos_antigos();
  q('UPDATE envios SET latitude = NULL, longitude = NULL WHERE (latitude IS NOT NULL OR longitude IS NOT NULL) AND enviado_em < ?', [$dias(RETENCAO_LOCALIZACAO_DIAS)]);
  q('DELETE FROM login_tentativas WHERE criado_em < ?', [$dias(RETENCAO_TOKENS_DIAS)]);
  q('DELETE FROM password_resets WHERE criado_em < ?', [$dias(RETENCAO_TOKENS_DIAS)]);
  q('DELETE FROM emails_enviados WHERE criado_em < ?', [$dias(RETENCAO_EMAILS_DIAS)]);
  q('DELETE FROM auditoria WHERE criado_em < ?', [$dias(RETENCAO_AUDITORIA_DIAS)]);
  return $feito;
}

/** Roda a retenção no máximo uma vez por dia (chamada no fim das requisições, como os lembretes). */
function lgpd_retencao_agendada(): void {
  $marca = RAIZ . '/storage/logs/.retencao';
  if (is_file($marca) && time() - (int) filemtime($marca) < 86400) {
    return;
  }
  @touch($marca);
  try {
    $feito = lgpd_retencao_processar();
    if (array_sum($feito) > 0) {
      auditar('retencao_lgpd', null, null, $feito, 'sistema');
    }
  } catch (Throwable $e) {
    error_log('Retenção LGPD: ' . $e->getMessage());
  }
}
