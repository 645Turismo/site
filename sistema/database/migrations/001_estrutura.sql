-- Estrutura do sistema de guias da 645 Turismo.
-- Escrito num subconjunto de SQL comum a MySQL e SQLite. O executor de migrations troca:
--   {{PK}}     -> chave primária autoincremental do banco em uso
--   {{TABELA}} -> opções de tabela (InnoDB/utf8mb4 no MySQL; vazio no SQLite)
-- Datas e horas são gravadas pelo PHP no fuso America/Sao_Paulo (sem funções de data do banco).
--
-- Modelo, na linguagem do turismo:
--   guia      -> profissional cadastrado (guia, monitor, receptivo)
--   viagem    -> uma Viagem/Tour: city tour, excursão, receptivo, turismo pedagógico, trem, evento corporativo...
--   diaria    -> cada dia de trabalho de uma viagem/tour (com horário de apresentação e ponto de encontro)
--   escala    -> o guia escalado numa diária, que passa por convite, confirmação, campo, relatório, NF e pagamento

CREATE TABLE admins (
  id {{PK}},
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  senha_hash VARCHAR(255) NOT NULL,
  papel VARCHAR(20) NOT NULL DEFAULT 'coordenador',
  ativo SMALLINT NOT NULL DEFAULT 1,
  telefone VARCHAR(20) NULL,
  ultimo_login_em DATETIME NULL,
  criado_em DATETIME NOT NULL,
  atualizado_em DATETIME NULL
) {{TABELA}};
CREATE UNIQUE INDEX ux_admins_email ON admins (email);

CREATE TABLE guias (
  id {{PK}},
  codigo VARCHAR(8) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'rascunho',
  status_motivo TEXT NULL,
  cadastro_etapa SMALLINT NOT NULL DEFAULT 1,
  nome VARCHAR(160) NOT NULL,
  nome_social VARCHAR(120) NULL,
  cpf VARCHAR(11) NOT NULL,
  celular VARCHAR(20) NULL,
  email VARCHAR(190) NULL,
  nascimento DATE NULL,
  genero VARCHAR(30) NULL,
  nacionalidade VARCHAR(30) NULL,
  camiseta VARCHAR(5) NULL,
  pcd SMALLINT NOT NULL DEFAULT 0,
  pcd_descricao VARCHAR(255) NULL,
  foto_path VARCHAR(255) NULL,
  apresentacao TEXT NULL,
  especialidades VARCHAR(500) NULL,
  aceita_pernoite SMALLINT NOT NULL DEFAULT 0,
  cep VARCHAR(8) NULL,
  logradouro VARCHAR(200) NULL,
  numero VARCHAR(20) NULL,
  complemento VARCHAR(120) NULL,
  bairro VARCHAR(120) NULL,
  cidade VARCHAR(120) NULL,
  uf VARCHAR(2) NULL,
  cadastur_numero VARCHAR(40) NULL,
  cadastur_situacao VARCHAR(30) NULL,
  cadastur_uf VARCHAR(2) NULL,
  cadastur_categorias VARCHAR(255) NULL,
  cadastur_validade DATE NULL,
  cnpj VARCHAR(14) NULL,
  razao_social VARCHAR(200) NULL,
  nome_fantasia VARCHAR(200) NULL,
  senha_hash VARCHAR(255) NULL,
  termos_aceitos_em DATETIME NULL,
  aprovado_em DATETIME NULL,
  aprovado_por INTEGER NULL,
  tour_visto SMALLINT NOT NULL DEFAULT 0,
  notas_internas TEXT NULL,
  ultimo_login_em DATETIME NULL,
  anonimizado_em DATETIME NULL,
  criado_em DATETIME NOT NULL,
  atualizado_em DATETIME NULL
) {{TABELA}};
CREATE UNIQUE INDEX ux_guias_cpf ON guias (cpf);
CREATE UNIQUE INDEX ux_guias_codigo ON guias (codigo);
CREATE INDEX ix_guias_status ON guias (status);

