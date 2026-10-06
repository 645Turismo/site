<?php
// Leitura de planilhas enviadas pelo ADM (CSV e XLSX), sem bibliotecas externas.
// Devolve sempre uma lista de [número da linha na planilha, [células como texto]].
// O XLSX é um ZIP com XML: usa ZipArchive quando existe no servidor e, se não existir, um leitor próprio.

const PLANILHA_MAX_BYTES = 5 * 1024 * 1024;
const PLANILHA_MAX_DESCOMPACTADO = 40 * 1024 * 1024; // proteção contra "zip bomb"
const PLANILHA_MAX_LINHAS = 3000;

/** Lê o arquivo enviado pelo formulário. Lança RuntimeException com mensagem para o usuário. */
function planilha_ler_upload(array $arquivo): array {
  $erro = $arquivo['error'] ?? UPLOAD_ERR_NO_FILE;
  if ($erro === UPLOAD_ERR_NO_FILE) {
    throw new RuntimeException('Escolha o arquivo da planilha.');
  }
  if ($erro === UPLOAD_ERR_INI_SIZE || $erro === UPLOAD_ERR_FORM_SIZE || ($arquivo['size'] ?? 0) > PLANILHA_MAX_BYTES) {
    throw new RuntimeException('O arquivo passa de 5 MB.');
  }
  if ($erro !== UPLOAD_ERR_OK || !is_uploaded_file($arquivo['tmp_name'])) {
    throw new RuntimeException('Não foi possível receber o arquivo. Tente de novo.');
  }
  $ext = strtolower(pathinfo((string) $arquivo['name'], PATHINFO_EXTENSION));
  $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']) ?: '';
  if ($ext === 'xlsx') {
    if (!in_array($mime, ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'], true)) {
      throw new RuntimeException('O arquivo não parece ser uma planilha .xlsx válida.');
    }
    return xlsx_ler($arquivo['tmp_name']);
  }
  if ($ext === 'csv' || $ext === 'txt') {
    if (!str_starts_with($mime, 'text/') && !in_array($mime, ['application/csv', 'application/octet-stream'], true)) {
      throw new RuntimeException('O arquivo não parece ser um CSV válido.');
    }
    return csv_ler((string) file_get_contents($arquivo['tmp_name']));
  }
  if ($ext === 'xls') {
    throw new RuntimeException('Arquivos .xls (Excel antigo) não são aceitos. No Excel, use "Salvar como" e escolha .xlsx ou CSV.');
  }
  throw new RuntimeException('Envie um arquivo .xlsx ou .csv.');
}

/** CSV do Excel/Google Planilhas: detecta separador (; , tab), remove BOM e converte de Windows-1252 se preciso. */
function csv_ler(string $conteudo): array {
  if (str_starts_with($conteudo, "\xEF\xBB\xBF")) {
    $conteudo = substr($conteudo, 3);
  }
  if (!mb_check_encoding($conteudo, 'UTF-8')) {
    $conteudo = mb_convert_encoding($conteudo, 'UTF-8', 'Windows-1252');
  }
  $amostra = implode("\n", array_slice(preg_split('/\r\n|\r|\n/', $conteudo), 0, 15));
  $contagem = [';' => substr_count($amostra, ';'), ',' => substr_count($amostra, ','), "\t" => substr_count($amostra, "\t")];
  arsort($contagem);
  $separador = (string) key($contagem);

  $fp = fopen('php://temp', 'r+');
  fwrite($fp, $conteudo);
  rewind($fp);
  $linhas = [];
  $n = 0;
  while (($celulas = fgetcsv($fp, 0, $separador, '"', '')) !== false) {
    $n++;
    if ($celulas === [null]) {
      continue;
    }
    $linhas[] = [$n, array_map(fn($c) => trim((string) $c), $celulas)];
    if (count($linhas) > PLANILHA_MAX_LINHAS) {
      throw new RuntimeException('A planilha tem mais de ' . PLANILHA_MAX_LINHAS . ' linhas.');
    }
  }
  fclose($fp);
  return $linhas;
}

/** Primeira aba de um arquivo .xlsx. */
function xlsx_ler(string $caminho, bool $forcarLeitorProprio = false): array {
  $arquivos = zip_ler_entradas($caminho, fn($nome) => (bool) preg_match('#^xl/(workbook\.xml|_rels/workbook\.xml\.rels|sharedStrings\.xml|worksheets/[^/]+\.xml)$#', $nome),
    $forcarLeitorProprio);
  if (!isset($arquivos['xl/workbook.xml'])) {
    throw new RuntimeException('O arquivo não parece ser uma planilha .xlsx válida.');
  }
  $opcoes = LIBXML_NONET | LIBXML_NOCDATA;
  $nsRel = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

  // Descobre o arquivo da primeira aba pela relação declarada no workbook.
  $workbook = simplexml_load_string($arquivos['xl/workbook.xml'], 'SimpleXMLElement', $opcoes);
  $alvo = 'xl/worksheets/sheet1.xml';
  $primeira = $workbook && isset($workbook->sheets->sheet[0]) ? $workbook->sheets->sheet[0] : null;
  if ($primeira !== null && isset($arquivos['xl/_rels/workbook.xml.rels'])) {
    $rid = (string) $primeira->attributes($nsRel)['id'];
    $rels = simplexml_load_string($arquivos['xl/_rels/workbook.xml.rels'], 'SimpleXMLElement', $opcoes);
    foreach ($rels->Relationship ?? [] as $rel) {
      if ((string) $rel['Id'] === $rid) {
        $destino = (string) $rel['Target'];
        $alvo = str_starts_with($destino, '/') ? ltrim($destino, '/') : 'xl/' . $destino;
      }
    }
  }
  if (!isset($arquivos[$alvo])) {
    throw new RuntimeException('Não encontrei a primeira aba da planilha.');
  }

  $textos = [];
  if (isset($arquivos['xl/sharedStrings.xml'])) {
    $sst = simplexml_load_string($arquivos['xl/sharedStrings.xml'], 'SimpleXMLElement', $opcoes);
    foreach ($sst->si ?? [] as $si) {
      // Texto simples (<t>) ou com formatação (vários <r><t>).
      $partes = [];
      foreach ($si->xpath('.//*[local-name()="t"]') as $t) {
        $partes[] = (string) $t;
      }
      $textos[] = implode('', $partes);
    }
  }

  $planilha = simplexml_load_string($arquivos[$alvo], 'SimpleXMLElement', $opcoes);
  $linhas = [];
  foreach ($planilha->sheetData->row ?? [] as $row) {
    $numero = (int) $row['r'];
    $celulas = [];
    foreach ($row->c as $c) {
      $indice = xlsx_indice_coluna((string) $c['r']);
      $tipo = (string) $c['t'];
      if ($tipo === 's') {
        $valor = $textos[(int) $c->v] ?? '';
      } elseif ($tipo === 'inlineStr') {
        $valor = implode('', array_map('strval', $c->xpath('.//*[local-name()="t"]')));
      } else {
        $valor = (string) $c->v;
        // Números inteiros gravados como "12.0" ou "1.1999990000E10" viram texto limpo (poltrona, telefone).
        if ($valor !== '' && is_numeric($valor) && $tipo !== 'str' && floor((float) $valor) == (float) $valor && abs((float) $valor) < 1e15) {
          $valor = number_format((float) $valor, 0, '', '');
        }
      }
      $celulas[$indice] = trim($valor);
    }
    if ($celulas) {
      $max = max(array_keys($celulas));
      $linha = [];
      for ($i = 0; $i <= $max; $i++) {
        $linha[] = $celulas[$i] ?? '';
      }
      $linhas[] = [$numero ?: count($linhas) + 1, $linha];
    }
    if (count($linhas) > PLANILHA_MAX_LINHAS) {
      throw new RuntimeException('A planilha tem mais de ' . PLANILHA_MAX_LINHAS . ' linhas.');
    }
  }
  return $linhas;
}

/** "AB12" -> 27 (índice da coluna, começando em 0). */
function xlsx_indice_coluna(string $referencia): int {
  $letras = preg_replace('/[^A-Z]/', '', strtoupper($referencia));
  $n = 0;
  foreach (str_split($letras) as $l) {
    $n = $n * 26 + (ord($l) - 64);
  }
  return max(0, $n - 1);
}

/** Lê do ZIP as entradas aceitas pelo filtro. */
function zip_ler_entradas(string $caminho, callable $filtro, bool $forcarLeitorProprio = false): array {
  $saida = [];
  $total = 0;
  if (class_exists('ZipArchive') && !$forcarLeitorProprio) {
    $zip = new ZipArchive();
    if ($zip->open($caminho, ZipArchive::RDONLY) !== true) {
      throw new RuntimeException('O arquivo .xlsx está corrompido ou não é uma planilha.');
    }
    for ($i = 0; $i < $zip->numFiles; $i++) {
      $info = $zip->statIndex($i);
      if (!$filtro($info['name'])) {
        continue;
      }
      $total += $info['size'];
      if ($total > PLANILHA_MAX_DESCOMPACTADO) {
        throw new RuntimeException('A planilha é grande demais.');
      }
      $saida[$info['name']] = (string) $zip->getFromIndex($i);
    }
    $zip->close();
    return $saida;
  }

  // Leitor próprio: percorre o diretório central do ZIP (métodos "stored" e "deflate", os usados pelo Excel).
  $dados = (string) file_get_contents($caminho);
  $fim = strrpos($dados, "PK\x05\x06");
  if ($fim === false) {
    throw new RuntimeException('O arquivo .xlsx está corrompido ou não é uma planilha.');
  }
  $eocd = unpack('vdisco/vdisco_cd/ventradas_disco/ventradas/Vtamanho_cd/Voffset_cd', substr($dados, $fim + 4, 16));
  $p = $eocd['offset_cd'];
  for ($i = 0; $i < $eocd['entradas']; $i++) {
    if (substr($dados, $p, 4) !== "PK\x01\x02") {
      throw new RuntimeException('O arquivo .xlsx está corrompido.');
    }
    $c = unpack('vversao/vprecisa/vflags/vmetodo/vhora/vdata/Vcrc/Vcomprimido/Vtamanho/vnome/vextra/vcomentario/vdisco/vinterno/Vexterno/Voffset',
      substr($dados, $p + 4, 42));
    $nome = substr($dados, $p + 46, $c['nome']);
    $p += 46 + $c['nome'] + $c['extra'] + $c['comentario'];
    if (!$filtro($nome)) {
      continue;
    }
    $total += $c['tamanho'];
    if ($total > PLANILHA_MAX_DESCOMPACTADO) {
      throw new RuntimeException('A planilha é grande demais.');
    }
    $local = unpack('vnome/vextra', substr($dados, $c['offset'] + 26, 4));
    $bruto = substr($dados, $c['offset'] + 30 + $local['nome'] + $local['extra'], $c['comprimido']);
    $conteudo = match ($c['metodo']) {
      0 => $bruto,
      8 => @gzinflate($bruto),
      default => false,
    };
    if ($conteudo === false) {
      throw new RuntimeException('Não foi possível ler o arquivo .xlsx (compressão não suportada).');
    }
    $saida[$nome] = $conteudo;
  }
  return $saida;
}
