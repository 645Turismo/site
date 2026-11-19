-- Cor da pulseira de identificação do passageiro (definida pelo guia ou pela coordenação)
ALTER TABLE passageiros ADD COLUMN pulseira VARCHAR(12) NULL;
