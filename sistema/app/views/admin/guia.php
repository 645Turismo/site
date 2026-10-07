<?php
$b = $x['bancarios'];
$idiomas = array_map(fn($i) => $i['idioma'] . ' (' . (NIVEIS_IDIOMA[$i['nivel']] ?? $i['nivel']) . ')', $x['idiomas']);
$acoesDisponiveis = match ($g['status']) {
  'em_analise' => ['aprovar', 'pendencia', 'reprovar'],
  'pendente' => ['aprovar', 'reprovar'],
  'aprovado' => ['pendencia', 'inativar', 'bloquear'],
  'inativo', 'bloqueado', 'reprovado' => ['reativar'],
  default => [],
};
$statusDoc = ['enviado' => ['Para conferir', 'aviso'], 'aprovado' => ['Aprovado', 'sucesso'], 'reprovado' => ['Devolvido', 'erro']];
?>
<div class="pagina-topo">
  <div class="ficha-guia-topo">
    <?php if ($g['foto_path']): ?><img src="<?= e(arquivo_url('foto', $g)) ?>" alt="" class="foto-guia" width="88" height="88"><?php endif; ?>
    <div>
      <p class="sobretitulo mono"><?= e($g['codigo']) ?></p>
      <h1><?= e($g['nome']) ?></h1>
      <p><?= $g['nome_social'] ? 'Prefere ser chamado(a) de ' . e($g['nome_social']) . ' · ' : '' ?>cadastro desde <?= e(formatar_data($g['criado_em'])) ?></p>
    </div>
  </div>
  <div class="acoes-topo"><?= selo(STATUS_CADASTRO, $g['status']) ?><a href="/admin/guias" class="link-sublinhado">Voltar</a></div>
</div>

<?php if ($g['status_motivo'] && in_array($g['status'], ['pendente', 'reprovado', 'bloqueado'], true)): ?>
  <div class="alerta alerta-aviso"><?= icone('alerta') ?><span><strong>Motivo registrado:</strong> <?= e($g['status_motivo']) ?></span></div>
<?php endif; ?>
<?php if ($faltam): ?>
  <div class="alerta alerta-erro"><?= icone('alerta') ?><span>Faltam documentos: <?= e(implode(', ', $faltam)) ?>.</span></div>
<?php endif; ?>

