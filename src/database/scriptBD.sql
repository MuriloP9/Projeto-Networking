
use master;

drop database prolink;

CREATE DATABASE prolink;
go
use prolink;

go
-- Tabela de Usuários
CREATE TABLE Usuario (
    id_usuario INT IDENTITY(1,1) PRIMARY KEY,
    nome NVARCHAR(255) NOT NULL,
    email NVARCHAR(100) UNIQUE NOT NULL,
    senha NVARCHAR(255) NOT NULL,
    dataNascimento DATE NOT NULL,
    telefone NVARCHAR(15) NOT NULL,
);
GO

-- Tabela de Perfil 
CREATE TABLE Perfil (
    id_perfil INT IDENTITY(1,1) PRIMARY KEY,
    id_usuario INT NOT NULL FOREIGN KEY REFERENCES Usuario(id_usuario),
	email NVARCHAR(100) UNIQUE NOT NULL,
    idade INT,
    endereco NVARCHAR(100),
    formacao NVARCHAR(255),
    experiencia_profissional NVARCHAR(MAX),
    interesses NVARCHAR(MAX),
    projetos_especializacoes NVARCHAR(MAX),
    habilidades NVARCHAR(MAX),
    qr_code NVARCHAR(255) -- Novo campo para QR Code
);
GO

CREATE TABLE Funcionario (
    id_func INT IDENTITY(1,1) PRIMARY KEY,
    email NVARCHAR(100) UNIQUE NOT NULL,
    senha NVARCHAR(255) NOT NULL,
    data_cadastro DATETIME DEFAULT GETDATE(),
    cnpj NVARCHAR(18) UNIQUE NOT NULL,
    razao_social NVARCHAR(255) NOT NULL,
    nome_fantasia NVARCHAR(255)
);
GO

-- Tabela de Áreas de Atuação
CREATE TABLE AreaAtuacao (
    id_area INT IDENTITY(1,1) PRIMARY KEY,
    nome_area NVARCHAR(100) NOT NULL
);
GO

-- Tabela de Profissionais em Áreas
CREATE TABLE ProfissionalArea (
    id_profissional_area INT IDENTITY(1,1) PRIMARY KEY,
    id_usuario INT NOT NULL FOREIGN KEY REFERENCES Usuario(id_usuario),
    id_area INT NOT NULL FOREIGN KEY REFERENCES AreaAtuacao(id_area)
);
GO

-- Tabela de Vagas de Emprego
CREATE TABLE Vagas (
    id_vaga INT IDENTITY(1,1) PRIMARY KEY,
    id_func INT NOT NULL FOREIGN KEY REFERENCES Funcionario(id_func),
    titulo_vaga NVARCHAR(255) NOT NULL,
    localizacao NVARCHAR(255),
    tipo_emprego NVARCHAR(20) NOT NULL,
    id_area INT,
    id_usuario INT FOREIGN KEY REFERENCES Usuario(id_usuario),
    FOREIGN KEY (id_area) REFERENCES AreaAtuacao(id_area),
    CONSTRAINT chk_tipo_emprego CHECK (tipo_emprego IN ('full-time', 'part-time', 'internship'))
);
GO

CREATE TABLE Candidatura (
    id_candidatura INT IDENTITY(1,1) PRIMARY KEY,
    id_vaga INT NOT NULL FOREIGN KEY REFERENCES Vagas(id_vaga),
    id_perfil INT NOT NULL FOREIGN KEY REFERENCES Perfil(id_perfil),
    data_candidatura DATETIME DEFAULT GETDATE(),
    status NVARCHAR(20) DEFAULT 'Pendente'
);
GO


-- Tabela de Mensagens
CREATE TABLE Mensagem (
    id_mensagem INT IDENTITY(1,1) PRIMARY KEY, 
    id_usuario_remetente INT,        
    id_usuario_destinatario INT ,     
    texto NVARCHAR(MAX) NOT NULL,            
    data_hora DATETIME DEFAULT GETDATE(),     
    CONSTRAINT FK_Remetente FOREIGN KEY (id_usuario_remetente) REFERENCES Usuario(id_usuario),
    CONSTRAINT FK_Destinatario FOREIGN KEY (id_usuario_destinatario) REFERENCES Usuario(id_usuario)
);
GO

