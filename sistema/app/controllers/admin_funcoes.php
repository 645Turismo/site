<?php
// Cadastro das funções que os guias podem exercer (Guia de Turismo, Bilíngue, Monitor, Receptivo...).
// Função desativada some do cadastro de guias e de novas vagas, mas o histórico é preservado.

function adm_funcao_ler(): array {
  return [
    'nome' => mb_substr(entrada('nome'), 0, 120),
    'descricao' => mb_substr(entrada('descricao'), 0, 255) ?: null,
    'exige_cadastur' => isset($_POST['exige_cadastur']) ? 1 : 0,
    'exige_idioma' => isset($_POST['exige_idioma']) ? 1 : 0,
    'ordem' => max(0, min(999, (int) entrada('ordem', '0'))),
  ];
}

function adm_funcoes(): void {
  $a = exigir_admin(['coordenador']);
  exibir('admin/funcoes', [
    'titulo' => 'Funções',
    'menu' => 'funcoes',
    'a' => $a,
    'funcoes' => todos('SELECT f.*,
        (SELECT COUNT(*) FROM guia_funcoes gf WHERE gf.funcao_id = f.id) AS guias,
        (SELECT COUNT(*) FROM viagem_vagas vv WHERE vv.funcao_id = f.id) AS usos
      FROM funcoes f ORDER BY f.ativo DESC, f.ordem, f.nome'),
  ], 'admin');
}

function adm_funcao_criar(): void {
  exigir_admin(['coordenador']);
  $dados = adm_funcao_ler();
  if (mb_strlen($dados['nome']) < 3) {
    guardar_antigo($_POST);
    flash('erro', 'Dê um nome à função (pelo menos 3 letras).');
    redirecionar('/admin/funcoes');
  }
  if (valor('SELECT 1 FROM funcoes WHERE LOWER(nome) = LOWER(?)', [$dados['nome']])) {
    guardar_antigo($_POST);
    flash('erro', 'Já existe uma função com esse nome.');
    redirecionar('/admin/funcoes');
  }
  $base = gerar_slug($dados['nome']);
  $slug = $base;
  for ($n = 2; valor('SELECT 1 FROM funcoes WHERE slug = ?', [$slug]); $n++) {
    $slug = $base . '-' . $n;
  }
  $id = inserir('funcoes', $dados + ['slug' => $slug, 'ativo' => 1]);
  auditar('funcao_criada', 'funcao', $id, ['nome' => $dados['nome']]);
  flash('sucesso', 'Função "' . $dados['nome'] . '" cadastrada.');
  redirecionar('/admin/funcoes');
}

function adm_funcao_editar(int $id): void {
  $a = exigir_admin(['coordenador']);
  $f = um('SELECT * FROM funcoes WHERE id = ?', [$id]) ?? abortar(404, 'Função não encontrada.');
  exibir('admin/funcao-editar', ['titulo' => 'Editar função', 'menu' => 'funcoes', 'a' => $a, 'f' => $f], 'admin');
}

function adm_funcao_salvar(int $id): void {
  exigir_admin(['coordenador']);
  um('SELECT id FROM funcoes WHERE id = ?', [$id]) ?? abortar(404);
  $dados = adm_funcao_ler();
  if (mb_strlen($dados['nome']) < 3) {
    flash('erro', 'Dê um nome à função (pelo menos 3 letras).');
    redirecionar("/admin/funcoes/$id");
  }
  if (valor('SELECT 1 FROM funcoes WHERE LOWER(nome) = LOWER(?) AND id <> ?', [$dados['nome'], $id])) {
    flash('erro', 'Já existe outra função com esse nome.');
    redirecionar("/admin/funcoes/$id");
  }
  atualizar('funcoes', $dados, 'id = ?', [$id]);
  auditar('funcao_editada', 'funcao', $id);
  flash('sucesso', 'Função atualizada.');
  redirecionar('/admin/funcoes');
}

function adm_funcao_status(int $id): void {
  exigir_admin(['coordenador']);
  $f = um('SELECT * FROM funcoes WHERE id = ?', [$id]) ?? abortar(404);
  $novo = (int) $f['ativo'] ? 0 : 1;
  atualizar('funcoes', ['ativo' => $novo], 'id = ?', [$id]);
  auditar($novo ? 'funcao_ativada' : 'funcao_desativada', 'funcao', $id);
  flash('sucesso', $novo ? 'Função reativada.' : 'Função desativada. Ela não aparece mais em novos cadastros e vagas.');
  redirecionar('/admin/funcoes');
}
