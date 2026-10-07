<?php
// Cadastro público de novos guias, em 5 etapas, e consulta de status.
// O rascunho fica ligado à sessão do navegador; ao concluir, o guia entra direto na Área do Guia (cadastro em análise).
// O guia pré-cadastrado pelo ADM usa o mesmo fluxo, já logado, para completar as informações pendentes.

function cadastro_rascunho(): ?array {
  $logado = guia_atual();
  if ($logado && $logado['status'] === 'pre_cadastro') {
    if ((int) $logado['trocar_senha']) {
      redirecionar('/primeiro-acesso');
    }
    return um('SELECT * FROM guias WHERE id = ?', [$logado['id']]);
  }
  $id = $_SESSION['cadastro_guia_id'] ?? null;
  $g = $id ? um("SELECT * FROM guias WHERE id = ? AND status = 'rascunho'", [$id]) : null;
  if (!$g) {
    unset($_SESSION['cadastro_guia_id']);
  }
  return $g;
}

function pub_cadastro(): void {
  if (guia_atual() && guia_atual()['status'] !== 'pre_cadastro') {
    redirecionar('/guia/perfil');
  }
  $g = cadastro_rascunho();
  $etapa = $g ? max(1, min(5, (int) $g['cadastro_etapa'])) : 1;
  redirecionar('/cadastro/' . (isset(etapas_cadastro()[$etapa]) ? $etapa : etapa_seguinte($etapa)));
}

function pub_cadastro_etapa(int $etapa): void {
  if (!isset(etapas_cadastro()[$etapa])) {
    abortar(404);
  }
  $g = cadastro_rascunho();
  $liberada = $g ? (int) $g['cadastro_etapa'] : 1;
  if (!isset(etapas_cadastro()[$liberada])) {
    $liberada = etapa_seguinte($liberada); // rascunho parado numa etapa que foi desligada
  }
  if ($etapa > $liberada) {
    redirecionar('/cadastro/' . $liberada);
  }
  $x = guia_extras($g['id'] ?? null);
  exibir('publico/cadastro', [
    'titulo' => 'Cadastro de guia · ' . etapas_cadastro()[$etapa],
    'etapa' => $etapa,
    'liberada' => $liberada,
    'g' => $g ?? [],
    'x' => $x,
    'cat' => guia_catalogos(),
    'exigeCadastur' => $g && (bool) valor('SELECT 1 FROM guia_funcoes gf JOIN funcoes f ON f.id = gf.funcao_id WHERE gf.guia_id = ? AND f.exige_cadastur = 1', [$g['id']]),
  ], 'publico');
}

