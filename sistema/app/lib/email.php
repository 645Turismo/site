<?php
// Envio de e-mail. Em dev grava em storage/logs/mail.log; em produção usa o SMTP do config.local.php.
// Cliente SMTP próprio e enxuto (SSL na 465 ou STARTTLS na 587, AUTH LOGIN), para não depender de Composer na Locaweb.

// Erro SMTP 451 é temporário (servidor ocupado, limite de envios, greylisting): a mensagem é guardada e reenviada
// depois de EMAIL_REENVIO_MINUTOS. Se o 451 persistir, tenta de novo a cada intervalo até EMAIL_REENVIO_TENTATIVAS.
const EMAIL_REENVIO_MINUTOS = 15;
const EMAIL_REENVIO_TENTATIVAS = 6; // 1ª tentativa + 5 reenvios (cerca de 75 minutos)

/** Versão em texto puro: links viram "texto: endereço" para continuarem clicáveis em qualquer leitor. */
function email_texto_puro(string $html): string {
  $texto = preg_replace('#<a\s[^>]*href="([^"]+)"[^>]*>(.*?)</a>#is', '$2: $1', $html);
  return trim(html_entity_decode(strip_tags(preg_replace('#<br\s*/?>|</p>|</li>#i', "\n", $texto)), ENT_QUOTES, 'UTF-8'));
}

/** A conversa SMTP terminou em erro 451? (a última linha guarda a resposta que derrubou o envio) */
function email_erro_451(array $conversa): bool {
  return $conversa && preg_match('/^SMTP inesperado: 451\b/', (string) $conversa[array_key_last($conversa)]) === 1;
}

function enviar_email(string $para, string $assunto, string $html): bool {
  if (!email_valido($para)) {
    return false;
  }
  $assunto = str_replace(["\r", "\n"], ' ', $assunto);
  $texto = email_texto_puro($html);

  if (em_dev() || !config('smtp.host')) {
    $registro = sprintf("==== %s\nPara: %s\nAssunto: %s\n\n%s\n\n", agora(), $para, $assunto, $texto);
    file_put_contents(RAIZ . '/storage/logs/mail.log', $registro, FILE_APPEND | LOCK_EX);
    email_registrar($para, $assunto, 'teste', null);
    return true;
  }
  $ok = smtp_enviar($para, $assunto, email_layout($assunto, $html), $texto, $conversa);
  if (!$ok && email_erro_451($conversa)) {
    $erro = (string) $conversa[array_key_last($conversa)];
    if (email_agendar_reenvio($para, $assunto, $html, $erro)) {
      email_registrar($para, $assunto, 'agendado', 'Erro 451 (temporário): reenvio em ' . EMAIL_REENVIO_MINUTOS . ' minutos. ' . $erro);
      return true;
    }
  }
  email_registrar($para, $assunto, $ok ? 'enviado' : 'falhou', $ok ? null : (string) end($conversa));
  return $ok;
}

function email_proxima_tentativa(): string {
  return date('Y-m-d H:i:s', time() + EMAIL_REENVIO_MINUTOS * 60);
}

/** Guarda a mensagem para reenvio. Retorna false se não conseguiu guardar (aí vale o comportamento normal de falha). */
function email_agendar_reenvio(string $para, string $assunto, string $html, string $erro): bool {
  try {
    inserir('emails_pendentes', ['para' => mb_substr($para, 0, 190), 'assunto' => mb_substr($assunto, 0, 255), 'html' => $html,
      'tentativas' => 1, 'proxima_tentativa_em' => email_proxima_tentativa(), 'ultimo_erro' => mb_substr($erro, 0, 500), 'criado_em' => agora()]);
    return true;
  } catch (Throwable $e) {
    error_log('Fila de reenvio de e-mail: ' . $e->getMessage());
    return false;
  }
}

