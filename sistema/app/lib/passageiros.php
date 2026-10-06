<?php
// Lista de passageiros e check-in/check-out por dia de trabalho.
// A mesma lógica atende o guia (em campo, pelo celular) e o ADM (montagem da lista e acompanhamento ao vivo).
// As telas consultam passageiros_estado() a cada poucos segundos, então o que um lado marca aparece no outro.
//
// Ajustes do guia: o guia pode corrigir dados ou incluir passageiros em campo. A correção NÃO apaga o dado
// do ADM: fica em passageiros.ajustes_guia, aparece destacada na lista e o ADM recebe e-mail.
// O ADM decide se incorpora o ajuste ao cadastro.


// Colunas da lista, na ordem da planilha da 645. [rótulo, tamanho máximo]
const CAMPOS_PASSAGEIRO = [
  'nome' => ['Nome completo', 160],
  'tipo_documento' => ['Tipo do documento', 30],
  'documento' => ['Documento', 40],
  'nascimento' => ['Data de nascimento', 10],
  'venda' => ['Venda', 80],
  'embarque' => ['Embarque', 120],
  'observacao' => ['Observação', 500],
  'poltrona' => ['Poltrona', 10],
  'telefone' => ['Telefone', 20],
];

// Veículos disponíveis no cadastro da viagem/tour. [rótulo, lugares padrão (null = informado pelo ADM)]
// Mapa: fileiras de 4 (2 + corredor + 2), numeração da janela esquerda para a janela direita.
const VEICULOS = [
  'micro_26' => ['Micro-ônibus - 26 lugares', 26],
  'onibus_46' => ['Ônibus Padrão - 46 lugares', 46],
  'onibus_50' => ['Ônibus Padrão - 50 lugares', 50],
  'dd_64' => ['Ônibus DD - 64 lugares', 64],
  'outro' => ['Outro tipo', null],
];
const DD_PISO_INFERIOR_PADRAO = 12;

/** Lê "1, 2, 45-46" em [1, 2, 45, 46], limitado à capacidade. */
function ler_poltronas_bloqueadas(?string $texto, int $lugares): array {
  $lista = [];
  foreach (preg_split('/[\s,;]+/', (string) $texto, -1, PREG_SPLIT_NO_EMPTY) as $parte) {
    if (preg_match('/^(\d+)-(\d+)$/', $parte, $m)) {
      for ($n = (int) $m[1]; $n <= min((int) $m[2], $lugares); $n++) {
        $lista[$n] = true;
      }
    } elseif (ctype_digit($parte) && (int) $parte >= 1 && (int) $parte <= $lugares) {
      $lista[(int) $parte] = true;
    }
  }
  $lista = array_keys($lista);
  sort($lista);
  return $lista;
}

/** Layout do carro para o mapa de poltronas (null quando a viagem não tem veículo definido). */
function veiculo_layout(array $viagem): ?array {
  $tipo = $viagem['veiculo'] ?? null;
  if (!$tipo || !isset(VEICULOS[$tipo])) {
    return null;
  }
  [$rotulo, $padrao] = VEICULOS[$tipo];
  $lugares = max(1, min(90, (int) ($viagem['lugares'] ?: $padrao)));
  if ($tipo === 'outro') {
    $rotulo = ($viagem['veiculo_descricao'] ?: 'Veículo') . ' - ' . $lugares . ' lugares';
  } elseif ($lugares !== (int) $padrao) {
    $rotulo .= ' (ajustado para ' . $lugares . ')';
  }
  return [
    'tipo' => $tipo,
    'rotulo' => $rotulo,
    'lugares' => $lugares,
    'por_fileira' => 4,
    // Ônibus DD: as últimas poltronas ficam no piso inferior (quantidade configurável).
    'piso_inferior' => $tipo === 'dd_64' ? min($lugares - 1, (int) ($viagem['lugares_piso_inferior'] ?? 0) ?: DD_PISO_INFERIOR_PADRAO) : 0,
    'bloqueadas' => ler_poltronas_bloqueadas($viagem['poltronas_bloqueadas'] ?? '', $lugares),
  ];
}

const TIPOS_DOCUMENTO =['RG', 'CPF', 'Passaporte', 'Certidão de nascimento', 'CNH', 'RNM / RNE', 'Outro'];

