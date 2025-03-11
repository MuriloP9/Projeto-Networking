<?php
session_start(); // Verifica se a sessão está sendo iniciada corretamente

// Função para conectar ao banco de dados
function conectar() {
    $local_server = "PC_NASA\SQLEXPRESS";
    $usuario_server = "sa";
    $senha_server = "etesp";
    $banco_de_dados = "prolink";

    try {
        $pdo = new PDO("sqlsrv:server=$local_server;database=$banco_de_dados", $usuario_server, $senha_server);
        return $pdo;
    } catch (Exception $erro) {
        echo "Erro na conexão: " . $erro->getMessage();
        die;
    }
}

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Dados do formulário
    $nome = isset($_POST["nome"]) ? $_POST["nome"] : null;
    $email = isset($_POST["email"]) ? $_POST["email"] : null;
    $senha = isset($_POST["senha"]) ? $_POST["senha"] : null;
    $dataNascimento = isset($_POST["dataNascimento"]) ? $_POST["dataNascimento"] : null;
    $telefone = isset($_POST["telefone"]) ? $_POST["telefone"] : null;
    $idade = isset($_POST["idade"]) ? $_POST["idade"] : null;
    $localizacao = isset($_POST["localizacao"]) ? $_POST["localizacao"] : null;
    $formacao = isset($_POST["formacao"]) ? $_POST["formacao"] : null;
    $experiencia_profissional = isset($_POST["experiencia_profissional"]) ? $_POST["experiencia_profissional"] : null;
    $interesses = isset($_POST["interesses"]) ? $_POST["interesses"] : null;
    $projetos_especializacoes = isset($_POST["projetos_especializacoes"]) ? $_POST["projetos_especializacoes"] : null;
    $habilidades = isset($_POST["habilidades"]) ? $_POST["habilidades"] : null;
    $contato_email = isset($_POST["contato_email"]) ? $_POST["contato_email"] : null;
    $contato_telefone = isset($_POST["contato_telefone"]) ? $_POST["contato_telefone"] : null;

    // Valida campos obrigatórios
    if (!$nome || !$email || !$senha || !$dataNascimento || !$telefone) {
        echo "Todos os campos são obrigatórios!";
        exit;
    }

    // Conectar ao banco de dados
    $pdo = conectar();

    // Inserir dados na tabela Usuario
    try {
        $sql = $pdo->prepare("INSERT INTO Usuario (nome, email, senha, dataNascimento, telefone) VALUES (:nome, :email, :senha, :dataNascimento, :telefone)");
        $sql->bindValue(":nome", $nome);
        $sql->bindValue(":email", $email);
        $sql->bindValue(":senha", $senha);
        $sql->bindValue(":dataNascimento", $dataNascimento);
        $sql->bindValue(":telefone", $telefone);
        $sql->execute();

        // Recupera o ID do usuário recém-cadastrado
        $id_usuario = $pdo->lastInsertId();

        // Inserir dados na tabela Perfil
        $sql_perfil = $pdo->prepare("
            INSERT INTO Perfil (id_usuario, idade, localizacao, formacao, experiencia_profissional, interesses, projetos_especializacoes, habilidades, contato_email, contato_telefone)
            VALUES (:id_usuario, :idade, :localizacao, :formacao, :experiencia_profissional, :interesses, :projetos_especializacoes, :habilidades, :contato_email, :contato_telefone)
        ");
        $sql_perfil->bindValue(":id_usuario", $id_usuario);
        $sql_perfil->bindValue(":idade", $idade);
        $sql_perfil->bindValue(":localizacao", $localizacao);
        $sql_perfil->bindValue(":formacao", $formacao);
        $sql_perfil->bindValue(":experiencia_profissional", $experiencia_profissional);
        $sql_perfil->bindValue(":interesses", $interesses);
        $sql_perfil->bindValue(":projetos_especializacoes", $projetos_especializacoes);
        $sql_perfil->bindValue(":habilidades", $habilidades);
        $sql_perfil->bindValue(":contato_email", $contato_email);
        $sql_perfil->bindValue(":contato_telefone", $contato_telefone);
        $sql_perfil->execute();

        $_SESSION['cadastro_realizado'] = true;
        header('Location: inclusaoCadastro.html'); // Redireciona para a página de sucesso
        exit;
    } catch (Exception $erro) {
        echo "Erro ao cadastrar: " . $erro->getMessage();
        exit;
    }
}
?>