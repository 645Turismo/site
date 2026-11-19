-- Fila de reenvio: e-mails que o servidor recusou com erro SMTP 451 (temporário) são guardados aqui
-- e reenviados depois de 15 minutos (app/lib/email.php). A linha some quando o e-mail é entregue ou a tentativa se esgota.
CREATE TABLE emails_pendentes (
  id {{PK}},
  para VARCHAR(190) NOT NULL,
  assunto VARCHAR(255) NOT NULL,
  html TEXT NOT NULL,
  tentativas SMALLINT NOT NULL DEFAULT 1,
  proxima_tentativa_em DATETIME NOT NULL,
  ultimo_erro VARCHAR(500) NULL,
  criado_em DATETIME NOT NULL
) {{TABELA}};
CREATE INDEX ix_emails_pendentes_proxima ON emails_pendentes (proxima_tentativa_em);