-- Tabela de Inscrições em Webinars
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
GO

-- Tabela de Notificações
CREATE TABLE Notificacao (
    id_notificacao INT IDENTITY(1,1) PRIMARY KEY,
    id_usuario INT NOT NULL FOREIGN KEY REFERENCES Usuario(id_usuario),
    mensagem NVARCHAR(MAX) NOT NULL,
    data_hora DATETIME DEFAULT GETDATE(),
    lida BIT DEFAULT 0
);
GO

CREATE TABLE QRCode (
    id_qr_code INT IDENTITY(1,1) PRIMARY KEY,
    id_usuario INT NOT NULL FOREIGN KEY REFERENCES Usuario(id_usuario),
    codigo NVARCHAR(255) NOT NULL,
    data_geracao DATETIME DEFAULT GETDATE()
);
GO 

/*INSERT INTO Usuario (nome, email, senha, dataNascimento, telefone)
VALUES 
('Lucas Andrade', 'lucas@email.com', 'senha123', '1995-04-12', '(11)91234-5678'),
('Carla Menezes', 'carla@email.com', 'segura456', '1992-08-30', '(21)99876-5432'),
('Rafael Torres', 'rafael@email.com', 'pass789', '2000-01-20', '(31)98765-1234');

INSERT INTO Perfil (id_usuario, idade, endereco, formacao, experiencia_profissional, interesses, projetos_especializacoes, habilidades, qr_code)
VALUES 
(1, 29, 'Rua A, 123 - SP', 'Engenharia da Computação', '3 anos como dev backend', 'Tecnologia, inovação', 'API em Node.js, Projeto IoT', 'JavaScript, SQL, C#', 'qr_lucas.png'),
(2, 32, 'Av. B, 456 - RJ', 'Design Gráfico', '6 anos em agências de publicidade', 'Artes, UX/UI', 'App de design colaborativo', 'Photoshop, Figma, Illustrator', 'qr_carla.png'),
(3, 24, 'Rua C, 789 - MG', 'Análise de Sistemas', '1 ano como suporte técnico', 'Redes, dados', 'Sistema de chamados', 'Python, MySQL', 'qr_rafael.png');

INSERT INTO Funcionario (email, senha, cnpj, razao_social, nome_fantasia)
VALUES 
('empresa1@corp.com', 'admin123', '12.345.678/0001-99', 'Empresa Um Ltda', 'Empre1'),
('empresa2@corp.com', 'admin456', '98.765.432/0001-11', 'Empresa Dois SA', 'Empre2');

INSERT INTO AreaAtuacao (nome_area)
VALUES 
('Desenvolvimento Web'),
('Design Gráfico'),
('Infraestrutura de Redes');

INSERT INTO ProfissionalArea (id_usuario, id_area)
VALUES 
(1, 1), -- Lucas → Web
(2, 2), -- Carla → Design
(3, 3); -- Rafael → Redes

INSERT INTO Vagas (id_func, titulo_vaga, localizacao, tipo_emprego, id_area, id_usuario)
VALUES 
(1, 'Desenvolvedor Full Stack', 'São Paulo - SP', 'full-time', 1, 1),
(1, 'Designer de Interfaces', 'Rio de Janeiro - RJ', 'part-time', 2, 2);

INSERT INTO Candidatura (id_vaga, id_perfil)
VALUES 
(1, 1), -- Lucas na vaga de dev
(2, 2); -- Carla na vaga de design

INSERT INTO inscricoes_webinar (nome_completo, email, telefone, recebe_notificacoes, consentimento_lgpd, id_usuario)
VALUES 
('Lucas Andrade', 'lucas@email.com', '(11)91234-5678', 1, 1, 1),
('Carla Menezes', 'carla@email.com', '(21)99876-5432', 0, 1, 2);
*/


select * from Perfil;

select*from inscricoes_webinar;

select*from Usuario;

select * from Mensagem;

select*from Vagas;






DROP TABLE perfil;
drop table usuario;