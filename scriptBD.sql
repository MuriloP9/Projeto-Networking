CREATE DATABASE prolink;
use prolink;


CREATE TABLE inscricoes_webinar
 (
    id INT IDENTITY(1,1) PRIMARY KEY,
    nome_completo NVARCHAR(100) NOT NULL,
    email NVARCHAR(100) NOT NULL,
    telefone NVARCHAR(20),
    recebe_notificacoes BIT DEFAULT 0,
    consentimento_lgpd BIT NOT NULL,
    data_inscricao DATETIME DEFAULT GETDATE()
);