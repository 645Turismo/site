<?php
$empresa = configuracao('empresa_razao_social', '645 TURISMO CONSULTORIA E SERVICOS LTDA');
$cnpj = formatar_cnpj(configuracao('empresa_cnpj', '48925512000195'));
$contato = email_equipe();
?>
<article class="legal">
  <p class="sobretitulo">Área do Prestador · 645 Turismo</p>
  <h1 class="cadastro-titulo"><?= e($titulo) ?></h1>
  <p class="texto-2">Versão <?= e(POLITICA_VERSAO) ?>. <?= e($empresa) ?>, CNPJ <?= e($cnpj) ?>.</p>

  <?php if ($documento === 'termos'): ?>
    <h2>1. O que é a Área do Prestador</h2>
    <p>Sistema da 645 Turismo para credenciar guias, recepcionistas, comissários(as) de bordo e demais prestadores, enviar convites para viagens, tours e serviços, compartilhar briefing e lista de passageiros, receber relatórios e notas fiscais e acompanhar pagamentos.</p>
    <h2>2. Cadastro</h2>
    <p>Você se compromete a informar dados verdadeiros e mantê-los atualizados, incluindo documentos, Cadastur e dados para pagamento. O cadastro passa por análise e pode ser aprovado, devolvido para ajuste ou recusado.</p>
    <h2>3. Acesso</h2>
    <p>O acesso é pessoal e intransferível, com CPF e senha. Não compartilhe sua senha. Em caso de suspeita de uso indevido, troque a senha e avise a equipe.</p>
    <h2>4. Convites e escalas</h2>
    <p>Estar cadastrado não garante trabalho. Os convites dependem das necessidades de cada viagem ou tour, da sua disponibilidade, função e idiomas. Ao aceitar um convite, você se compromete a comparecer no horário de apresentação e seguir as orientações do briefing. Imprevistos devem ser avisados o quanto antes pela Ajuda.</p>
    <h2>5. Lista de passageiros</h2>
    <p>Os dados dos passageiros são confidenciais e devem ser usados apenas para a operação da viagem: conferência no embarque, check-in, check-out e contato em caso de necessidade. É proibido copiar, fotografar, divulgar ou usar esses dados para qualquer outro fim, inclusive depois da viagem. Por proteção de dados, a lista só fica disponível no sistema até o dia seguinte ao fim da viagem. Ajustes feitos por você na lista ficam registrados e são comunicados à coordenação.</p>
    <h2>6. Relatórios, notas fiscais e pagamentos</h2>
    <p>As diárias são pagas mediante relatório da viagem e nota fiscal emitida pelo seu CNPJ, nos prazos informados em cada viagem ou tour. Faltas não são remuneradas.</p>
    <h2>7. Suspensão</h2>
    <p>A 645 Turismo pode suspender ou encerrar o acesso em caso de dados falsos, uso indevido de informações, faltas sem aviso ou descumprimento destes termos.</p>
    <p class="texto-2">O tratamento dos seus dados pessoais está descrito na <a href="/privacidade">política de privacidade</a>.</p>
  <?php else: ?>
    <p>Esta política explica, em linguagem direta, como a 645 Turismo trata os dados pessoais de quem se cadastra na Área do Prestador, conforme a Lei Geral de Proteção de Dados (Lei 13.709/2018, a LGPD).</p>

    <h2>1. Quem é responsável</h2>
    <p><strong>Controladora:</strong> <?= e($empresa) ?>, CNPJ <?= e($cnpj) ?>. <strong>Canal do encarregado pelo tratamento de dados (art. 41 da LGPD) e de pedidos de titulares:</strong> <a href="mailto:<?= e($contato) ?>"><?= e($contato) ?></a>, ou o menu Perfil → Privacidade, depois de entrar no sistema.</p>

    <h2>2. Quais dados tratamos</h2>
    <ul>
      <li><strong>Cadastro:</strong> nome, nome social, CPF, data de nascimento, gênero, nacionalidade, celular, e-mail, tamanho de camiseta e endereço.</li>
      <li><strong>Profissionais:</strong> funções, idiomas, Cadastur, especialidades, apresentação e disponibilidade.</li>
      <li><strong>Empresa e pagamento:</strong> CNPJ, razão social, banco, agência, conta e chave PIX.</li>
      <li><strong>Documentos:</strong> arquivos que a equipe pedir para conferir o cadastro (quando o envio estiver ativo).</li>
      <li id="saude"><strong>Dados de saúde (sensíveis):</strong> se você informar deficiência, restrição alimentar ou doença/cuidado de saúde. Só tratamos com a sua <strong>autorização específica e destacada</strong> (art. 11, I, da LGPD), registrada com data. São usados apenas para cuidar de você e adaptar a operação das viagens, e só a equipe da 645 acessa. Informar é opcional, e você pode revogar a qualquer momento em Perfil → Privacidade, o que apaga esses dados.</li>
      <li><strong>Trabalho:</strong> convites, escalas, check-ins, relatórios, notas fiscais, pagamentos e chamados.</li>
      <li><strong>Acesso:</strong> senha (guardada só como código irreversível), data do último acesso, endereço IP e registros de ações importantes (auditoria).</li>
      <li><strong>Passageiros:</strong> ao trabalhar numa viagem, você acessa dados dos passageiros (nome, documento, nascimento, telefone, observações). Esses dados pertencem à 645, que é a controladora deles, e você os usa só como pessoa autorizada para a operação (veja os Termos de uso).</li>
    </ul>

    <h2>3. Para que usamos e em que base legal</h2>
    <ul>
      <li><strong>Credenciar, conferir documentos, montar escalas, enviar convites e operar as viagens:</strong> execução de contrato e procedimentos preliminares a pedido do titular (art. 7, V).</li>
      <li><strong>Pagar diárias e guardar notas fiscais e registros:</strong> cumprimento de obrigação legal e regulatória, fiscal e do turismo (art. 7, II).</li>
      <li><strong>Segurança do sistema, prevenção a fraudes e organização da operação:</strong> legítimo interesse (art. 7, IX), com o mínimo de dados necessário.</li>
      <li><strong>Dados de saúde:</strong> consentimento específico (art. 11, I).</li>
      <li><strong>E-mails de convite, lembrete de embarque e avisos do trabalho:</strong> execução do contrato. Não enviamos propaganda.</li>
    </ul>

    <h2>4. Com quem compartilhamos</h2>
    <p>Somente com quem precisa para a operação: equipe interna da 645 Turismo; instituições financeiras para os pagamentos; autoridades quando a lei exigir; e provedores de tecnologia que operam o sistema em nosso nome (hospedagem e banco de dados no Brasil, na Locaweb, e serviço de envio de e-mails). Seus dados não são vendidos nem usados para publicidade.</p>
    <p>Serviços de terceiros que o seu navegador acessa: <strong>ViaCEP</strong> (recebe só o CEP que você digita, para preencher o endereço) e <strong>Google Fonts</strong> (recebe o endereço IP do seu navegador para entregar as fontes de letra, com transferência internacional aos EUA). Vídeos da Ajuda podem vir do YouTube (modo sem cookies) ou do Vimeo.</p>

    <h2 id="cookies">5. Cookies</h2>
    <p>Usamos <strong>um único cookie, essencial</strong>: <code>sis645</code>, que mantém você conectado e protege os formulários contra fraude. Ele expira quando você fecha o navegador ou depois de um período sem uso, é marcado como seguro e inacessível a scripts. <strong>Não usamos cookies de publicidade, estatística ou rastreamento</strong>, por isso este sistema não exibe faixa de consentimento: cookies estritamente necessários dispensam consentimento.</p>

    <h2>6. Por quanto tempo guardamos</h2>
    <ul>
      <li><strong>Cadastro de prestador:</strong> enquanto houver relação com a 645 Turismo e, depois, pelos prazos legais (por exemplo, registros fiscais por 5 anos). Em seguida os dados são anonimizados.</li>
      <li><strong>Cadastro começado e não enviado:</strong> apagado após <?= RETENCAO_RASCUNHO_DIAS ?> dias sem atividade.</li>
      <li><strong>Dados dos passageiros:</strong> apagados <?= RETENCAO_PASSAGEIROS_DIAS ?> dias depois do fim da viagem ou tour (incluindo listas anexadas). O guia só vê a lista até o dia seguinte ao fim da viagem.</li>
      <li><strong>Localização registrada em envios:</strong> apagada após <?= RETENCAO_LOCALIZACAO_DIAS ?> dias.</li>
      <li><strong>Histórico de e-mails enviados:</strong> <?= RETENCAO_EMAILS_DIAS ?> dias. <strong>Links de senha e tentativas de login:</strong> <?= RETENCAO_TOKENS_DIAS ?> dias.</li>
      <li><strong>Registros de auditoria (acessos e ações):</strong> 24 meses.</li>
    </ul>

    <h2>7. Como protegemos</h2>
    <p>Conexão criptografada (HTTPS); senhas guardadas com criptografia irreversível; bloqueio após tentativas de login incorretas; documentos em área restrita do servidor, acessíveis só com login e por endereço aleatório; CPF mascarado nas listagens; cada prestador vê apenas os próprios dados; consultas da equipe a dados sensíveis ficam registradas. Em caso de incidente que possa causar risco relevante, comunicaremos os titulares e a ANPD, conforme a lei.</p>

    <h2>8. Seus direitos</h2>
    <p>Você pode, a qualquer momento: confirmar que tratamos seus dados; acessar e baixar uma cópia (Perfil → Privacidade → Baixar meus dados); corrigir dados incompletos ou desatualizados (Perfil); pedir anonimização, bloqueio ou eliminação de dados desnecessários ou tratados fora da lei; pedir portabilidade; saber com quem compartilhamos; revogar consentimentos (como o de saúde); e se opor a um tratamento. Pedidos são feitos em Perfil → Privacidade ou por <a href="mailto:<?= e($contato) ?>"><?= e($contato) ?></a>, e respondemos em até <strong>15 dias</strong>. Registros que a lei obriga a guardar (como fiscais) são mantidos, sem identificar você, depois da eliminação.</p>
    <p>Se achar que seus direitos não foram respeitados, você pode reclamar à Autoridade Nacional de Proteção de Dados (ANPD): <a href="https://www.gov.br/anpd" target="_blank" rel="noopener">gov.br/anpd</a>.</p>

    <h2>9. Menores e mudanças</h2>
    <p>A Área do Prestador é destinada a maiores de 16 anos. Se alterarmos esta política de forma relevante, avisaremos no sistema e a nova versão passará a constar em Perfil → Privacidade.</p>
  <?php endif; ?>
  <p class="espaco-topo"><a href="/" class="link-sublinhado">Voltar ao início</a></p>
</article>
