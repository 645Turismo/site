-- Várias origens (pontos de embarque) por viagem/tour, cada uma com horário de saída opcional.
-- A primeira origem também fica em viagens.origem, usada nos resumos "origem → destino".
CREATE TABLE viagem_origens (
  id {{PK}},
  viagem_id INTEGER NOT NULL,
  local VARCHAR(160) NOT NULL,
  horario VARCHAR(5) NULL,
  ordem SMALLINT NOT NULL DEFAULT 0,
  FOREIGN KEY (viagem_id) REFERENCES viagens (id)
) {{TABELA}};
CREATE INDEX ix_viagem_origens ON viagem_origens (viagem_id, ordem);

-- Viagens já cadastradas passam a ter a origem atual como primeira da lista.
INSERT INTO viagem_origens (viagem_id, local, horario, ordem)
  SELECT id, origem, horario_saida, 0 FROM viagens WHERE origem IS NOT NULL AND origem <> '';
