<?php
// Envio de e-mail. Em dev grava em storage/logs/mail.log; em produção usa o SMTP do config.local.php.
// Cliente SMTP próprio e enxuto (SSL na 465 ou STARTTLS na 587, AUTH LOGIN), para não depender de Composer na Locaweb.

function enviar_email(string $para, string $assunto, string $html): bool {
  if (!email_valido($para)) {
    return false;
  }
  $assunto = str_replace(["\r", "\n"], ' ', $assunto);
  // Versão em texto puro: links viram "texto: endereço" para continuarem clicáveis em qualquer leitor.
  $texto = preg_replace('#<a\s[^>]*href="([^"]+)"[^>]*>(.*?)</a>#is', '$2: $1', $html);
  $texto = trim(html_entity_decode(strip_tags(preg_replace('#<br\s*/?>|</p>|</li>#i', "\n", $texto)), ENT_QUOTES, 'UTF-8'));

  if (em_dev() || !config('smtp.host')) {
    $registro = sprintf("==== %s\nPara: %s\nAssunto: %s\n\n%s\n\n", agora(), $para, $assunto, $texto);
    file_put_contents(RAIZ . '/storage/logs/mail.log', $registro, FILE_APPEND | LOCK_EX);
    return true;
  }
  return smtp_enviar($para, $assunto, email_layout($assunto, $html), $texto);
}

function email_layout(string $titulo, string $html): string {
  return '<!DOCTYPE html><html><body style="margin:0;background:#F2F5F3;font-family:Arial,Helvetica,sans-serif;color:#0B1A15">'
    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:24px 12px">'
    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#fff">'
    . '<tr><td style="background:#000;padding:20px 28px;color:#53D9B2;font-size:12px;font-weight:bold;letter-spacing:3px;text-transform:uppercase">645 Turismo · Área do Guia</td></tr>'
    . '<tr><td style="padding:28px;font-size:15px;line-height:1.6"><h1 style="margin:0 0 16px;font-size:20px;font-weight:normal">'
    . e($titulo) . '</h1>' . $html . '</td></tr>'
    . '<tr><td style="padding:16px 28px;border-top:1px solid #E3E8E5;font-size:12px;color:#6B7A74">'
    . 'Mensagem automática. Não responda este e-mail; para falar com a equipe, use o Suporte no sistema.</td></tr>'
    . '</table></td></tr></table></body></html>';
}

/**
 * $conversa (opcional) recebe o diálogo com o servidor, para o teste do Diagnóstico.
 * Usuário e senha nunca entram na conversa.
 */
function smtp_enviar(string $para, string $assunto, string $html, string $texto, ?array &$conversa = null): bool {
  $c = config('smtp');
  $conversa = [];
  $prefixo = ($c['seguranca'] ?? 'ssl') === 'ssl' ? 'ssl://' : 'tcp://';
  $fp = @stream_socket_client($prefixo . $c['host'] . ':' . (int) $c['porta'], $errno, $errstr, 15);
  if (!$fp) {
    $conversa[] = "Não conectou em {$c['host']}:{$c['porta']}: $errstr ($errno)";
    error_log("SMTP: falha ao conectar em {$c['host']}:{$c['porta']}: $errstr");
    return false;
  }
  stream_set_timeout($fp, 20);

  $ler = function () use ($fp): string {
    $resposta = '';
    while (($linha = fgets($fp, 515)) !== false) {
      $resposta .= $linha;
      if (strlen($linha) < 4 || $linha[3] === ' ') {
        break;
      }
    }
    return $resposta;
  };
  $comando = function (?string $cmd, array $esperado, ?string $mostrar = null) use ($fp, $ler, &$conversa): string {
    if ($cmd !== null) {
      fwrite($fp, $cmd . "\r\n");
      $conversa[] = '> ' . ($mostrar ?? (strlen($cmd) > 200 ? '(conteúdo da mensagem)' : $cmd));
    }
    $resposta = $ler();
    $conversa[] = '< ' . trim($resposta);
    if (!in_array((int) substr($resposta, 0, 3), $esperado, true)) {
      throw new RuntimeException('SMTP inesperado: ' . trim($resposta));
    }
    return $resposta;
  };

  try {
    $hostLocal = parse_url((string) config('url_base'), PHP_URL_HOST) ?: 'localhost';
    $comando(null, [220]);
    $comando('EHLO ' . $hostLocal, [250]);
    if (($c['seguranca'] ?? '') === 'tls') {
      $comando('STARTTLS', [220]);
      if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        throw new RuntimeException('SMTP: falha ao iniciar TLS');
      }
      $comando('EHLO ' . $hostLocal, [250]);
    }
    $comando('AUTH LOGIN', [334]);
    $comando(base64_encode($c['usuario']), [334], '(usuário)');
    $comando(base64_encode($c['senha']), [235], '(senha)');
    $comando('MAIL FROM:<' . $c['remetente'] . '>', [250]);
    $comando('RCPT TO:<' . $para . '>', [250, 251]);
    $comando('DATA', [354]);

    $fronteira = 'b' . bin2hex(random_bytes(12));
    $cabecalhos = [
      'Date: ' . date('r'),
      'From: =?UTF-8?B?' . base64_encode($c['remetente_nome']) . '?= <' . $c['remetente'] . '>',
      'To: <' . $para . '>',
      'Subject: =?UTF-8?B?' . base64_encode($assunto) . '?=',
      'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . substr(strrchr($c['remetente'], '@'), 1) . '>',
      'MIME-Version: 1.0',
      'Content-Type: multipart/alternative; boundary="' . $fronteira . '"',
    ];
    $corpo = implode("\r\n", $cabecalhos) . "\r\n\r\n"
      . "--$fronteira\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
      . chunk_split(base64_encode($texto)) . "\r\n"
      . "--$fronteira\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
      . chunk_split(base64_encode($html)) . "\r\n"
      . "--$fronteira--";
    $comando($corpo . "\r\n.", [250]);
    $comando('QUIT', [221]);
    return true;
  } catch (Throwable $e) {
    $conversa[] = $e->getMessage();
    error_log('SMTP para ' . $para . ': ' . $e->getMessage());
    return false;
  } finally {
    fclose($fp);
  }
}
