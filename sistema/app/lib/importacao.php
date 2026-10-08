<?php
// Importação da lista de passageiros a partir da planilha (arquivo CSV/XLSX ou linhas coladas).
// Encontra a linha do cabeçalho (mesmo com logo e títulos acima), mapeia as colunas pelo nome
// em qualquer ordem, ignora a coluna de numeração e as linhas vazias do modelo.

// Nomes de coluna aceitos (comparação sem acento e sem maiúsculas).
const COLUNAS_PLANILHA = [
  'nome' => ['nome completo', 'nome', 'passageiro', 'nome do passageiro'],
  'tipo_documento' => ['tipo do documento', 'tipo documento', 'tipo de documento', 'tipo doc', 'tipo'],
  'documento' => ['documento', 'n do documento', 'no do documento', 'numero do documento', 'doc', 'rg', 'cpf', 'rg/cpf'],
  'nascimento' => ['data de nascimento', 'nascimento', 'data nascimento', 'dt nascimento', 'data de nasc'],
  'venda' => ['venda', 'reserva', 'n da venda', 'no da venda', 'codigo da venda', 'pedido'],
  'embarque' => ['embarque', 'local de embarque', 'ponto de embarque'],
  'observacao' => ['observacao', 'observacoes', 'obs'],
  'poltrona' => ['poltrona', 'assento', 'lugar'],
  'telefone' => ['telefone', 'celular', 'whatsapp', 'fone', 'contato'],
  'tipo_pax' => ['tipo de passageiro', 'tipo do passageiro', 'tipo passageiro', 'tipo pax', 'tipo de pax', 'pax', 'categoria', 'faixa etaria'],
];

