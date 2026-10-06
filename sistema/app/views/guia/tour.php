<?php
$passos = [
  ['Bem-vindo à Área do Guia', 'Aqui fica tudo o que envolve seu trabalho com a 645 Turismo, do convite ao pagamento. Em Hoje você vê o próximo trabalho e o que falta resolver.'],
  ['Viagens/Tours', 'Os convites chegam aqui e por e-mail. Ao aceitar, você libera o briefing completo: ponto de encontro, horário de apresentação, roteiro e contatos.'],
  ['Relatório', 'No último dia de trabalho, escreva o relatório: como foi o grupo, ocorrências e passageiros atendidos. Ele libera a etapa da nota fiscal.'],
  ['Disponibilidade', 'Marque os dias e períodos em que pode trabalhar. A equipe usa essa agenda para montar as escalas.'],
  ['Recebimentos e ajuda', 'Acompanhe diárias e pagamentos em Recebimentos. Imprevisto ou dúvida? Fale com a equipe pela Ajuda.'],
];
?>
<div class="tour" id="tour" hidden data-auto="<?= (int) $p['tour_visto'] ? '0' : '1' ?>" data-csrf="<?= e(csrf_token()) ?>"
     role="dialog" aria-modal="true" aria-labelledby="tour-titulo">
  <div class="tour-caixa">
    <?php foreach ($passos as $i => [$tit, $txt]): ?>
      <div class="tour-passo" data-passo="<?= $i ?>" <?= $i ? 'hidden' : '' ?>>
        <span class="tour-contagem"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?> / <?= str_pad((string) count($passos), 2, '0', STR_PAD_LEFT) ?></span>
        <h2 <?= $i ? '' : 'id="tour-titulo"' ?>><?= e($tit) ?></h2>
        <p><?= e($txt) ?></p>
      </div>
    <?php endforeach; ?>
    <div class="tour-barra" aria-hidden="true"><span data-tour-progresso></span></div>
    <div class="tour-acoes">
      <button type="button" class="btn btn-texto" data-tour-fechar>Pular</button>
      <button type="button" class="btn btn-primario" data-tour-proximo>Próximo</button>
    </div>
  </div>
</div>