CREATE TABLE guia_dados_bancarios (
  id {{PK}},
  guia_id INTEGER NOT NULL,
  banco_codigo VARCHAR(5) NULL,
  banco_nome VARCHAR(120) NULL,
  tipo_conta VARCHAR(20) NULL,
  agencia VARCHAR(10) NULL,
  conta VARCHAR(20) NULL,
  pix_tipo VARCHAR(20) NULL,
  pix_chave VARCHAR(140) NULL,
  atualizado_em DATETIME NULL,
  FOREIGN KEY (guia_id) REFERENCES guias (id)
) {{TABELA}};
CREATE UNIQUE INDEX ux_bancarios_guia ON guia_dados_bancarios (guia_id);

-- Funções que um guia pode exercer numa viagem/tour (catálogo editável no ADM).
CREATE TABLE funcoes (
  id {{PK}},
  nome VARCHAR(120) NOT NULL,
  slug VARCHAR(60) NOT NULL,
  descricao VARCHAR(255) NULL,
  exige_cadastur SMALLINT NOT NULL DEFAULT 0,
  exige_idioma SMALLINT NOT NULL DEFAULT 0,
  ativo SMALLINT NOT NULL DEFAULT 1,
  ordem SMALLINT NOT NULL DEFAULT 0
) {{TABELA}};
CREATE UNIQUE INDEX ux_funcoes_slug ON funcoes (slug);

CREATE TABLE guia_funcoes (
  id {{PK}},
  guia_id INTEGER NOT NULL,
  funcao_id INTEGER NOT NULL,
  FOREIGN KEY (guia_id) REFERENCES guias (id),
  FOREIGN KEY (funcao_id) REFERENCES funcoes (id)
) {{TABELA}};
CREATE UNIQUE INDEX ux_guia_funcao ON guia_funcoes (guia_id, funcao_id);

-- Idiomas em que o guia conduz grupos. nivel: intermediario | fluente | nativo
CREATE TABLE guia_idiomas (
  id {{PK}},
  guia_id INTEGER NOT NULL,
  idioma VARCHAR(40) NOT NULL,
  nivel VARCHAR(20) NOT NULL DEFAULT 'fluente',
  FOREIGN KEY (guia_id) REFERENCES guias (id)
) {{TABELA}};
CREATE UNIQUE INDEX ux_guia_idioma ON guia_idiomas (guia_id, idioma);

CREATE TABLE regioes (
  id {{PK}},
  nome VARCHAR(120) NOT NULL,
  uf VARCHAR(2) NOT NULL,
  ordem SMALLINT NOT NULL DEFAULT 0,
  ativo SMALLINT NOT NULL DEFAULT 1
) {{TABELA}};

CREATE TABLE guia_regioes (
  id {{PK}},
  guia_id INTEGER NOT NULL,
  regiao_id INTEGER NOT NULL,
  FOREIGN KEY (guia_id) REFERENCES guias (id),
  FOREIGN KEY (regiao_id) REFERENCES regioes (id)
) {{TABELA}};
CREATE UNIQUE INDEX ux_guia_regiao ON guia_regioes (guia_id, regiao_id);

-- tipo: identidade | cadastur | comprovante_residencia | cnpj | certificado (primeiros socorros, cursos) | outro
CREATE TABLE guia_documentos (
  id {{PK}},
  guia_id INTEGER NOT NULL,
  tipo VARCHAR(30) NOT NULL,
  titulo VARCHAR(120) NULL,
  arquivo_path VARCHAR(255) NOT NULL,
  nome_original VARCHAR(255) NULL,
  mime VARCHAR(80) NULL,
  tamanho INTEGER NULL,
  validade DATE NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'enviado',
  motivo TEXT NULL,
  atual SMALLINT NOT NULL DEFAULT 1,
  revisado_por INTEGER NULL,
  revisado_em DATETIME NULL,
  criado_em DATETIME NOT NULL,
  FOREIGN KEY (guia_id) REFERENCES guias (id)
) {{TABELA}};
CREATE INDEX ix_documentos_guia ON guia_documentos (guia_id, tipo, atual);

