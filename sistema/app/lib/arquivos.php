<?php
// Arquivos enviados (documentos, fotos, notas fiscais, comprovantes, anexos).
// Ficam em storage/uploads (bloqueado para a web) com nome aleatório e só saem por /arquivos/..., com login.

const UPLOAD_MAX_BYTES = 20 * 1024 * 1024;
const UPLOAD_TIPOS = [
  'application/pdf' => 'pdf',
  'image/jpeg' => 'jpg',
  'image/png' => 'png',
  'image/webp' => 'webp',
];

/** O arquivo foi escolhido no formulário? */
function upload_enviado(string $campo): bool {
  return isset($_FILES[$campo]) && ($_FILES[$campo]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

/**
 * Valida e guarda o arquivo enviado em $campo. Retorna [caminho relativo, nome original, mime, tamanho].
 * $apenasImagem: aceita só fotos (ex.: foto de rosto). Lança RuntimeException com mensagem para o usuário.
 */
function salvar_upload(string $campo, string $pasta, bool $apenasImagem = false): array {
  $f = $_FILES[$campo] ?? null;
  $erro = $f['error'] ?? UPLOAD_ERR_NO_FILE;
  if ($erro === UPLOAD_ERR_NO_FILE) {
    throw new RuntimeException('Escolha o arquivo.');
  }
  if ($erro === UPLOAD_ERR_INI_SIZE || $erro === UPLOAD_ERR_FORM_SIZE || ($f['size'] ?? 0) > UPLOAD_MAX_BYTES) {
    throw new RuntimeException('O arquivo passa de 20 MB.');
  }
  if ($erro !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
    throw new RuntimeException('Não foi possível receber o arquivo. Tente de novo.');
  }
  // O tipo é conferido pelo conteúdo do arquivo, não pela extensão.
  $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) ?: '';
  if (!isset(UPLOAD_TIPOS[$mime]) || ($apenasImagem && $mime === 'application/pdf')) {
    throw new RuntimeException($apenasImagem ? 'Envie uma foto em JPG, PNG ou WebP.' : 'Envie um arquivo PDF, JPG, PNG ou WebP.');
  }
  $relativo = $pasta . '/' . date('Y') . '/' . bin2hex(random_bytes(16)) . '.' . UPLOAD_TIPOS[$mime];
  $destino = RAIZ . '/storage/uploads/' . $relativo;
  if (!is_dir(dirname($destino)) && !mkdir(dirname($destino), 0750, true)) {
    throw new RuntimeException('Não foi possível guardar o arquivo no servidor.');
  }
  if (!move_uploaded_file($f['tmp_name'], $destino)) {
    throw new RuntimeException('Não foi possível guardar o arquivo no servidor.');
  }
  return [$relativo, mb_substr(basename((string) $f['name']), 0, 200), $mime, (int) $f['size']];
}

/** Entrega um arquivo guardado (exibe no navegador; PDF e imagens). */
function servir_arquivo(?string $relativo, string $nome = 'arquivo'): never {
  $caminho = $relativo ? realpath(RAIZ . '/storage/uploads/' . $relativo) : false;
  $base = realpath(RAIZ . '/storage/uploads');
  if (!$caminho || !$base || !str_starts_with($caminho, $base) || !is_file($caminho)) {
    abortar(404, 'Arquivo não encontrado.');
  }
  $mime = (new finfo(FILEINFO_MIME_TYPE))->file($caminho) ?: 'application/octet-stream';
  if (!isset(UPLOAD_TIPOS[$mime])) {
    abortar(404, 'Arquivo não encontrado.');
  }
  // Nome com a extensão certa (senão o arquivo baixado não abre) e ?baixar=1 força o download.
  $nome = preg_replace('/[^\w.\- ]+/u', '_', $nome) ?: 'arquivo';
  $extensao = UPLOAD_TIPOS[$mime];
  if (strtolower(pathinfo($nome, PATHINFO_EXTENSION)) !== $extensao && !($extensao === 'jpg' && preg_match('/\.jpe?g$/i', $nome))) {
    $nome .= '.' . $extensao;
  }
  $modo = isset($_GET['baixar']) ? 'attachment' : 'inline';
  header('Content-Type: ' . $mime);
  header('Content-Length: ' . filesize($caminho));
  header('Content-Disposition: ' . $modo . '; filename="' . $nome . '"; filename*=UTF-8\'\'' . rawurlencode($nome));
  header('Cache-Control: private, no-store');
  readfile($caminho);
  exit;
}

function apagar_arquivo(?string $relativo): void {
  if ($relativo && is_file(RAIZ . '/storage/uploads/' . $relativo)) {
    @unlink(RAIZ . '/storage/uploads/' . $relativo);
  }
}

/** Data somando dias úteis (seg–sex), para prazos de nota fiscal. */
function somar_dias_uteis(string $data, int $dias): string {
  $t = strtotime($data);
  while ($dias > 0) {
    $t = strtotime('+1 day', $t);
    if ((int) date('N', $t) < 6) {
      $dias--;
    }
  }
  return date('Y-m-d', $t);
}

/** E-mail para avisos internos (cadastro novo, lista alterada, chamado urgente). */
function email_equipe(): string {
  return (string) configuracao('email_alertas_lista', 'contato@645turismo.com.br');
}

// Tabela e coluna da chave de cada tipo de arquivo servido em /arquivos/{tipo}/{chave}.
const ARQUIVOS_CHAVES = [
  'foto' => ['guias', 'foto_chave'],
  'documento' => ['guia_documentos', 'chave'],
  'envio' => ['envios', 'chave'],
  'comprovante' => ['pagamentos', 'chave'],
  'anexo' => ['chamado_mensagens', 'chave'],
];

/**
 * Endereço de um arquivo pela chave aleatória (gerada na primeira vez). Nunca expõe o id sequencial.
 * $linha precisa ter 'id' (e a coluna da chave, se já carregada).
 */
function arquivo_url(string $tipo, array $linha): string {
  [$tabela, $coluna] = ARQUIVOS_CHAVES[$tipo];
  $chave = $linha[$coluna] ?? null;
  if (!$chave) {
    $chave = valor("SELECT $coluna FROM $tabela WHERE id = ?", [(int) $linha['id']]);
  }
  if (!$chave) {
    $chave = bin2hex(random_bytes(16));
    q("UPDATE $tabela SET $coluna = ? WHERE id = ? AND $coluna IS NULL", [$chave, (int) $linha['id']]);
    $chave = valor("SELECT $coluna FROM $tabela WHERE id = ?", [(int) $linha['id']]);
  }
  return '/arquivos/' . $tipo . '/' . $chave;
}
