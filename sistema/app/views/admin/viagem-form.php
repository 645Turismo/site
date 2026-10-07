<?php
// $v vem do banco (edição) ou com padrões (nova). Após erro, os campos voltam do envio anterior.
$val = fn(string $c, string $padrao = '') => antigo($c, (string) ($v[$c] ?? $padrao));
$acao = $nova ? '/admin/viagens/nova' : '/admin/viagens/' . (int) $v['id'] . '/editar';

// Contatos extras: do envio anterior (se houve erro), senão do banco; sempre com 3 linhas livres a mais.
$ant = $_SESSION['antigo'] ?? null;
if ($ant && isset($ant['contato_papel'])) {
  $contatos = [];
  foreach ((array) $ant['contato_papel'] as $i => $papel) {
    $contatos[] = ['papel' => $papel, 'nome' => $ant['contato_nome'][$i] ?? '', 'telefone' => $ant['contato_telefone'][$i] ?? '',
      'observacao' => $ant['contato_observacao'][$i] ?? ''];
  }
  $contatos = array_values(array_filter($contatos, fn($c) => $c['nome'] !== '' || $c['telefone'] !== ''));
}
for ($i = 0; $i < 3; $i++) {
  $contatos[] = ['papel' => '', 'nome' => '', 'telefone' => '', 'observacao' => ''];
}
// Origens: do envio anterior (se houve erro), senão do banco; sempre pelo menos duas linhas.
if ($ant && isset($ant['origens'])) {
  $linhasOrigem = [];
  foreach ((array) $ant['origens'] as $i => $local) {
    $linhasOrigem[] = ['local' => (string) $local, 'horario' => (string) ($ant['origens_horario'][$i] ?? '')];
  }
} else {
  $linhasOrigem = $origens;
}
$linhasOrigem = array_values(array_filter($linhasOrigem, fn($o) => trim($o['local']) !== ''));
while (count($linhasOrigem) < 2) {
  $linhasOrigem[] = ['local' => '', 'horario' => ''];
}
$papeisContato = ['Hotel / hospedagem', 'Restaurante', 'Atrativo', 'Empresa de ônibus', 'Responsável do grupo', 'Emergência', 'Outro'];
?>
<div class="pagina-topo">
  <div>
    <p class="sobretitulo"><?= !empty($copia) ? 'Cópia de ' . e($copia['codigo']) : ($nova ? 'Cadastro' : 'Edição · ' . e($v['codigo'])) ?></p>
    <h1><?= !empty($copia) ? 'Copiar viagem/tour' : ($nova ? 'Nova viagem/tour' : e($v['nome'])) ?></h1>
    <?php if (!empty($copia)): ?><p>Os dados vieram de <strong><?= e($copia['codigo'] . ' · ' . $copia['nome']) ?></strong>. Informe o novo código, ajuste as datas e o que mais mudar. Passageiros e guias escalados não são copiados.</p><?php endif; ?>
    <p>O que você preencher aqui vira o briefing do guia. Contatos e lista de passageiros só aparecem para quem confirmar presença.</p>
  </div>
  <a href="<?= $nova ? '/admin/viagens' : '/admin/viagens/' . (int) $v['id'] ?>" class="link-sublinhado">Cancelar</a>
</div>