-- tipo: city_tour | excursao | receptivo | pedagogico | trem | evento | outro
-- status: rascunho | publicada | concluida | cancelada
CREATE TABLE viagens (
  id {{PK}},
  codigo VARCHAR(40) NOT NULL,
  nome VARCHAR(200) NOT NULL,
  tipo VARCHAR(20) NOT NULL DEFAULT 'city_tour',
  cliente VARCHAR(200) NULL,
  perfil_grupo VARCHAR(120) NULL,
  idioma_grupo VARCHAR(40) NULL,
  qtd_passageiros SMALLINT NULL,
  origem VARCHAR(160) NULL,
  destino VARCHAR(160) NULL,
  horario_apresentacao VARCHAR(5) NULL,
  horario_saida VARCHAR(5) NULL,
  horario_saida_destino VARCHAR(5) NULL,
  previsao_chegada VARCHAR(5) NULL,
  com_pernoite SMALLINT NOT NULL DEFAULT 0,
  hospedagem VARCHAR(255) NULL,
  roteiro TEXT NULL,
  ponto_encontro VARCHAR(255) NULL,
  ponto_encontro_mapa VARCHAR(500) NULL,
  transporte VARCHAR(200) NULL,
  veiculo VARCHAR(20) NULL,
  veiculo_descricao VARCHAR(120) NULL,
  lugares SMALLINT NULL,
  lugares_piso_inferior SMALLINT NULL,
  poltronas_bloqueadas VARCHAR(255) NULL,
  motorista_nome VARCHAR(120) NULL,
  motorista_telefone VARCHAR(20) NULL,
  coordenador_nome VARCHAR(120) NULL,
  coordenador_telefone VARCHAR(20) NULL,
  guia_local_nome VARCHAR(120) NULL,
  guia_local_telefone VARCHAR(20) NULL,
  uniforme VARCHAR(200) NULL,
  alimentacao VARCHAR(200) NULL,
  observacoes TEXT NULL,
  regras TEXT NULL,
  instrucoes_nf TEXT NULL,
  data_inicio DATE NOT NULL,
  data_fim DATE NOT NULL,
  prazo_nf_dias_uteis SMALLINT NOT NULL DEFAULT 3,
  prazo_pagamento_dias SMALLINT NOT NULL DEFAULT 30,
  status VARCHAR(20) NOT NULL DEFAULT 'rascunho',
  criado_por INTEGER NULL,
  criado_em DATETIME NOT NULL,
  atualizado_em DATETIME NULL
) {{TABELA}};
CREATE UNIQUE INDEX ux_viagens_codigo ON viagens (codigo);
CREATE INDEX ix_viagens_status ON viagens (status, data_inicio);

-- Outros contatos da viagem/tour (hotel, restaurante, atrativo, emergência...). Visíveis ao guia confirmado.
CREATE TABLE viagem_contatos (
  id {{PK}},
  viagem_id INTEGER NOT NULL,
  papel VARCHAR(60) NOT NULL,
  nome VARCHAR(120) NULL,
  telefone VARCHAR(20) NULL,
  observacao VARCHAR(255) NULL,
  ordem SMALLINT NOT NULL DEFAULT 0,
  FOREIGN KEY (viagem_id) REFERENCES viagens (id)
) {{TABELA}};
CREATE INDEX ix_viagem_contatos ON viagem_contatos (viagem_id, ordem);