/** Normaliza o valor de um campo. Retorna [valor|null, erro|null]. */
function passageiro_normalizar(string $campo, $valor): array {
  $valor = trim((string) $valor);
  if ($campo === 'nascimento') {
    if ($valor === '') {
      return [null, null];
    }
    if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $valor, $m)) {
      $valor = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    return data_valida($valor) && $valor <= hoje() ? [$valor, null] : [null, 'Data de nascimento inválida (use dd/mm/aaaa).'];
  }
  if ($campo === 'telefone') {
    $d = so_digitos($valor);
    return [$d === '' ? null : substr($d, 0, 13), null];
  }
  if ($campo === 'poltrona') {
    // "07" e "7" são a mesma poltrona no mapa do carro.
    $valor = strtoupper(preg_replace('/\s+/', '', $valor));
    $valor = preg_match('/^0+\d/', $valor) ? ltrim($valor, '0') : $valor;
  }
  $valor = mb_substr(preg_replace('/\s+/', ' ', $valor), 0, CAMPOS_PASSAGEIRO[$campo][1]);
  if ($campo === 'nome' && mb_strlen($valor) < 2) {
    return [null, 'Informe o nome completo.'];
  }
  return [$valor === '' ? null : $valor, null];
}

/** Lê os campos do formulário. Retorna [dados, erro|null]. */
function passageiro_ler_form(): array {
  $dados = [];
  foreach (array_keys(CAMPOS_PASSAGEIRO) as $campo) {
    [$v, $erro] = passageiro_normalizar($campo, $_POST[$campo] ?? '');
    if ($erro) {
      return [[], $erro];
    }
    $dados[$campo] = $v;
  }
  return [$dados, null];
}

function passageiro_exibir(string $campo, ?string $valor): string {
  if ($campo === 'nascimento') {
    return formatar_data($valor);
  }
  return $campo === 'telefone' ? formatar_celular($valor) : (string) $valor;
}

