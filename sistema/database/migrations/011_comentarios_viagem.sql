-- Comentários do guia durante a viagem (avisos para a equipe). O relatório formal fica no Pós-viagem.
CREATE TABLE viagem_comentarios (
  id {{PK}},
  viagem_id INTEGER NOT NULL,
  guia_id INTEGER NOT NULL,
  texto VARCHAR(2000) NOT NULL,
  criado_em DATETIME NOT NULL,
  FOREIGN KEY (viagem_id) REFERENCES viagens (id),
  FOREIGN KEY (guia_id) REFERENCES guias (id)
) {{TABELA}};
CREATE INDEX ix_viagem_comentarios ON viagem_comentarios (viagem_id, criado_em);
