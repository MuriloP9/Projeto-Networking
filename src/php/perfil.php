<?php
session_start();
include("../php/conexao.php"); 

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: ../pages/login.html");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

try {
    $pdo = conectar();
    
    // Busca os dados básicos do usuário incluindo a foto
    $sql = $pdo->prepare("SELECT nome, foto_perfil FROM Usuario WHERE id_usuario = :id_usuario");
    $sql->bindValue(":id_usuario", $id_usuario);
    $sql->execute();
    $usuario_basico = $sql->fetch(PDO::FETCH_ASSOC);
    $nome = $usuario_basico ? $usuario_basico['nome'] : "Usuário";
    
    // Verifica se há foto de perfil
    $foto_perfil = null;
    if (!empty($usuario_basico['foto_perfil'])) {
        // Se a foto estiver em formato binário no banco
        $foto_perfil = 'data:image/jpeg;base64,' . base64_encode($usuario_basico['foto_perfil']);
    }

    // Busca os dados completos do perfil
    $sql = $pdo->prepare("
        SELECT u.nome, u.email, u.dataNascimento, u.telefone, 
               COALESCE(p.idade, NULL) as idade, 
               COALESCE(p.endereco, 'Não informado') as endereco, 
               COALESCE(p.formacao, 'Não informado') as formacao, 
               COALESCE(p.experiencia_profissional, 'Nenhuma informação') as experiencia_profissional, 
               COALESCE(p.interesses, 'Nenhuma informação') as interesses, 
               COALESCE(p.projetos_especializacoes, 'Nenhuma informação') as projetos_especializacoes, 
               COALESCE(p.habilidades, 'Nenhuma informação') as habilidades
        FROM Usuario u
        LEFT JOIN Perfil p ON u.id_usuario = p.id_usuario
        WHERE u.id_usuario = :id_usuario
    ");
    $sql->bindValue(":id_usuario", $id_usuario);
    $sql->execute();
    $usuario = $sql->fetch(PDO::FETCH_ASSOC);

} catch (Exception $erro) {
    echo "Erro ao carregar perfil: " . $erro->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <title>Perfil - ProLink</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f4f7fb;
            color: #333;
            padding-top: 80px;
        }
        header {
            background-color: #3b6ebb;
            color: white;
            padding: 1em 2em;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 0.4em 1em rgba(0, 0, 0, 0.1);
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-container {
            display: flex;
            align-items: center;
        }
        .logo-icon {
            width: 50px;
            margin-right: 10px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        .menu {
            display: flex;
            gap: 1.5em;
        }
        .menu li {
            list-style: none;
        }
        .menu a {
            text-decoration: none;
            color: #0a0a0a;
            background-color: white;
            padding: 8px 16px;
            border-radius: 5px;
        }
        .menu a:hover {
            background-color: #2e5ca8;
            color: white;
        }
        .cabecalho {
            display: flex;
            align-items: center;
            background-color: #3b6ebb;
            color: white;
            border-radius: 1em;
            padding: 1em;
            margin: 2em auto;
            max-width: 960px;
        }
        .box-imagem {
            background-color: #3b6ebb;
            border-radius: 50%;
            padding: 10px;
            margin-right: 1em;
        }
        .perfil-imagem {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
        }
        .info-usuario h1 {
            font-size: 1.8em;
        }
        .detalhes, .projetos, .caixa-central {
            background-color: #fff;
            margin: 1em auto;
            padding: 1.5em;
            border-radius: 0.8em;
            max-width: 960px;
            box-shadow: 0 0.4em 1em rgba(0, 0, 0, 0.1);
            font-size: 0.95em;
        }
        .detalhes h2, .projetos h2, .caixa-central h2 {
            font-size: 1.5em;
            margin-bottom: 1em;
            color: #3b6ebb;
        }
        .detalhes div {
            display: flex;
            gap: 0.5em;
            margin-bottom: 0.8em;
        }
        .detalhes p {
            margin: 0;
        }
        .projetos .conteudo {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1em;
        }
        .imagem-projeto-perfil {
            max-width: 180px;
            height: auto;
        }
        .caixa-central .projetos ul {
            padding-left: 1em;
        }

        .box-imagem {
        background-color: #3b6ebb;
        border-radius: 50%;
        padding: 10px;
        margin-right: 1em;
        width: 110px; /* Tamanho fixo para o círculo */
        height: 110px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden; /* Garante que a imagem não ultrapasse o círculo */
       }

       .perfil-imagem {
        width: 100%;
        height: 100%;
        object-fit: cover; /* Garante que a imagem cubra todo o espaço sem distorcer */
        border-radius: 50%; /* Garante que a imagem fique redonda */
        border: 2px solid white; /* Borda branca ao redor da foto */
       }
        /* Responsividade */
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
            align-items: center;
            gap: 1em;
            padding: 20px;
        }

        .menu {
            flex-direction: column;
            align-items: center;
            gap: 1em;
            width: 100%;
        }

        .menu li a {
            font-size: 1.3em;
        }

        .cabecalho {
            flex-direction: column;
            text-align: center;
        }

        .cabecalho h1 {
            font-size: 2em;
        }

        .perfil-imagem {
            width: 7em;
            height: 7em;
        }

        .info-usuario {
            width: 100%;
        }

        .detalhes,
        .projetos,
        .caixa-central {
            width: 95%;
            padding: 5%;
        }

        .imagem-projeto-perfil {
            display: none;
        }
}

    @media (max-width: 480px) {
        .cabecalho h1 {
            font-size: 1.6em;
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
</head>
<body>
<header>
    <nav class="navbar">
        <div class="logo-container">
            <img src="../assets/img/globo-mundial.png" alt="Logo" class="logo-icon">
            <div class="logo">ProLink</div>
        </div>
        <ul class="menu">
            <li><a href="../php/index.php">Home</a></li>
        </ul>
    </nav>
</header>

<div class="cabecalho">
    <div class="box-imagem">
        <?php if ($foto_perfil): ?>
            <img src="<?php echo $foto_perfil; ?>" alt="Foto de perfil" class="perfil-imagem">
        <?php else: ?>
            <img src="../assets/img/userp.jpg" alt="Avatar padrão" class="perfil-imagem">
        <?php endif; ?>
    </div>
    <div class="info-usuario">
        <h1>Perfil</h1>
        <p><?php echo htmlspecialchars($nome); ?></p>
    </div>
</div>

<div class="detalhes">
    <h2>Detalhes</h2>
    <div><strong>Nome:</strong><p><?php echo htmlspecialchars($usuario['nome']); ?></p></div>
    <div><strong>Idade:</strong><p><?php echo $usuario['idade'] ?? 'Não informado'; ?></p></div>
    <div><strong>Endereço:</strong><p><?php echo htmlspecialchars($usuario['endereco']); ?></p></div>
    <div><strong>Formação:</strong><p><?php echo htmlspecialchars($usuario['formacao']); ?></p></div>
    <div><strong>Experiência Profissional:</strong><p><?php echo nl2br(htmlspecialchars($usuario['experiencia_profissional'])); ?></p></div>
    <div><strong>Interesses:</strong><p><?php echo nl2br(htmlspecialchars($usuario['interesses'])); ?></p></div>
</div>

<div class="projetos">
    <h2>Projetos e Especializações</h2>
    <div class="conteudo">
        <ul><li><?php echo nl2br(htmlspecialchars($usuario['projetos_especializacoes'])); ?></li></ul>
        <img src="../assets/img/organizing-projects-animate.svg" class="imagem-projeto-perfil" alt="Projetos">
    </div>
</div>

<div class="caixa-central">
    <h2>Habilidades</h2>
    <ul><li><?php echo nl2br(htmlspecialchars($usuario['habilidades'])); ?></li></ul>
</div>

<div class="caixa-central">
    <h2>Contato</h2>
    <p><strong>E-mail:</strong> <?php echo htmlspecialchars($usuario['email']); ?></p>
</div>

</body>
</html>