function texto_chave(string $s): string {
  // Tabela própria de acentos: o iconv do Windows translitera "ç" como "c," e quebraria a comparação.
  $s = strtr(mb_strtolower(trim($s)), ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'ê' => 'e', 'è' => 'e',
    'í' => 'i', 'ì' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ò' => 'o', 'ú' => 'u', 'ü' => 'u', 'ù' => 'u', 'ç' => 'c', 'º' => 'o', 'ª' => 'a']);
  return trim(preg_replace('/[^a-z0-9\/]+/', ' ', $s));
}

/** Linhas coladas (copiadas do Excel/Google Planilhas): colunas por tabulação ou ";". */
function planilha_do_texto(string $texto): array {
  $linhas = [];
  foreach (preg_split('/\r\n|\r|\n/', $texto) as $i => $linha) {
    if (trim($linha) !== '') {
      $linhas[] = [$i + 1, array_map('trim', preg_split(str_contains($linha, "\t") ? '/\t/' : '/;/', $linha))];
    }
  }
  return $linhas;
}

/** Procura o cabeçalho nas primeiras linhas. Retorna [índice da linha, [coluna => campo]] ou null. */
function planilha_achar_cabecalho(array $linhas, array $colunas = COLUNAS_PLANILHA): ?array {
  foreach (array_slice($linhas, 0, 25, true) as $idx => [, $celulas]) {
    $mapa = [];
    foreach ($celulas as $col => $valor) {
      $chave = texto_chave((string) $valor);
      foreach ($colunas as $campo => $nomes) {
        if ($chave !== '' && in_array($chave, $nomes, true) && !in_array($campo, $mapa, true)) {
          $mapa[$col] = $campo;
          break;
        }
      }
    }
    if (in_array('nome', $mapa, true) && count($mapa) >= 2) {
      return [$idx, $mapa];
    }
  }
  return null;
}

/** Data do Excel (número de série) ou texto dd/mm/aaaa. */
function planilha_data(string $valor): string {
  if (preg_match('/^\d{4,5}(\.\d+)?$/', $valor) && (float) $valor > 0 && (float) $valor < 80000) {
    return gmdate('Y-m-d', (int) round(((float) $valor - 25569) * 86400));
  }
  return $valor;
}

/**
 * Interpreta as linhas da planilha. Retorna:
 *   passageiros: [ ['linha' => n, 'dados' => [...], 'avisos' => [...]] ]
 *   ignoradas:   [ [linha, motivo] ]
 *   colunas:     campos reconhecidos no cabeçalho (vazio se foi pela ordem padrão)
 */
function planilha_interpretar(array $linhas): array {
  $resultado = ['passageiros' => [], 'ignoradas' => [], 'colunas' => []];
  $cab = planilha_achar_cabecalho($linhas);
  if ($cab) {
    [$inicio, $mapa] = $cab;
    $linhas = array_slice($linhas, $inicio + 1);
    if (!in_array('poltrona', $mapa, true) && ($coluna = planilha_achar_coluna_poltrona($linhas, $mapa)) !== null) {
      $mapa[$coluna] = 'poltrona';
      $resultado['poltrona_sem_titulo'] = planilha_letra_coluna($coluna);
    }
    $resultado['colunas'] = array_values($mapa);
  } else {
    $mapa = null; // sem cabeçalho: ordem padrão da planilha da 645
  }

  foreach ($linhas as [$numero, $celulas]) {
    if (!array_filter($celulas, fn($c) => trim((string) $c) !== '')) {
      continue;
    }
    if ($mapa === null) {
      // Ordem padrão da 645: a primeira coluna (número) é a poltrona; sem número, começa no nome.
      $ordem = isset($celulas[0]) && preg_match('/^\d{1,4}$/', trim((string) $celulas[0])) ? ORDEM_PLANILHA : array_slice(ORDEM_PLANILHA, 1);
      $valores = array_fill_keys(array_keys(CAMPOS_PASSAGEIRO), '');
      foreach ($ordem as $i => $campo) {
        $valores[$campo] = (string) ($celulas[$i] ?? '');
      }
    } else {
      $valores = array_fill_keys(array_keys(CAMPOS_PASSAGEIRO), '');
      foreach ($mapa as $col => $campo) {
        $valores[$campo] = (string) ($celulas[$col] ?? '');
      }
    }
    if (trim($valores['nome']) === '') {
      // Linha só com numeração (modelo em branco) é ignorada sem aviso.
      $resto = array_filter($valores, fn($v) => trim($v) !== '' && !preg_match('/^\d{1,4}$/', trim($v)));
      if ($resto) {
        $resultado['ignoradas'][] = [$numero, 'sem nome completo'];
      }
      continue;
    }
    $dados = [];
    $avisos = [];
    foreach ($valores as $campo => $valor) {
      if ($campo === 'nascimento') {
        $valor = planilha_data(trim($valor));
      }
      [$normalizado, $erro] = passageiro_normalizar($campo, $valor);
      if ($erro && $campo === 'nascimento') {
        $avisos[] = 'data de nascimento ilegível ("' . $valor . '")';
        $normalizado = null;
      } elseif ($erro && $campo === 'tipo_pax') {
        $avisos[] = 'tipo de passageiro não reconhecido ("' . $valor . '")';
        $normalizado = null;
      } elseif ($erro) {
        $resultado['ignoradas'][] = [$numero, $erro];
        continue 2;
      }
      $dados[$campo] = $normalizado;
    }
    $resultado['passageiros'][] = ['linha' => $numero, 'dados' => $dados, 'avisos' => $avisos];
  }
  return $resultado;
}

/** Chave para achar o mesmo passageiro: documento (só letras e números) ou nome + nascimento. */
function passageiro_chave(array $p): string {
  $doc = preg_replace('/[^0-9A-Za-z]/', '', (string) ($p['documento'] ?? ''));
  if ($doc !== '') {
    return 'doc:' . strtoupper($doc);
  }
  return 'nome:' . texto_chave((string) ($p['nome'] ?? '')) . '|' . ($p['nascimento'] ?? '');
}

/** Marca quem já está na lista da viagem (ou repetido no próprio arquivo). */
function importacao_marcar_repetidos(int $viagemId, array $passageiros): array {
  $existentes = [];
  foreach (todos("SELECT nome, documento, nascimento FROM passageiros WHERE viagem_id = ? AND status = 'ativo'", [$viagemId]) as $p) {
    $existentes[passageiro_chave($p)] = true;
  }
  foreach ($passageiros as &$p) {
    $chave = passageiro_chave($p['dados']);
    $p['repetido'] = isset($existentes[$chave]);
    $existentes[$chave] = true;
  }
  unset($p);
  return $passageiros;
}

/**
 * Aplica as regras da viagem aos passageiros novos da planilha, na ordem do arquivo:
 * embarque fora dos locais da viagem vai para a observação e poltrona bloqueada, inexistente ou já ocupada
 * fica em branco (o ADM escolhe outra depois). Cada caso vira um aviso na prévia.
 */
function importacao_aplicar_regras(array $viagem, array $passageiros): array {
  $ocupacao = passageiros_ocupacao((int) $viagem['id']);
  foreach ($passageiros as &$p) {
    $p['avisos'] = array_values(array_filter($p['avisos'], fn($a) => !str_starts_with($a, 'embarque') && !str_starts_with($a, 'poltrona')));
    if ($p['repetido']) {
      continue;
    }
    $d = $p['dados'];
    // Guia/staff identificado pela observação vira tipo Staff (pode ficar em poltrona bloqueada).
    if (($d['tipo_pax'] ?? null) === null && passageiro_e_equipe($d['observacao'] ?? null)) {
      $d['tipo_pax'] = 'staff';
    }
    [$semPoltrona, $erro] = passageiro_aplicar_regras($viagem, ['poltrona' => null] + $d, []);
    if ($erro) {
      $p['avisos'][] = 'embarque "' . $d['embarque'] . '" não cadastrado na viagem (foi para a observação)';
      $d['observacao'] = trim(($d['observacao'] ?? '') . ' Embarque na planilha: ' . $d['embarque']);
      $d['observacao'] = mb_substr($d['observacao'], 0, CAMPOS_PASSAGEIRO['observacao'][1]);
      $d['embarque'] = null;
    } else {
      $d['embarque'] = $semPoltrona['embarque'];
    }
    [, $erro] = passageiro_aplicar_regras($viagem, ['embarque' => null] + $d, $ocupacao);
    if ($erro) {
      $p['avisos'][] = 'poltrona ' . $d['poltrona'] . ' não pode ser usada (' . rtrim(preg_replace('/^A poltrona \S+ /', '', $erro), '.') . '), entra sem poltrona';
      $d['poltrona'] = null;
    }
    if ($d['poltrona'] !== null) {
      $ocupacao[$d['poltrona']][] = ['id' => 0, 'nome' => $d['nome'], 'colo' => ($d['tipo_pax'] ?? null) === 'colo'];
    }
    $p['dados'] = $d;
  }
  unset($p);
  return $passageiros;
}

/**
 * Planilha sem a coluna "Poltrona" no cabeçalho (na lista da 645 a numeração ao lado do nome é a poltrona):
 * procura uma coluna não reconhecida em que pelo menos 80% dos passageiros têm número inteiro de 1 a 99,
 * sem repetir. Retorna o índice da coluna ou null.
 */
function planilha_achar_coluna_poltrona(array $linhas, array $mapa): ?int {
  $colNome = array_search('nome', $mapa, true);
  $melhor = null;
  $melhorTaxa = 0.0;
  $candidatas = [];
  foreach ($linhas as [, $celulas]) {
    foreach (array_keys($celulas) as $col) {
      if (!isset($mapa[$col])) {
        $candidatas[$col] = true;
      }
    }
  }
  foreach (array_keys($candidatas) as $col) {
    $comNome = 0;
    $numeros = [];
    foreach ($linhas as [, $celulas]) {
      if (trim((string) ($celulas[$colNome] ?? '')) === '') {
        continue;
      }
      $comNome++;
      $v = trim((string) ($celulas[$col] ?? ''));
      if (preg_match('/^0*([1-9]\d?)(\.0+)?$/', $v, $m)) {
        $numeros[] = (int) $m[1];
      }
    }
    if ($comNome === 0 || count($numeros) !== count(array_unique($numeros))) {
      continue;
    }
    $taxa = count($numeros) / $comNome;
    // Empate: a coluna mais perto do nome.
    if ($taxa >= 0.8 && ($taxa > $melhorTaxa || ($taxa === $melhorTaxa && abs($col - $colNome) < abs($melhor - $colNome)))) {
      $melhor = $col;
      $melhorTaxa = $taxa;
    }
  }
  return $melhor;
}

function planilha_letra_coluna(int $indice): string {
  $letra = '';
  for ($n = $indice + 1; $n > 0; $n = intdiv($n - 1, 26)) {
    $letra = chr(65 + ($n - 1) % 26) . $letra;
  }
  return $letra;
}
