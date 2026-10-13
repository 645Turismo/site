-- Situação de cada dia: trabalho (normal) | folga ("Sem trabalho no dia") | sobreaviso.
ALTER TABLE diarias ADD COLUMN situacao VARCHAR(12) NOT NULL DEFAULT 'trabalho';

-- Funções que podem ser alocadas sem custo (sem diária nem valor fechado), como a coordenação.
ALTER TABLE funcoes ADD COLUMN sem_custo SMALLINT NOT NULL DEFAULT 0;
INSERT INTO funcoes (nome, slug, descricao, exige_cadastur, exige_idioma, ativo, ordem) VALUES ('Coordenador(a)', 'coordenador', 'Coordena a operação e a equipe em campo. Pode ser alocado sem custo.', 0, 0, 1, 9);
UPDATE funcoes SET sem_custo = 1 WHERE slug = 'coordenador';
