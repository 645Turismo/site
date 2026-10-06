<?php
// Dados do guia: usados no cadastro público (5 etapas) e no Perfil do guia (mesmos formulários).

const ETAPAS_CADASTRO = [
  1 => 'Quem é você',
  2 => 'Sua atuação',
  3 => 'Documentos',
  4 => 'Recebimento',
  5 => 'Acesso',
];

// [rótulo, obrigatório no cadastro]
const DOCUMENTOS_GUIA = [
  'identidade' => ['Documento com foto (RG, CNH ou passaporte)', true],
  'comprovante_residencia' => ['Comprovante de residência (até 90 dias)', true],
  'cadastur' => ['Certificado Cadastur', false],
  'cnpj' => ['Cartão CNPJ ou CCMEI', false],
  'certificado' => ['Certificados e cursos (primeiros socorros, especializações)', false],
];

const CATEGORIAS_CADASTUR = ['Regional', 'Nacional', 'América do Sul', 'Internacional', 'Especializado em atrativo turístico'];
const NIVEIS_IDIOMA = ['intermediario' => 'Intermediário', 'fluente' => 'Fluente', 'nativo' => 'Nativo'];
const IDIOMAS_SUGERIDOS = ['Inglês', 'Espanhol', 'Francês', 'Italiano', 'Alemão', 'Japonês', 'Mandarim', 'Libras'];
const GENEROS = ['Feminino', 'Masculino', 'Não binário', 'Prefiro não informar'];
const CAMISETAS = ['PP', 'P', 'M', 'G', 'GG', 'XG'];
const UFS = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
const TIPOS_PIX = ['cpf' => 'CPF', 'cnpj' => 'CNPJ', 'email' => 'E-mail', 'celular' => 'Celular', 'aleatoria' => 'Chave aleatória'];
const BANCOS_SUGERIDOS = ['001 - Banco do Brasil', '033 - Santander', '104 - Caixa Econômica Federal', '237 - Bradesco', '341 - Itaú',
  '260 - Nubank', '077 - Inter', '336 - C6 Bank', '208 - BTG Pactual', '290 - PagBank', '323 - Mercado Pago', '748 - Sicredi', '756 - Sicoob'];

/** Funções, idiomas, regiões e dados bancários do guia (para os formulários). */
function guia_extras(?int $guiaId): array {
  if (!$guiaId) {
    return ['funcoes' => [], 'idiomas' => [], 'regioes' => [], 'bancarios' => [], 'documentos' => []];
  }
  $docs = [];
  foreach (todos('SELECT * FROM guia_documentos WHERE guia_id = ? AND atual = 1 ORDER BY id', [$guiaId]) as $d) {
    $docs[$d['tipo']][] = $d;
  }
  return [
    'funcoes' => array_map('intval', array_column(todos('SELECT funcao_id FROM guia_funcoes WHERE guia_id = ?', [$guiaId]), 'funcao_id')),
    'idiomas' => todos('SELECT idioma, nivel FROM guia_idiomas WHERE guia_id = ? ORDER BY idioma', [$guiaId]),
    'regioes' => array_map('intval', array_column(todos('SELECT regiao_id FROM guia_regioes WHERE guia_id = ?', [$guiaId]), 'regiao_id')),
    'bancarios' => um('SELECT * FROM guia_dados_bancarios WHERE guia_id = ?', [$guiaId]) ?? [],
    'documentos' => $docs,
  ];
}

/** Catálogos para os formulários. */
function guia_catalogos(): array {
  return [
    'funcoes' => todos('SELECT * FROM funcoes WHERE ativo = 1 ORDER BY ordem, nome'),
    'regioes' => todos('SELECT * FROM regioes WHERE ativo = 1 ORDER BY ordem, nome'),
  ];
}

// ---------- Etapa 1: dados pessoais e endereço ----------

