CREATE DATABASE prolink;

use prolink;

use master;

drop database prolink;


-- Tabela de Usuários
CREATE TABLE Usuario (
    id_usuario INT IDENTITY(1,1) PRIMARY KEY,
    nome NVARCHAR(255) NOT NULL,
    email NVARCHAR(100) UNIQUE NOT NULL,
    senha NVARCHAR(255) NOT NULL,
    dataNascimento DATE NOT NULL,
    telefone NVARCHAR(15) NOT NULL
);

select*from Usuario;

drop table Usuario;

-- Tabela de Perfil 
CREATE TABLE Perfil (
    id_perfil INT IDENTITY(1,1) PRIMARY KEY,
    id_usuario INT NOT NULL FOREIGN KEY REFERENCES Usuario(id_usuario),
    idade INT,
    localizacao NVARCHAR(100),
    formacao NVARCHAR(255),
    experiencia_profissional NVARCHAR(MAX),
    interesses NVARCHAR(MAX),
    projetos_especializacoes NVARCHAR(MAX),
    habilidades NVARCHAR(MAX),
    contato_email NVARCHAR(100),
    contato_telefone NVARCHAR(15)
);

select * from Perfil;

-- Tabela de Áreas de Atuação
CREATE TABLE AreaAtuacao (
    id_area INT IDENTITY(1,1) PRIMARY KEY,
    nome_area NVARCHAR(100) NOT NULL
);

-- Tabela de Profissionais em Áreas
CREATE TABLE ProfissionalArea (
    id_profissional_area INT IDENTITY(1,1) PRIMARY KEY,
    id_usuario INT NOT NULL FOREIGN KEY REFERENCES Usuario(id_usuario),
    id_area INT NOT NULL FOREIGN KEY REFERENCES AreaAtuacao(id_area)
);

-- Tabela de Vagas de Emprego
CREATE TABLE Vagas (
    id_vaga INT IDENTITY(1,1) PRIMARY KEY,
    titulo_vaga NVARCHAR(255) NOT NULL,
    localizacao NVARCHAR(255),
    tipo_emprego NVARCHAR(20) NOT NULL,
    id_area INT,
	id_usuario INT FOREIGN KEY REFERENCES Usuario(id_usuario),
    FOREIGN KEY (id_area) REFERENCES AreaAtuacao(id_area),
    CONSTRAINT chk_tipo_emprego CHECK (tipo_emprego IN ('full-time', 'part-time', 'internship'))
);
select*from Vagas;

drop table Vagas;

CREATE TABLE Mensagem (
    id_mensagem INT IDENTITY(1,1) PRIMARY KEY, 
    id_usuario_remetente INT,        
    id_usuario_destinatario INT ,     
    texto NVARCHAR(MAX) NOT NULL,            
    data_hora DATETIME DEFAULT GETDATE(),     -- Data e hora da mensagem
    CONSTRAINT FK_Remetente FOREIGN KEY (id_usuario_remetente) REFERENCES Usuario(id_usuario),
    CONSTRAINT FK_Destinatario FOREIGN KEY (id_usuario_destinatario) REFERENCES Usuario(id_usuario)
);

select * from Mensagem;

drop table Mensagem;


CREATE TABLE inscricoes_webinar(
    id INT IDENTITY(1,1) PRIMARY KEY,
    nome_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefone VARCHAR(20),
    recebe_notificacoes BIT DEFAULT 0,
    consentimento_lgpd BIT NOT NULL,
	id_usuario INT FOREIGN KEY REFERENCES Usuario(id_usuario),
    data_inscricao DATETIME DEFAULT GETDATE()
);
select*from inscricoes_webinar;

drop table inscricoes_webinar;

