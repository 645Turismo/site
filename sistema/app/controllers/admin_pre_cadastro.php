<?php
// Pré-cadastro de guias em massa pelo ADM, a partir da planilha padrão (modelo para baixar).
// Cada guia recebe por e-mail uma senha temporária (válida por PRE_CADASTRO_VALIDADE_DIAS); no primeiro login
// troca a senha e completa o cadastro (dados, atuação, documentos e recebimento). Depois vai para a triagem.

const PRE_CADASTRO_VALIDADE_DIAS = 7;

// Colunas da planilha padrão, na ordem do formulário "Faça parte do time 645 Turismo": [rótulo, obrigatória].
// A planilha de respostas exportada do Google Forms também é aceita (cabeçalhos com o texto das perguntas).
const PRE_CADASTRO_COLUNAS = [
  'nome' => ['Nome completo', true],
  'nascimento' => ['Data de nascimento', false],
  'cpf' => ['CPF', true],
  'nome_social' => ['Os passageiros costumam me chamar de', false],
  'funcoes' => ['Gostaria de fazer meu cadastro para', false],
  'cadastur' => ['Número Cadastur', false],
  'idiomas' => ['Idiomas', false],
  'experiencia' => ['Experiência', false],
  'camiseta' => ['Camiseta', false],
  'restricao' => ['Restrições alimentares', false],
  'doenca' => ['Doença preexistente', false],
  'pix' => ['Chave PIX', false],
  'celular' => ['Telefone de contato', false],
  'email' => ['E-mail', true],
];
// Nomes aceitos no cabeçalho, comparados sem acento, pontuação e maiúsculas.
const PRE_CADASTRO_ALIASES = [
  'nome' => ['nome completo', 'nome', 'guia', 'nome do guia'],
  'nascimento' => ['data de nascimento', 'nascimento', 'data nascimento', 'dt nascimento'],
  'cpf' => ['cpf'],
  'nome_social' => ['os passageiros costumam me chamar de', 'como prefere ser chamado a', 'nome social', 'apelido'],
  'funcoes' => ['gostaria de fazer meu cadastro para', 'funcoes', 'funcao', 'atuacao'],
  'cadastur' => ['numero cadastur', 'cadastur', 'numero do cadastur'],
  'idiomas' => ['alem de portugues qual outro idioma voce fala', 'idiomas', 'idioma'],
  'experiencia' => ['nos conte um pouco mais sobre sua experiencia', 'experiencia', 'apresentacao'],
  'camiseta' => ['qual o tamanho de sua camiseta para uniforme', 'camiseta', 'tamanho da camiseta'],
  'restricao' => ['restricoes alimentares', 'restricao alimentar', 'alimentacao'],
  'doenca' => ['voce possui alguma doenca preexistente', 'doenca preexistente', 'doencas preexistentes', 'saude'],
  'pix' => ['chave pix', 'pix'],
  'celular' => ['telefone de contato', 'telefone', 'celular', 'whatsapp'],
  'email' => ['e mail', 'email', 'endereco de e mail'],
];
// Opções do formulário que têm nome diferente no sistema.
const PRE_CADASTRO_FUNCOES_FORM = ['monitor pedagogico' => 'monitor de turismo pedagogico'];

/** Restrição alimentar a partir do texto da planilha (opções do formulário ou nomes do sistema). */
function restricao_da_planilha(string $texto): ?string {
  $t = texto_chave($texto);
  foreach (RESTRICOES_ALIMENTARES as $chave => $rotulo) {
    if ($t === texto_chave($rotulo) || $t === texto_chave(str_replace('_', ' ', $chave))) {
      return $chave;
    }
  }
  return match (true) {
    $t === '' => null,
    str_contains($t, 'sem restri'), str_contains($t, 'padrao'), str_contains($t, 'nenhum') => 'padrao',
    str_contains($t, 'vegan') => 'vegana',
    str_contains($t, 'vegetarian') => 'vegetariana',
    str_contains($t, 'alerg') => 'alergia',
    str_contains($t, 'lactose') => 'sem_lactose',
    str_contains($t, 'gluten') => 'sem_gluten',
    default => null,
  };
}

