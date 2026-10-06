<?php
$empresa = configuracao('empresa_razao_social', '645 TURISMO CONSULTORIA E SERVICOS LTDA');
$cnpj = formatar_cnpj(configuracao('empresa_cnpj', '48925512000195'));
$contato = email_equipe();
?>
<article class="legal">
  <p class="sobretitulo">Área do Guia · 645 Turismo</p>
  <h1 class="cadastro-titulo"><?= e($titulo) ?></h1>
  <p class="texto-2">Versão de <?= e(date('m/Y')) ?>. <?= e($empresa) ?>, CNPJ <?= e($cnpj) ?>.</p>

  <?php if ($documento === 'termos'): ?>
    <h2>1. O que é a Área do Guia</h2>
    <p>Sistema da 645 Turismo para credenciar guias e profissionais de turismo, enviar convites para viagens e tours, compartilhar briefing e lista de passageiros, receber relatórios e notas fiscais e acompanhar pagamentos.</p>
    <h2>2. Cadastro</h2>
    <p>Você se compromete a informar dados verdadeiros e mantê-los atualizados, incluindo documentos, Cadastur e dados para pagamento. O cadastro passa por análise e pode ser aprovado, devolvido para ajuste ou recusado.</p>
    <h2>3. Acesso</h2>
    <p>O acesso é pessoal e intransferível, com CPF e senha. Não compartilhe sua senha. Em caso de suspeita de uso indevido, troque a senha e avise a equipe.</p>
    <h2>4. Convites e escalas</h2>
    <p>Estar cadastrado não garante trabalho. Os convites dependem das necessidades de cada viagem ou tour, da sua disponibilidade, função, idiomas e região. Ao aceitar um convite, você se compromete a comparecer no horário de apresentação e seguir as orientações do briefing. Imprevistos devem ser avisados o quanto antes pela Ajuda.</p>
    <h2>5. Lista de passageiros</h2>
    <p>Os dados dos passageiros são confidenciais e devem ser usados apenas para a operação da viagem: conferência no embarque, check-in, check-out e contato em caso de necessidade. É proibido copiar, divulgar ou usar esses dados para qualquer outro fim. Ajustes feitos por você na lista ficam registrados e são comunicados à coordenação.</p>
    <h2>6. Relatórios, notas fiscais e pagamentos</h2>
    <p>As diárias são pagas mediante relatório da viagem e nota fiscal emitida pelo seu CNPJ, nos prazos informados em cada viagem ou tour. Faltas não são remuneradas.</p>
    <h2>7. Suspensão</h2>
    <p>A 645 Turismo pode suspender ou encerrar o acesso em caso de dados falsos, uso indevido de informações, faltas sem aviso ou descumprimento destes termos.</p>
  <?php else: ?>
    <h2>1. Quais dados tratamos</h2>
    <p>Dados de cadastro (nome, CPF, nascimento, contatos, endereço, foto), dados profissionais (funções, idiomas, Cadastur, regiões, disponibilidade), documentos enviados, dados de empresa e de pagamento (CNPJ, chave PIX, conta), registros de trabalho (escalas, relatórios, notas fiscais, pagamentos) e registros de acesso.</p>
    <h2>2. Para que usamos</h2>
    <p>Credenciamento e conferência de documentos; montagem de escalas e envio de convites; operação das viagens; pagamento das diárias e cumprimento de obrigações fiscais; comunicação com você; segurança do sistema.</p>
    <h2>3. Base legal</h2>
    <p>Execução do contrato de prestação de serviços e procedimentos preliminares, cumprimento de obrigação legal e regulatória (fiscal e de turismo) e legítimo interesse na segurança e organização da operação (Lei 13.709/2018 — LGPD).</p>
    <h2>4. Com quem compartilhamos</h2>
    <p>Somente com quem precisa para a operação: equipe interna da 645 Turismo, instituições financeiras para os pagamentos e autoridades quando exigido por lei. Seus dados não são vendidos.</p>
    <h2>5. Segurança e guarda</h2>
    <p>O acesso é protegido por senha, os documentos ficam em área restrita do servidor e as consultas da equipe a dados sensíveis são registradas. Guardamos os dados enquanto houver relação com a 645 Turismo e pelo prazo exigido por lei (por exemplo, registros fiscais).</p>
    <h2>6. Seus direitos</h2>
    <p>Você pode pedir acesso, correção, atualização, portabilidade ou exclusão dos seus dados, observados os prazos legais de guarda. Boa parte você mesmo atualiza no seu Perfil.</p>
    <h2>7. Contato</h2>
    <p>Para assuntos de privacidade, escreva para <a href="mailto:<?= e($contato) ?>"><?= e($contato) ?></a>.</p>
  <?php endif; ?>
  <p class="espaco-topo"><a href="/" class="link-sublinhado">Voltar ao início</a></p>
</article>
