-- Função de estagiário (vem do formulário "Faça parte do time 645 Turismo").
INSERT INTO funcoes (nome, slug, descricao, exige_cadastur, exige_idioma, ativo, ordem) VALUES ('Estagiário de Guia', 'estagiario', 'Apoia o guia responsável enquanto conclui a formação.', 0, 0, 1, 6);

-- Alimentação e saúde do guia (para a operação: refeições e cuidados em viagem). Só a equipe e o próprio guia veem.
ALTER TABLE guias ADD COLUMN restricao_alimentar VARCHAR(20) NULL;
ALTER TABLE guias ADD COLUMN doencas_preexistentes VARCHAR(500) NULL;
