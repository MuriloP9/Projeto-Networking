<?php
session_start(); // Verifica se a sessão está sendo iniciada corretamente

// Função para conectar ao banco de dados
function conectar() {
    //$local_server = "PCNASA";
    $local_server = "Book3-Marina";
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

function limpar($valor) {
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}


// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = isset($_POST["nome"]) ? limpar($_POST["nome"]) : null;
    $email = isset($_POST["email"]) ? filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL) : null;
    $senha = isset($_POST["senha"]) ? trim($_POST["senha"]) : null;
    $dataNascimento = isset($_POST["dataNascimento"]) ? trim($_POST["dataNascimento"]) : null;
    $telefone = isset($_POST["telefone"]) ? limpar($_POST["telefone"]) : null;
    $idade = isset($_POST["idade"]) ? filter_var($_POST["idade"], FILTER_VALIDATE_INT) : null;
    $endereco = isset($_POST["endereco"]) ? limpar($_POST["endereco"]) : null;
    $formacao = isset($_POST["formacao"]) ? limpar($_POST["formacao"]) : null;
    $experiencia_profissional = isset($_POST["experiencia_profissional"]) ? limpar($_POST["experiencia_profissional"]) : null;
    $interesses = isset($_POST["interesses"]) ? limpar($_POST["interesses"]) : null;
    $projetos_especializacoes = isset($_POST["projetos_especializacoes"]) ? limpar($_POST["projetos_especializacoes"]) : null;
    $habilidades = isset($_POST["habilidades"]) ? limpar($_POST["habilidades"]) : null;

    if (!$nome || !$email || !$senha || !$dataNascimento || !$telefone) {
        echo "Todos os campos obrigatórios devem ser preenchidos!";
        exit;
    }

    $pdo = conectar();

    $query = $pdo->prepare("SELECT COUNT(*) FROM Usuario WHERE email = :email");
    $query->bindValue(":email", $email);
    $query->execute();
    $count = $query->fetchColumn();

    if ($count > 0) {
        echo "Este email já está em uso!";
        exit;
    }
    
    // Inserir dados na tabela Usuario
    // Inserir dados na tabela Usuario
try {
    $sql = $pdo->prepare("INSERT INTO Usuario (nome, email, senha, dataNascimento, telefone) 
                          VALUES (:nome, :email, :senha, :dataNascimento, :telefone)");
    $sql->bindValue(":nome", $nome);
    $sql->bindValue(":email", $email);
    $sql->bindValue(":senha", $senha);
    $sql->bindValue(":dataNascimento", $dataNascimento);
    $sql->bindValue(":telefone", $telefone);
    $sql->execute();

    // Recupera o ID do usuário recém-cadastrado
    $id_usuario = $pdo->lastInsertId();

    // ✅ Define as variáveis de sessão: logado e cadastro realizado
    $_SESSION['cadastro_realizado'] = true;

    // Inserir dados na tabela Perfil
    $sql_perfil = $pdo->prepare("
        INSERT INTO Perfil (id_usuario, idade, endereco, formacao, experiencia_profissional, interesses, projetos_especializacoes, habilidades)
        VALUES (:id_usuario, :idade, :endereco, :formacao, :experiencia_profissional, :interesses, :projetos_especializacoes, :habilidades)
    ");
    $sql_perfil->bindValue(":id_usuario", $id_usuario);
    $sql_perfil->bindValue(":idade", $idade);
    $sql_perfil->bindValue(":endereco", $endereco);
    $sql_perfil->bindValue(":formacao", $formacao);
    $sql_perfil->bindValue(":experiencia_profissional", $experiencia_profissional);
    $sql_perfil->bindValue(":interesses", $interesses);
    $sql_perfil->bindValue(":projetos_especializacoes", $projetos_especializacoes);
    $sql_perfil->bindValue(":habilidades", $habilidades);
    $sql_perfil->execute();

    echo "ok";
    exit;
    
} catch (Exception $erro) {
    echo "Erro ao cadastrar: " . $erro->getMessage();
    exit;
}
}
?>