function pub_cadastro_salvar(int $etapa): void {
  if (!isset(etapas_cadastro()[$etapa])) {
    abortar(404);
  }
  $g = cadastro_rascunho();
  if ($etapa > 1 && !$g) {
    flash('erro', 'Seu cadastro expirou. Comece de novo.');
    redirecionar('/cadastro/1');
  }
  $volta = function (array $erros) use ($etapa): never {
    guardar_antigo($_POST);
    flash('erro', implode(' ', $erros));
    redirecionar('/cadastro/' . $etapa);
  };

  if ($etapa === 1) {
    // CPF que já tem cadastro é tratado antes das outras validações (o e-mail, por exemplo, é o do próprio rascunho).
    $cpfInformado = so_digitos(entrada('cpf'));
    $existente = !$g && $cpfInformado !== '' ? um('SELECT * FROM guias WHERE cpf = ?', [$cpfInformado]) : null;
    if ($existente && $existente['status'] !== 'rascunho') {
      $volta(['Este CPF já tem cadastro. Entre com sua senha ou use "Esqueci a senha".']);
    }
    if ($existente && $existente['email']) {
      // Rascunho começado em outro momento/aparelho: não apaga o que foi preenchido, manda o link para continuar.
      cadastro_enviar_link_continuar($existente);
      $volta(['Você já começou um cadastro com este CPF. Enviamos para ' . mascarar_email($existente['email'])
        . ' um link para continuar de onde parou (confira também o spam).']);
    }
    if ($existente) {
      cadastro_descartar_rascunho((int) $existente['id']); // rascunho sem e-mail: não há como retomar
    }
    [$d, $erros] = guia_ler_pessoais($g === null, $g['id'] ?? null);
    if ($erros) {
      $volta($erros);
    }
    if (!$g) {
      $id = inserir('guias', $d + ['codigo' => gerar_codigo_guia($d['cpf']), 'status' => 'rascunho', 'cadastro_etapa' => 1, 'criado_em' => agora()]);
      $_SESSION['cadastro_guia_id'] = $id;
      $g = um('SELECT * FROM guias WHERE id = ?', [$id]);
    } else {
      atualizar('guias', $d + ['atualizado_em' => agora()], 'id = ?', [$g['id']]);
    }
  } elseif ($etapa === 2) {
    [$d, $funcoes, $idiomas, $regioes, $erros] = guia_ler_atuacao();
    if ($erros) {
      $volta($erros);
    }
    atualizar('guias', $d + ['atualizado_em' => agora()], 'id = ?', [$g['id']]);
    guia_salvar_atuacao((int) $g['id'], $funcoes, $idiomas, $regioes);
  } elseif ($etapa === 3) {
    [, $erros] = guia_salvar_documentos((int) $g['id'], 'guia');
    $faltam = guia_documentos_faltando((int) $g['id']);
    if ($faltam) {
      $erros[] = 'Faltam: ' . implode(', ', $faltam) . '.';
    }
    if ($erros) {
      $volta($erros);
    }
  } elseif ($etapa === 4) {
    [$d, $b, $erros] = guia_ler_recebimento();
    if ($erros) {
      $volta($erros);
    }
    atualizar('guias', $d + ['atualizado_em' => agora()], 'id = ?', [$g['id']]);
    guia_salvar_bancarios((int) $g['id'], $b);
  } else {
    // O pré-cadastrado já criou a senha no primeiro acesso.
    $preCadastro = $g['status'] === 'pre_cadastro';
    $erro = $preCadastro ? null : erro_senha((string) ($_POST['senha'] ?? ''), (string) ($_POST['confirmacao'] ?? ''));
    if (!$erro && empty($_POST['termos'])) {
      $erro = 'Para concluir, aceite os termos de uso e a política de privacidade.';
    }
    if (!$erro && ($faltam = guia_documentos_faltando((int) $g['id']))) {
      $erro = 'Faltam documentos: ' . implode(', ', $faltam) . '.';
    }
    if ($erro) {
      $volta([$erro]);
    }
    cadastro_concluir($g, $preCadastro ? null : (string) $_POST['senha']);
  }

  $proxima = etapa_seguinte($etapa);
  if ((int) $g['cadastro_etapa'] < $proxima) {
    atualizar('guias', ['cadastro_etapa' => $proxima], 'id = ?', [$g['id']]);
  }
  limpar_antigo();
  redirecionar('/cadastro/' . $proxima);
}

function cadastro_concluir(array $g, ?string $senha): never {
  atualizar('guias', ($senha !== null ? ['senha_hash' => hash_senha($senha)] : []) + [
    'termos_aceitos_em' => agora(), 'status' => 'em_analise', 'cadastro_etapa' => 5, 'atualizado_em' => agora(),
  ], 'id = ?', [$g['id']]);
  auditar('cadastro_concluido', 'guia', (int) $g['id'], null, 'guia', (int) $g['id']);
  enviar_email((string) $g['email'], 'Recebemos seu cadastro',
    '<p>Olá, ' . e(primeiro_nome($g)) . '.</p><p>Recebemos seu cadastro de guia na 645 Turismo. Nossa equipe vai conferir seus dados e documentos e avisar por e-mail.</p>'
    . '<p>Seu código de guia é <strong>' . e($g['codigo']) . '</strong>. Você já pode entrar na Área do Guia com seu CPF e senha.</p>');
  enviar_email(email_equipe(), 'Novo cadastro de guia: ' . $g['nome'],
    '<p><strong>' . e($g['nome']) . '</strong> (código ' . e($g['codigo']) . ') concluiu o cadastro e aguarda triagem.</p>'
    . '<p><a href="' . e(url_absoluta('/admin/guias/' . (int) $g['id'])) . '">Abrir ficha no Painel ADM</a></p>');
  unset($_SESSION['cadastro_guia_id']);
  // Entra direto na Área do Guia.
  session_regenerate_id(true);
  $_SESSION['guia_id'] = (int) $g['id'];
  limpar_antigo();
  flash('sucesso', 'Cadastro enviado! Agora é com a gente: avisamos por e-mail quando terminar a análise.');
  redirecionar('/guia/hoje');
}

/** Apaga um rascunho abandonado (dados, documentos e foto), para recomeçar com o mesmo CPF. */
function cadastro_descartar_rascunho(int $id): void {
  foreach (todos('SELECT arquivo_path FROM guia_documentos WHERE guia_id = ?', [$id]) as $d) {
    apagar_arquivo($d['arquivo_path']);
  }
  apagar_arquivo(valor('SELECT foto_path FROM guias WHERE id = ?', [$id]));
  foreach (['guia_documentos', 'guia_funcoes', 'guia_idiomas', 'guia_regioes', 'guia_dados_bancarios'] as $t) {
    q("DELETE FROM $t WHERE guia_id = ?", [$id]);
  }
  q("DELETE FROM guias WHERE id = ? AND status = 'rascunho'", [$id]);
}