/** Estado completo da lista num dia de trabalho, no formato consumido pelo JavaScript. */
function passageiros_estado(array $viagem, array $diaria, bool $podeMarcar): array {
  $linhas = todos("SELECT p.*, r.checkin_em, r.checkin_por_tipo, r.checkin_por_id, r.checkout_em, r.checkout_por_tipo, r.checkout_por_id
      FROM passageiros p LEFT JOIN passageiro_registros r ON r.passageiro_id = p.id AND r.diaria_id = ?
      WHERE p.viagem_id = ? AND p.status = 'ativo'
      ORDER BY p.ordem, p.id", [$diaria['id'], $viagem['id']]);

  $nomes = passageiros_nomes_autores($linhas);
  $lista = [];
  $totais = ['total' => 0, 'checkin' => 0, 'checkout' => 0, 'ajustados' => 0];
  foreach ($linhas as $i => $l) {
    $ajustes = json_decode((string) $l['ajustes_guia'], true) ?: [];
    $campos = [];
    foreach (array_keys(CAMPOS_PASSAGEIRO) as $campo) {
      $editado = array_key_exists($campo, $ajustes);
      $campos[$campo] = [
        'v' => passageiro_exibir($campo, $editado ? $ajustes[$campo] : $l[$campo]),
        'bruto' => (string) ($editado ? $ajustes[$campo] : $l[$campo]),
        'g' => $editado,
        'o' => $editado ? passageiro_exibir($campo, $l[$campo]) : null,
      ];
    }
    $marca = function (string $tipo) use ($l, $nomes) {
      if (!$l[$tipo . '_em']) {
        return null;
      }
      return ['hora' => date('H:i', strtotime($l[$tipo . '_em'])),
        'por' => $nomes[$l[$tipo . '_por_tipo'] . ':' . $l[$tipo . '_por_id']] ?? ''];
    };
    $checkin = $marca('checkin');
    $checkout = $marca('checkout');
    $incluidoGuia = $l['criado_por_tipo'] === 'guia';
    $totais['total']++;
    $totais['checkin'] += $checkin ? 1 : 0;
    $totais['checkout'] += $checkout ? 1 : 0;
    $totais['ajustados'] += ($ajustes || $incluidoGuia) ? 1 : 0;
    $lista[] = [
      'id' => (int) $l['id'],
      'n' => $i + 1,
      'campos' => $campos,
      'incluido_guia' => $incluidoGuia,
      'ajustado_em' => $l['ajustado_em'] ? formatar_data_hora($l['ajustado_em']) : '',
      'checkin' => $checkin,
      'checkout' => $checkout,
    ];
  }
  return [
    'diaria' => ['id' => (int) $diaria['id'], 'data' => formatar_data($diaria['data'])],
    'veiculo' => veiculo_layout($viagem),
    'passageiros' => $lista,
    'totais' => $totais,
    'pode_marcar' => $podeMarcar,
    'atualizado' => date('H:i:s'),
  ];
}

/** Nomes curtos de quem fez cada marcação ("Ana", "Bruno (equipe)"). */
function passageiros_nomes_autores(array $linhas): array {
  $ids = ['guia' => [], 'admin' => []];
  foreach ($linhas as $l) {
    foreach (['checkin', 'checkout'] as $t) {
      if ($l[$t . '_por_tipo'] && $l[$t . '_por_id']) {
        $ids[$l[$t . '_por_tipo']][(int) $l[$t . '_por_id']] = true;
      }
    }
  }
  $nomes = [];
  foreach (['guia' => 'guias', 'admin' => 'admins'] as $tipo => $tabela) {
    if (!$ids[$tipo]) {
      continue;
    }
    $lista = array_keys($ids[$tipo]);
    $marcas = implode(',', array_fill(0, count($lista), '?'));
    foreach (todos("SELECT id, nome FROM $tabela WHERE id IN ($marcas)", $lista) as $u) {
      $primeiro = explode(' ', trim($u['nome']))[0];
      $nomes["$tipo:{$u['id']}"] = $tipo === 'guia' ? $primeiro : $primeiro . ' (equipe)';
    }
  }
  return $nomes;
}

/**
 * Marca ou desfaz check-in/check-out. Retorna null em caso de sucesso ou a mensagem de erro.
 * O check-out exige check-in; desfazer o check-in também desfaz o check-out.
 */
function passageiro_marcar(int $viagemId, int $passageiroId, int $diariaId, string $tipo, bool $desfazer,
                           string $atorTipo, int $atorId): ?string {
  if (!in_array($tipo, ['checkin', 'checkout'], true)) {
    return 'Marcação inválida.';
  }
  if (!valor("SELECT 1 FROM passageiros WHERE id = ? AND viagem_id = ? AND status = 'ativo'", [$passageiroId, $viagemId])) {
    return 'Passageiro não encontrado nesta lista.';
  }
  if (!valor('SELECT 1 FROM diarias WHERE id = ? AND viagem_id = ?', [$diariaId, $viagemId])) {
    return 'Dia de trabalho inválido.';
  }
  $r = um('SELECT * FROM passageiro_registros WHERE passageiro_id = ? AND diaria_id = ?', [$passageiroId, $diariaId]);
  if (!$r) {
    $id = inserir('passageiro_registros', ['passageiro_id' => $passageiroId, 'diaria_id' => $diariaId, 'atualizado_em' => agora()]);
    $r = um('SELECT * FROM passageiro_registros WHERE id = ?', [$id]);
  }
  if ($desfazer) {
    $dados = [$tipo . '_em' => null, $tipo . '_por_tipo' => null, $tipo . '_por_id' => null];
    if ($tipo === 'checkin') {
      $dados += ['checkout_em' => null, 'checkout_por_tipo' => null, 'checkout_por_id' => null];
    }
  } else {
    if ($r[$tipo . '_em']) {
      return null; // Outra pessoa já marcou: mantém o primeiro registro.
    }
    if ($tipo === 'checkout' && !$r['checkin_em']) {
      return 'Faça o check-in antes do check-out.';
    }
    $dados = [$tipo . '_em' => agora(), $tipo . '_por_tipo' => $atorTipo, $tipo . '_por_id' => $atorId];
  }
  atualizar('passageiro_registros', $dados + ['atualizado_em' => agora()], 'id = ?', [$r['id']]);
  return null;
}

function passageiro_incluir(int $viagemId, array $dados, string $atorTipo, ?int $atorId): int {
  $ordem = (int) valor('SELECT COALESCE(MAX(ordem), 0) FROM passageiros WHERE viagem_id = ?', [$viagemId]) + 1;
  return inserir('passageiros', $dados + [
    'viagem_id' => $viagemId, 'ordem' => $ordem, 'status' => 'ativo',
    'criado_por_tipo' => $atorTipo, 'criado_por_id' => $atorId, 'criado_em' => agora(), 'atualizado_em' => agora(),
  ]);
}

/**
 * Ajuste feito pelo guia. Em passageiros cadastrados pelo ADM, o valor vai para ajustes_guia (o original fica
 * guardado); em passageiros que o próprio guia incluiu, grava direto. Retorna a lista de alterações [rótulo, antes, depois].
 */
function passageiro_ajustar_pelo_guia(array $p, array $novos, int $guiaId): array {
  $ajustes = json_decode((string) $p['ajustes_guia'], true) ?: [];
  $alteracoes = [];
  $direto = $p['criado_por_tipo'] === 'guia';
  $gravarDireto = [];
  foreach ($novos as $campo => $valor) {
    $atual = array_key_exists($campo, $ajustes) ? $ajustes[$campo] : $p[$campo];
    if ((string) $valor === (string) $atual) {
      continue;
    }
    $alteracoes[] = [CAMPOS_PASSAGEIRO[$campo][0], passageiro_exibir($campo, $atual), passageiro_exibir($campo, $valor)];
    if ($direto) {
      $gravarDireto[$campo] = $valor;
    } elseif ((string) $valor === (string) $p[$campo]) {
      unset($ajustes[$campo]); // voltou ao valor do ADM
    } else {
      $ajustes[$campo] = $valor;
    }
  }
  if ($alteracoes) {
    atualizar('passageiros', $gravarDireto + [
      'ajustes_guia' => $ajustes ? json_encode($ajustes, JSON_UNESCAPED_UNICODE) : null,
      'ajustado_por_guia_id' => $guiaId,
      'ajustado_em' => agora(),
      'atualizado_em' => agora(),
    ], 'id = ?', [$p['id']]);
  }
  return $alteracoes;
}

/** Edição pelo ADM: grava no cadastro e descarta ajustes do guia nos campos que o ADM alterou. */
function passageiro_editar_pelo_admin(array $p, array $novos): void {
  $ajustes = json_decode((string) $p['ajustes_guia'], true) ?: [];
  foreach ($novos as $campo => $valor) {
    if ((string) $valor !== (string) $p[$campo]) {
      unset($ajustes[$campo]);
    }
  }
  atualizar('passageiros', $novos + [
    'ajustes_guia' => $ajustes ? json_encode($ajustes, JSON_UNESCAPED_UNICODE) : null,
    'atualizado_em' => agora(),
  ], 'id = ?', [$p['id']]);
}

/** ADM aceita os ajustes do guia: passam a ser o dado oficial e o destaque some. */
function passageiro_incorporar_ajustes(array $p): void {
  $ajustes = json_decode((string) $p['ajustes_guia'], true) ?: [];
  $dados = array_intersect_key($ajustes, CAMPOS_PASSAGEIRO);
  atualizar('passageiros', $dados + ['ajustes_guia' => null, 'criado_por_tipo' => 'admin', 'atualizado_em' => agora()], 'id = ?', [$p['id']]);
}

/** Avisa a coordenação por e-mail (e no painel) que um guia alterou a lista. */
function notificar_alteracao_lista(array $viagem, array $guia, string $passageiro, array $alteracoes, bool $inclusao): void {
  // E-mail vai para o endereço configurado (por enquanto contato@645turismo.com.br);
  // o aviso no painel vai para administradores e coordenadores ativos.
  $emailAlerta = configuracao('email_alertas_lista', 'contato@645turismo.com.br');
  $destinatarios = todos("SELECT id FROM admins WHERE ativo = 1 AND papel IN ('admin','coordenador')");
  $link = url_absoluta('/admin/viagens/' . (int) $viagem['id'] . '/passageiros');
  $guiaNome = $guia['nome_social'] ?: $guia['nome'];
  $assunto = 'Lista alterada pelo guia: ' . $viagem['codigo'];
  $itens = '';
  foreach ($alteracoes as [$rotulo, $antes, $depois]) {
    $itens .= '<li><strong>' . e($rotulo) . ':</strong> ' . ($inclusao ? '' : e($antes !== '' ? $antes : '(vazio)') . ' → ')
      . e($depois !== '' ? $depois : '(vazio)') . '</li>';
  }
  $html = '<p><strong>' . e($guiaNome) . '</strong> ' . ($inclusao ? 'incluiu um passageiro' : 'alterou dados de um passageiro')
    . ' na lista de <strong>' . e($viagem['codigo'] . ' · ' . $viagem['nome']) . '</strong>.</p>'
    . '<p>Passageiro: <strong>' . e($passageiro) . '</strong></p><ul>' . $itens . '</ul>'
    . '<p>Na lista, o ajuste aparece destacado como "editado pelo guia" até você incorporá-lo ao cadastro.</p>'
    . '<p><a href="' . e($link) . '" style="display:inline-block;padding:12px 22px;background:#53D9B2;color:#000;text-decoration:none;font-weight:bold;border-radius:999px">Ver lista de passageiros</a></p>';
  enviar_email((string) $emailAlerta, $assunto, $html);
  foreach ($destinatarios as $d) {
    inserir('notificacoes', ['destinatario_tipo' => 'admin', 'destinatario_id' => $d['id'], 'titulo' => $assunto,
      'mensagem' => $guiaNome . ' · ' . $passageiro, 'link' => '/admin/viagens/' . (int) $viagem['id'] . '/passageiros', 'criado_em' => agora()]);
  }
}

function resposta_json(array $dados, int $codigo = 200): never {
  http_response_code($codigo);
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store');
  echo json_encode($dados, JSON_UNESCAPED_UNICODE);
  exit;
}

