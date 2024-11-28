CREATE DATABASE prolink;

use prolink;

use master;

drop database prolink;

CREATE TABLE inscricoes_webinar
 (
    id INT IDENTITY(1,1) PRIMARY KEY,
    nome_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefone VARCHAR(20),
    recebe_notificacoes BIT DEFAULT 0,
    consentimento_lgpd BIT NOT NULL,
    data_inscricao DATETIME DEFAULT GETDATE()
);

select*from inscricoes_webinar;


CREATE TABLE cadastro (
    id INT IDENTITY(1,1) PRIMARY KEY, -- ID único e auto-incrementado
    nome VARCHAR(255) NOT NULL,         -- Nome completo
    email VARCHAR(255) NOT NULL UNIQUE, -- Email único
    senha VARCHAR(255) NOT NULL,        -- Senha (será salva de forma segura, idealmente criptografada)
    dataNascimento DATE NOT NULL,      -- Data de nascimento
    telefone VARCHAR(20) NOT NULL       -- Telefone
);

select * from cadastro;