// ---------- Consulta de status ----------

function pub_status(): void {
  exibir('publico/status', ['titulo' => 'Acompanhar cadastro', 'resultado' => null], 'publico');
}

function pub_status_consultar(): void {
  $chaves = ['status-ip:' . ip_cliente()];
  if (login_bloqueado($chaves)) {
    flash('erro', 'Muitas consultas. Aguarde alguns minutos.');
    redirecionar('/status');
  }
  registrar_tentativa($chaves);
  $g = um('SELECT status, status_motivo, nome, nome_social, codigo FROM guias WHERE cpf = ? AND nascimento = ? AND anonimizado_em IS NULL',
    [so_digitos(entrada('cpf')), entrada('nascimento')]);
  exibir('publico/status', ['titulo' => 'Acompanhar cadastro', 'resultado' => $g ?: false], 'publico');
}

// ---------- Continuar o cadastro em outro momento ou aparelho ----------
// O rascunho fica ligado ao navegador e ainda não tem senha. Quem saiu no meio recebe por e-mail
// um link (válido por 3 dias) que reabre o cadastro na etapa em que parou, em qualquer aparelho.

const CADASTRO_LINK_VALIDADE_MIN = 3 * 24 * 60;

function mascarar_email(?string $email): string {
  if (!$email || !str_contains($email, '@')) {
    return '';
  }
  [$usuario, $dominio] = explode('@', $email, 2);
  return mb_substr($usuario, 0, 2) . str_repeat('*', max(3, mb_strlen($usuario) - 2)) . '@' . $dominio;
}

function cadastro_enviar_link_continuar(array $g): bool {
  if (!$g['email']) {
    return false;
  }
  $token = criar_token_reset('cadastro', (int) $g['id'], CADASTRO_LINK_VALIDADE_MIN);
  auditar('cadastro_link_continuar', 'guia', (int) $g['id']);
  return enviar_email((string) $g['email'], 'Continue seu cadastro na 645 Turismo',
    '<p>Olá, ' . e(primeiro_nome($g)) . '.</p>'
    . '<p>Seu cadastro de guia na 645 Turismo está em andamento. Use o botão abaixo para continuar de onde parou, em qualquer celular ou computador.</p>'
    . '<p><a href="' . e(url_absoluta('/cadastro/retomar?token=' . $token)) . '" style="display:inline-block;padding:12px 22px;background:#53D9B2;color:#000;text-decoration:none;font-weight:bold;border-radius:999px">Continuar meu cadastro</a></p>'
    . '<p>O link vale por 3 dias. Se ele vencer, peça outro em ' . e(url_absoluta('/cadastro/continuar')) . '.</p>');
}

function pub_cadastro_continuar(): void {
  exibir('publico/continuar-cadastro', ['titulo' => 'Continuar cadastro'], 'publico');
}

function pub_cadastro_continuar_enviar(): void {
  $chaves = ['continuar-ip:' . ip_cliente()];
  if (login_bloqueado($chaves)) {
    flash('erro', 'Muitas solicitações. Aguarde alguns minutos e tente de novo.');
    redirecionar('/cadastro/continuar');
  }
  registrar_tentativa($chaves);
  $cpf = so_digitos(entrada('cpf'));
  $g = $cpf ? um("SELECT * FROM guias WHERE cpf = ? AND status = 'rascunho' AND anonimizado_em IS NULL", [$cpf]) : null;
  if ($g) {
    cadastro_enviar_link_continuar($g);
  }
  // Mesma resposta exista ou não o rascunho, para não revelar quem tem cadastro.
  flash('sucesso', 'Se houver um cadastro em andamento com este CPF, enviamos o link para continuar ao e-mail informado nele. Confira também o spam.');
  redirecionar('/cadastro/continuar');
}

function pub_cadastro_retomar(): void {
  $reset = buscar_token_reset((string) ($_GET['token'] ?? ''), 'cadastro');
  $g = $reset ? um("SELECT * FROM guias WHERE id = ? AND status = 'rascunho'", [$reset['usuario_id']]) : null;
  if (!$g) {
    flash('erro', 'Link inválido ou vencido. Peça um novo abaixo.');
    redirecionar('/cadastro/continuar');
  }
  consumir_token_reset((int) $reset['id']);
  session_regenerate_id(true);
  $_SESSION['cadastro_guia_id'] = (int) $g['id'];
  flash('sucesso', 'Bem-vindo(a) de volta! Seu cadastro continua de onde parou.');
  redirecionar('/cadastro');
}
