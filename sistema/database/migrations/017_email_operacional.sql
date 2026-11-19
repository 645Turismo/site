-- Todos os avisos e contatos passam de contato@ para operacional@645turismo.com.br
UPDATE configuracoes SET valor = 'operacional@645turismo.com.br' WHERE chave = 'email_alertas_lista' AND valor = 'contato@645turismo.com.br';
UPDATE orientacoes SET conteudo = REPLACE(conteudo, 'contato@645turismo.com.br', 'operacional@645turismo.com.br') WHERE conteudo LIKE '%contato@645turismo.com.br%';