/** Lê dados pessoais. $comCpf: só no cadastro (no perfil o CPF não muda). Retorna [dados, erros]. */
function guia_ler_pessoais(bool $comCpf, ?int $guiaId): array {
  $txt = fn(string $c, int $max) => ($v = mb_substr(entrada($c), 0, $max)) === '' ? null : $v;
  $d = [
    'nome' => mb_substr(preg_replace('/\s+/', ' ', entrada('nome')), 0, 160),
    'nome_social' => $txt('nome_social', 120),
    'nascimento' => entrada('nascimento') ?: null,
    'celular' => so_digitos(entrada('celular')) ?: null,
    'email' => mb_strtolower(entrada('email')) ?: null,
    'genero' => in_array(entrada('genero'), GENEROS, true) ? entrada('genero') : null,
    'nacionalidade' => $txt('nacionalidade', 30),
    'camiseta' => in_array(entrada('camiseta'), CAMISETAS, true) ? entrada('camiseta') : null,
    'pcd' => entrada('pcd') === '1' ? 1 : 0,
    'pcd_descricao' => entrada('pcd') === '1' ? $txt('pcd_descricao', 255) : null,
    'cep' => so_digitos(entrada('cep')) ?: null,
    'logradouro' => $txt('logradouro', 200),
    'numero' => $txt('numero', 20),
    'complemento' => $txt('complemento', 120),
    'bairro' => $txt('bairro', 120),
    'cidade' => $txt('cidade', 120),
    'uf' => in_array(entrada('uf'), UFS, true) ? entrada('uf') : null,
  ];
  $erros = [];
  if ($comCpf) {
    $d['cpf'] = so_digitos(entrada('cpf'));
    if (!cpf_valido($d['cpf'])) {
      $erros[] = 'CPF inválido.';
    }
  }
  if (mb_strlen($d['nome']) < 5 || !str_contains($d['nome'], ' ')) {
    $erros[] = 'Informe o nome completo.';
  }
  if (!$d['nascimento'] || !data_valida($d['nascimento']) || $d['nascimento'] > date('Y-m-d', strtotime('-16 years'))) {
    $erros[] = 'Informe uma data de nascimento válida.';
  }
  if (!$d['celular'] || strlen($d['celular']) < 10) {
    $erros[] = 'Informe o celular com DDD.';
  }
  if (!$d['email'] || !email_valido($d['email'])) {
    $erros[] = 'Informe um e-mail válido.';
  } elseif (valor('SELECT 1 FROM guias WHERE email = ? AND id <> ? AND anonimizado_em IS NULL', [$d['email'], $guiaId ?? 0])) {
    $erros[] = 'Este e-mail já está em outro cadastro.';
  }
  if (!$d['cidade'] || !$d['uf']) {
    $erros[] = 'Informe a cidade e o estado onde você mora.';
  }
  return [$d, $erros];
}

// ---------- Etapa 2: atuação ----------

