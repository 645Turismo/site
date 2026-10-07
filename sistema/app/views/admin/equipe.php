<div class="pagina-topo">
  <div>
    <p class="sobretitulo">Acessos</p>
    <h1>Equipe</h1>
    <p>Quem da 645 Turismo acessa o Painel ADM e com qual perfil.</p>
  </div>
  <?php if ($a['papel'] === 'admin'): ?><a href="/admin/diagnostico" class="btn btn-contorno btn-p">Diagnóstico do servidor</a><?php endif; ?>
</div>

<div class="colunas">
  <section class="bloco" aria-labelledby="t-equipe">
    <div class="bloco-titulo"><h2 id="t-equipe">Pessoas com acesso</h2></div>
    <div class="tabela-rolagem">
      <table class="tabela">
        <thead><tr><th>Nome</th><th>Perfil</th><th>Último acesso</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($usuarios as $u): ?>
            <tr class="<?= (int) $u['ativo'] ? '' : 'inativo' ?>">
              <td><strong><?= e($u['nome']) ?></strong><br><span class="texto-2"><?= e($u['email']) ?></span></td>
              <td><?= e(PAPEIS_ADMIN[$u['papel']] ?? $u['papel']) ?><?= (int) $u['ativo'] ? '' : ' <span class="selo selo-neutro">Inativo</span>' ?></td>
              <td><?= e(formatar_data_hora($u['ultimo_login_em']) ?: '—') ?></td>
              <td class="acoes">
                <?php if ((int) $u['id'] !== (int) $a['id']): ?>
                  <form method="post" action="/admin/equipe/<?= (int) $u['id'] ?>/status"
                        data-confirmar="<?= (int) $u['ativo'] ? 'Desativar o acesso de ' . e($u['nome']) . '?' : 'Reativar o acesso de ' . e($u['nome']) . '?' ?>">
                    <?= csrf_campo() ?>
                    <button type="submit" class="btn btn-texto btn-p"><?= (int) $u['ativo'] ? 'Desativar' : 'Reativar' ?></button>
                  </form>
                <?php else: ?>
                  <span class="texto-2">Você</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="bloco" aria-labelledby="t-novo">
    <div class="bloco-titulo"><h2 id="t-novo">Dar acesso</h2></div>
    <form method="post" action="/admin/equipe" class="form painel" novalidate>
      <?= csrf_campo() ?>
      <label class="campo"><span>Nome</span><input type="text" name="nome" value="<?= e(antigo('nome')) ?>" required></label>
      <label class="campo"><span>E-mail</span><input type="email" name="email" value="<?= e(antigo('email')) ?>" required></label>
      <label class="campo"><span>Perfil</span>
        <select name="papel" required>
          <?php foreach (PAPEIS_ADMIN as $valor => $rotulo): ?>
            <option value="<?= e($valor) ?>" <?= antigo('papel', 'coordenador') === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <p class="texto-2">A pessoa recebe por e-mail um link (válido por 3 dias) para criar a própria senha.</p>
      <div class="form-rodape"><button type="submit" class="btn btn-primario">Enviar convite</button></div>
      <dl class="lista-papeis">
        <dt>Administrador</dt><dd>Acesso total, inclusive equipe e funções.</dd>
        <dt>Coordenador</dt><dd>Viagens/tours, passageiros, guias, conferência, atendimento e conteúdo. Recebe os avisos de alteração de lista.</dd>
        <dt>Financeiro</dt><dd>Pagamentos, conferência de notas fiscais e atendimento.</dd>
      </dl>
    </form>
  </section>
</div>
