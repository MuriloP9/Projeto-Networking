-- Verifica se o banco de dados existe e o deleta se existir
IF EXISTS (SELECT name FROM sys.databases WHERE name = 'prolink01')
BEGIN
    -- Força a desconexão de todos os usuários conectados ao banco
    ALTER DATABASE prolink01 SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
    DROP DATABASE prolink01;
END
GO

-- Criação do banco de dados
CREATE DATABASE prolink01;
GO

USE prolink01;
GO

-- Tabela de Usuários (não depende de nenhuma outra tabela)
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
    foto_perfil VARBINARY(MAX) NULL,
    token_rec_senha NVARCHAR(64) NULL,
    dt_expiracao_token DATETIME NULL,
    timestamp_expiracao BIGINT NULL,
	statusLGPD BIT NOT NULL DEFAULT 0,
    IP_registro VARCHAR(45) NULL
);
GO

-- Tabela de Funcionários (auto-referencial)
CREATE TABLE Funcionario (
    id_funcionario INT IDENTITY(1,1) PRIMARY KEY,
    nome_completo NVARCHAR(255) NOT NULL,
    email NVARCHAR(100) UNIQUE NOT NULL,
    senha NVARCHAR(255) NOT NULL,
    nivel_acesso INT NOT NULL DEFAULT 2,  -- 0=Admin, 1=Gerente, 2=Supervisor
    criado_por INT NULL,                 -- ID de quem cadastrou (NULL = Admin Master)
    data_cadastro DATETIME DEFAULT GETDATE(),
    ultimo_acesso DATETIME NULL,         -- Último login
    ativo BIT DEFAULT 1                  -- 1=Ativo, 0=Inativo
);
GO

-- Adicionando a restrição de chave estrangeira auto-referencial após a criação da tabela
ALTER TABLE Funcionario
ADD CONSTRAINT FK_Funcionario_CriadoPor FOREIGN KEY (criado_por) 
REFERENCES Funcionario(id_funcionario);
GO

-- Tabela de Perfil (depende de Usuario)
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

-- Tabela de Áreas de Atuação (não depende de nenhuma outra tabela)
CREATE TABLE AreaAtuacao (
    id_area INT IDENTITY(1,1) PRIMARY KEY,
    nome_area NVARCHAR(100) NOT NULL
);
GO

-- Tabela de Vagas (depende de Funcionario, AreaAtuacao e Usuario)
CREATE TABLE Vagas (
    id_vaga INT IDENTITY(1,1) PRIMARY KEY,
    id_funcionario INT NOT NULL,
    titulo_vaga NVARCHAR(255) NOT NULL,
    localizacao NVARCHAR(255),
    tipo_emprego NVARCHAR(20) NOT NULL,
    descricao NVARCHAR(MAX) NULL,
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

-- Tabela de Profissionais em Áreas (depende de Usuario e AreaAtuacao)
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

-- Tabela de Candidaturas (depende de Vagas e Perfil)
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

-- Tabela de Mensagens (depende de Usuario)
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

-- Tabela de Webinar (não depende de nenhuma outra tabela)
CREATE TABLE Webinar (
    id_webinar INT IDENTITY(1,1) PRIMARY KEY,
    tema NVARCHAR(255) NULL,
    data_hora DATETIME NULL,
    palestrante NVARCHAR(255) NULL,
    link NVARCHAR(500) NULL,
    descricao NVARCHAR(MAX) NULL,
    data_cadastro DATETIME DEFAULT GETDATE()
);
GO

-- Tabela de Inscrições em Webinars (depende de Usuario)
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

-- Tabela de Contatos (depende de Usuario)
CREATE TABLE Contatos (
    id_contatos INT IDENTITY(1,1) PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_contato INT NOT NULL,
    data_adicao DATETIME NOT NULL DEFAULT GETDATE(),
    bloqueado BIT DEFAULT 0,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario),
    FOREIGN KEY (id_contato) REFERENCES Usuario(id_usuario),
    -- Garante que um usuário não adicione o mesmo contato duas vezes
    CONSTRAINT UC_Contato UNIQUE (id_usuario, id_contato)
);
GO
-- ================= COMANDOS INSERT ================== --
-- Começo com as tabelas que não dependem de outras

