-- Histórico de e-mails enviados pelo sistema, para conferir no Diagnóstico se uma mensagem saiu.
-- status: enviado (aceito pelo servidor de e-mail) | falhou | teste (ambiente local, gravado em arquivo)
CREATE TABLE emails_enviados (
  id {{PK}},
  para VARCHAR(190) NOT NULL,
  assunto VARCHAR(255) NOT NULL,
  status VARCHAR(10) NOT NULL,
  erro VARCHAR(500) NULL,
  criado_em DATETIME NOT NULL
) {{TABELA}};
CREATE INDEX ix_emails_enviados_data ON emails_enviados (criado_em);
