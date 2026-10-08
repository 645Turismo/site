<?php
// Lista de passageiros no Painel ADM: montagem (um a um ou colando da planilha), acompanhamento ao vivo
// do check-in/check-out feito pelos guias, revisão dos ajustes feitos pelos guias em campo.

function adm_passageiros_dia(array $v, ?int $diariaId): ?array {
  $dias = todos('SELECT * FROM diarias WHERE viagem_id = ? ORDER BY data', [$v['id']]);
  if (!$dias) {
    return null;
  }
  foreach ($dias as $d) {
    if ((int) $d['id'] === $diariaId) {
      return $d;
    }
  }
  // Sem escolha: o dia de hoje, senão o próximo, senão o último.
  foreach ($dias as $d) {
    if ($d['data'] >= hoje()) {
      return $d;
    }
  }
  return end($dias);
}

function adm_viagem_passageiros(int $id): void {
  $a = exigir_admin(PAPEIS_VIAGENS);
  $v = adm_viagem_carregar($id);
  $dia = adm_passageiros_dia($v, isset($_GET['diaria']) ? (int) $_GET['diaria'] : null);
  exibir('comum/passageiros', [
    'titulo' => 'Passageiros · ' . $v['codigo'],
    'menu' => 'viagens',
    'a' => $a,
    'v' => $v,
    'dias' => array_map(fn($d) => ['id' => (int) $d['id'], 'data' => $d['data']],
      todos('SELECT id, data FROM diarias WHERE viagem_id = ? ORDER BY data', [$id])),
    'escolhido' => $dia ? (int) $dia['id'] : null,
    'base' => "/admin/viagens/$id/passageiros",
    'origens' => viagem_origens($id),
    'voltar' => "/admin/viagens/$id",
    'admin' => true,
  ], 'admin');
}

function adm_passageiros_dados(int $id): void {
  exigir_admin(PAPEIS_VIAGENS);
  $v = adm_viagem_carregar($id);
  $dia = um('SELECT * FROM diarias WHERE id = ? AND viagem_id = ?', [(int) ($_GET['diaria'] ?? 0), $id])
    ?? resposta_json(['erro' => 'Dia de trabalho inválido.'], 404);
  resposta_json(passageiros_estado($v, $dia, true));
}

function adm_passageiro_marcar(int $id, int $passageiroId): void {
  $a = exigir_admin(PAPEIS_VIAGENS);
  $v = adm_viagem_carregar($id);
  $dia = um('SELECT * FROM diarias WHERE id = ? AND viagem_id = ?', [(int) entrada('diaria'), $id])
    ?? resposta_json(['erro' => 'Dia de trabalho inválido.'], 404);
  $erro = passageiro_marcar($id, $passageiroId, (int) $dia['id'], entrada('tipo'), entrada('desfazer') === '1', 'admin', (int) $a['id']);
  if ($erro) {
    resposta_json(['erro' => $erro], 422);
  }
  resposta_json(passageiros_estado($v, $dia, true));
}

function adm_passageiro_criar(int $id): void {
  $a = exigir_admin(PAPEIS_VIAGENS);
  $v = adm_viagem_carregar($id);
  [$dados, $erro] = passageiro_ler_form();
  if (!$erro) {
    [$dados, $erro] = passageiro_aplicar_regras($v, $dados, passageiros_ocupacao($id));
  }
  if ($erro) {
    resposta_json(['erro' => $erro], 422);
  }
  passageiro_incluir($id, $dados, 'admin', (int) $a['id']);
  auditar('passageiro_incluido', 'viagem', $id);
  resposta_json(['ok' => true]);
}

function adm_passageiro_editar(int $id, int $passageiroId): void {
  exigir_admin(PAPEIS_VIAGENS);
  $v = adm_viagem_carregar($id);
  $p = um("SELECT * FROM passageiros WHERE id = ? AND viagem_id = ? AND status = 'ativo'", [$passageiroId, $id])
    ?? resposta_json(['erro' => 'Passageiro não encontrado.'], 404);
  [$dados, $erro] = passageiro_ler_form();
  if (!$erro) {
    [$dados, $erro] = passageiro_aplicar_regras($v, $dados, passageiros_ocupacao($id, $passageiroId));
  }
  if ($erro) {
    resposta_json(['erro' => $erro], 422);
  }
  passageiro_editar_pelo_admin($p, $dados);
  auditar('passageiro_editado', 'passageiro', $passageiroId);
  resposta_json(['ok' => true]);
}

