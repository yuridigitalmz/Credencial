CREATE DATABASE IF NOT EXISTS credestagio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SET NAMES utf8mb4;
USE credestagio;

CREATE TABLE departamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL UNIQUE,
  chefe_nome VARCHAR(120) NULL,
  assinatura VARCHAR(80) NULL
) ENGINE=InnoDB;

CREATE TABLE cursos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL UNIQUE,
  faculdade VARCHAR(120) NOT NULL,
  departamento_id INT NOT NULL,
  FOREIGN KEY (departamento_id) REFERENCES departamentos(id)
) ENGINE=InnoDB;

CREATE TABLE administradores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(40) NOT NULL UNIQUE,
  nome VARCHAR(120) NOT NULL,
  departamento_id INT NULL COMMENT 'NULL = secretaria; preenchido = chefe de departamento',
  senha_hash VARCHAR(255) NOT NULL,
  trocar_senha TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (departamento_id) REFERENCES departamentos(id)
) ENGINE=InnoDB;

CREATE TABLE estudantes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  numero VARCHAR(30) NOT NULL UNIQUE,
  nome VARCHAR(120) NOT NULL,
  nome_pai VARCHAR(120) NULL,
  nome_mae VARCHAR(120) NULL,
  bi VARCHAR(30) NULL,
  bi_emissao VARCHAR(120) NULL,
  contacto VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  curso_id INT NOT NULL,
  ano TINYINT NOT NULL,
  regime ENUM('Laboral','Pós-laboral') NOT NULL DEFAULT 'Laboral',
  financeiro ENUM('Regularizada','Em dívida') NOT NULL DEFAULT 'Regularizada',
  senha_hash VARCHAR(255) NOT NULL,
  trocar_senha TINYINT(1) NOT NULL DEFAULT 0,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (curso_id) REFERENCES cursos(id)
) ENGINE=InnoDB;

CREATE TABLE pedidos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  estudante_id INT NOT NULL,
  local_estagio VARCHAR(180) NOT NULL,
  sector VARCHAR(180) NULL,
  meses TINYINT NOT NULL,
  data_inicio DATE NOT NULL,
  estado ENUM('Pendente','Aprovado','Rejeitado') NOT NULL DEFAULT 'Pendente',
  motivo VARCHAR(255) NULL,
  codigo CHAR(16) NULL,
  decidido_por INT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  decidido_em DATETIME NULL,
  UNIQUE KEY uk_codigo (codigo),
  FOREIGN KEY (estudante_id) REFERENCES estudantes(id) ON DELETE CASCADE,
  INDEX (estado)
) ENGINE=InnoDB;

CREATE TABLE ligacoes (
  chave VARCHAR(20) PRIMARY KEY,
  titulo VARCHAR(80) NOT NULL,
  url VARCHAR(500) NOT NULL DEFAULT ''
) ENGINE=InnoDB;

CREATE TABLE tentativas_login (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  tipo VARCHAR(10) NOT NULL DEFAULT 'login',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (ip, tipo, criado_em)
) ENGINE=InnoDB;

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

INSERT INTO departamentos (id, nome) VALUES
 (1,'Departamento de Matemática e Estatística'),
 (2,'Departamento de Física');

INSERT INTO cursos (id, nome, faculdade, departamento_id) VALUES
 (1,'Estatística e Gestão de Informação','Faculdade de Ciências e Tecnologia',1),
 (2,'Matemática','Faculdade de Ciências e Tecnologia',1),
 (3,'Física','Faculdade de Ciências e Tecnologia',2);

INSERT INTO ligacoes (chave, titulo, url) VALUES
 ('horario','Sistema de Horários',''),
 ('jornal','Jornal da Faculdade','');

INSERT INTO administradores (usuario, nome, departamento_id, senha_hash) VALUES
 ('admin','Secretaria Académica',NULL,'$2y$10$5YP1bHhiB8UhtqF4wZPBNONlV8zT.QSBVnob0oSlp5MMX8IrKZ6nm'),
 ('chefe.mat','Chefe do Dep. de Matemática e Estatística',1,'$2y$10$WvyXDNuxKVVToqPxPFjsBehsP6EV6GBfs5GsuHtNx3Ix/W2s0dna6'),
 ('chefe.fis','Chefe do Dep. de Física',2,'$2y$10$WvyXDNuxKVVToqPxPFjsBehsP6EV6GBfs5GsuHtNx3Ix/W2s0dna6');