/** Reenvia as mensagens pendentes cujo horário chegou. Retorna quantas foram processadas. */
function emails_reenviar_pendentes(): int {
  if (em_dev() || !config('smtp.host')) {
    return 0;
  }
  $feitos = 0;
  foreach (todos('SELECT * FROM emails_pendentes WHERE proxima_tentativa_em <= ? ORDER BY id LIMIT 20', [agora()]) as $p) {
    // Reserva a mensagem por alguns minutos para dois acessos simultâneos não enviarem em duplicidade.
    $reserva = q('UPDATE emails_pendentes SET proxima_tentativa_em = ? WHERE id = ? AND proxima_tentativa_em = ?',
      [date('Y-m-d H:i:s', time() + 600), $p['id'], $p['proxima_tentativa_em']]);
    if ($reserva->rowCount() !== 1) {
      continue;
    }
    $ok = smtp_enviar($p['para'], $p['assunto'], email_layout($p['assunto'], $p['html']), email_texto_puro($p['html']), $conversa);
    $erro = (string) ($conversa ? $conversa[array_key_last($conversa)] : '');
    if ($ok) {
      q('DELETE FROM emails_pendentes WHERE id = ?', [$p['id']]);
      email_registrar($p['para'], $p['assunto'], 'enviado', 'Reenviado depois de erro 451 (tentativa ' . ((int) $p['tentativas'] + 1) . ').');
    } elseif (email_erro_451($conversa) && (int) $p['tentativas'] + 1 < EMAIL_REENVIO_TENTATIVAS) {
      q('UPDATE emails_pendentes SET tentativas = tentativas + 1, proxima_tentativa_em = ?, ultimo_erro = ? WHERE id = ?',
        [email_proxima_tentativa(), mb_substr($erro, 0, 500), $p['id']]);
      email_registrar($p['para'], $p['assunto'], 'agendado', 'Erro 451 de novo: nova tentativa em ' . EMAIL_REENVIO_MINUTOS . ' minutos. ' . $erro);
    } else {
      q('DELETE FROM emails_pendentes WHERE id = ?', [$p['id']]);
      email_registrar($p['para'], $p['assunto'], 'falhou', 'Desistiu depois de ' . ((int) $p['tentativas'] + 1) . ' tentativas. ' . $erro);
    }
    $feitos++;
  }
  return $feitos;
}

/** Chamada no fim das requisições: confere a fila no máximo 1x por minuto e só trabalha se houver algo vencido. */
function email_reenvio_agendado(): void {
  $marca = RAIZ . '/storage/logs/.reenvio-email';
  if (is_file($marca) && time() - (int) filemtime($marca) < 60) {
    return;
  }
  @touch($marca);
  try {
    if (!valor('SELECT 1 FROM emails_pendentes WHERE proxima_tentativa_em <= ?', [agora()])) {
      return;
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
      session_write_close();
    }
    if (function_exists('fastcgi_finish_request')) {
      fastcgi_finish_request();
    }
    emails_reenviar_pendentes();
  } catch (Throwable $e) {
    error_log('Reenvio de e-mails: ' . $e->getMessage());
  }
}

/** Guarda o envio no histórico (Diagnóstico). Uma falha aqui nunca impede o resto da operação. */
function email_registrar(string $para, string $assunto, string $status, ?string $erro): void {
  try {
    inserir('emails_enviados', ['para' => mb_substr($para, 0, 190), 'assunto' => mb_substr($assunto, 0, 255), 'status' => $status,
      'erro' => $erro !== null ? mb_substr($erro, 0, 500) : null, 'criado_em' => agora()]);
  } catch (Throwable $e) {
    error_log('Histórico de e-mail: ' . $e->getMessage());
  }
}

function email_layout(string $titulo, string $html): string {
  return '<!DOCTYPE html><html><body style="margin:0;background:#F2F5F3;font-family:Arial,Helvetica,sans-serif;color:#0B1A15">'
    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:24px 12px">'
    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#fff">'
    . '<tr><td style="background:#000;padding:20px 28px;color:#53D9B2;font-size:12px;font-weight:bold;letter-spacing:3px;text-transform:uppercase">645 Turismo · Área do Prestador</td></tr>'
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
      'From: =?UTF-8?B?' . base64_encode(configuracao('email_remetente_nome', $c['remetente_nome']) ?: $c['remetente_nome']) . '?= <' . $c['remetente'] . '>',
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
