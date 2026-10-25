<?php
// Aba Privacidade do perfil (LGPD). Variáveis: $g (guia).
$autorizouSaude = !empty($g['consentimento_saude_em']);
?>
<fieldset>
  <legend><span>01</span> Seus dados e seus direitos</legend>
  <p class="texto-2">A 645 Turismo cuida dos seus dados conforme a Lei Geral de Proteção de Dados (LGPD). Aqui você baixa uma cópia deles, faz pedidos à equipe e controla a autorização de dados de saúde. Veja também a <a href="/privacidade" target="_blank">política de privacidade</a>.</p>
  <p class="texto-2">Política aceita: versão <strong><?= e($g['termos_versao'] ?: 'anterior a ' . POLITICA_VERSAO) ?></strong><?= !empty($g['termos_aceitos_em']) ? ' em ' . e(formatar_data(substr((string) $g['termos_aceitos_em'], 0, 10))) : '' ?>. Versão atual: <strong><?= e(POLITICA_VERSAO) ?></strong>.</p>
</fieldset>

<fieldset>
  <legend><span>02</span> Cópia dos meus dados</legend>
  <p class="texto-2">Baixe um arquivo (JSON) com o que a 645 Turismo guarda sobre você: cadastro, funções, idiomas, dados de pagamento, escalas, relatórios, pagamentos e chamados. Não inclui a senha.</p>
  <form method="post" action="/guia/perfil/privacidade/baixar" class="inline-form"><?= csrf_campo() ?>
    <button type="submit" class="btn btn-secundario">Baixar meus dados</button></form>
</fieldset>

<fieldset>
  <legend><span>03</span> Dados de saúde</legend>
  <?php if ($autorizouSaude): ?>
    <p class="texto-2">Você autorizou o tratamento dos seus dados de saúde em <?= e(formatar_data(substr((string) $g['consentimento_saude_em'], 0, 10))) ?>. Eles ficam visíveis só para a equipe da 645 e servem para cuidar de você nas viagens.</p>
    <form method="post" action="/guia/perfil/privacidade/revogar-saude" class="inline-form" data-confirmar="Revogar a autorização? Deficiência, restrição alimentar e doenças informadas serão apagadas do seu cadastro."><?= csrf_campo() ?>
      <button type="submit" class="btn btn-secundario">Revogar autorização e apagar esses dados</button></form>
  <?php else: ?>
    <p class="texto-2">Você não registrou autorização para dados de saúde, então a 645 não guarda esse tipo de informação sobre você.</p>
  <?php endif; ?>
</fieldset>

<fieldset>
  <legend><span>04</span> Fazer um pedido à equipe</legend>
  <p class="texto-2">Você pode pedir acesso, correção, portabilidade, informações sobre compartilhamento ou a exclusão dos seus dados. Respondemos em até 15 dias, pelo chamado. Registros que a lei obriga a guardar (por exemplo, fiscais) são mantidos sem identificar você depois da exclusão.</p>
  <form method="post" action="/guia/perfil/privacidade/solicitar" class="form" novalidate><?= csrf_campo() ?>
    <div class="grade-campos">
      <label class="campo"><span>O que você precisa? *</span>
        <select name="tipo" required>
          <?php foreach (PEDIDOS_PRIVACIDADE as $chave => $rotulo): ?><option value="<?= e($chave) ?>"><?= e($rotulo) ?></option><?php endforeach; ?>
        </select></label>
      <label class="campo campo-largo"><span>Detalhes (opcional)</span>
        <textarea name="detalhe" rows="3" maxlength="2000"></textarea></label>
    </div>
    <div class="form-rodape"><button type="submit" class="btn btn-primario">Enviar pedido</button></div>
  </form>
</fieldset>
