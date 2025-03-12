<?php
include("cadastro.php"); 

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

try {
    $pdo = conectar();

    // Consulta para buscar os dados do usuário
    $sql = $pdo->prepare("
    SELECT u.nome, u.email, u.dataNascimento, u.telefone, 
           COALESCE(p.idade, NULL) as idade, 
           COALESCE(p.localizacao, 'Não informado') as localizacao, 
           COALESCE(p.formacao, 'Não informado') as formacao, 
           COALESCE(p.experiencia_profissional, 'Nenhuma informação') as experiencia_profissional, 
           COALESCE(p.interesses, 'Nenhuma informação') as interesses, 
           COALESCE(p.projetos_especializacoes, 'Nenhuma informação') as projetos_especializacoes, 
           COALESCE(p.habilidades, 'Nenhuma informação') as habilidades, 
           COALESCE(p.contato_email, 'Não informado') as contato_email, 
           COALESCE(p.contato_telefone, 'Não informado') as contato_telefone 
    FROM Usuario u
    LEFT JOIN Perfil p ON u.id_usuario = p.id_usuario
    WHERE u.id_usuario = :id_usuario
");

    $sql->bindValue(":id_usuario", $id_usuario);
    $sql->execute();
    $usuario = $sql->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        echo "Erro ao carregar perfil.";
        exit();
    }

} catch (Exception $erro) {
    echo "Erro ao carregar perfil: " . $erro->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <title>Perfil - ProLink</title>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo-container">
                <img src="img/globo-mundial.png" alt="Logo" class="logo-icon">
                <div class="logo">ProLink</div>
            </div>
            <ul class="menu">
                <li><a href="index.php">Home</a></li>
            </ul>
        </nav>
    </header>

    <div class="cabecalho">
        <h1>Perfil</h1>
        <p>Sou um Desenvolvedor</p>
        <br>
        <img src="./img/296fe121-5dfa-43f4-98b5-db50019738a7.jpg" alt="Avatar" class="perfil-imagem">
    </div>

    <div class="detalhes">
    <h2>Detalhes</h2>
    <div>
        <strong class="Atributos">Nome: </strong><p><?php echo htmlspecialchars($usuario['nome']); ?></p>
    </div>
    <div>
        <strong class="Atributos">Idade: </strong><p><?php echo ($usuario['idade'] !== NULL) ? htmlspecialchars($usuario['idade']) : 'Não informado'; ?></p>
    </div>
    <div>
        <strong class="Atributos">Localização: </strong><p><?php echo htmlspecialchars($usuario['localizacao']); ?></p>
    </div>
    <div>
        <strong class="Atributos">Formação: </strong><p><?php echo htmlspecialchars($usuario['formacao']); ?></p>
    </div>
    <div>
        <strong class="Atributos">Experiência Profissional: </strong><p><?php echo nl2br(htmlspecialchars($usuario['experiencia_profissional'])); ?></p>
    </div>
    <div>
        <strong class="Atributos">Interesses: </strong><p><?php echo nl2br(htmlspecialchars($usuario['interesses'])); ?></p>
    </div>
</div>

<div class="projetos">
    <h2>Projetos e Especializações</h2>
    <div class="conteudo">
        <ul>
            <li><?php echo nl2br(htmlspecialchars($usuario['projetos_especializacoes'])); ?></li>
        </ul>
        <img src="./img/organizing-projects-animate.svg" class="imagem-projeto-perfil" alt="Projetos">
    </div>
</div>

<div class="habilidades">
    <h2>Habilidades</h2>
    <ul>
        <li><?php echo nl2br(htmlspecialchars($usuario['habilidades'])); ?></li>
    </ul>
</div>

<div class="contato">
    <h2>Contato</h2>
    <p><strong>E-mail:</strong> <?php echo htmlspecialchars($usuario['contato_email']); ?></p>
    <p><strong>Telefone:</strong> <?php echo htmlspecialchars($usuario['contato_telefone']); ?></p>
</div>
</body>
</html>

<style> * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Montserrat", sans-serif;
            background-color: #f4f7fb;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            min-height: 100vh;
        }

        header {
            width: 100%;
            background-color: #3b6ebb;
            padding: 1% 0;
            box-shadow: 0 0.4em 1em rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }

        .navbar {
            position: fixed;
            z-index: 1000;
            display: flex;
            width: 100%;
            top: 0;
            left: 0;
            justify-content: space-between;
            align-items: center;
            padding: 20px 50px;  /* Aumenta o tamanho da navbar */
            background-color: #3b6ebb;
        }

        .logo-container {
            display: flex;
            align-items: center;
        }

        .logo-icon {
            width: 50px;  /* Tamanho da imagem do logo */
            height: 50px;
            margin-right: 15px;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #fff;
        }

        .menu {
            list-style: none;
            display: flex;
            justify-content: center;
            gap: 30px; /* Aumenta a distância entre os itens do menu */
        }

        .menu li a {
            color: #0a0a0a;
            text-decoration: none;
            padding: 8px 20px;
            background-color: white;
            border-radius: 5px;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .menu li {
            margin: 0 5px; /* Reduzindo a margem entre os botões */
        }

        .menu li a:hover {
            background-color: #3b6ebb;
            color: #fff;
            transform: scale(1.1);  /* Pequeno efeito de zoom ao passar o mouse */
        }
    .cabecalho {
        text-align: center;
        padding: 5% 3%;
        background-color: #3b6ebb;
        color: #fff;
        border-radius: 0.9em;
        margin: 8em 5%;
        box-shadow: 0 0.4em 2em rgba(0, 0, 0, 0.1);
        width: 90%;
    }

    .cabecalho h1 {
        font-size: 2.5em;
        margin-bottom: 0.5em;
    }

    .cabecalho p {
        font-size: 1.5em;
        font-weight: 300;
    }

    .perfil-imagem {
        width: 10em;
        height: 10em;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 1em;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        box-shadow: 0 0.4em 2em rgba(0, 0, 0, 0.1);
    }

    .perfil-imagem:hover {
        transform: scale(1.1);
        box-shadow: 0 0.8em 3em rgba(0, 0, 0, 0.2);
    }

    .detalhes,
    .projetos,
    .habilidades,
    .contato {
        padding: 3%;
        background-color: #ffffff;
        margin: 2% 5%;
        border-radius: 0.9em;
        box-shadow: 0 0.4em 2em rgba(0, 0, 0, 0.1);
        width: 90%;
    }

    .detalhes h2,
    .projetos h2,
    .habilidades h2,
    .contato h2 {
        font-size: 2em;
        color: #3b6ebb;
        margin-bottom: 1em;
    }

    .detalhes div,
    .projetos ul li,
    .habilidades ul li {
        font-size: 1.2em;
        margin-bottom: 1em;
    }

    .banners {
        display: flex;
        justify-content: center;
        gap: 2em;
        margin-top: 2%;
    }

    .banners img {
        width: 3.5em;
        height: 3.5em;
        border-radius: 50%;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 0.3em solid #fff;
        box-shadow: 0 0.4em 2em rgba(0, 0, 0, 0.1);
    }

    .banners img:hover {
        transform: scale(1.1);
        box-shadow: 0 0.8em 3em rgba(0, 0, 0, 0.2);
    }

    .projetos .conteudo {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        width: 100%;
    }

    .imagem-projeto-perfil {
        width: 17vw;
        height: auto;
        margin-left: 20px;
        align-self: flex-start;
    }

    .habilidades {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 30px 20px;
        background-color: #ffffff;
        margin: 20px auto;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        width: 90%;
        height: auto;
    }

    .contato {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 30px 20px;
        background-color: #ffffff;
        margin: 20px auto;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        width: 90%;
    }

    /* Estilos responsivos */
    @media (max-width: 1200px) {
        .cabecalho {
            width: 95%;
            margin: 9em 2.5%;
        }
        .menu {
            gap: 1.5em;
        }
        .menu li a {
            font-size: 1.1em;
        }
        .banners img {
            width: 3em;
            height: 3em;
        }
    }

    @media (max-width: 768px) {
        .navbar {
            flex-direction: column;
            gap: 1em;
        }
        .menu {
            flex-direction: column;
            align-items: center;
            gap: 1em;
        }
        .menu li a {
            font-size: 1.3em;
        }
        .cabecalho h1 {
            font-size: 2em;
        }
        .cabecalho p {
            font-size: 1.2em;
        }
        .perfil-imagem {
            width: 8em;
            height: 8em;
        }
        .detalhes,
        .projetos,
        .habilidades,
        .contato {
            width: 95%;
            padding: 5%;
        }
    }

    @media (max-width: 480px) {
        .cabecalho h1 {
            font-size: 1.75em;
        }
        .cabecalho p {
            font-size: 1em;
        }
        .menu li a {
            font-size: 1em;
        }
        .perfil-imagem {
            width: 6em;
            height: 6em;
        }
        .banners img {
            width: 2.5em;
            height: 2.5em;
        }
    }
</style>