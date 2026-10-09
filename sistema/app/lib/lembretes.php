<?php
// Lembrete por e-mail 12 horas antes do embarque, para cada guia com presença confirmada.
// A hospedagem não garante cron: a verificação roda no fim das requisições (no máximo a cada 10 min)
// e também pode ser chamada por um agendador com `php bin/lembretes.php`.

const LEMBRETE_HORAS_ANTES = 12;

/** Roda no fim da requisição, depois que a página já foi entregue ao navegador. */
function tarefas_agendadas(): void {
  $marca = RAIZ . '/storage/logs/.lembretes';
  if (is_file($marca) && time() - (int) filemtime($marca) < 600) {
    return;
  }
  @touch($marca);
  if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
  }
  if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
  }
  try {
    lembretes_embarque_processar();
  } catch (Throwable $e) {
    error_log('Lembretes de embarque: ' . $e->getMessage());
  }
}

/** Horário de embarque do guia: primeiro dia dele na viagem, no horário mais cedo informado. */
function lembrete_horario_embarque(array $l, array $origens): string {
  $hora = null;
  if ($l['data'] === $l['data_inicio']) {
    $horas = array_filter(array_merge(array_column($origens, 'horario'), [$l['horario_saida'], $l['dia_apresentacao'], $l['horario_apresentacao']]),
      fn($h) => is_string($h) && preg_match('/^\d{2}:\d{2}$/', $h));
    $hora = $horas ? min($horas) : null;
  } else {
    foreach ([$l['dia_apresentacao'], $l['hora_inicio']] as $h) {
      if (is_string($h) && preg_match('/^\d{2}:\d{2}$/', $h)) {
        $hora = $h;
        break;
      }
    }
  }
  return $l['data'] . ' ' . ($hora ?? '08:00') . ':00';
}

/** Envia os lembretes que estão dentro da janela de 12 h. Devolve quantos saíram. */
function lembretes_embarque_processar(): int {
  $agora = agora();
  $ate = date('Y-m-d', strtotime('+2 days'));
  $linhas = todos("SELECT s.guia_id, d.viagem_id, MIN(d.data) AS data
    FROM escalas s JOIN diarias d ON d.id = s.diaria_id JOIN viagens v ON v.id = d.viagem_id
    WHERE s.status IN ('confirmado', 'em_campo') AND v.status = 'publicada' AND d.data BETWEEN ? AND ?
      AND NOT EXISTS (SELECT 1 FROM lembretes_embarque le WHERE le.viagem_id = d.viagem_id AND le.guia_id = s.guia_id)
    GROUP BY s.guia_id, d.viagem_id", [hoje(), $ate]);
  $enviados = 0;
  foreach ($linhas as $base) {
    // Primeiro dia do guia (já contando dias anteriores a hoje, para não lembrar no meio da viagem).
    $primeiro = um("SELECT MIN(d.data) AS data FROM escalas s JOIN diarias d ON d.id = s.diaria_id
      WHERE s.guia_id = ? AND d.viagem_id = ? AND s.status IN ('confirmado', 'em_campo')", [$base['guia_id'], $base['viagem_id']]);
    if (($primeiro['data'] ?? '') < hoje()) {
      continue;
    }
    $l = um('SELECT v.id, v.codigo, v.nome, v.data_inicio, v.horario_saida, v.horario_apresentacao, v.ponto_encontro,
        v.coordenador_nome, v.coordenador_telefone, d.data, d.horario_apresentacao AS dia_apresentacao, d.hora_inicio
      FROM viagens v JOIN diarias d ON d.viagem_id = v.id WHERE v.id = ? AND d.data = ? ORDER BY d.id LIMIT 1',
      [$base['viagem_id'], $base['data']]);
    $g = um('SELECT id, nome, nome_social, email FROM guias WHERE id = ?', [$base['guia_id']]);
    if (!$l || !$g) {
      continue;
    }
    $origens = todos('SELECT local, horario FROM viagem_origens WHERE viagem_id = ? ORDER BY ordem', [$l['id']]);
    $embarque = lembrete_horario_embarque($l, $origens);
    $janela = date('Y-m-d H:i:s', strtotime($embarque) - LEMBRETE_HORAS_ANTES * 3600);
    if ($agora < $janela || $agora >= $embarque) {
      continue;
    }
    // Marca antes de enviar: duas requisições juntas não mandam o e-mail em dobro.
    try {
      inserir('lembretes_embarque', ['viagem_id' => $l['id'], 'guia_id' => $g['id'], 'embarque_em' => $embarque, 'criado_em' => $agora]);
    } catch (Throwable $e) {
      continue;
    }
    lembrete_embarque_enviar($l, $g, $embarque, $origens);
    $enviados++;
  }
  return $enviados;
}

function lembrete_embarque_enviar(array $v, array $g, string $embarque, array $origens): void {
  $nome = explode(' ', trim((string) ($g['nome_social'] ?: $g['nome'])))[0];
  $quando = formatar_data(substr($embarque, 0, 10)) . ' às ' . substr($embarque, 11, 5);
  $locais = array_filter(array_map(fn($o) => trim((string) $o['local']) . ($o['horario'] ? ' (' . $o['horario'] . ')' : ''), $origens));
  $onde = $locais ? implode(' · ', $locais) : (string) $v['ponto_encontro'];
  $link = url_absoluta('/guia/viagens/' . (int) $v['id']);

  if ($v['coordenador_telefone']) {
    $contato = '<p>Ficou com alguma dúvida ou está faltando alguma informação? Fale com a coordenação'
      . ($v['coordenador_nome'] ? ', <strong>' . e($v['coordenador_nome']) . '</strong>,' : '')
      . ' pelo WhatsApp <a href="' . e(link_whatsapp($v['coordenador_telefone'])) . '">' . e(formatar_celular($v['coordenador_telefone'])) . '</a>.</p>';
  } else {
    $contato = '<p>Ficou com alguma dúvida ou está faltando alguma informação? Fale com a coordenação'
      . ($v['coordenador_nome'] ? ', <strong>' . e($v['coordenador_nome']) . '</strong>,' : '') . ' ou abra um chamado no Suporte do portal.</p>';
  }

  $assunto = 'Lembrete de embarque: ' . $v['codigo'] . ' · ' . $v['nome'];
  enviar_email((string) $g['email'], $assunto,
    '<p>Olá, ' . e($nome) . '.</p>'
    . '<p>Sua viagem <strong>' . e($v['codigo'] . ' · ' . $v['nome']) . '</strong> começa em <strong>' . e($quando) . '</strong>'
    . ($onde !== '' ? '. Embarque: ' . e($onde) : '') . '.</p>'
    . '<p>Antes de sair, confira no portal as informações da viagem: horários, pontos de embarque, roteiro, lista de passageiros e materiais.</p>'
    . '<p><a href="' . e($link) . '" style="display:inline-block;padding:12px 22px;background:#53D9B2;color:#000;text-decoration:none;font-weight:bold;border-radius:999px">Ver a viagem no portal</a></p>'
    . $contato
    . '<p>Boa viagem!</p>');
  inserir('notificacoes', ['destinatario_tipo' => 'guia', 'destinatario_id' => $g['id'], 'titulo' => $assunto,
    'link' => '/guia/viagens/' . (int) $v['id'], 'criado_em' => agora()]);
}