-- 1. Inserção na tabela Usuario
INSERT INTO Usuario (nome, email, senha, dataNascimento, telefone, ativo, statusLGPD)
VALUES 
('João Silva', 'joao.silva@email.com', 'senha123', '1990-05-15', '11987654321', 1, 1),
('Maria Oliveira', 'maria.oliveira@email.com', 'senha456', '1985-08-20', '21987654321', 1, 1),
('Carlos Souza', 'carlos.souza@email.com', 'senha789', '1995-03-10', '31987654321', 1, 1),
('Ana Pereira', 'ana.pereira@email.com', 'senhaabc', '1992-11-25', '41987654321', 1, 1),
('Pedro Costa', 'pedro.costa@email.com', 'senhaxyz', '1988-07-30', '51987654321', 1, 1);
GO

-- 2. Inserção na tabela Funcionario (começando pelo admin master que não tem referência)
-- Admin master (sem criado_por)
INSERT INTO Funcionario (nome_completo, email, senha, nivel_acesso, ativo)
VALUES ('Admin Master', 'admin@empresa.com', 'admin123', 0, 1);

-- Outros funcionários (criados pelo admin master)
INSERT INTO Funcionario (nome_completo, email, senha, nivel_acesso, criado_por, ativo)
VALUES
('Gerente RH', 'gerente.rh@empresa.com', 'gerente123', 1, 1, 1),
('Supervisor TI', 'supervisor.ti@empresa.com', 'super123', 2, 1, 1),
('Analista Recrutamento', 'recrutamento@empresa.com', 'rec123', 2, 2, 1);
GO

-- 3. Inserção na tabela de Áreas de Atuação
INSERT INTO AreaAtuacao (nome_area)
VALUES
('Tecnologia da Informação'),
('Recursos Humanos'),
('Engenharia'),
('Design'),
('Administração');
GO

-- 4. Inserção na tabela Webinar
INSERT INTO Webinar (tema, data_hora, palestrante, link, descricao)
VALUES
('Introdução à Inteligência Artificial', '20240615 14:00:00', 'Dr. Carlos Silva', 'https://www.youtube.com/watch?v=8tPnX7OPo0Q', 'Webinar introdutório sobre os conceitos básicos de IA e machine learning para iniciantes.'),
('Desenvolvimento Web Moderno com React', '20240620 19:30:00', 'Ana Beatriz Souza', 'https://www.youtube.com/watch?v=Ke90Tje7VS0', 'Aprenda os fundamentos do React e como criar aplicações web modernas.'),
('Gestão Ágil de Projetos com Scrum', '20240625 10:00:00', 'Roberto Almeida', 'https://www.youtube.com/watch?v=9TycLR0TqFA', 'Domine as práticas essenciais do framework Scrum para gerenciamento de projetos.'),
('Segurança da Informação para Empresas', '20240705 16:00:00', 'Dra. Fernanda Costa', 'https://www.youtube.com/watch?v=inWWhr5tnEA', 'Proteja seus dados corporativos contra ameaças cibernéticas.'),
('Data Science na Prática', '20240712 15:00:00', 'Prof. Marcelo Santos', 'https://www.youtube.com/watch?v=ua-CiDNNj30', 'Aplicações reais de Data Science e análise de dados em diferentes setores.'),
('Blockchain e Criptomoedas', '20240718 20:00:00', 'Lucas Oliveira', 'https://www.youtube.com/watch?v=1PU5AfTfN3Q', 'Entenda a tecnologia por trás do Bitcoin e outras criptomoedas.'),
('UX/UI Design para Iniciantes', '20240725 11:00:00', 'Camila Rodrigues', 'https://www.youtube.com/watch?v=c9Wg6Cb_YlU', 'Princípios fundamentais de design de interface e experiência do usuário.'),
('Cloud Computing com AWS', '20240803 14:30:00', 'Eng. Thiago Lima', 'https://www.youtube.com/watch?v=IT1X42D1KeA', 'Introdução aos serviços de nuvem da Amazon Web Services.'),
('Marketing Digital para Pequenos Negócios', '20240810 09:00:00', 'Patrícia Mendes', 'https://www.youtube.com/watch?v=4qNBNg4gX3Y', 'Estratégias eficazes de marketing digital com orçamento limitado.'),
('Programação em Python para Finanças', '20240817 18:00:00', 'Dr. Ricardo Fernandes', 'https://www.youtube.com/watch?v=GhrvZ6nUoG8', 'Aplicações de Python em análise financeira e algoritmos de trading.');
GO