-- Vagas por função numa viagem/tour, com o valor da diária.
CREATE TABLE viagem_vagas (
  id {{PK}},
  viagem_id INTEGER NOT NULL,
  funcao_id INTEGER NOT NULL,
  vagas SMALLINT NOT NULL DEFAULT 1,
  valor_diaria DECIMAL(10,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (viagem_id) REFERENCES viagens (id),
  FOREIGN KEY (funcao_id) REFERENCES funcoes (id)
) {{TABELA}};

-- Materiais da viagem/tour: lista de passageiros, roteiro detalhado, vouchers, mapas.
CREATE TABLE viagem_anexos (
  id {{PK}},
  viagem_id INTEGER NOT NULL,
  titulo VARCHAR(160) NOT NULL,
  arquivo_path VARCHAR(255) NOT NULL,
  mime VARCHAR(80) NULL,
  tamanho INTEGER NULL,
  criado_em DATETIME NOT NULL,
  FOREIGN KEY (viagem_id) REFERENCES viagens (id)
) {{TABELA}};

CREATE TABLE diarias (
  id {{PK}},
  viagem_id INTEGER NOT NULL,
  data DATE NOT NULL,
  horario_apresentacao VARCHAR(5) NULL,
  hora_inicio VARCHAR(5) NULL,
  hora_fim VARCHAR(5) NULL,
  pernoite SMALLINT NOT NULL DEFAULT 0,
  observacao VARCHAR(255) NULL,
  FOREIGN KEY (viagem_id) REFERENCES viagens (id)
) {{TABELA}};
CREATE INDEX ix_diarias_viagem ON diarias (viagem_id, data);
CREATE INDEX ix_diarias_data ON diarias (data);

-- Ciclo da escala:
--   convidado -> confirmado -> em_campo (check-in) -> realizada (check-out)
--   -> aguardando_nf (relatório entregue) -> nf_em_conferencia -> a_pagar -> paga
-- desvios: recusado | cancelado | falta
CREATE TABLE escalas (
  id {{PK}},
  diaria_id INTEGER NOT NULL,
  guia_id INTEGER NOT NULL,
  viagem_vaga_id INTEGER NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'convidado',
  valor DECIMAL(10,2) NOT NULL DEFAULT 0,
  e_lider SMALLINT NOT NULL DEFAULT 0,
  motivo_recusa VARCHAR(255) NULL,
  pagamento_id INTEGER NULL,
  convidado_por INTEGER NULL,
  convidado_em DATETIME NOT NULL,
  respondido_em DATETIME NULL,
  atualizado_em DATETIME NULL,
  FOREIGN KEY (diaria_id) REFERENCES diarias (id),
  FOREIGN KEY (guia_id) REFERENCES guias (id)
) {{TABELA}};
CREATE UNIQUE INDEX ux_escala ON escalas (diaria_id, guia_id);
CREATE INDEX ix_escalas_guia ON escalas (guia_id, status);

-- tipo: checkin | checkout | relatorio | nf
CREATE TABLE envios (
  id {{PK}},
  tipo VARCHAR(10) NOT NULL,
  viagem_id INTEGER NOT NULL,
  guia_id INTEGER NOT NULL,
  escala_id INTEGER NULL,
  arquivo_path VARCHAR(255) NULL,
  nome_original VARCHAR(255) NULL,
  mime VARCHAR(80) NULL,
  tamanho INTEGER NULL,
  texto TEXT NULL,
  qtd_passageiros SMALLINT NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  numero_nf VARCHAR(40) NULL,
  valor_nf DECIMAL(10,2) NULL,
  enviado_por_tipo VARCHAR(10) NOT NULL DEFAULT 'guia',
  enviado_por_id INTEGER NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'enviado',
  motivo TEXT NULL,
  revisado_por INTEGER NULL,
  revisado_em DATETIME NULL,
  enviado_em DATETIME NOT NULL,
  FOREIGN KEY (viagem_id) REFERENCES viagens (id),
  FOREIGN KEY (guia_id) REFERENCES guias (id)
) {{TABELA}};
CREATE INDEX ix_envios_fila ON envios (status, tipo);
CREATE INDEX ix_envios_guia ON envios (guia_id, viagem_id);

CREATE TABLE pagamentos (
  id {{PK}},
  guia_id INTEGER NOT NULL,
  viagem_id INTEGER NULL,
  valor DECIMAL(10,2) NOT NULL,
  pago_em DATE NOT NULL,
  comprovante_path VARCHAR(255) NULL,
  observacao VARCHAR(255) NULL,
  registrado_por INTEGER NULL,
  criado_em DATETIME NOT NULL,
  FOREIGN KEY (guia_id) REFERENCES guias (id)
) {{TABELA}};

-- Disponibilidade marcada pelo guia. tipo: disponivel | indisponivel. periodo: dia | manha | tarde | noite
CREATE TABLE disponibilidade (
  id {{PK}},
  guia_id INTEGER NOT NULL,
  data DATE NOT NULL,
  tipo VARCHAR(12) NOT NULL,
  periodo VARCHAR(10) NOT NULL DEFAULT 'dia',
  observacao VARCHAR(255) NULL,
  criado_em DATETIME NOT NULL,
  FOREIGN KEY (guia_id) REFERENCES guias (id)
) {{TABELA}};
CREATE INDEX ix_disponibilidade_guia ON disponibilidade (guia_id, data);
CREATE INDEX ix_disponibilidade_data ON disponibilidade (data, tipo);

CREATE TABLE chamado_motivos (
  id {{PK}},
  nome VARCHAR(120) NOT NULL,
  ativo SMALLINT NOT NULL DEFAULT 1,
  ordem SMALLINT NOT NULL DEFAULT 0
) {{TABELA}};

-- status: aberto | respondido | aguardando_confirmacao | resolvido
CREATE TABLE chamados (
  id {{PK}},
  guia_id INTEGER NOT NULL,
  titulo VARCHAR(200) NOT NULL,
  motivo_id INTEGER NULL,
  viagem_id INTEGER NULL,
  diaria_id INTEGER NULL,
  urgente SMALLINT NOT NULL DEFAULT 0,
  status VARCHAR(25) NOT NULL DEFAULT 'aberto',
  responsavel_admin_id INTEGER NULL,
  criado_em DATETIME NOT NULL,
  atualizado_em DATETIME NULL,
  resolvido_em DATETIME NULL,
  FOREIGN KEY (guia_id) REFERENCES guias (id)
) {{TABELA}};
CREATE INDEX ix_chamados_status ON chamados (status);
CREATE INDEX ix_chamados_guia ON chamados (guia_id);

CREATE TABLE chamado_mensagens (
  id {{PK}},
  chamado_id INTEGER NOT NULL,
  autor_tipo VARCHAR(10) NOT NULL,
  autor_id INTEGER NOT NULL,
  mensagem TEXT NOT NULL,
  anexo_path VARCHAR(255) NULL,
  criado_em DATETIME NOT NULL,
  FOREIGN KEY (chamado_id) REFERENCES chamados (id)
) {{TABELA}};
CREATE INDEX ix_mensagens_chamado ON chamado_mensagens (chamado_id);

-- Recados para os guias. publico: todos | funcao | viagem
CREATE TABLE avisos (
  id {{PK}},
  titulo VARCHAR(200) NOT NULL,
  mensagem TEXT NOT NULL,
  publico VARCHAR(10) NOT NULL DEFAULT 'todos',
  funcao_id INTEGER NULL,
  viagem_id INTEGER NULL,
  inicio DATE NULL,
  fim DATE NULL,
  ativo SMALLINT NOT NULL DEFAULT 1,
  criado_por INTEGER NULL,
  criado_em DATETIME NOT NULL
) {{TABELA}};

-- Conteúdo da Ajuda. secao: faq | guia_pratico | video | contato
CREATE TABLE orientacoes (
  id {{PK}},
  secao VARCHAR(15) NOT NULL,
  categoria VARCHAR(80) NULL,
  titulo VARCHAR(255) NOT NULL,
  conteudo TEXT NULL,
  video_url VARCHAR(255) NULL,
  ordem SMALLINT NOT NULL DEFAULT 0,
  ativo SMALLINT NOT NULL DEFAULT 1,
  atualizado_em DATETIME NULL
) {{TABELA}};
CREATE INDEX ix_orientacoes ON orientacoes (secao, ordem);

CREATE TABLE notificacoes (
  id {{PK}},
  destinatario_tipo VARCHAR(10) NOT NULL,
  destinatario_id INTEGER NOT NULL,
  titulo VARCHAR(200) NOT NULL,
  mensagem TEXT NULL,
  link VARCHAR(255) NULL,
  lida_em DATETIME NULL,
  criado_em DATETIME NOT NULL
) {{TABELA}};
CREATE INDEX ix_notificacoes ON notificacoes (destinatario_tipo, destinatario_id, lida_em);

CREATE TABLE auditoria (
  id {{PK}},
  ator_tipo VARCHAR(10) NOT NULL,
  ator_id INTEGER NULL,
  acao VARCHAR(60) NOT NULL,
  entidade VARCHAR(40) NULL,
  entidade_id INTEGER NULL,
  detalhes TEXT NULL,
  ip VARCHAR(45) NULL,
  criado_em DATETIME NOT NULL
) {{TABELA}};
CREATE INDEX ix_auditoria_entidade ON auditoria (entidade, entidade_id);

CREATE TABLE password_resets (
  id {{PK}},
  usuario_tipo VARCHAR(10) NOT NULL,
  usuario_id INTEGER NOT NULL,
  token_hash VARCHAR(64) NOT NULL,
  expira_em DATETIME NOT NULL,
  usado_em DATETIME NULL,
  criado_em DATETIME NOT NULL
) {{TABELA}};
CREATE UNIQUE INDEX ux_password_resets_token ON password_resets (token_hash);

CREATE TABLE login_tentativas (
  id {{PK}},
  chave VARCHAR(120) NOT NULL,
  criado_em DATETIME NOT NULL
) {{TABELA}};
CREATE INDEX ix_login_tentativas ON login_tentativas (chave, criado_em);

CREATE TABLE configuracoes (
  chave VARCHAR(60) NOT NULL PRIMARY KEY,
  valor TEXT NULL
) {{TABELA}};

-- Lista de passageiros da viagem/tour (mesmas colunas da planilha da 645), mantida pelo ADM
-- e consultada pelos guias em tempo real.
-- Os ajustes feitos pelo guia NÃO sobrescrevem os dados do ADM: ficam em ajustes_guia (JSON campo => valor),
-- aparecem destacados na lista e podem ser incorporados pelo ADM. criado_por_tipo = guia indica passageiro incluído em campo.
-- status: ativo | cancelado
CREATE TABLE passageiros (
  id {{PK}},
  viagem_id INTEGER NOT NULL,
  ordem INTEGER NOT NULL DEFAULT 0,
  nome VARCHAR(160) NOT NULL,
  tipo_documento VARCHAR(30) NULL,
  documento VARCHAR(40) NULL,
  nascimento DATE NULL,
  venda VARCHAR(80) NULL,
  embarque VARCHAR(120) NULL,
  observacao VARCHAR(500) NULL,
  poltrona VARCHAR(10) NULL,
  telefone VARCHAR(20) NULL,
  ajustes_guia TEXT NULL,
  ajustado_por_guia_id INTEGER NULL,
  ajustado_em DATETIME NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'ativo',
  criado_por_tipo VARCHAR(10) NOT NULL DEFAULT 'admin',
  criado_por_id INTEGER NULL,
  criado_em DATETIME NOT NULL,
  atualizado_em DATETIME NOT NULL,
  FOREIGN KEY (viagem_id) REFERENCES viagens (id)
) {{TABELA}};
CREATE INDEX ix_passageiros_viagem ON passageiros (viagem_id, status, ordem);

-- Check-in (embarque) e check-out (desembarque) de cada passageiro em cada dia de trabalho.
-- *_por_tipo: guia | admin
CREATE TABLE passageiro_registros (
  id {{PK}},
  passageiro_id INTEGER NOT NULL,
  diaria_id INTEGER NOT NULL,
  checkin_em DATETIME NULL,
  checkin_por_tipo VARCHAR(10) NULL,
  checkin_por_id INTEGER NULL,
  checkout_em DATETIME NULL,
  checkout_por_tipo VARCHAR(10) NULL,
  checkout_por_id INTEGER NULL,
  atualizado_em DATETIME NOT NULL,
  FOREIGN KEY (passageiro_id) REFERENCES passageiros (id),
  FOREIGN KEY (diaria_id) REFERENCES diarias (id)
) {{TABELA}};
CREATE UNIQUE INDEX ux_passageiro_registro ON passageiro_registros (passageiro_id, diaria_id);
CREATE INDEX ix_registros_diaria ON passageiro_registros (diaria_id, atualizado_em);
