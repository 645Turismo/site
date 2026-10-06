-- Tipo de passageiro: adulto | crianca | colo (criança de colo, a única que pode dividir poltrona).
ALTER TABLE passageiros ADD COLUMN tipo_pax VARCHAR(10) NULL;

-- No-show: passageiro que não compareceu ao embarque naquele dia de trabalho.
ALTER TABLE passageiro_registros ADD COLUMN noshow_em DATETIME NULL;
ALTER TABLE passageiro_registros ADD COLUMN noshow_por_tipo VARCHAR(10) NULL;
ALTER TABLE passageiro_registros ADD COLUMN noshow_por_id INTEGER NULL;

-- Guia que não emite nota fiscal (sem CNPJ): recebe sem a etapa de NF.
ALTER TABLE guias ADD COLUMN emite_nf SMALLINT NOT NULL DEFAULT 1;
