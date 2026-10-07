<?php
// Mapa de rotas: [método, caminho, função]. Todo POST passa pela verificação de CSRF no roteador.

require RAIZ . '/app/controllers/publico.php';
require RAIZ . '/app/controllers/guia.php';
require RAIZ . '/app/controllers/admin.php';
require RAIZ . '/app/controllers/admin_viagens.php';
require RAIZ . '/app/controllers/admin_funcoes.php';
require RAIZ . '/app/controllers/admin_passageiros.php';
require RAIZ . '/app/controllers/instalar.php';
require RAIZ . '/app/controllers/cadastro.php';
require RAIZ . '/app/controllers/arquivos.php';
require RAIZ . '/app/controllers/guia_perfil.php';
require RAIZ . '/app/controllers/guia_disponibilidade.php';
require RAIZ . '/app/controllers/guia_recebimentos.php';
require RAIZ . '/app/controllers/guia_ajuda.php';
require RAIZ . '/app/controllers/admin_guias.php';
require RAIZ . '/app/controllers/admin_pre_cadastro.php';
require RAIZ . '/app/controllers/admin_financeiro.php';
require RAIZ . '/app/controllers/admin_atendimento.php';

return [
  // Área pública
  ['GET', '/', 'pub_inicio'],
  ['POST', '/entrar', 'pub_entrar'],
  ['GET', '/esqueci-senha', 'pub_esqueci_senha'],
  ['POST', '/esqueci-senha', 'pub_esqueci_senha_enviar'],
  ['GET', '/redefinir-senha', 'pub_redefinir_senha'],
  ['POST', '/redefinir-senha', 'pub_redefinir_senha_salvar'],
  ['GET', '/primeiro-acesso', 'pub_primeiro_acesso'],
  ['POST', '/primeiro-acesso', 'pub_primeiro_acesso_salvar'],
  ['GET', '/cadastro', 'pub_cadastro'],
  ['GET', '/cadastro/{etapa}', 'pub_cadastro_etapa'],
  ['POST', '/cadastro/{etapa}', 'pub_cadastro_salvar'],
  ['POST', '/status', 'pub_status_consultar'],
  ['GET', '/termos', 'pub_termos'],
  ['GET', '/privacidade', 'pub_privacidade'],

  // Arquivos (com conferência de permissão)
  ['GET', '/arquivos/foto/{chave}', 'arq_foto'],
  ['GET', '/arquivos/documento/{chave}', 'arq_documento'],
  ['GET', '/arquivos/envio/{chave}', 'arq_envio'],
  ['GET', '/arquivos/comprovante/{chave}', 'arq_comprovante'],
  ['GET', '/arquivos/anexo/{chave}', 'arq_anexo'],
  ['GET', '/status', 'pub_status'],
  ['GET', '/instalar', 'instalar_form'],
  ['POST', '/instalar', 'instalar_executar'],

  // Área do Guia, na ordem do trabalho: hoje -> viagens/tours -> disponibilidade -> recebimentos
  ['GET', '/guia', 'guia_raiz'],
  ['GET', '/guia/hoje', 'guia_hoje'],
  ['GET', '/guia/viagens', 'guia_viagens'],
  ['GET', '/guia/viagens/{id}', 'guia_viagem'],
  ['POST', '/guia/viagens/{id}/responder', 'guia_viagem_responder'],
  ['POST', '/guia/viagens/{id}/relatorio', 'guia_viagem_relatorio'],
  ['GET', '/guia/viagens/{id}/passageiros', 'guia_passageiros'],
  ['GET', '/guia/viagens/{id}/passageiros/dados', 'guia_passageiros_dados'],
  ['POST', '/guia/viagens/{id}/passageiros/{p}/marcar', 'guia_passageiro_marcar'],
  ['POST', '/guia/viagens/{id}/passageiros/{p}/editar', 'guia_passageiro_editar'],
  ['POST', '/guia/viagens/{id}/passageiros/incluir', 'guia_passageiro_incluir'],
  ['GET', '/guia/disponibilidade', 'guia_disponibilidade'],
  ['POST', '/guia/disponibilidade', 'guia_disponibilidade_salvar'],
  ['POST', '/guia/disponibilidade/{id}/remover', 'guia_disponibilidade_remover'],
  ['GET', '/guia/recebimentos', 'guia_recebimentos'],
  ['POST', '/guia/recebimentos/{id}/nf', 'guia_recebimentos_nf'],
  ['GET', '/guia/perfil', 'guia_perfil'],
  ['POST', '/guia/perfil/pessoais', 'guia_perfil_pessoais'],
  ['POST', '/guia/perfil/atuacao', 'guia_perfil_atuacao'],
  ['POST', '/guia/perfil/documentos', 'guia_perfil_documentos'],
  ['POST', '/guia/perfil/recebimento', 'guia_perfil_recebimento'],
  ['POST', '/guia/perfil/senha', 'guia_perfil_senha'],
  ['POST', '/guia/perfil/reenviar', 'guia_perfil_reenviar'],
  ['GET', '/guia/ajuda', 'guia_ajuda'],
  ['POST', '/guia/ajuda/chamados', 'guia_chamado_criar'],
  ['GET', '/guia/ajuda/chamados/{id}', 'guia_chamado'],
  ['POST', '/guia/ajuda/chamados/{id}/responder', 'guia_chamado_responder'],
  ['POST', '/guia/ajuda/chamados/{id}/resolvido', 'guia_chamado_resolvido'],
  ['POST', '/guia/tour-visto', 'guia_tour_visto'],
  ['POST', '/guia/sair', 'guia_sair'],

  // Painel ADM, na ordem da operação
  ['GET', '/admin', 'adm_login'],
  ['POST', '/admin/entrar', 'adm_entrar'],
  ['GET', '/admin/esqueci-senha', 'adm_esqueci_senha'],
  ['POST', '/admin/esqueci-senha', 'adm_esqueci_senha_enviar'],
  ['GET', '/admin/redefinir-senha', 'adm_redefinir_senha'],
  ['POST', '/admin/redefinir-senha', 'adm_redefinir_senha_salvar'],
  ['GET', '/admin/hoje', 'adm_hoje'],

  ['GET', '/admin/viagens', 'adm_viagens'],
  ['GET', '/admin/viagens/nova', 'adm_viagem_nova'],
  ['POST', '/admin/viagens/nova', 'adm_viagem_criar'],
  ['GET', '/admin/viagens/{id}', 'adm_viagem'],
  ['GET', '/admin/viagens/{id}/editar', 'adm_viagem_editar'],
  ['POST', '/admin/viagens/{id}/editar', 'adm_viagem_salvar'],
  ['POST', '/admin/viagens/{id}/status', 'adm_viagem_status'],
  ['POST', '/admin/viagens/{id}/diarias', 'adm_viagem_diaria_criar'],
  ['POST', '/admin/viagens/{id}/diarias/{d}/remover', 'adm_viagem_diaria_remover'],
  ['POST', '/admin/viagens/{id}/vagas', 'adm_viagem_vaga_criar'],
  ['POST', '/admin/viagens/{id}/vagas/{v}/remover', 'adm_viagem_vaga_remover'],
  ['POST', '/admin/viagens/{id}/convidar', 'adm_viagem_convidar'],
  ['POST', '/admin/viagens/{id}/escalas/{e}/cancelar', 'adm_viagem_escala_cancelar'],
  ['GET', '/admin/viagens/{id}/passageiros', 'adm_viagem_passageiros'],
  ['GET', '/admin/viagens/{id}/passageiros/dados', 'adm_passageiros_dados'],
  ['POST', '/admin/viagens/{id}/passageiros', 'adm_passageiro_criar'],
  ['POST', '/admin/viagens/{id}/passageiros/importar', 'adm_passageiros_importar'],
  ['GET', '/admin/viagens/{id}/passageiros/importar', 'adm_passageiros_importar_previa'],
  ['POST', '/admin/viagens/{id}/passageiros/importar/confirmar', 'adm_passageiros_importar_confirmar'],
  ['GET', '/admin/passageiros/modelo.csv', 'adm_passageiros_modelo'],
  ['POST', '/admin/viagens/{id}/passageiros/{p}/marcar', 'adm_passageiro_marcar'],
  ['POST', '/admin/viagens/{id}/passageiros/{p}/remover', 'adm_passageiro_remover'],
  ['POST', '/admin/viagens/{id}/passageiros/{p}/editar', 'adm_passageiro_editar'],
  ['POST', '/admin/viagens/{id}/passageiros/{p}/incorporar', 'adm_passageiro_incorporar'],

  ['GET', '/admin/funcoes', 'adm_funcoes'],
  ['POST', '/admin/funcoes', 'adm_funcao_criar'],
  ['GET', '/admin/funcoes/{id}', 'adm_funcao_editar'],
  ['POST', '/admin/funcoes/{id}', 'adm_funcao_salvar'],
  ['POST', '/admin/funcoes/{id}/status', 'adm_funcao_status'],

  ['GET', '/admin/guias', 'adm_guias'],
  ['GET', '/admin/guias/pre-cadastro', 'adm_pre_cadastro'],
  ['POST', '/admin/guias/pre-cadastro', 'adm_pre_cadastro_ler'],
  ['POST', '/admin/guias/pre-cadastro/confirmar', 'adm_pre_cadastro_confirmar'],
  ['POST', '/admin/guias/pre-cadastro/cancelar', 'adm_pre_cadastro_cancelar'],
  ['GET', '/admin/guias/pre-cadastro/modelo.csv', 'adm_pre_cadastro_modelo'],
  ['POST', '/admin/guias/{id}/reenviar-acesso', 'adm_guia_reenviar_acesso'],
  ['GET', '/admin/guias/{id}', 'adm_guia'],
  ['POST', '/admin/guias/{id}/status', 'adm_guia_status'],
  ['POST', '/admin/guias/{id}/notas', 'adm_guia_notas'],
  ['POST', '/admin/guias/{id}/documentos/{d}', 'adm_guia_documento'],
  ['POST', '/admin/guias/{id}/senha', 'adm_guia_senha'],
  ['GET', '/admin/conferencia', 'adm_conferencia'],
  ['POST', '/admin/conferencia/{id}', 'adm_conferencia_decidir'],
  ['GET', '/admin/pagamentos', 'adm_pagamentos'],
  ['POST', '/admin/pagamentos/registrar', 'adm_pagamento_registrar'],
  ['GET', '/admin/pagamentos/exportar.csv', 'adm_pagamentos_exportar'],
  ['GET', '/admin/atendimento', 'adm_atendimento'],
  ['GET', '/admin/atendimento/{id}', 'adm_chamado'],
  ['POST', '/admin/atendimento/{id}/responder', 'adm_chamado_responder'],
  ['POST', '/admin/avisos', 'adm_aviso_criar'],
  ['POST', '/admin/avisos/{id}/status', 'adm_aviso_status'],
  ['POST', '/admin/avisos/{id}/remover', 'adm_aviso_remover'],
  ['GET', '/admin/conteudo', 'adm_conteudo'],
  ['POST', '/admin/conteudo', 'adm_conteudo_criar'],
  ['POST', '/admin/conteudo/{id}', 'adm_conteudo_salvar'],
  ['POST', '/admin/conteudo/{id}/status', 'adm_conteudo_status'],
  ['POST', '/admin/conteudo/{id}/remover', 'adm_conteudo_remover'],
  ['GET', '/admin/equipe', 'adm_equipe'],
  ['GET', '/admin/diagnostico', 'adm_diagnostico'],
  ['POST', '/admin/equipe', 'adm_equipe_criar'],
  ['POST', '/admin/equipe/{id}/status', 'adm_equipe_status'],
  ['POST', '/admin/sair', 'adm_sair'],
];
