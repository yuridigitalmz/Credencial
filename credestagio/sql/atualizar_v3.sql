-- Actualização para quem já tem a versão anterior instalada (execute UMA vez).
SET NAMES utf8mb4;
USE credestagio;
ALTER TABLE administradores ADD COLUMN trocar_senha TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE estudantes ADD COLUMN trocar_senha TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE pedidos ADD COLUMN codigo CHAR(16) NULL, ADD COLUMN decidido_por INT NULL, ADD UNIQUE KEY uk_codigo (codigo);
ALTER TABLE tentativas_login ADD COLUMN tipo VARCHAR(10) NOT NULL DEFAULT 'login';

CREATE TABLE disciplinas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_id INT NOT NULL,
  ano TINYINT NOT NULL,
  nome VARCHAR(120) NOT NULL,
  FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  estudante_id INT NOT NULL,
  disciplina_id INT NOT NULL,
  nota DECIMAL(3,1) NOT NULL,
  UNIQUE KEY uk_nota (estudante_id, disciplina_id),
  FOREIGN KEY (estudante_id) REFERENCES estudantes(id) ON DELETE CASCADE,
  FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notificacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  estudante_id INT NOT NULL,
  mensagem VARCHAR(500) NOT NULL,
  lida TINYINT(1) NOT NULL DEFAULT 0,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (estudante_id) REFERENCES estudantes(id) ON DELETE CASCADE,
  INDEX (estudante_id, lida)
) ENGINE=InnoDB;

CREATE TABLE reset_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  estudante_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expira_em DATETIME NOT NULL,
  usado TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (estudante_id) REFERENCES estudantes(id) ON DELETE CASCADE,
  INDEX (token_hash)
) ENGINE=InnoDB;

CREATE TABLE emails_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  destinatario VARCHAR(160) NOT NULL,
  assunto VARCHAR(200) NOT NULL,
  corpo TEXT NOT NULL,
  estado VARCHAR(20) NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE registo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  papel VARCHAR(12) NULL,
  utilizador_id INT NULL,
  nome VARCHAR(120) NULL,
  acao VARCHAR(80) NOT NULL,
  detalhe VARCHAR(250) NOT NULL DEFAULT '',
  ip VARCHAR(45) NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (criado_em)
) ENGINE=InnoDB;

-- Atenção: sem disciplinas e notas registadas, a regra das cadeiras aprovadas não se aplica a cada curso.