<?php if ($acoesDisponiveis): ?>
  <section class="painel acoes-guia">
    <?php foreach ($acoesDisponiveis as $acao): [, $exigeMotivo, $rotulo] = ACOES_GUIA[$acao]; ?>
      <?php if ($exigeMotivo): ?>
        <details class="acao-motivo">
          <summary class="btn btn-contorno btn-p"><?= e($rotulo) ?></summary>
          <form method="post" action="/admin/guias/<?= (int) $g['id'] ?>/status" class="form">
            <?= csrf_campo() ?><input type="hidden" name="acao" value="<?= e($acao) ?>">
            <label class="campo"><span>Motivo (o guia vê)</span><textarea name="motivo" rows="3" required></textarea></label>
            <button type="submit" class="btn btn-primario btn-p">Confirmar: <?= e(mb_strtolower($rotulo)) ?></button>
          </form>
        </details>
      <?php else: ?>
        <form method="post" action="/admin/guias/<?= (int) $g['id'] ?>/status" class="inline-form" <?= $acao === 'inativar' ? 'data-confirmar="Inativar este guia? Ele sai das escalas futuras."' : '' ?>>
          <?= csrf_campo() ?><input type="hidden" name="acao" value="<?= e($acao) ?>">
          <button type="submit" class="btn <?= in_array($acao, ['aprovar', 'reativar'], true) ? 'btn-primario' : 'btn-contorno' ?> btn-p"><?= e($rotulo) ?></button>
        </form>
      <?php endif; ?>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<div class="colunas">
  <div>
    <section class="bloco" aria-labelledby="t-pessoais">
      <div class="bloco-titulo"><h2 id="t-pessoais">Dados pessoais</h2></div>
      <dl class="ficha">
        <dt>CPF</dt><dd><?= e(formatar_cpf($g['cpf'])) ?></dd>
        <dt>Nascimento</dt><dd><?= e(formatar_data($g['nascimento'])) ?></dd>
        <dt>Celular</dt><dd><?php if ($g['celular']): ?><a href="<?= e(link_whatsapp($g['celular'])) ?>" target="_blank" rel="noopener noreferrer"><?= e(formatar_celular($g['celular'])) ?></a><?php endif; ?></dd>
        <dt>E-mail</dt><dd><?= e($g['email']) ?></dd>
        <dt>Endereço</dt><dd><?= e(implode(', ', array_filter([$g['logradouro'], $g['numero'], $g['complemento'], $g['bairro']]))) ?><br><?= e(trim($g['cidade'] . '/' . $g['uf'], '/')) ?> <?= e($g['cep']) ?></dd>
        <dt>Outros</dt><dd><?= e(implode(' · ', array_filter([$g['genero'], $g['nacionalidade'], $g['camiseta'] ? 'camiseta ' . $g['camiseta'] : null, (int) $g['pcd'] ? 'PCD: ' . ($g['pcd_descricao'] ?: 'sim') : null]))) ?></dd>
        <dt>Alimentação</dt><dd><?= e(RESTRICOES_ALIMENTARES[$g['restricao_alimentar'] ?? ''] ?? '—') ?></dd>
        <dt>Saúde</dt><dd><?= e($g['doencas_preexistentes'] ?: '—') ?></dd>
      </dl>
    </section>

    <section class="bloco" aria-labelledby="t-atuacao">
      <div class="bloco-titulo"><h2 id="t-atuacao">Atuação</h2></div>
      <dl class="ficha">
        <dt>Funções</dt><dd><?= e(implode(', ', $funcoesNomes) ?: '—') ?></dd>
        <dt>Idiomas</dt><dd><?= e(implode(', ', $idiomas) ?: 'Só português') ?></dd>
        <dt>Regiões</dt><dd><?= e(implode(', ', $regioesNomes) ?: '—') ?></dd>
        <dt>Especialidades</dt><dd><?= e($g['especialidades'] ?: '—') ?></dd>
        <dt>Pernoite</dt><dd><?= (int) $g['aceita_pernoite'] ? 'Aceita' : 'Não aceita' ?></dd>
        <dt>Cadastur</dt><dd><?= e(implode(' · ', array_filter([$g['cadastur_numero'], $g['cadastur_uf'], $g['cadastur_categorias'], $g['cadastur_validade'] ? 'validade ' . formatar_data($g['cadastur_validade']) : null])) ?: '—') ?>
          <?php if ($g['cadastur_validade'] && $g['cadastur_validade'] < hoje()): ?><br><span class="vermelho">Vencido</span><?php endif; ?></dd>
        <?php if ($g['apresentacao']): ?><dt>Apresentação</dt><dd class="texto-longo"><?= nl2br(e($g['apresentacao'])) ?></dd><?php endif; ?>
      </dl>
    </section>

    <section class="bloco" id="documentos" aria-labelledby="t-docs">
      <div class="bloco-titulo"><h2 id="t-docs">Documentos</h2></div>
      <?php if (!$x['documentos']): ?>
        <p class="vazio">Nenhum documento enviado.</p>
      <?php else: ?>
        <ul class="docs-adm">
          <?php foreach ($x['documentos'] as $tipo => $docs): foreach ($docs as $d): [$r, $c] = $statusDoc[$d['status']] ?? [$d['status'], 'neutro']; ?>
            <li>
              <div><strong><?= e(DOCUMENTOS_GUIA[$tipo][0] ?? $tipo) ?></strong>
                <span class="texto-2">enviado em <?= e(formatar_data_hora($d['criado_em'])) ?></span>
                <?php if ($d['motivo']): ?><small class="vermelho">Motivo: <?= e($d['motivo']) ?></small><?php endif; ?></div>
              <div class="docs-adm-acoes">
                <span class="selo selo-<?= e($c) ?>"><?= e($r) ?></span>
                <a href="<?= e(arquivo_url('documento', $d)) ?>" target="_blank" rel="noopener" class="btn btn-contorno btn-p">Abrir</a>
                <?php if ($d['status'] !== 'aprovado'): ?>
                  <form method="post" action="/admin/guias/<?= (int) $g['id'] ?>/documentos/<?= (int) $d['id'] ?>" class="inline-form"><?= csrf_campo() ?>
                    <input type="hidden" name="decisao" value="aprovar"><button type="submit" class="btn btn-primario btn-p">Aprovar</button></form>
                <?php endif; ?>
                <?php if ($d['status'] !== 'reprovado'): ?>
                  <details class="acao-motivo"><summary class="btn btn-texto btn-p">Devolver</summary>
                    <form method="post" action="/admin/guias/<?= (int) $g['id'] ?>/documentos/<?= (int) $d['id'] ?>" class="form"><?= csrf_campo() ?>
                      <input type="hidden" name="decisao" value="reprovar">
                      <label class="campo"><span>Motivo</span><input type="text" name="motivo" maxlength="500" required placeholder="Ex.: foto ilegível"></label>
                      <button type="submit" class="btn btn-contorno btn-p">Devolver ao guia</button></form>
                  </details>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>

  <aside>
    <section class="bloco" aria-labelledby="t-receb">
      <div class="bloco-titulo"><h2 id="t-receb">Recebimento</h2></div>
      <dl class="ficha ficha-compacta">
        <?php if (isset($g['emite_nf']) && !(int) $g['emite_nf']): ?>
          <dt>Nota fiscal</dt><dd>Não emite (recebe sem NF)</dd>
        <?php else: ?>
          <dt>CNPJ</dt><dd><?= e(formatar_cnpj($g['cnpj'])) ?: '—' ?></dd>
          <dt>Razão social</dt><dd><?= e($g['razao_social'] ?: '—') ?></dd>
        <?php endif; ?>
        <dt>PIX</dt><dd><?= $b ? e((TIPOS_PIX[$b['pix_tipo']] ?? '') . ': ' . $b['pix_chave']) : '—' ?></dd>
        <dt>Banco</dt><dd><?= $b ? e(implode(' · ', array_filter([$b['banco_nome'], $b['agencia'] ? 'ag. ' . $b['agencia'] : null, $b['conta'] ? 'cc ' . $b['conta'] : null]))) : '—' ?></dd>
      </dl>
    </section>

    <section class="bloco" aria-labelledby="t-hist">
      <div class="bloco-titulo"><h2 id="t-hist">Histórico</h2></div>
      <p class="texto-2"><?= count($historico) ?> viagem(ns)/tour(s) · <?= $faltas ?> falta(s) · <?= $recusas ?> recusa(s)</p>
      <?php if ($historico): ?>
        <ul class="feed feed-simples">
          <?php foreach ($historico as $h): ?>
            <li><span class="feed-data"><?= e(date('d/m/y', strtotime($h['inicio']))) ?></span>
              <div><strong><a href="/admin/viagens/<?= (int) $h['id'] ?>" class="link-limpo"><span class="mono codigo"><?= e($h['codigo']) ?></span> <?= e($h['nome']) ?></a></strong>
                <p><?= (int) $h['diarias'] ?> diária(s)</p></div></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <section class="bloco" aria-labelledby="t-disp">
      <div class="bloco-titulo"><h2 id="t-disp">Próximos 30 dias</h2></div>
      <?php if (!$proximaDisponibilidade): ?>
        <p class="vazio">Sem marcações de disponibilidade.</p>
      <?php else: ?>
        <p class="disp-resumo">
          <?php foreach ($proximaDisponibilidade as $m): ?>
            <span class="selo <?= $m['tipo'] === 'disponivel' ? 'selo-sucesso' : 'selo-erro' ?>"><?= e(date('d/m', strtotime($m['data']))) ?><?= $m['periodo'] !== 'dia' ? ' ' . e(substr($m['periodo'], 0, 3)) : '' ?></span>
          <?php endforeach; ?>
        </p>
      <?php endif; ?>
    </section>

    <section class="bloco" id="notas" aria-labelledby="t-notas">
      <div class="bloco-titulo"><h2 id="t-notas">Notas internas</h2></div>
      <form method="post" action="/admin/guias/<?= (int) $g['id'] ?>/notas" class="form">
        <?= csrf_campo() ?>
        <label class="campo"><span>Só a equipe vê</span><textarea name="notas_internas" rows="4" maxlength="5000"><?= e($g['notas_internas']) ?></textarea></label>
        <button type="submit" class="btn btn-contorno btn-p">Salvar notas</button>
      </form>
    </section>

    <?php if ($g['status'] === 'pre_cadastro'): ?>
      <p class="texto-2"><?= (int) $g['trocar_senha'] ? 'Ainda não fez o primeiro acesso' . ($g['senha_temporaria_expira'] ? ' (senha temporária vale até ' . e(formatar_data_hora($g['senha_temporaria_expira'])) . ')' : '') . '.' : 'Já criou a senha; falta completar o cadastro.' ?></p>
    <?php endif; ?>
    <?php if (in_array($g['status'], ['pre_cadastro', 'em_analise', 'pendente', 'aprovado'], true) && $g['email']): ?>
      <form method="post" action="/admin/guias/<?= (int) $g['id'] ?>/reenviar-acesso" class="inline-form" data-confirmar="Gerar uma nova senha temporária e enviar para <?= e($g['email']) ?>? A senha atual deixa de valer.">
        <?= csrf_campo() ?><button type="submit" class="btn btn-texto btn-p">Reenviar acesso (senha temporária)</button>
      </form>
    <?php endif; ?>
    <?php if ($g['status'] !== 'pre_cadastro'): ?>
    <form method="post" action="/admin/guias/<?= (int) $g['id'] ?>/senha" class="inline-form" data-confirmar="Enviar ao guia um link para criar nova senha?">
      <?= csrf_campo() ?><button type="submit" class="btn btn-texto btn-p">Enviar link de nova senha</button>
    </form>
    <?php endif; ?>
  </aside>
</div>