/** Tipo da chave PIX pelo formato (o formulário só pede a chave). */
function pix_tipo_da_chave(string $chave): string {
  $digitos = so_digitos($chave);
  if (str_contains($chave, '@')) {
    return 'email';
  }
  if (preg_match('/^[0-9a-f]{8}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{12}$/i', trim($chave))) {
    return 'aleatoria';
  }
  if (strlen($digitos) === 11 && cpf_valido($digitos) && !preg_match('/^\(?\d{2}\)?\s*9/', trim($chave))) {
    return 'cpf';
  }
  if (strlen($digitos) === 14 && cnpj_valido($digitos)) {
    return 'cnpj';
  }
  return strlen($digitos) >= 10 ? 'celular' : 'aleatoria';
}

function adm_pre_cadastro(): void {
  $a = exigir_admin(['coordenador']);
  exibir('admin/pre-cadastro', [
    'titulo' => 'Pré-cadastro de guias',
    'menu' => 'guias',
    'a' => $a,
    'imp' => $_SESSION['pre_cadastro'] ?? null,
    'funcoes' => todos('SELECT nome FROM funcoes WHERE ativo = 1 ORDER BY ordem, nome'),
  ], 'admin');
}

/** Modelo da planilha padrão (CSV que abre direto no Excel, com acentos). */
function adm_pre_cadastro_modelo(): void {
  exigir_admin(['coordenador']);
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="modelo-pre-cadastro-guias-645.csv"');
  $saida = fopen('php://output', 'w');
  fwrite($saida, "\xEF\xBB\xBF");
  fputcsv($saida, array_map(fn($c) => $c[0], PRE_CADASTRO_COLUNAS), ';', '"', '');
  fputcsv($saida, ['Maria da Silva Souza', '10/05/1985', '529.982.247-25', 'Mari', 'Guia de Turismo', '21.123456.10-0001',
    'Espanhol, Inglês', 'Guia há 5 anos em São Paulo e no interior; trabalhei com grupos escolares e de terceira idade.', 'M', 'Vegetariana', '',
    'maria@exemplo.com.br', '(11) 99999-0000', 'maria@exemplo.com.br'], ';', '"', '');
  fclose($saida);
  exit;
}

/** Lê a planilha e valida cada linha. Nada é gravado: o resultado fica na sessão para a prévia. */
function adm_pre_cadastro_ler(): void {
  exigir_admin(['coordenador']);
  $arquivo = $_FILES['arquivo'] ?? null;
  if (!$arquivo || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    flash('erro', 'Envie a planilha (.xlsx ou .csv) no formato do modelo.');
    redirecionar('/admin/guias/pre-cadastro');
  }
  try {
    $linhas = planilha_ler_upload($arquivo);
  } catch (RuntimeException $e) {
    flash('erro', $e->getMessage());
    redirecionar('/admin/guias/pre-cadastro');
  }
  $cab = planilha_achar_cabecalho($linhas, PRE_CADASTRO_ALIASES);
  if (!$cab || !in_array('cpf', $cab[1], true)) {
    flash('erro', 'Não encontrei o cabeçalho da planilha padrão (Nome completo e CPF). Baixe o modelo e use as mesmas colunas.');
    redirecionar('/admin/guias/pre-cadastro');
  }
  if (!in_array('email', $cab[1], true)) {
    flash('erro', 'A planilha não tem a coluna de e-mail. O e-mail é obrigatório: é para ele que vai a senha temporária. No Google Forms, ative "Coletar endereços de e-mail" ou inclua a pergunta E-mail.');
    redirecionar('/admin/guias/pre-cadastro');
  }
  [$inicio, $mapa] = $cab;
  $_SESSION['pre_cadastro'] = [
    'origem' => (string) $arquivo['name'],
    'linhas' => pre_cadastro_validar(array_slice($linhas, $inicio + 1), $mapa),
  ];
  redirecionar('/admin/guias/pre-cadastro#previa');
}

