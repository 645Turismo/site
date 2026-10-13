-- Além de viagens/tours, o sistema passa a ter Serviços (recepção, bordo, eventos) e, depois, Consultoria.
-- categoria: viagem | servico | consultoria. modalidade (forma de pagamento): diaria | empreitada (valor fechado pelo serviço).
ALTER TABLE viagens ADD COLUMN categoria VARCHAR(12) NOT NULL DEFAULT 'viagem';
ALTER TABLE viagens ADD COLUMN modalidade VARCHAR(12) NOT NULL DEFAULT 'diaria';
CREATE INDEX ix_viagens_categoria ON viagens (categoria, status);

-- Funções de prestadores de serviço.
INSERT INTO funcoes (nome, slug, descricao, exige_cadastur, exige_idioma, ativo, ordem) VALUES ('Recepcionista', 'recepcionista', 'Recepção, credenciamento e atendimento ao público em eventos e embarques.', 0, 0, 1, 7);
INSERT INTO funcoes (nome, slug, descricao, exige_cadastur, exige_idioma, ativo, ordem) VALUES ('Comissário(a) de bordo', 'comissario-bordo', 'Atendimento aos passageiros a bordo durante o trajeto.', 0, 0, 1, 8);