function adm_passageiro_incorporar(int $id, int $passageiroId): void {
  exigir_admin(PAPEIS_VIAGENS);
  adm_viagem_carregar($id);
  $p = um("SELECT * FROM passageiros WHERE id = ? AND viagem_id = ? AND status = 'ativo'", [$passageiroId, $id])
    ?? resposta_json(['erro' => 'Passageiro não encontrado.'], 404);
  passageiro_incorporar_ajustes($p);
  auditar('ajuste_guia_incorporado', 'passageiro', $passageiroId, $p['ajustes_guia']);
  resposta_json(['ok' => true]);
}

/**
 * Passo 1 da importação: lê a planilha (arquivo .xlsx/.csv ou linhas coladas), interpreta e guarda na sessão
 * para a prévia. Nada é gravado na lista ainda.
 */
function adm_passageiros_importar(int $id): void {
  exigir_admin(PAPEIS_VIAGENS);
  adm_viagem_carregar($id);
  $arquivo = $_FILES['arquivo'] ?? null;
  $temArquivo = $arquivo && ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
  try {
    $linhas = $temArquivo ? planilha_ler_upload($arquivo) : planilha_do_texto((string) ($_POST['lista'] ?? ''));
  } catch (RuntimeException $e) {
    flash('erro', $e->getMessage());
    redirecionar("/admin/viagens/$id/passageiros#importar");
  }
  $r = planilha_interpretar($linhas);
  if (!$r['passageiros']) {
    flash('erro', 'Nenhum passageiro encontrado. Confira se a planilha tem a coluna "Nome completo".');
    redirecionar("/admin/viagens/$id/passageiros#importar");
  }
  $_SESSION['importacao'][$id] = [
    'origem' => $temArquivo ? (string) $arquivo['name'] : 'linhas coladas',
    'passageiros' => importacao_marcar_repetidos($id, $r['passageiros']),
    'ignoradas' => $r['ignoradas'],
    'colunas' => $r['colunas'],
    'poltrona_sem_titulo' => $r['poltrona_sem_titulo'] ?? null,
  ];
  redirecionar("/admin/viagens/$id/passageiros/importar");
}

/** Passo 2: prévia do que vai entrar na lista. */
function adm_passageiros_importar_previa(int $id): void {
  $a = exigir_admin(PAPEIS_VIAGENS);
  $v = adm_viagem_carregar($id);
  $imp = $_SESSION['importacao'][$id] ?? null;
  if (!$imp) {
    redirecionar("/admin/viagens/$id/passageiros#importar");
  }
  // Recalcula repetidos: a lista pode ter mudado desde a leitura.
  $imp['passageiros'] = importacao_aplicar_regras($v, importacao_marcar_repetidos($id, $imp['passageiros']));
  exibir('admin/importar-previa', [
    'titulo' => 'Importar passageiros · ' . $v['codigo'],
    'menu' => 'viagens',
    'a' => $a,
    'v' => $v,
    'imp' => $imp,
  ], 'admin');
}

/** Passo 3: grava os passageiros novos (os que já estão na lista são pulados). */
function adm_passageiros_importar_confirmar(int $id): void {
  $a = exigir_admin(PAPEIS_VIAGENS);
  $v = adm_viagem_carregar($id);
  $imp = $_SESSION['importacao'][$id] ?? null;
  if (!$imp) {
    flash('erro', 'A leitura da planilha expirou. Envie o arquivo de novo.');
    redirecionar("/admin/viagens/$id/passageiros#importar");
  }
  // "Substituir a lista atual": esvazia a lista antes, e só repetidos dentro do próprio arquivo são pulados.
  $substituir = entrada('substituir') === '1';
  [$novos, $passageiros, $limpeza] = transacao(function () use ($v, $id, $a, $imp, $substituir) {
    $limpeza = $substituir ? passageiros_limpar_lista($id) : [0, 0];
    $passageiros = importacao_aplicar_regras($v, importacao_marcar_repetidos($id, $imp['passageiros']));
    $novos = array_values(array_filter($passageiros, fn($p) => !$p['repetido']));
    foreach ($novos as $p) {
      passageiro_incluir($id, $p['dados'], 'admin', (int) $a['id']);
    }
    return [$novos, $passageiros, $limpeza];
  });
  unset($_SESSION['importacao'][$id]);
  auditar('passageiros_importados', 'viagem', $id, ['quantidade' => count($novos), 'origem' => $imp['origem'], 'substituiu' => $substituir]);
  $pulados = count($passageiros) - count($novos);
  flash('sucesso', ($substituir ? 'Lista anterior removida (' . ($limpeza[0] + $limpeza[1]) . '). ' : '')
    . count($novos) . ' passageiro(s) incluído(s)' . ($pulados ? ", $pulados " . ($substituir ? 'repetidos na planilha foram pulados.' : 'já estavam na lista.') : '.'));
  redirecionar("/admin/viagens/$id/passageiros");
}

