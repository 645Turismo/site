-- LGPD: registro da versão da política aceita, consentimento específico para dados de saúde (art. 11)
-- e marca de passageiros já anonimizados pela rotina de retenção.
ALTER TABLE guias ADD COLUMN termos_versao VARCHAR(10) NULL;
ALTER TABLE guias ADD COLUMN consentimento_saude_em DATETIME NULL;
ALTER TABLE passageiros ADD COLUMN anonimizado_em DATETIME NULL;

-- Canal do titular: o pedido de acesso, correção, exclusão etc. vira um chamado com este motivo.
INSERT INTO chamado_motivos (nome, ativo, ordem) VALUES ('Privacidade (LGPD): acesso, correção ou exclusão dos meus dados', 1, 90);
