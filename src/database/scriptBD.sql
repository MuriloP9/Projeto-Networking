USE master;
GO

-- Drop e recriação do banco de dados
IF EXISTS (SELECT name FROM sys.databases WHERE name = 'prolink')
    DROP DATABASE prolink;
GO

CREATE DATABASE prolink;
GO

USE prolink;
GO

-- Tabela de Usuários
CREATE TABLE Usuario (
    id_usuario INT IDENTITY(1,1) PRIMARY KEY,
    nome NVARCHAR(255) NOT NULL,
    email NVARCHAR(100) UNIQUE NOT NULL,
    senha NVARCHAR(255) NOT NULL,
    dataNascimento DATE NULL,
    telefone NVARCHAR(15) NULL,
    qr_code NVARCHAR(255) NULL,
    data_criacao DATETIME NOT NULL DEFAULT GETDATE(),
    data_geracao_qr DATETIME NULL,
    ultimo_acesso DATETIME NULL,
    ativo BIT DEFAULT 1,
    foto_perfil VARBINARY(MAX) NULL
);
GO

-- Tabela de Perfil 
CREATE TABLE Perfil (
    id_perfil INT IDENTITY(1,1) PRIMARY KEY,
    id_usuario INT NOT NULL,
    idade INT,
    endereco NVARCHAR(100),
    formacao NVARCHAR(255),
    experiencia_profissional NVARCHAR(MAX),
    interesses NVARCHAR(MAX),
    projetos_especializacoes NVARCHAR(MAX),
    habilidades NVARCHAR(MAX),
    qr_code NVARCHAR(255),
    CONSTRAINT FK_Perfil_Usuario FOREIGN KEY (id_usuario)
        REFERENCES Usuario(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);
GO

-- Tabela de Funcionários (corrigida)
CREATE TABLE Funcionario (
    id_funcionario INT IDENTITY(1,1) PRIMARY KEY,
    nome_completo NVARCHAR(255) NOT NULL,
    email NVARCHAR(100) UNIQUE NOT NULL,
    senha NVARCHAR(255) NOT NULL,
    nivel_acesso INT NOT NULL DEFAULT 2,  -- 0=Admin, 1=Gerente, 2=Supervisor
    criado_por INT NULL,                 -- ID de quem cadastrou (NULL = Admin Master)
    data_cadastro DATETIME DEFAULT GETDATE(),
    ultimo_acesso DATETIME NULL,         -- Último login
    ativo BIT DEFAULT 1,                 -- 1=Ativo, 0=Inativo
    FOREIGN KEY (criado_por) REFERENCES Funcionario(id_funcionario)
);
GO

-- Tabela de Áreas de Atuação
CREATE TABLE AreaAtuacao (
    id_area INT IDENTITY(1,1) PRIMARY KEY,
    nome_area NVARCHAR(100) NOT NULL
);
GO

-- Tabela de Vagas (corrigida para referenciar id_funcionario corretamente)
CREATE TABLE Vagas (
    id_vaga INT IDENTITY(1,1) PRIMARY KEY,
    id_funcionario INT NOT NULL,
    titulo_vaga NVARCHAR(255) NOT NULL,
    localizacao NVARCHAR(255),
    tipo_emprego NVARCHAR(20) NOT NULL,
    id_area INT,
    id_usuario INT,
    CONSTRAINT FK_Vagas_Funcionario FOREIGN KEY (id_funcionario) 
        REFERENCES Funcionario(id_funcionario),
    CONSTRAINT FK_Vagas_Area FOREIGN KEY (id_area) 
        REFERENCES AreaAtuacao(id_area),
    CONSTRAINT FK_Vagas_Usuario FOREIGN KEY (id_usuario) 
        REFERENCES Usuario(id_usuario),
    CONSTRAINT chk_tipo_emprego CHECK (tipo_emprego IN ('full-time', 'part-time', 'internship'))
);
GO

-- Tabela de Profissionais em Áreas
CREATE TABLE ProfissionalArea (
    id_profissional_area INT IDENTITY(1,1) PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_area INT NOT NULL,
    CONSTRAINT FK_ProfissionalArea_Usuario FOREIGN KEY (id_usuario) 
        REFERENCES Usuario(id_usuario),
    CONSTRAINT FK_ProfissionalArea_Area FOREIGN KEY (id_area) 
        REFERENCES AreaAtuacao(id_area)
);
GO

-- Tabela de Candidaturas (corrigida)
CREATE TABLE Candidatura (
    id_candidatura INT IDENTITY(1,1) PRIMARY KEY,
    id_vaga INT NOT NULL,
    id_perfil INT NOT NULL,
    data_candidatura DATETIME DEFAULT GETDATE(),
    status NVARCHAR(20) DEFAULT 'Pendente',
    CONSTRAINT FK_Candidatura_Vaga FOREIGN KEY (id_vaga) 
        REFERENCES Vagas(id_vaga),
    CONSTRAINT FK_Candidatura_Perfil FOREIGN KEY (id_perfil) 
        REFERENCES Perfil(id_perfil)
);
GO

-- Tabela de Mensagens
CREATE TABLE Mensagem (
    id_mensagem INT IDENTITY(1,1) PRIMARY KEY,
    id_usuario_remetente INT NOT NULL,
    id_usuario_destinatario INT NOT NULL,
    texto NVARCHAR(MAX) NOT NULL,
    data_hora DATETIME DEFAULT GETDATE(),
    lida BIT DEFAULT 0,
    CONSTRAINT FK_Remetente FOREIGN KEY (id_usuario_remetente) 
        REFERENCES Usuario(id_usuario),
    CONSTRAINT FK_Destinatario FOREIGN KEY (id_usuario_destinatario) 
        REFERENCES Usuario(id_usuario)
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
    id_usuario INT,
    data_inscricao DATETIME DEFAULT GETDATE(),
    CONSTRAINT FK_Inscricoes_Usuario FOREIGN KEY (id_usuario) 
        REFERENCES Usuario(id_usuario)
);
GO

-- Tabela de Notificações
CREATE TABLE Notificacao (
    id_notificacao INT IDENTITY(1,1) PRIMARY KEY,
    id_usuario INT NOT NULL,
    mensagem NVARCHAR(MAX) NOT NULL,
    data_hora DATETIME DEFAULT GETDATE(),
    lida BIT DEFAULT 0,
    CONSTRAINT FK_Notificacao_Usuario FOREIGN KEY (id_usuario) 
        REFERENCES Usuario(id_usuario)
);
GO

USE prolink;
GO

-- Inserções na tabela Usuario
INSERT INTO Usuario (nome, email, senha, dataNascimento, telefone, qr_code, data_geracao_qr)
VALUES 
('João Silva', 'joao.silva@email.com', 'senha123', '1990-05-15', '(11)98765-4321', 'qr_joao.png', GETDATE()),
('Maria Oliveira', 'maria.oliveira@email.com', 'senha456', '1985-08-22', '(21)99876-5432', 'qr_maria.png', GETDATE()),
('Carlos Souza', 'carlos.souza@email.com', 'senha789', '1995-03-10', '(31)98765-1234', 'qr_carlos.png', GETDATE()),
('Ana Costa', 'ana.costa@email.com', 'senha101', '1988-11-30', '(41)91234-5678', 'qr_ana.png', GETDATE()),
('Pedro Santos', 'pedro.santos@email.com', 'senha202', '1992-07-25', '(51)92345-6789', 'qr_pedro.png', GETDATE());
GO

-- Inserções na tabela Perfil
INSERT INTO Perfil (id_usuario, idade, endereco, formacao, experiencia_profissional, interesses, projetos_especializacoes, habilidades, qr_code)
VALUES 
(1, 33, 'Rua A, 100 - São Paulo/SP', 'Engenharia de Software', '5 anos como desenvolvedor full-stack', 'Tecnologia, esportes', 'Sistema de gestão ERP', 'Java, Python, SQL', 'qr_joao_profile.png'),
(2, 38, 'Av. B, 200 - Rio de Janeiro/RJ', 'Administração', '10 anos em RH', 'Leitura, viagens', 'Programa de treinamento', 'Recrutamento, Treinamento', 'qr_maria_profile.png'),
(3, 28, 'Rua C, 300 - Belo Horizonte/MG', 'Ciência da Computação', '3 anos como analista de dados', 'Data Science, IA', 'Modelo preditivo de vendas', 'Python, R, SQL', 'qr_carlos_profile.png'),
(4, 35, 'Av. D, 400 - Curitiba/PR', 'Design Gráfico', '7 anos em agências', 'Arte, fotografia', 'Identidade visual para startups', 'Photoshop, Illustrator', 'qr_ana_profile.png'),
(5, 31, 'Rua E, 500 - Porto Alegre/RS', 'Marketing Digital', '6 anos em e-commerce', 'Redes sociais, SEO', 'Campanhas de performance', 'Google Ads, Facebook Ads', 'qr_pedro_profile.png');
GO

-- Inserções na tabela Funcionario (incluindo um admin master)
INSERT INTO Funcionario (nome_completo, email, senha, nivel_acesso, criado_por, data_cadastro, ultimo_acesso, ativo)
VALUES 
('Admin Master', 'admin@prolink.com', 'admin123', 0, NULL, GETDATE(), GETDATE(), 1),
('Gerente RH', 'rh@prolink.com', 'rh456', 1, 1, GETDATE(), GETDATE(), 1),
('Supervisor TI', 'ti@prolink.com', 'ti789', 2, 1, GETDATE(), GETDATE(), 1),
('Analista Marketing', 'marketing@prolink.com', 'mkt101', 2, 2, GETDATE(), GETDATE(), 1),
('Assistente Comercial', 'comercial@prolink.com', 'com202', 2, 2, GETDATE(), GETDATE(), 1);
GO

-- Inserções na tabela AreaAtuacao
INSERT INTO AreaAtuacao (nome_area)
VALUES 
('Desenvolvimento de Software'),
('Recursos Humanos'),
('Ciência de Dados'),
('Design Gráfico'),
('Marketing Digital');
GO

-- Inserções na tabela ProfissionalArea
INSERT INTO ProfissionalArea (id_usuario, id_area)
VALUES 
(1, 1), -- João - Desenvolvimento
(2, 2), -- Maria - RH
(3, 3), -- Carlos - Ciência de Dados
(4, 4), -- Ana - Design
(5, 5), -- Pedro - Marketing
(1, 3), -- João também em Ciência de Dados
(3, 1); -- Carlos também em Desenvolvimento
GO

-- Inserções na tabela Vagas
INSERT INTO Vagas (id_funcionario, titulo_vaga, localizacao, tipo_emprego, id_area, id_usuario)
VALUES 
(3, 'Desenvolvedor Back-end Java', 'São Paulo - SP', 'full-time', 1, NULL),
(2, 'Analista de RH Pleno', 'Rio de Janeiro - RJ', 'full-time', 2, 2),
(3, 'Cientista de Dados Júnior', 'Remoto', 'part-time', 3, NULL),
(4, 'Designer UX/UI', 'Curitiba - PR', 'full-time', 4, 4),
(5, 'Especialista em SEO', 'Remoto', 'part-time', 5, NULL);
GO

-- Inserções na tabela Candidatura
INSERT INTO Candidatura (id_vaga, id_perfil, status)
VALUES 
(1, 1, 'Aprovado'),
(1, 3, 'Em análise'),
(2, 2, 'Aprovado'),
(3, 3, 'Reprovado'),
(4, 4, 'Aprovado');
GO

-- Inserções na tabela Mensagem
INSERT INTO Mensagem (id_usuario_remetente, id_usuario_destinatario, texto, data_hora, lida)
VALUES 
(1, 2, 'Olá Maria, gostaria de saber mais sobre a vaga de RH.', GETDATE(), 0),
(2, 1, 'Claro João, que informações você precisa?', GETDATE(), 0),
(3, 5, 'Pedro, vi seu perfil e temos uma oportunidade em Marketing.', GETDATE(), 1),
(4, 3, 'Carlos, como vai o projeto de dados?', GETDATE(), 0),
(5, 4, 'Ana, precisamos atualizar nosso material gráfico.', GETDATE(), 1);
GO

-- Inserções na tabela inscricoes_webinar
INSERT INTO inscricoes_webinar (nome_completo, email, telefone, recebe_notificacoes, consentimento_lgpd, id_usuario)
VALUES 
('João Silva', 'joao.silva@email.com', '(11)98765-4321', 1, 1, 1),
('Maria Oliveira', 'maria.oliveira@email.com', '(21)99876-5432', 1, 1, 2),
('Carlos Souza', 'carlos.souza@email.com', '(31)98765-1234', 0, 1, 3),
('Ana Costa', 'ana.costa@email.com', '(41)91234-5678', 1, 1, 4),
('Pedro Santos', 'pedro.santos@email.com', '(51)92345-6789', 0, 1, 5);
GO

-- Inserções na tabela Notificacao
INSERT INTO Notificacao (id_usuario, mensagem, data_hora, lida)
VALUES 
(1, 'Sua candidatura foi aprovada!', GETDATE(), 0),
(2, 'Nova mensagem recebida de João Silva', GETDATE(), 1),
(3, 'Webinar sobre Data Science amanhã às 10h', GETDATE(), 0),
(4, 'Seu design foi selecionado para a campanha', GETDATE(), 1),
(5, 'Atualização nas políticas de Marketing Digital', GETDATE(), 0);
GO

-- Verificando as inserções
SELECT 'Usuario' AS Tabela, COUNT(*) AS Registros FROM Usuario
UNION ALL
SELECT 'Perfil', COUNT(*) FROM Perfil
UNION ALL
SELECT 'Funcionario', COUNT(*) FROM Funcionario
UNION ALL
SELECT 'AreaAtuacao', COUNT(*) FROM AreaAtuacao
UNION ALL
SELECT 'ProfissionalArea', COUNT(*) FROM ProfissionalArea
UNION ALL
SELECT 'Vagas', COUNT(*) FROM Vagas
UNION ALL
SELECT 'Candidatura', COUNT(*) FROM Candidatura
UNION ALL
SELECT 'Mensagem', COUNT(*) FROM Mensagem
UNION ALL
SELECT 'inscricoes_webinar', COUNT(*) FROM inscricoes_webinar
UNION ALL
SELECT 'Notificacao', COUNT(*) FROM Notificacao;

SELECT * FROM Usuario 