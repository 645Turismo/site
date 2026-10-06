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
function planilha_achar_cabecalho(array $linhas): ?array {
  foreach (array_slice($linhas, 0, 25, true) as $idx => [, $celulas]) {
    $mapa = [];
    foreach ($celulas as $col => $valor) {
      $chave = texto_chave((string) $valor);
      foreach (COLUNAS_PLANILHA as $campo => $nomes) {
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
    $resultado['colunas'] = array_values($mapa);
    $linhas = array_slice($linhas, $inicio + 1);
  } else {
    $mapa = null; // sem cabeçalho: ordem padrão da planilha da 645
  }

  foreach ($linhas as [$numero, $celulas]) {
    if (!array_filter($celulas, fn($c) => trim((string) $c) !== '')) {
      continue;
    }
    if ($mapa === null) {
      if (isset($celulas[0]) && preg_match('/^\d{1,4}$/', (string) $celulas[0])) {
        array_shift($celulas); // coluna de numeração
      }
      $valores = [];
      foreach (array_keys(CAMPOS_PASSAGEIRO) as $i => $campo) {
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
