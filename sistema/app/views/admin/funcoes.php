<div class="pagina-topo">
  <div>
    <p class="sobretitulo">Cadastros</p>
    <h1>Funções</h1>
    <p>As funções que os guias exercem nas viagens/tours. Aparecem no cadastro dos guias e na definição de vagas.</p>
  </div>
</div>

<div class="colunas">
  <section class="bloco" aria-labelledby="t-lista">
    <div class="bloco-titulo"><h2 id="t-lista">Cadastradas</h2></div>
    <div class="tabela-rolagem">
      <table class="tabela">
        <thead><tr><th>Função</th><th>Exige</th><th>Guias</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($funcoes as $f): ?>
            <tr class="<?= (int) $f['ativo'] ? '' : 'inativo' ?>">
              <td><strong><?= e($f['nome']) ?></strong><?= (int) $f['ativo'] ? '' : ' <span class="selo selo-neutro">Inativa</span>' ?>
                <?php if ($f['descricao']): ?><br><span class="texto-2"><?= e($f['descricao']) ?></span><?php endif; ?></td>
              <td class="texto-2"><?= e(implode(', ', array_filter([(int) $f['exige_cadastur'] ? 'Cadastur' : null, (int) $f['exige_idioma'] ? 'Idioma' : null])) ?: '—') ?></td>
              <td><?= (int) $f['guias'] ?></td>
              <td class="acoes">
                <a href="/admin/funcoes/<?= (int) $f['id'] ?>" class="btn btn-texto btn-p">Editar</a>
                <form method="post" action="/admin/funcoes/<?= (int) $f['id'] ?>/status">
                  <?= csrf_campo() ?><button type="submit" class="btn btn-texto btn-p"><?= (int) $f['ativo'] ? 'Desativar' : 'Reativar' ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="bloco" aria-labelledby="t-nova">
    <div class="bloco-titulo"><h2 id="t-nova">Nova função</h2></div>
    <form method="post" action="/admin/funcoes" class="form painel" novalidate>
      <?= csrf_campo() ?>
      <?php require RAIZ . '/app/views/admin/funcao-campos.php'; ?>
      <div class="form-rodape"><button type="submit" class="btn btn-primario">Cadastrar função</button></div>
    </form>
  </section>
</div>