/** Valida as linhas: [['linha', 'dados', 'erros', 'funcoes']]. Repetidos (no arquivo ou já cadastrados) viram erro. */
function pre_cadastro_validar(array $linhas, array $mapa): array {
  $funcoes = [];
  foreach (todos('SELECT id, nome FROM funcoes WHERE ativo = 1') as $f) {
    $funcoes[texto_chave($f['nome'])] = (int) $f['id'];
  }
  $vistos = ['cpf' => [], 'email' => []];
  $resultado = [];
  foreach ($linhas as [$numero, $celulas]) {
    $v = array_fill_keys(array_keys(PRE_CADASTRO_COLUNAS), '');
    foreach ($mapa as $col => $campo) {
      $v[$campo] = trim((string) ($celulas[$col] ?? ''));
    }
    if (!array_filter($v, fn($x) => $x !== '')) {
      continue;
    }
    $d = [
      'nome' => mb_substr(preg_replace('/\s+/', ' ', $v['nome']), 0, 160),
      'nome_social' => mb_substr($v['nome_social'], 0, 120) ?: null,
      'cpf' => so_digitos($v['cpf']),
      'email' => mb_strtolower($v['email']),
      'celular' => normalizar_celular($v['celular']) ?: null,
      'nascimento' => null,
      'cadastur_numero' => mb_substr($v['cadastur'], 0, 40) ?: null,
      'apresentacao' => mb_substr($v['experiencia'], 0, 1500) ?: null,
      'camiseta' => null,
      'restricao_alimentar' => restricao_da_planilha($v['restricao']),
      // "Não", "Nenhuma" etc. não são informação de saúde.
      'doencas_preexistentes' => in_array(texto_chave($v['doenca']), ['', 'nao', 'nenhuma', 'nenhum', 'nao possuo', 'nao tenho', 'n a', 'nao se aplica'], true)
        ? null : mb_substr($v['doenca'], 0, 500),
    ];
    $erros = [];
    $avisos = [];
    if ($v['restricao'] !== '' && !$d['restricao_alimentar']) {
      $avisos[] = 'restrição alimentar "' . $v['restricao'] . '" não reconhecida (fica em branco)';
    }
    if ($v['camiseta'] !== '') {
      $tam = strtoupper(trim($v['camiseta']));
      if (in_array($tam, CAMISETAS, true)) {
        $d['camiseta'] = $tam;
      } else {
        $avisos[] = 'camiseta ' . $tam . ' não existe no sistema (fica em branco)';
      }
    }
    // Idiomas além do português (no Google Forms vêm separados por vírgula). Nível começa como "fluente".
    $idiomas = [];
    foreach (preg_split('/[,;\/]+/', $v['idiomas'], -1, PREG_SPLIT_NO_EMPTY) as $idioma) {
      $idioma = mb_substr(trim($idioma), 0, 40);
      if ($idioma !== '' && !in_array(texto_chave($idioma), ['portugues', 'nenhum', 'nao', 'so portugues'], true)) {
        $idiomas[mb_strtolower($idioma)] = mb_strtoupper(mb_substr($idioma, 0, 1)) . mb_substr($idioma, 1);
      }
    }
    $pix = mb_substr(trim($v['pix']), 0, 140);
    if (strlen($d['cpf']) < 11 && $d['cpf'] !== '') {
      $d['cpf'] = str_pad($d['cpf'], 11, '0', STR_PAD_LEFT); // Excel tira o zero da frente
    }
    if (mb_strlen($d['nome']) < 5 || !str_contains($d['nome'], ' ')) {
      $erros[] = 'nome completo';
    }
    if (!cpf_valido($d['cpf'])) {
      $erros[] = 'CPF inválido';
    } elseif (isset($vistos['cpf'][$d['cpf']])) {
      $erros[] = 'CPF repetido na planilha';
    } elseif (valor("SELECT 1 FROM guias WHERE cpf = ? AND status <> 'rascunho'", [$d['cpf']])) {
      $erros[] = 'CPF já cadastrado';
    }
    if (!email_valido($d['email'])) {
      $erros[] = 'e-mail inválido';
    } elseif (isset($vistos['email'][$d['email']])) {
      $erros[] = 'e-mail repetido na planilha';
    } elseif (valor("SELECT 1 FROM guias WHERE email = ? AND status <> 'rascunho' AND cpf <> ?", [$d['email'], $d['cpf']])) {
      $erros[] = 'e-mail já usado por outro guia';
    }
    if ($v['nascimento'] !== '') {
      $nasc = planilha_data($v['nascimento']);
      if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $nasc, $m)) {
        $nasc = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
      }
      if (data_valida($nasc) && $nasc < hoje()) {
        $d['nascimento'] = $nasc;
      } else {
        $erros[] = 'data de nascimento ilegível';
      }
    }
    $ids = [];
    $naoAchadas = [];
    foreach (preg_split('/[,;\/]+/', $v['funcoes'], -1, PREG_SPLIT_NO_EMPTY) as $nomeFuncao) {
      $chave = texto_chave($nomeFuncao);
      $chave = PRE_CADASTRO_FUNCOES_FORM[$chave] ?? $chave;
      if (isset($funcoes[$chave])) {
        $ids[$funcoes[$chave]] = true;
      } elseif ($chave !== '') {
        $naoAchadas[] = trim($nomeFuncao);
      }
    }
    $vistos['cpf'][$d['cpf']] = $vistos['email'][$d['email']] = true;
    if ($naoAchadas) {
      $avisos[] = 'função não cadastrada no sistema: ' . implode(', ', $naoAchadas) . ' (não entra)';
    }
    $resultado[] = ['linha' => $numero, 'dados' => $d, 'erros' => $erros, 'funcoes' => array_keys($ids),
      'idiomas' => array_values($idiomas), 'pix' => $pix !== '' ? ['pix_tipo' => pix_tipo_da_chave($pix), 'pix_chave' => $pix] : null,
      'avisos' => $avisos];
  }
  return $resultado;
}

