<div class="pagina-topo">
  <div>
    <p class="sobretitulo">Equipe de campo</p>
    <h1>Guias</h1>
    <p>Triagem de novos cadastros e banco de guias.</p>
  </div>
  <a href="/admin/guias/pre-cadastro" class="btn btn-primario">Pré-cadastrar em massa</a>
</div>

<nav class="filtros" aria-label="Situação">
  <?php foreach (ABAS_GUIAS as $chave => [$rotulo]): ?>
    <a href="?aba=<?= e($chave) ?>" class="<?= $aba === $chave ? 'ativo' : '' ?>"><?= e($rotulo) ?><?= $contagem[$chave] !== null ? ' <span>' . (int) $contagem[$chave] . '</span>' : '' ?></a>
  <?php endforeach; ?>
</nav>

<form method="get" class="filtros-form" role="search">
  <input type="hidden" name="aba" value="<?= e($aba) ?>">
  <input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Nome, código, CPF ou e-mail" aria-label="Buscar guia">
  <select name="funcao" aria-label="Função"><option value="">Todas as funções</option>
    <?php foreach ($cat['funcoes'] as $fn): ?><option value="<?= (int) $fn['id'] ?>" <?= $f['funcao'] === (int) $fn['id'] ? 'selected' : '' ?>><?= e($fn['nome']) ?></option><?php endforeach; ?>
  </select>
  <input type="text" name="idioma" value="<?= e($f['idioma']) ?>" placeholder="Idioma" aria-label="Idioma" list="idiomas-filtro">
  <datalist id="idiomas-filtro"><?php foreach (IDIOMAS_SUGERIDOS as $s): ?><option value="<?= e($s) ?>"><?php endforeach; ?></datalist>
  <button type="submit" class="btn btn-contorno btn-p">Filtrar</button>
</form>

<?php if (!$guias): ?>
  <p class="vazio">Nenhum guia nesta lista.</p>
<?php else: ?>
  <div class="tabela-rolagem">
    <table class="tabela">
      <thead><tr><th>Guia</th><th>Funções e idiomas</th><th>Cidade</th><th>Situação</th></tr></thead>
      <tbody>
        <?php foreach ($guias as $g): ?>
          <tr>
            <td><a href="/admin/guias/<?= (int) $g['id'] ?>" class="link-limpo"><strong><?= e($g['nome_social'] ?: $g['nome']) ?></strong></a><br>
              <span class="texto-2"><span class="mono amarelo"><?= e($g['codigo']) ?></span> · <?= e(formatar_celular($g['celular'])) ?></span></td>
            <td><?= e($g['funcoes'] ?: '—') ?><?= $g['idiomas'] ? '<br><span class="texto-2">' . e($g['idiomas']) . '</span>' : '' ?></td>
            <td><?= e(trim(($g['cidade'] ?? '') . ($g['uf'] ? '/' . $g['uf'] : ''), '/')) ?></td>
            <td><?= selo(STATUS_CADASTRO, $g['status']) ?><?= (int) $g['docs_revisar'] ? '<br><span class="texto-2">' . (int) $g['docs_revisar'] . ' doc. para conferir</span>' : '' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
