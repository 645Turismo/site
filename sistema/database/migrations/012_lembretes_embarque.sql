-- Lembretes de embarque já enviados (um por guia e viagem), para não repetir o e-mail de 12 h antes.
CREATE TABLE lembretes_embarque (
  id {{PK}},
  viagem_id INTEGER NOT NULL,
  guia_id INTEGER NOT NULL,
  embarque_em DATETIME NOT NULL,
  criado_em DATETIME NOT NULL,
  FOREIGN KEY (viagem_id) REFERENCES viagens (id),
  FOREIGN KEY (guia_id) REFERENCES guias (id)
) {{TABELA}};
CREATE UNIQUE INDEX ux_lembretes_embarque ON lembretes_embarque (viagem_id, guia_id);
