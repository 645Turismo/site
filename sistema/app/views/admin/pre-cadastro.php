<?php
$validos = $imp ? array_values(array_filter($imp['linhas'], fn($l) => !$l['erros'])) : [];
$invalidos = $imp ? array_values(array_filter($imp['linhas'], fn($l) => $l['erros'])) : [];
$colunas = array_map(fn($c) => $c[0] . ($c[1] ? ' *' : ''), PRE_CADASTRO_COLUNAS);
?>
<div class="pagina-topo">
  <div>
    <p class="sobretitulo">Guias</p>
    <h1>Pré-cadastro em massa</h1>
    <p>Suba a planilha padrão. Cada guia recebe por e-mail uma senha temporária, troca a senha no primeiro acesso e completa o cadastro.</p>
  </div>
  <a href="/admin/guias" class="link-sublinhado">Voltar</a>
</div>

<?php if (!$imp): ?>
  <section class="bloco" aria-labelledby="t-planilha">
    <div class="bloco-titulo"><h2 id="t-planilha">1. Baixe e preencha a planilha padrão</h2></div>
    <p>As mesmas perguntas do formulário "Faça parte do time 645 Turismo", nesta ordem: <b><?= e(implode(' · ', $colunas)) ?></b> (* obrigatória).</p>
    <p class="texto-2">Você também pode enviar direto a planilha de respostas do Google Forms (Respostas → Ver no Planilhas → Arquivo → Fazer download → .xlsx), desde que ela tenha a coluna de e-mail.</p>
    <ul class="texto-2 lista-simples">
      <li>Uma linha por guia. CPF com ou sem pontuação.</li>
      <li>"Gostaria de fazer meu cadastro para": nomes das funções do sistema<?= $funcoes ? ' (' . e(implode(', ', array_column($funcoes, 'nome'))) . ')' : '' ?>; "Monitor pedagógico" vira Monitor de Turismo Pedagógico.</li>
      <li>Idiomas separados por vírgula; camiseta PP, P, M, G, GG ou XG; o tipo da chave PIX é reconhecido sozinho.</li>
      <li>CPF ou e-mail que já existem no sistema são recusados na prévia (nada é duplicado).</li>
    </ul>
    <p><a href="/admin/guias/pre-cadastro/modelo.csv" class="btn btn-contorno btn-p">Baixar planilha padrão</a></p>
  </section>

  <section class="bloco" aria-labelledby="t-enviar">
    <div class="bloco-titulo"><h2 id="t-enviar">2. Envie a planilha preenchida</h2></div>
    <form method="post" action="/admin/guias/pre-cadastro" class="form" enctype="multipart/form-data">
      <?= csrf_campo() ?>
      <label class="campo"><span>Planilha (.xlsx ou .csv, até 5 MB)</span>
        <input type="file" name="arquivo" required accept=".xlsx,.csv,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"></label>
      <p class="texto-2">Você confere a prévia antes de criar os acessos. Nenhum e-mail é enviado nesta etapa.</p>
      <div class="form-rodape"><button type="submit" class="btn btn-primario">Ler planilha e conferir</button></div>
    </form>
  </section>
<?php else: ?>
  <section class="bloco" id="previa" aria-labelledby="t-previa">
    <div class="bloco-titulo"><h2 id="t-previa">Conferir antes de criar</h2></div>
    <p class="texto-2">Arquivo: <?= e($imp['origem']) ?>. Nada foi gravado ainda.</p>
    <div class="lp-contadores importar-resumo">
      <div><span class="rotulo">Vão receber acesso</span><strong><b><?= count($validos) ?></b></strong></div>
      <div><span class="rotulo">Com problema</span><strong class="<?= $invalidos ? 'vermelho' : '' ?>"><b><?= count($invalidos) ?></b></strong></div>
    </div>

    <?php if ($invalidos): ?>
      <div class="alerta alerta-erro espaco-topo"><?= icone('alerta') ?>
        <span><strong>Estas linhas não serão importadas:</strong>
          <?php foreach (array_slice($invalidos, 0, 50) as $l): ?>
            <br>Linha <?= (int) $l['linha'] ?> · <?= e($l['dados']['nome'] ?: '(sem nome)') ?>: <?= e(implode(', ', $l['erros'])) ?>
          <?php endforeach; ?></span>
      </div>
    <?php endif; ?>

    <?php if ($validos): ?>
      <div class="tabela-rolagem espaco-topo">
        <table class="tabela">
          <thead><tr><th>Linha</th><th>Nome completo</th><th>CPF</th><th>E-mail</th><th>Celular</th><th>Observação</th></tr></thead>
          <tbody>
            <?php foreach (array_slice($validos, 0, 300) as $l): $d = $l['dados']; ?>
              <tr>
                <td class="texto-2"><?= (int) $l['linha'] ?></td>
                <td><strong><?= e($d['nome']) ?></strong></td>
                <td class="mono"><?= e(formatar_cpf($d['cpf'])) ?></td>
                <td><?= e($d['email']) ?></td>
                <td><?= e(formatar_celular($d['celular'])) ?></td>
                <td class="texto-2"><?= e(implode('; ', $l['avisos'])) ?: (count($l['funcoes']) ? count($l['funcoes']) . ' função(ões)' : '') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <div class="importar-confirmar espaco-topo">
      <?php if ($validos): ?>
        <form method="post" action="/admin/guias/pre-cadastro/confirmar" class="inline-form"
              data-confirmar="Criar <?= count($validos) ?> pré-cadastro(s) e enviar a senha temporária por e-mail para cada guia?">
          <?= csrf_campo() ?><button type="submit" class="btn btn-primario">Criar e enviar <?= count($validos) ?> acesso(s)</button>
        </form>
      <?php endif; ?>
      <form method="post" action="/admin/guias/pre-cadastro/cancelar" class="inline-form">
        <?= csrf_campo() ?><button type="submit" class="btn btn-texto">Enviar outra planilha</button>
      </form>
    </div>
  </section>
<?php endif; ?>