INSERT INTO estudantes (numero, nome, nome_pai, nome_mae, bi, bi_emissao, contacto, email, curso_id, ano, regime, financeiro, senha_hash, trocar_senha) VALUES
('04.0021.2023','Ana Paula Mondlane','Paulo Mondlane','Maria Cossa','110302145221A','12/03/2019 — Beira','82 301 4455','ana@exemplo.ac.mz',1,4,'Laboral','Regularizada','$2y$10$P.NFYa4vkGVnAT74Ovmpy.ZQFthJvDLhyBRkukL4vfGtQZsc5w8m2',0),
('04.0034.2023','Carlos Eduardo Nhaca','Eduardo Nhaca','Rosa Machaieie','110409987654B','05/07/2020 — Dondo','82 998 1122','carlos@exemplo.ac.mz',1,3,'Pós-laboral','Regularizada','$2y$10$P.NFYa4vkGVnAT74Ovmpy.ZQFthJvDLhyBRkukL4vfGtQZsc5w8m2',0),
('04.0057.2024','Beatriz Fernandes Sitoe','Fernandes Sitoe','Lúcia Mabunda','110508231477C','20/01/2021 — Beira','84 220 3398','beatriz@exemplo.ac.mz',1,2,'Laboral','Regularizada','$2y$10$P.NFYa4vkGVnAT74Ovmpy.ZQFthJvDLhyBRkukL4vfGtQZsc5w8m2',0),
('05.0012.2023','Domingos Artur Feniasse','Artur Feniasse','Elsa Tembe','110611874502D','02/09/2018 — Dondo','86 410 7721','domingos@exemplo.ac.mz',2,3,'Laboral','Em dívida','$2y$10$P.NFYa4vkGVnAT74Ovmpy.ZQFthJvDLhyBRkukL4vfGtQZsc5w8m2',0),
('05.0028.2022','Rosinda Manuel Bila','Manuel Bila','Teresa Nhantumbo','110703316689E','15/05/2019 — Beira','87 552 1044','rosinda@exemplo.ac.mz',2,4,'Pós-laboral','Regularizada','$2y$10$P.NFYa4vkGVnAT74Ovmpy.ZQFthJvDLhyBRkukL4vfGtQZsc5w8m2',0),
('06.0009.2025','Anselmo Paulo Machava','Paulo Machava','Irene Zimba','110812459033F','30/11/2022 — Beira','82 774 6610','anselmo@exemplo.ac.mz',3,1,'Laboral','Regularizada','$2y$10$P.NFYa4vkGVnAT74Ovmpy.ZQFthJvDLhyBRkukL4vfGtQZsc5w8m2',0),
('06.0015.2023','Suzana Amélia Macuácua','Amélio Macuácua','Graça Langa','110905562118G','08/04/2020 — Dondo','84 689 2307','suzana@exemplo.ac.mz',3,3,'Laboral','Regularizada','$2y$10$P.NFYa4vkGVnAT74Ovmpy.ZQFthJvDLhyBRkukL4vfGtQZsc5w8m2',0);

INSERT INTO disciplinas (id, curso_id, ano, nome) VALUES
(1,1,1,'Matemática I'),
(2,1,1,'Introdução à Estatística'),
(3,1,2,'Probabilidades'),
(4,1,2,'Bases de Dados'),
(5,1,3,'Inferência Estatística'),
(6,1,3,'Gestão de Informação'),
(7,1,4,'Análise de Dados'),
(8,2,1,'Cálculo I'),
(9,2,1,'Álgebra Linear'),
(10,2,2,'Cálculo II'),
(11,2,2,'Análise Real'),
(12,2,3,'Equações Diferenciais'),
(13,2,3,'Geometria'),
(14,2,4,'Topologia'),
(15,3,1,'Mecânica Clássica'),
(16,3,1,'Cálculo I'),
(17,3,2,'Electromagnetismo'),
(18,3,2,'Termodinâmica'),
(19,3,3,'Física Quântica'),
(20,3,3,'Óptica'),
(21,3,4,'Física Nuclear');

INSERT INTO notas (estudante_id, disciplina_id, nota) VALUES
(1,1,12.9),
(1,2,11.9),
(1,3,14.9),
(1,4,11.4),
(1,5,14.2),
(1,6,13.2),
(2,1,11.3),
(2,2,14.0),
(2,3,11.2),
(2,4,13.6),
(2,5,11.4),
(2,6,11.5),
(3,1,13.5),
(3,2,16.0),
(4,8,11.7),
(4,9,12.3),
(4,10,14.8),
(4,11,16.7),
(4,12,14.5),
(4,13,13.4),
(5,8,16.9),
(5,9,11.3),
(5,10,16.2),
(5,11,12.7),
(5,12,11.9),
(5,13,11.7),
(6,15,12.9),
(6,16,15.9),
(7,15,12.1),
(7,16,14.5),
(7,17,14.8),
(7,18,13.2),
(7,19,14.3),
(7,20,8.5);