/** Senha temporária legível (sem 0/O, 1/l), com maiúscula, minúscula e número. */
function gerar_senha_temporaria(): string {
  $grupos = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghjkmnpqrstuvwxyz', '23456789'];
  $todos = implode('', $grupos);
  $senha = [];
  foreach ($grupos as $g) {
    $senha[] = $g[random_int(0, strlen($g) - 1)];
  }
  while (count($senha) < 10) {
    $senha[] = $todos[random_int(0, strlen($todos) - 1)];
  }
  for ($i = count($senha) - 1; $i > 0; $i--) {
    $j = random_int(0, $i);
    [$senha[$i], $senha[$j]] = [$senha[$j], $senha[$i]];
  }
  return implode('', $senha);
}

/** Gera nova senha temporária e envia o e-mail de acesso. */
function pre_cadastro_enviar_acesso(array $g): bool {
  $senha = gerar_senha_temporaria();
  $expira = date('Y-m-d H:i:s', strtotime('+' . PRE_CADASTRO_VALIDADE_DIAS . ' days'));
  atualizar('guias', ['senha_hash' => hash_senha($senha), 'trocar_senha' => 1, 'senha_temporaria_expira' => $expira,
    'atualizado_em' => agora()], 'id = ?', [$g['id']]);
  $completar = $g['status'] === 'pre_cadastro';
  return enviar_email((string) $g['email'], 'Seu acesso à Área do Guia da 645 Turismo',
    '<p>Olá, ' . e(primeiro_nome($g)) . '.</p>'
    . '<p>A equipe da 645 Turismo ' . ($completar ? 'criou seu pré-cadastro' : 'gerou um novo acesso para você') . ' na Área do Guia, onde você recebe convites, vê a lista de passageiros e acompanha seus pagamentos.</p>'
    . '<p><strong>Login:</strong> seu CPF (' . e(mascarar_cpf($g['cpf'])) . ')<br><strong>Senha temporária:</strong> <span style="font-family:monospace;font-size:18px;letter-spacing:1px">' . e($senha) . '</span></p>'
    . '<p>No primeiro acesso você cria sua própria senha' . ($completar ? ' e completa seu cadastro (dados, atuação e dados para pagamento)' : '') . '. A senha temporária vale por ' . PRE_CADASTRO_VALIDADE_DIAS . ' dias.</p>'
    . '<p><a href="' . e(url_absoluta('/')) . '" style="display:inline-block;padding:12px 22px;background:#53D9B2;color:#000;text-decoration:none;font-weight:bold;border-radius:999px">Entrar na Área do Guia</a></p>'
    . '<p style="color:#666;font-size:13px">Se você não esperava este e-mail, ignore-o.</p>');
}