<form method="post" action="<?= e($acao) ?>" class="form form-secoes" novalidate data-form-viagem>
  <?php if (!empty($copia)): ?><input type="hidden" name="copiar_de" value="<?= (int) $copia['id'] ?>"><?php endif; ?>
  <?= csrf_campo() ?>

  <fieldset>
    <legend><span>01</span> Identificação</legend>
    <div class="grade-campos">
      <label class="campo"><span>Código da viagem *</span>
        <input type="text" name="codigo" maxlength="40" required value="<?= e($val('codigo')) ?>" placeholder="TR.R.20261010.1"
               spellcheck="false" class="mono"></label>
      <label class="campo campo-largo"><span>Nome *</span>
        <input type="text" name="nome" maxlength="200" required value="<?= e($val('nome')) ?>" placeholder="Ex.: Trem da República — Rodoviário"></label>
      <label class="campo"><span>Tipo *</span>
        <select name="tipo" required>
          <?php foreach (TIPOS_VIAGEM as $chave => $rotulo): ?>
            <option value="<?= e($chave) ?>" <?= $val('tipo') === $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option>
          <?php endforeach; ?>
        </select></label>
      <label class="campo"><span>Cliente</span>
        <input type="text" name="cliente" maxlength="200" value="<?= e($val('cliente')) ?>" placeholder="Escola, empresa ou grupo"></label>
      <label class="campo"><span>Data de ida *</span><input type="date" name="data_inicio" required value="<?= e($val('data_inicio')) ?>"></label>
      <label class="campo"><span>Data de volta *</span><input type="date" name="data_fim" required value="<?= e($val('data_fim')) ?>"></label>
      <div class="campo">
        <span>Pernoite *</span>
        <div class="opcoes-linha">
          <label class="opcao"><input type="radio" name="com_pernoite" value="0" <?= $val('com_pernoite', '0') !== '1' ? 'checked' : '' ?> data-pernoite> <span>Sem pernoite (bate e volta)</span></label>
          <label class="opcao"><input type="radio" name="com_pernoite" value="1" <?= $val('com_pernoite') === '1' ? 'checked' : '' ?> data-pernoite> <span>Com pernoite</span></label>
        </div>
      </div>
      <label class="campo campo-largo" data-so-pernoite><span>Hospedagem</span>
        <input type="text" name="hospedagem" maxlength="255" value="<?= e($val('hospedagem')) ?>" placeholder="Hotel, endereço e regime (ex.: café da manhã incluso)"></label>
    </div>
  </fieldset>

  <fieldset>
    <legend><span>02</span> Trajeto e horários</legend>
    <div class="origens" data-origens>
      <?php foreach ($linhasOrigem as $i => $o): ?>
        <div class="origem-linha" data-origem-linha>
          <label class="campo"><span>Origem <?= $i + 1 ?><?= $i === 0 ? ' *' : '' ?></span>
            <input type="text" name="origens[]" maxlength="160" <?= $i === 0 ? 'required' : '' ?> value="<?= e($o['local']) ?>"
                   placeholder="<?= $i === 0 ? 'Ex.: São Paulo — Metrô Barra Funda' : 'Outro ponto de embarque (opcional)' ?>"></label>
          <label class="campo"><span>Saída deste ponto</span>
            <input type="time" name="origens_horario[]" value="<?= e((string) $o['horario']) ?>"></label>
          <button type="button" class="origem-remover" data-origem-remover aria-label="Remover esta origem" <?= $i < 2 ? 'hidden' : '' ?>>×</button>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-contorno btn-p" data-origem-adicionar>+ Adicionar origem</button>
    <div class="grade-campos espaco-topo">
      <label class="campo campo-largo"><span>Destino *</span>
        <input type="text" name="destino" maxlength="160" required value="<?= e($val('destino')) ?>" placeholder="Ex.: Jundiaí — Estação"></label>
      <label class="campo campo-largo"><span>Ponto de encontro (endereço exato)</span>
        <input type="text" name="ponto_encontro" maxlength="255" value="<?= e($val('ponto_encontro')) ?>" placeholder="Endereço e referência para o guia"></label>
      <label class="campo campo-largo"><span>Link do mapa</span>
        <input type="url" name="ponto_encontro_mapa" maxlength="500" value="<?= e($val('ponto_encontro_mapa')) ?>" placeholder="https://maps.google.com/..."></label>
    </div>
    <div class="grade-campos grade-4">
      <label class="campo"><span>Apresentação *</span><input type="time" name="horario_apresentacao" required value="<?= e($val('horario_apresentacao')) ?>"></label>
      <label class="campo"><span>Saída *</span><input type="time" name="horario_saida" required value="<?= e($val('horario_saida')) ?>"></label>
      <label class="campo"><span>Saída do destino *</span><input type="time" name="horario_saida_destino" required value="<?= e($val('horario_saida_destino')) ?>"></label>
      <label class="campo"><span>Previsão de chegada *</span><input type="time" name="previsao_chegada" required value="<?= e($val('previsao_chegada')) ?>"></label>
    </div>
  </fieldset>

  <fieldset>
    <legend><span>03</span> Veículo e mapa de poltronas</legend>
    <div class="grade-campos">
      <label class="campo"><span>Tipo de veículo</span>
        <select name="veiculo" data-veiculo>
          <option value="">Sem mapa de poltronas</option>
          <?php foreach (VEICULOS as $chave => [$rotulo, $lug]): ?>
            <option value="<?= e($chave) ?>" data-lugares="<?= (int) $lug ?>" <?= $val('veiculo') === $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option>
          <?php endforeach; ?>
        </select></label>
      <label class="campo" data-so-outro><span>Descrição do veículo *</span>
        <input type="text" name="veiculo_descricao" maxlength="120" value="<?= e($val('veiculo_descricao')) ?>" placeholder="Ex.: Van executiva, Ônibus leito"></label>
      <label class="campo" data-com-veiculo><span>Lugares <small data-lugares-dica></small></span>
        <input type="number" name="lugares" min="1" max="90" inputmode="numeric" value="<?= e($val('lugares')) ?>" placeholder="Padrão do veículo"></label>
      <label class="campo" data-so-dd><span>Poltronas no piso inferior</span>
        <input type="number" name="lugares_piso_inferior" min="1" max="40" inputmode="numeric" value="<?= e($val('lugares_piso_inferior')) ?>" placeholder="<?= DD_PISO_INFERIOR_PADRAO ?>"></label>
      <label class="campo campo-largo" data-com-veiculo><span>Poltronas bloqueadas</span>
        <input type="text" name="poltronas_bloqueadas" maxlength="255" value="<?= e($val('poltronas_bloqueadas')) ?>" placeholder="Ex.: 1, 2, 45-46 (guia, motorista reserva, manutenção)"></label>
      <label class="campo campo-largo"><span>Empresa / placa</span>
        <input type="text" name="transporte" maxlength="200" value="<?= e($val('transporte')) ?>" placeholder="Ex.: Viação X, placa ABC1D23"></label>
    </div>
  </fieldset>

  <fieldset>
    <legend><span>04</span> Equipe e contatos</legend>
    <div class="grade-campos">
      <label class="campo"><span>Coordenação 645</span><input type="text" name="coordenador_nome" maxlength="120" value="<?= e($val('coordenador_nome')) ?>"></label>
      <label class="campo"><span>WhatsApp da coordenação</span><input type="tel" name="coordenador_telefone" maxlength="20" value="<?= e(formatar_celular($val('coordenador_telefone'))) ?>"></label>
      <label class="campo"><span>Motorista</span><input type="text" name="motorista_nome" maxlength="120" value="<?= e($val('motorista_nome')) ?>"></label>
      <label class="campo"><span>Telefone do motorista</span><input type="tel" name="motorista_telefone" maxlength="20" value="<?= e(formatar_celular($val('motorista_telefone'))) ?>"></label>
      <label class="campo"><span>Guia local</span><input type="text" name="guia_local_nome" maxlength="120" value="<?= e($val('guia_local_nome')) ?>" placeholder="Quem recebe o grupo no destino"></label>
      <label class="campo"><span>Telefone do guia local</span><input type="tel" name="guia_local_telefone" maxlength="20" value="<?= e(formatar_celular($val('guia_local_telefone'))) ?>"></label>
    </div>
    <p class="rotulo espaco-topo">Outros contatos</p>
    <div class="contatos-linhas">
      <?php foreach ($contatos as $c): ?>
        <div class="contato-linha">
          <label class="campo"><span>Tipo</span>
            <input type="text" name="contato_papel[]" maxlength="60" list="papeis-contato" value="<?= e($c['papel']) ?>"></label>
          <label class="campo"><span>Nome</span><input type="text" name="contato_nome[]" maxlength="120" value="<?= e($c['nome']) ?>"></label>
          <label class="campo"><span>Telefone</span><input type="tel" name="contato_telefone[]" maxlength="20" value="<?= e(formatar_celular($c['telefone'])) ?>"></label>
          <label class="campo"><span>Observação</span><input type="text" name="contato_observacao[]" maxlength="255" value="<?= e($c['observacao']) ?>"></label>
        </div>
      <?php endforeach; ?>
    </div>
    <datalist id="papeis-contato"><?php foreach ($papeisContato as $pc): ?><option value="<?= e($pc) ?>"><?php endforeach; ?></datalist>
  </fieldset>

  <fieldset>
    <legend><span>05</span> Grupo</legend>
    <div class="grade-campos">
      <label class="campo"><span>Perfil do grupo</span>
        <input type="text" name="perfil_grupo" maxlength="120" value="<?= e($val('perfil_grupo')) ?>" placeholder="Ex.: alunos do 8º ano, terceira idade"></label>
      <label class="campo"><span>Idioma do grupo</span>
        <input type="text" name="idioma_grupo" maxlength="40" value="<?= e($val('idioma_grupo')) ?>" placeholder="Português"></label>
      <label class="campo"><span>Passageiros previstos</span>
        <input type="number" name="qtd_passageiros" min="0" max="9999" inputmode="numeric" value="<?= e($val('qtd_passageiros')) ?>"></label>
      <label class="campo"><span>Uniforme</span><input type="text" name="uniforme" maxlength="200" value="<?= e($val('uniforme')) ?>" placeholder="Ex.: camiseta 645 e calça escura"></label>
      <label class="campo"><span>Alimentação</span><input type="text" name="alimentacao" maxlength="200" value="<?= e($val('alimentacao')) ?>" placeholder="Ex.: almoço incluso com o grupo"></label>
    </div>
  </fieldset>

  <fieldset>
    <legend><span>06</span> Roteiro e orientações</legend>
    <label class="campo"><span>Roteiro</span>
      <textarea name="roteiro" rows="6" placeholder="Horário a horário, atrativos e paradas"><?= e($val('roteiro')) ?></textarea></label>
    <label class="campo"><span>Regras e orientações</span>
      <textarea name="regras" rows="4" placeholder="Postura, pontualidade, fotos, o que fazer em caso de atraso"><?= e($val('regras')) ?></textarea></label>
    <label class="campo"><span>Observações</span>
      <textarea name="observacoes" rows="3"><?= e($val('observacoes')) ?></textarea></label>
  </fieldset>

  <fieldset>
    <legend><span>07</span> Pagamento e nota fiscal</legend>
    <div class="grade-campos">
      <label class="campo"><span>Prazo para enviar a NF (dias úteis)</span>
        <input type="number" name="prazo_nf_dias_uteis" min="0" max="30" value="<?= e($val('prazo_nf_dias_uteis', '3')) ?>"></label>
      <label class="campo"><span>Pagamento após a NF (dias)</span>
        <input type="number" name="prazo_pagamento_dias" min="0" max="120" value="<?= e($val('prazo_pagamento_dias', '30')) ?>"></label>
      <label class="campo campo-largo"><span>Instruções para a nota fiscal</span>
        <textarea name="instrucoes_nf" rows="3" placeholder="Dados da 645 Turismo, o que escrever na discriminação"><?= e($val('instrucoes_nf')) ?></textarea></label>
    </div>
  </fieldset>

  <?php if ($nova): ?>
    <fieldset>
      <legend><span>08</span> Dias de trabalho</legend>
      <label class="opcao"><input type="checkbox" name="gerar_diarias" value="1" <?= antigo('gerar_diarias', '1') ? 'checked' : '' ?>>
        <span>Criar um dia de trabalho para cada data, de ida até a volta, com os horários acima (dá para ajustar depois)</span></label>
      <?php if (!empty($copia)): ?>
        <label class="opcao"><input type="checkbox" name="copiar_vagas" value="1" <?= antigo('copiar_vagas', '1') ? 'checked' : '' ?>>
          <span>Copiar as vagas por função e o valor da diária<?= $vagasCopia ? ': ' . e(implode(', ', array_map(fn($x) => $x['vagas'] . ' × ' . $x['nome'] . ' (' . formatar_moeda($x['valor_diaria']) . ')', $vagasCopia))) : ' (a viagem original não tem vagas)' ?></span></label>
      <?php endif; ?>
    </fieldset>
  <?php endif; ?>

  <div class="form-rodape">
    <button type="submit" class="btn btn-primario"><?= $nova ? 'Criar como rascunho' : 'Salvar alterações' ?></button>
  </div>
</form>
