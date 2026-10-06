<?php
// Disponibilidade do guia: calendário do mês com marcações (disponível/indisponível por período),
// trabalhos confirmados e convites. A equipe usa essas marcações na hora de alocar.

const PERIODOS = ['dia' => 'Dia todo', 'manha' => 'Manhã', 'tarde' => 'Tarde', 'noite' => 'Noite'];
const LIMITE_DIAS_MARCACAO = 62;

function guia_disponibilidade(): void {
  $g = exigir_guia();
  $mes = (string) ($_GET['mes'] ?? date('Y-m'));
  if (!preg_match('/^\d{4}-\d{2}$/', $mes) || !data_valida($mes . '-01')) {
    $mes = date('Y-m');
  }
  $inicio = $mes . '-01';
  $fim = date('Y-m-t', strtotime($inicio));

  $marcas = [];
  foreach (todos('SELECT * FROM disponibilidade WHERE guia_id = ? AND data BETWEEN ? AND ? ORDER BY data, periodo', [$g['id'], $inicio, $fim]) as $m) {
    $marcas[$m['data']][] = $m;
  }
  $trabalhos = [];
  foreach (todos("SELECT d.data, s.status, v.id AS viagem_id, v.codigo, v.nome FROM escalas s JOIN diarias d ON d.id = s.diaria_id
      JOIN viagens v ON v.id = d.viagem_id
      WHERE s.guia_id = ? AND d.data BETWEEN ? AND ? AND s.status NOT IN ('recusado','cancelado') AND v.status IN ('publicada','concluida')",
      [$g['id'], $inicio, $fim]) as $t) {
    $trabalhos[$t['data']][] = $t;
  }
  exibir('guia/disponibilidade', [
    'titulo' => 'Disponibilidade',
    'menu' => 'disponibilidade',
    'p' => $g,
    'mes' => $mes,
    'inicio' => $inicio,
    'marcas' => $marcas,
    'trabalhos' => $trabalhos,
    'dataSelecionada' => (string) ($_GET['data'] ?? ''),
  ], 'guia');
}

function guia_disponibilidade_salvar(): void {
  $g = exigir_guia();
  $data = entrada('data');
  $ate = entrada('ate') ?: $data;
  $tipo = entrada('tipo');
  $periodo = entrada('periodo');
  $obs = mb_substr(entrada('observacao'), 0, 255) ?: null;
  $mes = data_valida($data) ? substr($data, 0, 7) : date('Y-m');
  $volta = '/guia/disponibilidade?mes=' . $mes;
  if (!data_valida($data) || !data_valida($ate) || $ate < $data) {
    flash('erro', 'Escolha o dia (e, se quiser, até que dia vale).');
    redirecionar($volta);
  }
  if ($data < hoje()) {
    flash('erro', 'Não dá para marcar dias que já passaram.');
    redirecionar($volta);
  }
  if (!in_array($tipo, ['disponivel', 'indisponivel'], true) || !isset(PERIODOS[$periodo])) {
    flash('erro', 'Escolha se está disponível ou indisponível e o período.');
    redirecionar($volta);
  }
  $dias = (int) ((strtotime($ate) - strtotime($data)) / 86400) + 1;
  if ($dias > LIMITE_DIAS_MARCACAO) {
    flash('erro', 'Marque no máximo ' . LIMITE_DIAS_MARCACAO . ' dias de uma vez.');
    redirecionar($volta);
  }
  $semana = array_map('intval', (array) ($_POST['dias_semana'] ?? []));
  $gravados = 0;
  transacao(function () use ($g, $data, $dias, $tipo, $periodo, $obs, $semana, &$gravados) {
    for ($i = 0; $i < $dias; $i++) {
      $dia = date('Y-m-d', strtotime("$data +$i days"));
      if ($semana && !in_array((int) date('w', strtotime($dia)), $semana, true)) {
        continue;
      }
      // "Dia todo" substitui as marcações por período; um período substitui o "dia todo" e ele mesmo.
      $substituir = $periodo === 'dia' ? [] : [$periodo, 'dia'];
      if ($substituir) {
        q('DELETE FROM disponibilidade WHERE guia_id = ? AND data = ? AND periodo IN (?, ?)', [$g['id'], $dia, $substituir[0], $substituir[1]]);
      } else {
        q('DELETE FROM disponibilidade WHERE guia_id = ? AND data = ?', [$g['id'], $dia]);
      }
      inserir('disponibilidade', ['guia_id' => $g['id'], 'data' => $dia, 'tipo' => $tipo, 'periodo' => $periodo, 'observacao' => $obs, 'criado_em' => agora()]);
      $gravados++;
    }
  });
  flash('sucesso', $gravados === 1 ? 'Marcação salva.' : "$gravados dias marcados.");
  redirecionar($volta);
}

function guia_disponibilidade_remover(int $id): void {
  $g = exigir_guia();
  $m = um('SELECT * FROM disponibilidade WHERE id = ? AND guia_id = ?', [$id, $g['id']]) ?? abortar(404);
  q('DELETE FROM disponibilidade WHERE id = ?', [$id]);
  flash('sucesso', 'Marcação removida.');
  redirecionar('/guia/disponibilidade?mes=' . substr($m['data'], 0, 7));
}