/** Cria os guias válidos da prévia e envia as senhas temporárias. */
function adm_pre_cadastro_confirmar(): void {
  $a = exigir_admin(['coordenador']);
  $imp = $_SESSION['pre_cadastro'] ?? null;
  if (!$imp) {
    flash('erro', 'A leitura da planilha expirou. Envie o arquivo de novo.');
    redirecionar('/admin/guias/pre-cadastro');
  }
  @set_time_limit(300);
  $criados = 0;
  $semEmail = [];
  foreach ($imp['linhas'] as $l) {
    if ($l['erros']) {
      continue;
    }
    $d = $l['dados'];
    // Rascunho abandonado no cadastro público com o mesmo CPF dá lugar ao pré-cadastro.
    if ($rascunho = valor("SELECT id FROM guias WHERE cpf = ? AND status = 'rascunho'", [$d['cpf']])) {
      cadastro_descartar_rascunho((int) $rascunho);
    }
    if (valor('SELECT 1 FROM guias WHERE cpf = ?', [$d['cpf']])) {
      continue; // cadastrado por outra pessoa depois da prévia
    }
    $id = transacao(function () use ($d, $l, $a) {
      $id = inserir('guias', $d + ['codigo' => gerar_codigo_guia($d['cpf']), 'status' => 'pre_cadastro', 'cadastro_etapa' => 1,
        'pre_cadastrado_por' => (int) $a['id'], 'criado_em' => agora()]);
      foreach ($l['funcoes'] as $funcaoId) {
        inserir('guia_funcoes', ['guia_id' => $id, 'funcao_id' => $funcaoId]);
      }
      foreach ($l['idiomas'] ?? [] as $idioma) {
        inserir('guia_idiomas', ['guia_id' => $id, 'idioma' => $idioma, 'nivel' => 'fluente']);
      }
      if (!empty($l['pix'])) {
        guia_salvar_bancarios($id, $l['pix']);
      }
      return $id;
    });
    auditar('guia_pre_cadastrado', 'guia', $id);
    $criados++;
    if (!pre_cadastro_enviar_acesso(um('SELECT * FROM guias WHERE id = ?', [$id]))) {
      $semEmail[] = $d['nome'];
    }
  }
  unset($_SESSION['pre_cadastro']);
  auditar('pre_cadastro_em_massa', 'guia', null, ['criados' => $criados, 'origem' => $imp['origem']]);
  flash($semEmail ? 'aviso' : 'sucesso', $criados . ' guia(s) pré-cadastrado(s) e avisado(s) por e-mail.'
    . ($semEmail ? ' Não consegui enviar o e-mail para: ' . implode(', ', $semEmail) . '. Use "Reenviar acesso" na ficha.' : ''));
  redirecionar('/admin/guias?aba=pre');
}

function adm_pre_cadastro_cancelar(): void {
  exigir_admin(['coordenador']);
  unset($_SESSION['pre_cadastro']);
  redirecionar('/admin/guias/pre-cadastro');
}

/** Ficha do guia com cadastro incompleto: envia o link para continuar de onde parou. */
function adm_guia_link_cadastro(int $id): void {
  exigir_admin(['coordenador']);
  $g = um("SELECT * FROM guias WHERE id = ? AND status = 'rascunho'", [$id]) ?? abortar(404);
  $ok = cadastro_enviar_link_continuar($g);
  flash($ok ? 'sucesso' : 'erro', $ok ? 'Link para continuar o cadastro enviado para ' . $g['email'] . '.' : 'Este cadastro não tem e-mail válido ou o envio falhou.');
  redirecionar("/admin/guias/$id");
}

/** Ficha do guia: gera nova senha temporária (pré-cadastro que expirou ou e-mail perdido). */
function adm_guia_reenviar_acesso(int $id): void {
  exigir_admin(['coordenador']);
  $g = um('SELECT * FROM guias WHERE id = ?', [$id]) ?? abortar(404);
  if (!$g['email'] || in_array($g['status'], ['rascunho', 'inativo', 'bloqueado'], true) || $g['anonimizado_em']) {
    flash('erro', 'Não é possível enviar acesso para este guia.');
    redirecionar("/admin/guias/$id");
  }
  $ok = pre_cadastro_enviar_acesso($g);
  auditar('guia_acesso_reenviado', 'guia', $id);
  flash($ok ? 'sucesso' : 'erro', $ok ? 'Nova senha temporária enviada para ' . $g['email'] . '.' : 'Não foi possível enviar o e-mail. Confira a configuração de e-mail.');
  redirecionar("/admin/guias/$id");
}
