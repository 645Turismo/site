<?php
// Lista de passageiros (guia e ADM). Os dados chegam por JSON e se atualizam sozinhos (assets/js/passageiros.js).
$campos = array_map(fn($c) => $c[0], CAMPOS_PASSAGEIRO);
$urlIncluir = $admin ? $base : $base . '/incluir';
?>
<div class="pagina-topo">
  <div>
    <p class="sobretitulo mono"><?= e($v['codigo']) ?> · <?= e(TIPOS_VIAGEM[$v['tipo']] ?? '') ?></p>
    <h1>Passageiros</h1>
    <p><?= e($v['nome']) ?> · <?= e(periodo_viagem($v)) ?></p>
  </div>
  <div class="acoes-topo">
    <?php if ($admin): ?>
      <a href="<?= e($base) ?>/modelo.xlsx" class="btn btn-contorno btn-p">Baixar planilha padrão</a>
      <a href="#importar" class="btn btn-primario btn-p">Importar planilha</a>
    <?php endif; ?>
    <a href="<?= e($voltar) ?>" class="link-sublinhado">Voltar</a>
  </div>
</div>

<?php if (!$dias): ?>
  <p class="vazio">Cadastre os dias de trabalho da viagem/tour para usar a lista.</p>
<?php else: ?>
<div id="lista-passageiros" class="lp"
     data-base="<?= e($base) ?>" data-incluir="<?= e($urlIncluir) ?>" data-diaria="<?= (int) $escolhido ?>"
     data-csrf="<?= e(csrf_token()) ?>" data-admin="<?= $admin ? '1' : '0' ?>">

  <div class="lp-topo">
    <?php if (count($dias) > 1): ?>
      <nav class="filtros" aria-label="Dia de trabalho">
        <?php foreach ($dias as $d): ?>
          <a href="?diaria=<?= (int) $d['id'] ?>" class="<?= (int) $d['id'] === (int) $escolhido ? 'ativo' : '' ?>"><?= e(dia_semana_curto($d['data']) . ' ' . date('d/m', strtotime($d['data']))) ?></a>
        <?php endforeach; ?>
      </nav>
    <?php else: ?>
      <p class="rotulo">Dia de trabalho: <?= e(formatar_data($dias[0]['data'])) ?></p>
    <?php endif; ?>

    <div class="lp-contadores" aria-live="polite">
      <div><span class="rotulo">Check-in</span><strong><b data-tot="checkin">–</b><small>/<span data-tot="total">–</span></small></strong></div>
      <div><span class="rotulo">Check-out</span><strong><b data-tot="checkout">–</b></strong></div>
      <div><span class="rotulo">No-show</span><strong class="vermelho"><b data-tot="noshow">–</b></strong></div>
      <div><span class="rotulo">Ajustes do guia</span><strong class="amarelo"><b data-tot="ajustados">–</b></strong></div>
    </div>
    <p class="lp-status" data-status><span class="ponto"></span> Carregando lista…</p>
  </div>

  <div class="lp-abas" role="tablist" aria-label="Visualização">
    <button type="button" role="tab" aria-selected="true" data-aba="lista"><?= icone('documento') ?> Lista</button>
    <button type="button" role="tab" aria-selected="false" data-aba="mapa"><?= icone('painel') ?> Mapa do carro</button>
  </div>

  <section data-painel="lista">
    <div class="lp-ferramentas">
      <input type="search" data-busca placeholder="Buscar nome, documento, venda ou poltrona" aria-label="Buscar passageiro">
      <select data-filtro aria-label="Filtrar">
        <option value="todos">Todos</option>
        <option value="sem-checkin">Faltam check-in</option>
        <option value="checkin">Com check-in</option>
        <option value="checkout">Com check-out</option>
        <option value="noshow">No-show</option>
        <option value="ajustados">Ajustados pelo guia</option>
      </select>
      <button type="button" class="btn btn-contorno btn-p" data-incluir-abrir>+ Incluir passageiro</button>
    </div>
    <p class="lp-legenda">Guia ou staff (observação com "Guia" ou "Staff") aparece na lista, mas não entra na contagem de check-in.<br><span class="marca-guia"></span> Em amarelo: informação ajustada ou incluída pelo guia<?= $admin ? ' (o original aparece ao passar o mouse ou no editar)' : '. A coordenação é avisada por e-mail' ?>.</p>
    <div class="lp-tabela-rolagem">
      <table class="lp-tabela">
        <thead>
          <tr>
            <th class="col-n"><?= e($campos['poltrona']) ?></th>
            <th class="col-nome"><?= e($campos['nome']) ?></th>
            <th>Check-in</th>
            <th>Check-out</th>
            <th><?= e($campos['tipo_documento']) ?></th>
            <th><?= e($campos['documento']) ?></th>
            <th><?= e($campos['nascimento']) ?></th>
            <th><?= e($campos['venda']) ?></th>
            <th><?= e($campos['embarque']) ?></th>
            <th><?= e($campos['telefone']) ?></th>
            <th><?= e($campos['observacao']) ?></th>
            <th><span class="sr">Ações</span></th>
          </tr>
        </thead>
        <tbody data-corpo></tbody>
      </table>
    </div>
  </section>

  <section data-painel="mapa" hidden>
    <div class="mapa">
      <div class="carro-moldura">
        <p class="carro-titulo" data-carro-titulo></p>
        <div class="carro-frente"><span>Frente</span></div>
        <div class="carro" data-carro></div>
      </div>
      <aside class="poltrona-info" data-info aria-live="polite">
        <p class="rotulo">Toque numa poltrona</p>
        <p class="texto-2">Veja quem está nela, ligue para o passageiro e faça o check-in.</p>
      </aside>
    </div>
    <ul class="mapa-legenda">
      <li><span class="leg leg-livre"></span>Livre</li>
      <li><span class="leg leg-ocupada"></span>Reservada</li>
      <li><span class="leg leg-checkin"></span>Check-in feito</li>
      <li><span class="leg leg-noshow"></span>No-show</li>
      <li><span class="leg leg-reserva"></span>Mesma reserva (pisca)</li>
      <li><span class="leg leg-bloqueada"></span>Bloqueada</li>
      <li><span class="leg leg-conflito"></span>Poltrona duplicada</li>
    </ul>
    <div data-sem-poltrona></div>
  </section>

  <dialog class="dialogo" data-dialogo aria-labelledby="dlg-titulo">
    <form method="dialog" data-form-passageiro class="form" novalidate>
      <h2 id="dlg-titulo" data-dlg-titulo>Passageiro</h2>
      <p class="texto-2" data-dlg-aviso></p>
      <div class="grade-campos">
        <?php foreach (CAMPOS_PASSAGEIRO as $campo => [$rotulo, $max]): ?>
          <label class="campo <?= in_array($campo, ['nome', 'observacao', 'embarque'], true) ? 'campo-largo' : '' ?>">
            <span><?= e($rotulo) ?><?= $campo === 'nome' ? ' *' : '' ?></span>
            <?php if ($campo === 'observacao'): ?>
              <textarea name="observacao" rows="2" maxlength="<?= (int) $max ?>"></textarea>
            <?php elseif ($campo === 'tipo_documento'): ?>
              <input type="text" name="tipo_documento" maxlength="<?= (int) $max ?>" list="tipos-documento">
            <?php elseif ($campo === 'nascimento'): ?>
              <input type="text" name="nascimento" placeholder="dd/mm/aaaa" inputmode="numeric" maxlength="10">
            <?php elseif ($campo === 'telefone'): ?>
              <input type="tel" name="telefone" maxlength="20">
            <?php elseif ($campo === 'tipo_pax'): ?>
              <select name="tipo_pax"><option value="">Não informado</option>
                <?php foreach (TIPOS_PAX as $k => $rot): ?><option value="<?= e($k) ?>"><?= e($rot) ?></option><?php endforeach; ?>
              </select>
            <?php elseif ($campo === 'embarque' && !empty($origens)): ?>
              <select name="embarque"><option value="">Não informado</option>
                <?php foreach ($origens as $o): ?><option value="<?= e($o['local']) ?>"><?= e($o['local'] . ($o['horario'] ? ' · ' . substr($o['horario'], 0, 5) : '')) ?></option><?php endforeach; ?>
              </select>
            <?php else: ?>
              <input type="text" name="<?= e($campo) ?>" maxlength="<?= (int) $max ?>"<?= $campo === 'poltrona' ? ' inputmode="numeric"' : '' ?>>
            <?php endif; ?>
            <small class="original" data-original="<?= e($campo) ?>" hidden></small>
          </label>
        <?php endforeach; ?>
      </div>
      <p class="texto-2">Embarque: só os locais cadastrados na viagem. Poltrona: só uma pessoa por poltrona, exceto criança de colo.</p>
      <datalist id="tipos-documento">
        <?php foreach (TIPOS_DOCUMENTO as $t): ?><option value="<?= e($t) ?>"><?php endforeach; ?>
      </datalist>
      <p class="erro-form" data-dlg-erro hidden></p>
      <div class="form-rodape">
        <?php if ($admin): ?>
          <button type="button" class="btn btn-texto" data-remover hidden>Remover da lista</button>
          <button type="button" class="btn btn-contorno btn-p" data-incorporar hidden>Incorporar ajustes do guia</button>
        <?php endif; ?>
        <button type="button" class="btn btn-texto" data-fechar>Cancelar</button>
        <button type="submit" class="btn btn-primario" data-salvar>Salvar</button>
      </div>
    </form>
  </dialog>
