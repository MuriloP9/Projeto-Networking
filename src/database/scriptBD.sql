-- =====================================================
-- SCRIPT DE CRIAÇÃO DO BANCO DE DADOS PROLINK01
-- =====================================================

-- Criação do banco de dados
CREATE DATABASE prolink01;
GO

USE prolink01;
GO

-- =====================================================
-- CRIAÇÃO DAS TABELAS
-- =====================================================

-- Tabela de Usuários
CREATE TABLE Usuario (
    id_usuario INT IDENTITY(1,1) PRIMARY KEY,
    nome NVARCHAR(255) NOT NULL,
    email NVARCHAR(100) UNIQUE NOT NULL,
    senha VARBINARY(MAX) NOT NULL,
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

-- Tabela de Funcionários
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

-- Tabela de Vagas
CREATE TABLE Vagas (
    id_vaga INT IDENTITY(1,1) PRIMARY KEY,
    id_funcionario INT NOT NULL,
    titulo_vaga NVARCHAR(255) NOT NULL,
    localizacao NVARCHAR(255),
    tipo_emprego NVARCHAR(20) NOT NULL,
    descricao NVARCHAR(MAX),
    id_area INT,
    id_usuario INT,
    empresa NVARCHAR(100) NOT NULL,
    salario DECIMAL(10,2) NULL,
    requisitos NVARCHAR(MAX) NULL,
    beneficios NVARCHAR(MAX) NULL,
    data_publicacao DATETIME DEFAULT GETDATE(),
    data_encerramento DATETIME NULL,
    ativa BIT DEFAULT 1,
    FOREIGN KEY (id_funcionario) REFERENCES Funcionario(id_funcionario),
    FOREIGN KEY (id_area) REFERENCES AreaAtuacao(id_area),
    CONSTRAINT CHK_TipoEmprego CHECK (tipo_emprego IN ('full-time', 'part-time', 'internship'))
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

-- Tabela de Candidaturas
CREATE TABLE Candidatura (
    id_candidatura INT IDENTITY(1,1) PRIMARY KEY,
    id_vaga INT NOT NULL,
    id_perfil INT NOT NULL,
    ativo BIT DEFAULT 1,
    data_candidatura DATETIME DEFAULT GETDATE(),
    data_atualizacao_status DATETIME NULL,
    status NVARCHAR(20) DEFAULT 'Pendente',
    CONSTRAINT FK_Candidatura_Vaga FOREIGN KEY (id_vaga) 
        REFERENCES Vagas(id_vaga),
    CONSTRAINT FK_Candidatura_Perfil FOREIGN KEY (id_perfil) 
        REFERENCES Perfil(id_perfil),
    CONSTRAINT CK_Candidatura_Status CHECK (status IN ('Pendente', 'Aprovado', 'Reprovado'))
);
GO

-- Alteração da tabela Candidatura (adicionar coluna ativo caso não exista)
ALTER TABLE Candidatura 
ADD ativo BIT DEFAULT 1;

-- Atualizar registros existentes para ativo = 1
UPDATE Candidatura 
SET ativo = 1 
WHERE ativo IS NULL;
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

-- Tabela de Webinar
CREATE TABLE Webinar (
    id_webinar INT IDENTITY(1,1) PRIMARY KEY,
    tema NVARCHAR(255) NULL,
    data_hora DATETIME NULL,
    palestrante NVARCHAR(255) NULL,
    link NVARCHAR(500) NULL,
    ativo BIT DEFAULT 1,
    descricao NVARCHAR(MAX) NULL,
    data_cadastro DATETIME DEFAULT GETDATE()
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

-- Tabela de Contatos
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

-- Tabela de Histórico de Acessos
CREATE TABLE HistoricoAcessos (
    id_historico INT IDENTITY(1,1) PRIMARY KEY,
    id_funcionario INT NOT NULL,
    email NVARCHAR(100) NOT NULL,
    data_login DATETIME NOT NULL DEFAULT GETDATE(),
    data_logout DATETIME NULL,
    FOREIGN KEY (id_funcionario) REFERENCES Funcionario(id_funcionario)
);
GO

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Trigger para atualização automática da data_atualizacao_status
CREATE TRIGGER TR_Candidatura_StatusUpdate
ON Candidatura
AFTER UPDATE
AS
BEGIN
    IF UPDATE(status)
    BEGIN
        UPDATE Candidatura 
        SET data_atualizacao_status = GETDATE()
        FROM Candidatura c
        INNER JOIN inserted i ON c.id_candidatura = i.id_candidatura
        WHERE c.status IN ('Aprovado', 'Reprovado');
    END
END;
GO

-- =====================================================
-- CONFIGURAÇÃO DE CRIPTOGRAFIA
-- =====================================================

-- Criar Master Key
IF NOT EXISTS (SELECT * FROM sys.symmetric_keys WHERE name = '##MS_DatabaseMasterKey##')
BEGIN
    CREATE MASTER KEY 
    ENCRYPTION BY PASSWORD = 'PASSWORD@123ProLink2024!'
END
GO

-- Criar Certificado
IF NOT EXISTS (SELECT * FROM sys.certificates WHERE name = 'CertificadoSenhaUsuario')
BEGIN
    CREATE CERTIFICATE CertificadoSenhaUsuario
    ENCRYPTION BY PASSWORD = 'SENHA@123ProLink2024!'
    WITH SUBJECT = 'Certificado para Criptografia de Senhas'
END
GO

-- Criar Chave Simétrica
IF NOT EXISTS (SELECT * FROM sys.symmetric_keys WHERE name = 'ChaveSenhaUsuario')
BEGIN
    CREATE SYMMETRIC KEY ChaveSenhaUsuario
    WITH ALGORITHM = AES_256
    ENCRYPTION BY CERTIFICATE CertificadoSenhaUsuario
END
GO

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

-- Procedure para criptografar senha
IF EXISTS (SELECT * FROM sys.procedures WHERE name = 'sp_CriptografarSenha')
    DROP PROCEDURE sp_CriptografarSenha
GO

CREATE PROCEDURE sp_CriptografarSenha
    @SenhaTexto NVARCHAR(255),
    @SenhaCriptografada VARBINARY(MAX) OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    
    BEGIN TRY
        -- Abrir chave simétrica
        OPEN SYMMETRIC KEY ChaveSenhaUsuario
        DECRYPTION BY CERTIFICATE CertificadoSenhaUsuario 
        WITH PASSWORD = 'SENHA@123ProLink2024!'
        
        -- Criptografar senha
        DECLARE @GUID UNIQUEIDENTIFIER = (SELECT KEY_GUID('ChaveSenhaUsuario'))
        SET @SenhaCriptografada = ENCRYPTBYKEY(@GUID, @SenhaTexto)
        
        -- Fechar chave
        CLOSE SYMMETRIC KEY ChaveSenhaUsuario
        
    END TRY
    BEGIN CATCH
        -- Garantir que a chave seja fechada em caso de erro
        IF EXISTS (SELECT * FROM sys.openkeys WHERE key_name = 'ChaveSenhaUsuario')
            CLOSE SYMMETRIC KEY ChaveSenhaUsuario
        
        RAISERROR('Erro ao criptografar senha', 16, 1)
        RETURN
    END CATCH
END
GO

-- Procedure para descriptografar senha (apenas para validação)
IF EXISTS (SELECT * FROM sys.procedures WHERE name = 'sp_DescriptografarSenha')
    DROP PROCEDURE sp_DescriptografarSenha
GO

CREATE PROCEDURE sp_DescriptografarSenha
    @SenhaCriptografada VARBINARY(MAX),
    @SenhaTexto NVARCHAR(255) OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    
    BEGIN TRY
        -- Abrir chave simétrica
        OPEN SYMMETRIC KEY ChaveSenhaUsuario
        DECRYPTION BY CERTIFICATE CertificadoSenhaUsuario 
        WITH PASSWORD = 'SENHA@123ProLink2024!'
        
        -- Descriptografar senha
        SET @SenhaTexto = CAST(DECRYPTBYKEY(@SenhaCriptografada) AS NVARCHAR(255))
        
        -- Fechar chave
        CLOSE SYMMETRIC KEY ChaveSenhaUsuario
        
    END TRY
    BEGIN CATCH
        -- Garantir que a chave seja fechada em caso de erro
        IF EXISTS (SELECT * FROM sys.openkeys WHERE key_name = 'ChaveSenhaUsuario')
            CLOSE SYMMETRIC KEY ChaveSenhaUsuario
        
        RAISERROR('Erro ao descriptografar senha', 16, 1)
        RETURN
    END CATCH
END
GO

-- Procedure de login WEB
IF EXISTS (SELECT * FROM sys.procedures WHERE name = 'sp_ValidarLogin')
    DROP PROCEDURE sp_ValidarLogin
GO

CREATE PROCEDURE sp_ValidarLogin
    @Email NVARCHAR(255),
    @Senha NVARCHAR(255)
AS
BEGIN
    SET NOCOUNT ON;
    
    DECLARE @Resultado TABLE (
        sucesso BIT,
        mensagem NVARCHAR(500),
        id_usuario INT,
        nome NVARCHAR(255),
        id_perfil INT
    );
    
    BEGIN TRY
        -- Abrir chave simétrica para descriptografia
        OPEN SYMMETRIC KEY ChaveSenhaUsuario
        DECRYPTION BY CERTIFICATE CertificadoSenhaUsuario 
        WITH PASSWORD = 'SENHA@123ProLink2024!'
        
        -- Verificar se o usuário existe e está ativo
        IF EXISTS (
            SELECT 1 
            FROM Usuario u 
            WHERE u.email = @Email 
            AND u.ativo = 1
            AND CAST(DECRYPTBYKEY(u.senha) AS NVARCHAR(255)) = @Senha
        )
        BEGIN
            -- Usuário válido e ativo
            INSERT INTO @Resultado (sucesso, mensagem, id_usuario, nome, id_perfil)
            SELECT 
                1 as sucesso,
                'Login realizado com sucesso!' as mensagem,
                u.id_usuario,
                u.nome,
                ISNULL(p.id_perfil, 0) as id_perfil
            FROM Usuario u
            LEFT JOIN Perfil p ON u.id_usuario = p.id_usuario
            WHERE u.email = @Email 
            AND u.ativo = 1
            AND CAST(DECRYPTBYKEY(u.senha) AS NVARCHAR(255)) = @Senha;
            
            -- Atualizar último acesso
            UPDATE Usuario 
            SET ultimo_acesso = GETDATE() 
            WHERE email = @Email AND ativo = 1;
        END
        ELSE
        BEGIN
            -- Verificar se o usuário existe mas está inativo
            IF EXISTS (SELECT 1 FROM Usuario WHERE email = @Email AND ativo = 0)
            BEGIN
                INSERT INTO @Resultado (sucesso, mensagem, id_usuario, nome, id_perfil)
                VALUES (0, 'Usuário inativo. Entre em contato com o administrador.', NULL, NULL, NULL);
            END
            ELSE IF EXISTS (SELECT 1 FROM Usuario WHERE email = @Email AND ativo = 1)
            BEGIN
                -- Usuário existe mas senha incorreta
                INSERT INTO @Resultado (sucesso, mensagem, id_usuario, nome, id_perfil)
                VALUES (0, 'Email ou senha incorretos.', NULL, NULL, NULL);
            END
            ELSE
            BEGIN
                -- Usuário não existe
                INSERT INTO @Resultado (sucesso, mensagem, id_usuario, nome, id_perfil)
                VALUES (0, 'Email ou senha incorretos.', NULL, NULL, NULL);
            END
        END
        
        -- Fechar chave simétrica
        CLOSE SYMMETRIC KEY ChaveSenhaUsuario
        
    END TRY
    BEGIN CATCH
        -- Garantir que a chave seja fechada em caso de erro
        IF EXISTS (SELECT * FROM sys.openkeys WHERE key_name = 'ChaveSenhaUsuario')
            CLOSE SYMMETRIC KEY ChaveSenhaUsuario
        
        -- Erro na execução
        INSERT INTO @Resultado (sucesso, mensagem, id_usuario, nome, id_perfil)
        VALUES (0, 'Erro interno do sistema. Tente novamente.', NULL, NULL, NULL);
    END CATCH
    
    -- Retornar resultado
    SELECT sucesso, mensagem, id_usuario, nome, id_perfil
    FROM @Resultado;
END;
GO

-- =====================================================
-- INSERÇÃO DE DADOS DE TESTE
-- =====================================================

-- Inserção de Webinars
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

-- Inserção de Usuários
INSERT INTO Usuario (nome, email, senha, dataNascimento, telefone, ativo)
VALUES 
('João Silva', 'joao.silva@email.com', 'senha123', '1990-05-15', '11987654321', 1),
('Maria Oliveira', 'maria.oliveira@email.com', 'senha456', '1985-08-20', '21987654321', 1),
('Carlos Souza', 'carlos.souza@email.com', 'senha789', '1995-03-10', '31987654321', 1),
('Ana Pereira', 'ana.pereira@email.com', 'senhaabc', '1992-11-25', '41987654321', 1),
('Pedro Costa', 'pedro.costa@email.com', 'senhaxyz', '1988-07-30', '51987654321', 1);
GO

-- Inserção de Perfis
INSERT INTO Perfil (id_usuario, idade, endereco, formacao, experiencia_profissional, interesses, habilidades)
VALUES
(1, 33, 'Rua A, 123 - São Paulo/SP', 'Engenharia de Software', '5 anos como desenvolvedor Java', 'Tecnologia, Esportes', 'Java, Spring, SQL'),
(2, 38, 'Av. B, 456 - Rio de Janeiro/RJ', 'Administração de Empresas', '10 anos em RH', 'Recursos Humanos, Psicologia', 'Recrutamento, Treinamento'),
(3, 28, 'Rua C, 789 - Belo Horizonte/MG', 'Ciência da Computação', '3 anos como desenvolvedor Front-end', 'Programação, Games', 'JavaScript, React, HTML/CSS'),
(4, 31, 'Av. D, 101 - Curitiba/PR', 'Design Gráfico', '6 anos como designer', 'Arte, Fotografia', 'Photoshop, Illustrator, UI/UX'),
(5, 35, 'Rua E, 202 - Porto Alegre/RS', 'Engenharia Civil', '8 anos em construção civil', 'Arquitetura, Sustentabilidade', 'AutoCAD, Gestão de Projetos');
GO

-- Inserção de Funcionários
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

-- Inserção de Áreas de Atuação
INSERT INTO AreaAtuacao (nome_area)
VALUES
('Tecnologia da Informação'),
('Recursos Humanos'),
('Engenharia'),
('Design'),
('Administração');
GO

-- Inserção de Vagas (primeira parte)
INSERT INTO Vagas (id_funcionario, titulo_vaga, localizacao, tipo_emprego, descricao, id_area)
VALUES
(2, 'Desenvolvedor Back-end Java', 'São Paulo/SP', 'full-time', 'Vaga para desenvolvedor Java com experiência em Spring Boot', 1),
(3, 'Analista de RH', 'Rio de Janeiro/RJ', 'full-time', 'Vaga para analista de RH com experiência em recrutamento', 2),
(2, 'Designer UI/UX', 'Remoto', 'part-time', 'Vaga para designer com experiência em interfaces', 4),
(4, 'Engenheiro Civil', 'Belo Horizonte/MG', 'full-time', 'Vaga para engenheiro civil com experiência em obras', 3);
GO

-- Inserção de Vagas (segunda parte - com mais detalhes)
INSERT INTO Vagas (id_funcionario, titulo_vaga, localizacao, tipo_emprego, descricao, id_area, id_usuario, empresa, salario, requisitos, beneficios)
VALUES
(2, 'Desenvolvedor Back-end Java', 'São Paulo/SP', 'full-time', 'Vaga para desenvolvedor Java com experiência em Spring Boot', 1, 1, 'TechCorp Soluções', 8500.00, 'Graduação em Ciência da Computação ou áreas afins; Experiência mínima de 3 anos com Java; Conhecimento em Spring Boot, JPA/Hibernate; Experiência com banco de dados relacionais (MySQL, PostgreSQL)', 'Vale alimentação; Vale transporte; Plano de saúde; Plano odontológico; Auxílio home office'),

(3, 'Analista de RH', 'Rio de Janeiro/RJ', 'full-time', 'Vaga para analista de RH com experiência em recrutamento', 2, 2, 'Recursos Humanos Plus', 5500.00, 'Graduação em Psicologia, Administração ou Recursos Humanos; Experiência mínima de 2 anos em recrutamento e seleção; Conhecimento em técnicas de entrevista; Domínio do pacote Office', 'Vale alimentação; Vale transporte; Plano de saúde; Participação nos lucros; Flexibilidade de horário'),

(2, 'Designer UI/UX', 'Remoto', 'part-time', 'Vaga para designer com experiência em interfaces', 4, 3, 'Creative Design Studio', 3200.00, 'Graduação em Design, Design Gráfico ou áreas correlatas; Portfolio demonstrando experiência em UI/UX; Domínio de ferramentas como Figma, Adobe XD, Sketch; Conhecimento em prototipagem', 'Horário flexível; Trabalho 100% remoto; Cursos e certificações pagas pela empresa; Equipment allowance'),

(4, 'Engenheiro Civil', 'Belo Horizonte/MG', 'full-time', 'Vaga para engenheiro civil com experiência em obras', 3, 1, 'Construtora MG Ltda', 7800.00, 'Graduação em Engenharia Civil; CREA ativo; Experiência mínima de 4 anos em gerenciamento de obras; Conhecimento em AutoCAD e MS Project; Disponibilidade para viagens', 'Vale alimentação; Vale combustível; Plano de saúde; Plano odontológico; Carro da empresa; Participação nos lucros');
GO

-- Inserção de Profissionais em Áreas
INSERT INTO ProfissionalArea (id_usuario, id_area)
VALUES
(1, 1), -- João Silva em TI
(2, 2), -- Maria Oliveira em RH
(3, 1), -- Carlos Souza em TI
(4, 4), -- Ana Pereira em Design
(5, 3); -- Pedro Costa em Engenharia
GO

-- Inserção de Candidaturas
INSERT INTO Candidatura (id_vaga, id_perfil, status)
VALUES
(1, 1, 'Pendente'), -- João se candidatou a vaga de Dev Java
(1, 3, 'Aprovada'), -- Carlos se candidatou a vaga de Dev Java
(2, 2, 'Recusada'), -- Maria se candidatou a vaga de RH
(3, 4, 'Pendente'); -- Ana se candidatou a vaga de Designer
GO

-- Inserção de Mensagens
INSERT INTO Mensagem (id_usuario_remetente, id_usuario_destinatario, texto)
VALUES
(1, 3, 'Olá Carlos, vi seu perfil e gostaria de conversar sobre oportunidades'),
(3, 1, 'Oi João, claro! Podemos marcar uma conversa'),
(2, 4, 'Ana, você tem interesse em participar de um projeto?'),
(4, 2, 'Sim Maria, me conte mais sobre esse projeto');
GO

-- Inserção de Inscrições em Webinar
INSERT INTO inscricoes_webinar (nome_completo, email, telefone, recebe_notificacoes, consentimento_lgpd, id_usuario)
VALUES
('João Silva', 'joao.silva@email.com', '11987654321', 1, 1, 1),
('Carlos Souza', 'carlos.souza@email.com', '31987654321', 0, 1, 3),
('Ana Pereira', 'ana.pereira@email.com', '41987654321', 1, 1, 4);
GO

-- =====================================================
-- COMANDOS DE CONSULTA E ATUALIZAÇÃO
-- =====================================================

-- Consultas para verificação
select * from Usuario
GO

select * from Perfil
GO

select * from Vagas
GO

select * from Funcionario
GO

select * from Candidatura
GO

select * from Contatos
GO

select * from Mensagem
GO

Select * from Webinar
GO

-- Comandos de atualização específicos
UPDATE Candidatura 
SET status = 'Reprovado' 
WHERE id_candidatura = 53; -- Substituir ? pelo ID da candidatura

-- REPROVAR uma candidatura específica
UPDATE Vagas 
SET ativa = 1
WHERE id_vaga = 8;

UPDATE Webinar 
SET ativo = 1
WHERE id_webinar = 10;