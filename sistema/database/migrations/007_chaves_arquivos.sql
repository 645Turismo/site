-- Arquivos passam a ser servidos por uma chave aleatória (32 hex), nunca pelo id sequencial:
-- o endereço não pode ser adivinhado trocando um número. A chave é gerada na primeira exibição.
ALTER TABLE guia_documentos ADD COLUMN chave VARCHAR(32) NULL;
ALTER TABLE envios ADD COLUMN chave VARCHAR(32) NULL;
ALTER TABLE pagamentos ADD COLUMN chave VARCHAR(32) NULL;
ALTER TABLE chamado_mensagens ADD COLUMN chave VARCHAR(32) NULL;
ALTER TABLE guias ADD COLUMN foto_chave VARCHAR(32) NULL;
CREATE UNIQUE INDEX ux_guia_documentos_chave ON guia_documentos (chave);
CREATE UNIQUE INDEX ux_envios_chave ON envios (chave);
CREATE UNIQUE INDEX ux_pagamentos_chave ON pagamentos (chave);
CREATE UNIQUE INDEX ux_chamado_mensagens_chave ON chamado_mensagens (chave);
CREATE UNIQUE INDEX ux_guias_foto_chave ON guias (foto_chave);