/** Retorna [dados da tabela guias, funções, idiomas, regiões, erros]. */
function guia_ler_atuacao(): array {
  $catalogo = guia_catalogos();
  $idsFuncao = array_map('intval', array_column($catalogo['funcoes'], 'id'));
  $idsRegiao = array_map('intval', array_column($catalogo['regioes'], 'id'));
  $funcoes = array_values(array_intersect(array_map('intval', (array) ($_POST['funcoes'] ?? [])), $idsFuncao));
  $regioes = array_values(array_intersect(array_map('intval', (array) ($_POST['regioes'] ?? [])), $idsRegiao));
  $idiomas = [];
  foreach ((array) ($_POST['idioma'] ?? []) as $i => $idioma) {
    $idioma = mb_substr(trim((string) $idioma), 0, 40);
    $nivel = (string) ($_POST['idioma_nivel'][$i] ?? 'fluente');
    if ($idioma !== '' && !isset($idiomas[mb_strtolower($idioma)])) {
      $idiomas[mb_strtolower($idioma)] = ['idioma' => $idioma, 'nivel' => isset(NIVEIS_IDIOMA[$nivel]) ? $nivel : 'fluente'];
    }
  }
  $especialidades = array_values(array_intersect((array) ($_POST['especialidades'] ?? []), ESPECIALIDADES));
  $categorias = array_values(array_intersect((array) ($_POST['cadastur_categorias'] ?? []), CATEGORIAS_CADASTUR));
  $d = [
    'apresentacao' => mb_substr(trim((string) ($_POST['apresentacao'] ?? '')), 0, 1500) ?: null,
    'especialidades' => $especialidades ? implode(', ', $especialidades) : null,
    'aceita_pernoite' => entrada('aceita_pernoite') === '1' ? 1 : 0,
    'cadastur_numero' => mb_substr(entrada('cadastur_numero'), 0, 40) ?: null,
    'cadastur_uf' => in_array(entrada('cadastur_uf'), UFS, true) ? entrada('cadastur_uf') : null,
    'cadastur_categorias' => $categorias ? implode(', ', $categorias) : null,
    'cadastur_validade' => data_valida(entrada('cadastur_validade')) ? entrada('cadastur_validade') : null,
  ];
  $erros = [];
  if (!$funcoes) {
    $erros[] = 'Escolha pelo menos uma função.';
  }
  if (!$regioes) {
    $erros[] = 'Escolha pelo menos uma região onde pode trabalhar.';
  }
  $exigeCadastur = (bool) array_filter($catalogo['funcoes'], fn($f) => in_array((int) $f['id'], $funcoes, true) && (int) $f['exige_cadastur']);
  $exigeIdioma = (bool) array_filter($catalogo['funcoes'], fn($f) => in_array((int) $f['id'], $funcoes, true) && (int) $f['exige_idioma']);
  if ($exigeCadastur && (!$d['cadastur_numero'] || !$d['cadastur_uf'])) {
    $erros[] = 'As funções de guia exigem Cadastur: informe o número e o estado.';
  }
  if ($exigeIdioma && !$idiomas) {
    $erros[] = 'Para guia bilíngue, informe pelo menos um idioma.';
  }
  return [$d, $funcoes, array_values($idiomas), $regioes, $erros];
}

function guia_salvar_atuacao(int $guiaId, array $funcoes, array $idiomas, array $regioes): void {
  q('DELETE FROM guia_funcoes WHERE guia_id = ?', [$guiaId]);
  foreach ($funcoes as $f) {
    inserir('guia_funcoes', ['guia_id' => $guiaId, 'funcao_id' => $f]);
  }
  q('DELETE FROM guia_idiomas WHERE guia_id = ?', [$guiaId]);
  foreach ($idiomas as $i) {
    inserir('guia_idiomas', $i + ['guia_id' => $guiaId]);
  }
  q('DELETE FROM guia_regioes WHERE guia_id = ?', [$guiaId]);
  foreach ($regioes as $r) {
    inserir('guia_regioes', ['guia_id' => $guiaId, 'regiao_id' => $r]);
  }
}

// ---------- Etapa 3: documentos ----------

/** Guarda os documentos enviados no formulário. Retorna [quantidade salva, erros]. */
function guia_salvar_documentos(int $guiaId, string $enviadoPor): array {
  $salvos = 0;
  $erros = [];
  foreach (DOCUMENTOS_GUIA as $tipo => [$rotulo]) {
    if (!upload_enviado('doc_' . $tipo)) {
      continue;
    }
    try {
      [$caminho, $nome, $mime, $tamanho] = salvar_upload('doc_' . $tipo, 'documentos');
    } catch (RuntimeException $e) {
      $erros[] = $rotulo . ': ' . $e->getMessage();
      continue;
    }
    // O envio novo substitui o anterior (que fica no histórico, fora da lista atual).
    if ($tipo !== 'certificado') {
      q('UPDATE guia_documentos SET atual = 0 WHERE guia_id = ? AND tipo = ?', [$guiaId, $tipo]);
    }
    inserir('guia_documentos', [
      'guia_id' => $guiaId, 'tipo' => $tipo, 'titulo' => $rotulo, 'arquivo_path' => $caminho, 'nome_original' => $nome,
      'mime' => $mime, 'tamanho' => $tamanho, 'status' => 'enviado', 'atual' => 1, 'criado_em' => agora(),
    ]);
    $salvos++;
  }
  if ($salvos) {
    auditar('documentos_enviados', 'guia', $guiaId, ['quantidade' => $salvos, 'por' => $enviadoPor]);
  }
  return [$salvos, $erros];
}

