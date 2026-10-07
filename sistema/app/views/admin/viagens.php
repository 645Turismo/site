<?php
$filtros = ['ativas' => 'Em andamento', 'rascunho' => 'Rascunhos', 'publicada' => 'Publicadas', 'concluida' => 'Concluídas', 'cancelada' => 'Canceladas', 'todas' => 'Todas'];
?>
<div class="pagina-topo">
  <div>
    <p class="sobretitulo">Operação</p>
    <h1>Viagens/Tours</h1>
    <p>Cadastre o trabalho, defina dias e vagas por função, publique e convide os guias.</p>
  </div>
  <a href="/admin/viagens/nova" class="btn btn-primario">Nova viagem/tour</a>
</div>

<div class="barra-filtros">
  <nav class="filtros" aria-label="Filtrar por situação">
    <?php foreach ($filtros as $chave => $rotulo): ?>
      <a href="?status=<?= e($chave) ?><?= $busca !== '' ? '&q=' . urlencode($busca) : '' ?>" class="<?= $filtro === $chave ? 'ativo' : '' ?>"><?= e($rotulo) ?></a>
    <?php endforeach; ?>
  </nav>
  <form method="get" class="busca" role="search">
    <input type="hidden" name="status" value="<?= e($filtro) ?>">
    <input type="search" name="q" value="<?= e($busca) ?>" placeholder="Buscar por nome ou cliente" aria-label="Buscar">
  </form>
</div>

<?php if (!$viagens): ?>
  <p class="vazio">Nenhuma viagem/tour encontrado. <a href="/admin/viagens/nova">Cadastrar o primeiro</a></p>
<?php else: ?>
  <div class="tabela-rolagem">
    <table class="tabela">
      <thead><tr><th>Viagem/Tour</th><th>Período</th><th>Dias</th><th>Guias / vagas</th><th>Check-in</th><th>Situação</th></tr></thead>
      <tbody>
        <?php foreach ($viagens as $v): ?>
          <tr>
            <td><a href="/admin/viagens/<?= (int) $v['id'] ?>" class="link-limpo"><span class="mono codigo"><?= e($v['codigo']) ?></span><br><strong><?= e($v['nome']) ?></strong></a><br>
              <span class="texto-2"><?= e(($v['origem'] ?? '') . ' → ' . ($v['destino'] ?? '')) ?><?= (int) $v['com_pernoite'] ? ' · pernoite' : '' ?></span></td>
            <td><?= e(periodo_viagem($v)) ?></td>
            <td><?= (int) $v['dias'] ?></td>
            <td><?= (int) $v['guias'] ?> / <?= (int) $v['vagas'] ?></td>
            <td><?php if ($c = $v['checkin']): ?>
              <a href="/admin/viagens/<?= (int) $v['id'] ?>/passageiros?diaria=<?= $c['diaria_id'] ?>" class="link-limpo resumo-checkin" title="<?= $c['feitos'] ?> com check-in, <?= $c['faltam'] ?> faltando<?= $c['noshow'] ? ', ' . $c['noshow'] . ' no-show' : '' ?> de <?= $c['total'] ?> passageiros">
                <strong><span class="verde"><?= $c['feitos'] ?></span> / <span class="<?= $c['faltam'] ? 'amarelo' : '' ?>"><?= $c['faltam'] ?></span></strong>
                <small class="texto-2">feitos / faltam<?= $c['noshow'] ? ' · ' . $c['noshow'] . ' no-show' : '' ?><br><?= e(date('d/m', strtotime($c['data']))) ?><?= $c['data'] === hoje() ? ' (hoje)' : '' ?></small></a>
            <?php else: ?><span class="texto-2">—</span><?php endif; ?></td>
            <td><?= selo(STATUS_VIAGEM, $v['status']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