/**
 * Planilha padrão (.xlsx) para subir a lista. Com viagem: uma linha por poltrona do veículo (bloqueadas
 * ficam de fora) e lista de escolha com os locais de embarque da viagem.
 */
function adm_passageiros_modelo_xlsx(?int $viagemId = null): void {
  exigir_admin(PAPEIS_VIAGENS);
  $v = $viagemId ? adm_viagem_carregar($viagemId) : null;
  $linhas = [array_map(fn($c) => CAMPOS_PASSAGEIRO[$c][0], ORDEM_PLANILHA)];
  $layout = $v ? veiculo_layout($v) : null;
  if ($layout) {
    for ($p = 1; $p <= $layout['lugares']; $p++) {
      if (!in_array($p, $layout['bloqueadas'], true)) {
        $linhas[] = [$p];
      }
    }
  } else {
    $linhas[] = [1, 'Maria da Silva', 'RG', '12.345.678-9', '10/05/1980', 'V-1020', '', '', '(11) 99999-0000', 'Adulto'];
    for ($p = 2; $p <= 46; $p++) {
      $linhas[] = [$p];
    }
  }
  $col = array_flip(ORDEM_PLANILHA);
  $listas = [$col['tipo_documento'] => TIPOS_DOCUMENTO, $col['tipo_pax'] => array_values(TIPOS_PAX)];
  $origens = $v ? array_column(viagem_origens((int) $v['id']), 'local') : [];
  if ($origens) {
    $listas[$col['embarque']] = $origens;
  }
  $larguras = [9, 34, 16, 18, 16, 12, 24, 28, 17, 18];
  $nome = 'lista-passageiros-645' . ($v ? '-' . preg_replace('/[^A-Za-z0-9.]+/', '-', $v['codigo']) : '') . '.xlsx';
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="' . $nome . '"');
  header('Cache-Control: private, no-store');
  echo planilha_gerar_xlsx($linhas, $listas, $larguras);
  exit;
}

/** Endereço antigo do modelo (CSV): passa a entregar a planilha padrão em .xlsx. */
function adm_passageiros_modelo(): void {
  adm_passageiros_modelo_xlsx();
}

function adm_passageiro_remover(int $id, int $passageiroId): void {
  exigir_admin(PAPEIS_VIAGENS);
  adm_viagem_carregar($id);
  um("SELECT id FROM passageiros WHERE id = ? AND viagem_id = ? AND status = 'ativo'", [$passageiroId, $id])
    ?? resposta_json(['erro' => 'Passageiro não encontrado.'], 404);
  if (valor('SELECT 1 FROM passageiro_registros WHERE passageiro_id = ? AND (checkin_em IS NOT NULL OR checkout_em IS NOT NULL)', [$passageiroId])) {
    // Já embarcou em algum dia: mantém o histórico e só tira da lista.
    atualizar('passageiros', ['status' => 'cancelado', 'atualizado_em' => agora()], 'id = ?', [$passageiroId]);
  } else {
    q('DELETE FROM passageiro_registros WHERE passageiro_id = ?', [$passageiroId]);
    q('DELETE FROM passageiros WHERE id = ?', [$passageiroId]);
  }
  auditar('passageiro_removido', 'viagem', $id, ['passageiro_id' => $passageiroId]);
  resposta_json(['ok' => true]);
}

/** Esvazia a lista de passageiros da viagem de uma vez (para subir uma lista nova). */
function adm_passageiros_limpar(int $id): void {
  exigir_admin(PAPEIS_VIAGENS);
  adm_viagem_carregar($id);
  if (entrada('confirmacao') !== 'LIMPAR') {
    flash('erro', 'Para limpar a lista, digite LIMPAR no campo de confirmação.');
    redirecionar("/admin/viagens/$id/passageiros#limpar");
  }
  [$apagados, $guardados] = transacao(fn() => passageiros_limpar_lista($id));
  auditar('passageiros_lista_limpa', 'viagem', $id, ['apagados' => $apagados, 'guardados' => $guardados]);
  flash('sucesso', 'Lista de passageiros limpa: ' . ($apagados + $guardados) . ' removido(s)'
    . ($guardados ? " ($guardados com check-in ou no-show ficam guardados no histórico da viagem)" : '') . '. Agora é só importar a lista nova.');
  redirecionar("/admin/viagens/$id/passageiros#importar");
}