</div>

<?php if ($admin): ?>
  <details class="bloco importar" id="importar">
    <summary>Importar da planilha</summary>
    <form method="post" action="<?= e($base) ?>/importar" class="form" enctype="multipart/form-data">
      <?= csrf_campo() ?>
      <p class="texto-2">Envie a planilha da lista (.xlsx ou .csv). Você confere a prévia antes de gravar.</p>
      <p><a href="<?= e($base) ?>/modelo.xlsx" class="btn btn-contorno btn-p">Baixar planilha padrão</a>
        <span class="texto-2">já numerada com as poltronas do veículo desta viagem e com a lista de embarques.</span></p>
      <label class="campo"><span>Arquivo da planilha (.xlsx ou .csv, até 5 MB)</span>
        <input type="file" name="arquivo" accept=".xlsx,.csv,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"></label>
      <p class="texto-2">As colunas são reconhecidas pelo nome do cabeçalho, em qualquer ordem. Passageiros que já estão na lista são pulados. Sem arquivo, você pode colar as linhas abaixo, nesta ordem:
        <b><?= e(implode(' · ', array_map(fn($c) => $campos[$c], ORDEM_PLANILHA))) ?></b>. O número da primeira coluna é a poltrona; o cabeçalho é ignorado.</p>
      <label class="campo"><span>Linhas da planilha</span>
        <textarea name="lista" rows="8" class="mono" placeholder="12&#9;Maria Souza&#9;RG&#9;12.345.678-9&#9;10/05/1980&#9;V-1020&#9;Estação da Luz&#9;Vegetariana&#9;11999990000&#9;Adulto"></textarea></label>
      <div class="form-rodape"><button type="submit" class="btn btn-primario">Ler planilha e conferir</button></div>
    </form>
  </details>

  <details class="bloco importar" id="limpar">
    <summary>Limpar a lista de passageiros</summary>
    <form method="post" action="<?= e($base) ?>/limpar" class="form" data-confirmar="Remover todos os passageiros desta lista? Não dá para desfazer.">
      <?= csrf_campo() ?>
      <p class="texto-2">Remove todos os passageiros de uma vez, para subir uma lista nova. Quem já teve check-in, check-out ou no-show sai da lista, mas fica guardado no histórico da viagem. Dica: na prévia da importação também existe a opção "Substituir a lista atual", que limpa e importa de uma vez.</p>
      <label class="campo"><span>Para confirmar, digite LIMPAR</span><input type="text" name="confirmacao" required autocomplete="off" placeholder="LIMPAR"></label>
      <div class="form-rodape"><button type="submit" class="btn btn-contorno vermelho">Limpar lista</button></div>
    </form>
  </details>
<?php endif; ?>
<?php endif; ?>
<script src="<?= e(asset('js/passageiros.js')) ?>"></script>