-- 5. Inserção na tabela Perfil (depende de Usuario)
INSERT INTO Perfil (id_usuario, idade, endereco, formacao, experiencia_profissional, interesses, habilidades)
VALUES
(1, 33, 'Rua A, 123 - São Paulo/SP', 'Engenharia de Software', '5 anos como desenvolvedor Java', 'Tecnologia, Esportes', 'Java, Spring, SQL'),
(2, 38, 'Av. B, 456 - Rio de Janeiro/RJ', 'Administração de Empresas', '10 anos em RH', 'Recursos Humanos, Psicologia', 'Recrutamento, Treinamento'),
(3, 28, 'Rua C, 789 - Belo Horizonte/MG', 'Ciência da Computação', '3 anos como desenvolvedor Front-end', 'Programação, Games', 'JavaScript, React, HTML/CSS'),
(4, 31, 'Av. D, 101 - Curitiba/PR', 'Design Gráfico', '6 anos como designer', 'Arte, Fotografia', 'Photoshop, Illustrator, UI/UX'),
(5, 35, 'Rua E, 202 - Porto Alegre/RS', 'Engenharia Civil', '8 anos em construção civil', 'Arquitetura, Sustentabilidade', 'AutoCAD, Gestão de Projetos');
GO

-- 6. Inserção na tabela Vagas (depende de Funcionario e AreaAtuacao)
INSERT INTO Vagas (id_funcionario, titulo_vaga, localizacao, tipo_emprego, descricao, id_area)
VALUES
(2, 'Desenvolvedor Back-end Java', 'São Paulo/SP', 'full-time', 'Vaga para desenvolvedor Java com experiência em Spring Boot', 1),
(3, 'Analista de RH', 'Rio de Janeiro/RJ', 'full-time', 'Vaga para analista de RH com experiência em recrutamento', 2),
(2, 'Designer UI/UX', 'Remoto', 'part-time', 'Vaga para designer com experiência em interfaces', 4),
(4, 'Engenheiro Civil', 'Belo Horizonte/MG', 'full-time', 'Vaga para engenheiro civil com experiência em obras', 3);
GO

-- 7. Inserção na tabela ProfissionalArea (depende de Usuario e AreaAtuacao)
INSERT INTO ProfissionalArea (id_usuario, id_area)
VALUES
(1, 1), -- João Silva em TI
(2, 2), -- Maria Oliveira em RH
(3, 1), -- Carlos Souza em TI
(4, 4), -- Ana Pereira em Design
(5, 3); -- Pedro Costa em Engenharia
GO

-- 8. Inserção na tabela Candidatura (depende de Vagas e Perfil)
INSERT INTO Candidatura (id_vaga, id_perfil, status)
VALUES
(1, 1, 'Pendente'), -- João se candidatou a vaga de Dev Java
(1, 3, 'Aprovada'), -- Carlos se candidatou a vaga de Dev Java
(2, 2, 'Recusada'), -- Maria se candidatou a vaga de RH
(3, 4, 'Pendente'); -- Ana se candidatou a vaga de Designer
GO

-- 9. Inserção na tabela Mensagem (depende de Usuario)
INSERT INTO Mensagem (id_usuario_remetente, id_usuario_destinatario, texto)
VALUES
(1, 3, 'Olá Carlos, vi seu perfil e gostaria de conversar sobre oportunidades'),
(3, 1, 'Oi João, claro! Podemos marcar uma conversa'),
(2, 4, 'Ana, você tem interesse em participar de um projeto?'),
(4, 2, 'Sim Maria, me conte mais sobre esse projeto');
GO

-- 10. Inserção na tabela inscricoes_webinar (depende de Usuario)
INSERT INTO inscricoes_webinar (nome_completo, email, telefone, recebe_notificacoes, consentimento_lgpd, id_usuario)
VALUES
('João Silva', 'joao.silva@email.com', '11987654321', 1, 1, 1),
('Carlos Souza', 'carlos.souza@email.com', '31987654321', 0, 1, 3),
('Ana Pereira', 'ana.pereira@email.com', '41987654321', 1, 1, 4);
GO


 --ALTER TABLE Usuario
    --ADD timestamp_expiracao BIGINT NULL;

	--ALTER TABLE Usuario
	--Drop column token_ativacao




