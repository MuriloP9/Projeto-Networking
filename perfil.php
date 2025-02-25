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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - ProLink</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="perfil-container">
        <h2>Perfil de <?php echo htmlspecialchars($usuario['nome']); ?></h2>

        <div class="perfil-info">
            <p><strong>Email:</strong> <?php echo htmlspecialchars($usuario['email']); ?></p>
            <p><strong>Data de Nascimento:</strong> <?php echo date("d/m/Y", strtotime($usuario['dataNascimento'])); ?></p>
            <p><strong>Telefone:</strong> <?php echo htmlspecialchars($usuario['telefone']); ?></p>

            <!-- Exibindo dados do perfil com valores padrão quando não informados -->
            <p><strong>Idade:</strong> <?php echo ($usuario['idade'] !== NULL) ? htmlspecialchars($usuario['idade']) : 'Não informado'; ?></p>
            <p><strong>Localização:</strong> <?php echo htmlspecialchars($usuario['localizacao']); ?></p>
            <p><strong>Formação:</strong> <?php echo htmlspecialchars($usuario['formacao']); ?></p>
            <p><strong>Experiência Profissional:</strong> <?php echo nl2br(htmlspecialchars($usuario['experiencia_profissional'])); ?></p>
            <p><strong>Interesses:</strong> <?php echo nl2br(htmlspecialchars($usuario['interesses'])); ?></p>
            <p><strong>Projetos e Especializações:</strong> <?php echo nl2br(htmlspecialchars($usuario['projetos_especializacoes'])); ?></p>
            <p><strong>Habilidades:</strong> <?php echo nl2br(htmlspecialchars($usuario['habilidades'])); ?></p>
            <p><strong>Contato (Email):</strong> <?php echo htmlspecialchars($usuario['contato_email']); ?></p>
            <p><strong>Contato (Telefone):</strong> <?php echo htmlspecialchars($usuario['contato_telefone']); ?></p>
        </div>
    </div>
</body>
</html>

<style>
    .perfil-container {
    width: 60%;
    margin: auto;
    padding: 20px;
    background: #f0f8ff;
    border-radius: 10px;
    box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
    font-family: Arial, sans-serif;
}

h2 {
    color: #0077b6;
    text-align: center;
}

.perfil-info {
    margin-top: 20px;
}

.perfil-info p {
    font-size: 18px;
    margin-bottom: 10px;
    border-bottom: 1px solid #ddd;
    padding-bottom: 5px;
}
</style>
