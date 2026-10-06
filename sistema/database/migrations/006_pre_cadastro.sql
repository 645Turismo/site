-- Pré-cadastro em massa pelo ADM: o guia recebe uma senha temporária por e-mail, troca no primeiro login
-- e completa o cadastro (status 'pre_cadastro' até concluir).
ALTER TABLE guias ADD COLUMN trocar_senha SMALLINT NOT NULL DEFAULT 0;
ALTER TABLE guias ADD COLUMN senha_temporaria_expira DATETIME NULL;
ALTER TABLE guias ADD COLUMN pre_cadastrado_por INTEGER NULL;