/** Documentos obrigatórios que ainda faltam. */
function guia_documentos_faltando(int $guiaId): array {
  $tem = array_flip(array_column(todos('SELECT DISTINCT tipo FROM guia_documentos WHERE guia_id = ? AND atual = 1', [$guiaId]), 'tipo'));
  $exigeCadastur = (bool) valor('SELECT 1 FROM guia_funcoes gf JOIN funcoes f ON f.id = gf.funcao_id WHERE gf.guia_id = ? AND f.exige_cadastur = 1', [$guiaId]);
  $faltam = [];
  foreach (DOCUMENTOS_GUIA as $tipo => [$rotulo, $obrigatorio]) {
    if (($obrigatorio || ($tipo === 'cadastur' && $exigeCadastur)) && !isset($tem[$tipo])) {
      $faltam[] = $rotulo;
    }
  }
  return $faltam;
}

// ---------- Etapa 4: recebimento ----------

/** Retorna [dados da tabela guias (CNPJ), dados bancários, erros]. */
function guia_ler_recebimento(): array {
  $d = [
    'cnpj' => so_digitos(entrada('cnpj')) ?: null,
    'razao_social' => mb_substr(entrada('razao_social'), 0, 200) ?: null,
    'nome_fantasia' => mb_substr(entrada('nome_fantasia'), 0, 200) ?: null,
  ];
  $pixTipo = isset(TIPOS_PIX[entrada('pix_tipo')]) ? entrada('pix_tipo') : null;
  $banco = mb_substr(entrada('banco'), 0, 120);
  $b = [
    'banco_codigo' => preg_match('/^(\d{3})/', $banco, $m) ? $m[1] : null,
    'banco_nome' => $banco ?: null,
    'tipo_conta' => in_array(entrada('tipo_conta'), ['corrente', 'poupanca', 'pagamento'], true) ? entrada('tipo_conta') : null,
    'agencia' => mb_substr(entrada('agencia'), 0, 10) ?: null,
    'conta' => mb_substr(entrada('conta'), 0, 20) ?: null,
    'pix_tipo' => $pixTipo,
    'pix_chave' => mb_substr(entrada('pix_chave'), 0, 140) ?: null,
  ];
  $erros = [];
  if (!$d['cnpj'] || !cnpj_valido($d['cnpj'])) {
    $erros[] = 'Informe um CNPJ válido (MEI ou empresa). Ele é necessário para a nota fiscal.';
  }
  if (!$d['razao_social']) {
    $erros[] = 'Informe a razão social do CNPJ.';
  }
  if (!$b['pix_tipo'] || !$b['pix_chave']) {
    $erros[] = 'Informe a chave PIX para receber os pagamentos.';
  }
  return [$d, $b, $erros];
}

function guia_salvar_bancarios(int $guiaId, array $b): void {
  $b['atualizado_em'] = agora();
  if (valor('SELECT 1 FROM guia_dados_bancarios WHERE guia_id = ?', [$guiaId])) {
    atualizar('guia_dados_bancarios', $b, 'guia_id = ?', [$guiaId]);
  } else {
    inserir('guia_dados_bancarios', $b + ['guia_id' => $guiaId]);
  }
}

/** Foto de rosto (opcional no envio se já houver uma). */
function guia_salvar_foto(int $guiaId, ?string $fotoAtual): ?string {
  if (!upload_enviado('foto')) {
    return null;
  }
  [$caminho] = salvar_upload('foto', 'fotos', true);
  apagar_arquivo($fotoAtual);
  atualizar('guias', ['foto_path' => $caminho, 'atualizado_em' => agora()], 'id = ?', [$guiaId]);
  return $caminho;
}
