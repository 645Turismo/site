-- Catálogos iniciais. Tudo aqui pode ser editado depois no Painel ADM.

INSERT INTO funcoes (nome, slug, descricao, exige_cadastur, exige_idioma, ativo, ordem) VALUES ('Guia de Turismo', 'guia', 'Conduz o grupo e apresenta os atrativos.', 1, 0, 1, 1);
INSERT INTO funcoes (nome, slug, descricao, exige_cadastur, exige_idioma, ativo, ordem) VALUES ('Guia Bilíngue', 'guia-bilingue', 'Conduz grupos em outro idioma.', 1, 1, 1, 2);
INSERT INTO funcoes (nome, slug, descricao, exige_cadastur, exige_idioma, ativo, ordem) VALUES ('Guia Acompanhante de Excursão', 'acompanhante', 'Acompanha o grupo em viagens com um ou mais dias.', 1, 0, 1, 3);
INSERT INTO funcoes (nome, slug, descricao, exige_cadastur, exige_idioma, ativo, ordem) VALUES ('Monitor de Turismo Pedagógico', 'monitor', 'Apoio a grupos escolares.', 0, 0, 1, 4);
INSERT INTO funcoes (nome, slug, descricao, exige_cadastur, exige_idioma, ativo, ordem) VALUES ('Receptivo', 'receptivo', 'Recepção em aeroporto, rodoviária ou hotel.', 0, 0, 1, 5);

INSERT INTO regioes (nome, uf, ordem, ativo) VALUES ('São Paulo — Centro', 'SP', 1, 1);
INSERT INTO regioes (nome, uf, ordem, ativo) VALUES ('São Paulo — Zona Norte', 'SP', 2, 1);
INSERT INTO regioes (nome, uf, ordem, ativo) VALUES ('São Paulo — Zona Sul', 'SP', 3, 1);
INSERT INTO regioes (nome, uf, ordem, ativo) VALUES ('São Paulo — Zona Leste', 'SP', 4, 1);
INSERT INTO regioes (nome, uf, ordem, ativo) VALUES ('São Paulo — Zona Oeste', 'SP', 5, 1);
INSERT INTO regioes (nome, uf, ordem, ativo) VALUES ('Grande São Paulo', 'SP', 6, 1);
INSERT INTO regioes (nome, uf, ordem, ativo) VALUES ('Interior de São Paulo', 'SP', 7, 1);
INSERT INTO regioes (nome, uf, ordem, ativo) VALUES ('Litoral de São Paulo', 'SP', 8, 1);
INSERT INTO regioes (nome, uf, ordem, ativo) VALUES ('Outros estados', 'BR', 9, 1);

INSERT INTO chamado_motivos (nome, ativo, ordem) VALUES ('Imprevisto / não poderei comparecer', 1, 1);
INSERT INTO chamado_motivos (nome, ativo, ordem) VALUES ('Dúvida sobre uma viagem/tour', 1, 2);
INSERT INTO chamado_motivos (nome, ativo, ordem) VALUES ('Ocorrência com o grupo', 1, 3);
INSERT INTO chamado_motivos (nome, ativo, ordem) VALUES ('Pagamento', 1, 4);
INSERT INTO chamado_motivos (nome, ativo, ordem) VALUES ('Nota fiscal', 1, 5);
INSERT INTO chamado_motivos (nome, ativo, ordem) VALUES ('Cadastro e documentos', 1, 6);
INSERT INTO chamado_motivos (nome, ativo, ordem) VALUES ('Outro assunto', 1, 7);

INSERT INTO configuracoes (chave, valor) VALUES ('empresa_razao_social', '645 TURISMO CONSULTORIA E SERVICOS LTDA');
INSERT INTO configuracoes (chave, valor) VALUES ('empresa_nome_fantasia', '645 Turismo');
INSERT INTO configuracoes (chave, valor) VALUES ('empresa_cnpj', '48925512000195');
INSERT INTO configuracoes (chave, valor) VALUES ('nf_prazo_dias_uteis_padrao', '3');
INSERT INTO configuracoes (chave, valor) VALUES ('pagamento_prazo_dias_padrao', '30');
INSERT INTO configuracoes (chave, valor) VALUES ('email_alertas_lista', 'contato@645turismo.com.br');
