<?php
session_start();
include("../php/conexao.php");

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: ../pages/login.html");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$mensagem = '';

// Função para limpar e normalizar strings (igual ao cadastro.php)
function limpar($valor) {
    // Remove apenas caracteres de controle (0-31) e DEL (127)
    $valor = preg_replace('/[\x00-\x1F\x7F]/u', '', $valor);
    // Mantém acentos e caracteres especiais, apenas remove tags HTML e espaços extras
    $valor = strip_tags(trim($valor));
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

// Processamento do formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = conectar();
        
        // Dados básicos (com tratamento de encoding igual ao cadastro.php)
        $nome = isset($_POST["nome"]) ? mb_convert_encoding(limpar($_POST["nome"]), 'UTF-8', 'auto') : null;
        $telefone = isset($_POST["telefone"]) ? mb_convert_encoding(limpar($_POST["telefone"]), 'ASCII', 'auto') : null;
        $dataNascimento = isset($_POST["dataNascimento"]) ? trim($_POST["dataNascimento"]) : null;
        
        // Dados do perfil (com tratamento de encoding)
        $idade = isset($_POST["idade"]) ? filter_var($_POST["idade"], FILTER_VALIDATE_INT) : null;
        $endereco = isset($_POST["endereco"]) ? mb_convert_encoding(limpar($_POST["endereco"]), 'UTF-8', 'auto') : null;
        $formacao = isset($_POST["formacao"]) ? mb_convert_encoding(limpar($_POST["formacao"]), 'UTF-8', 'auto') : null;
        $experiencia_profissional = isset($_POST["experiencia_profissional"]) ? mb_convert_encoding(limpar($_POST["experiencia_profissional"]), 'UTF-8', 'auto') : null;
        $interesses = isset($_POST["interesses"]) ? mb_convert_encoding(limpar($_POST["interesses"]), 'UTF-8', 'auto') : null;
        $projetos_especializacoes = isset($_POST["projetos_especializacoes"]) ? mb_convert_encoding(limpar($_POST["projetos_especializacoes"]), 'UTF-8', 'auto') : null;
        $habilidades = isset($_POST["habilidades"]) ? mb_convert_encoding(limpar($_POST["habilidades"]), 'UTF-8', 'auto') : null;

        $pdo->beginTransaction();
        
        // Atualiza os dados básicos na tabela Usuario
        $sql = $pdo->prepare("UPDATE Usuario SET nome = :nome, telefone = :telefone, dataNascimento = :dataNascimento WHERE id_usuario = :id_usuario");
        $sql->bindValue(":nome", $nome, PDO::PARAM_STR);
        $sql->bindValue(":telefone", $telefone, PDO::PARAM_STR);
        $sql->bindValue(":dataNascimento", $dataNascimento, PDO::PARAM_STR);
        $sql->bindValue(":id_usuario", $id_usuario, PDO::PARAM_INT);
        $sql->execute();
        
        // Processamento da foto de perfil (igual ao cadastro.php)
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            // Validação da imagem
            $mime = mime_content_type($_FILES['foto_perfil']['tmp_name']);
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif'])) {
                throw new Exception("Formato de imagem inválido! Apenas JPEG, PNG ou GIF são permitidos.");
            }
            
            // Verifica tamanho máximo (5MB)
            if ($_FILES['foto_perfil']['size'] > 5 * 1024 * 1024) {
                throw new Exception("A imagem deve ter no máximo 5MB!");
            }
            
            $foto_temp = $_FILES['foto_perfil']['tmp_name'];
            $foto_perfil = file_get_contents($foto_temp);
            
            // Atualização da foto (usando o mesmo método que funciona no cadastro)
            $sql_foto = $pdo->prepare("UPDATE Usuario SET foto_perfil = CONVERT(VARBINARY(MAX), :foto) WHERE id_usuario = :id");
            $sql_foto->bindParam(":foto", $foto_perfil, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
            $sql_foto->bindValue(":id", $id_usuario, PDO::PARAM_INT);
            $sql_foto->execute();
        }
        
        // Verifica se já existe um perfil para o usuário
        $sql = $pdo->prepare("SELECT COUNT(*) FROM Perfil WHERE id_usuario = :id_usuario");
        $sql->bindValue(":id_usuario", $id_usuario, PDO::PARAM_INT);
        $sql->execute();
        $existe_perfil = $sql->fetchColumn();
        
        if ($existe_perfil > 0) {
            // Atualiza o perfil existente
            $sql = $pdo->prepare("
                UPDATE Perfil SET 
                    idade = :idade,
                    endereco = :endereco,
                    formacao = :formacao,
                    experiencia_profissional = :experiencia_profissional,
                    interesses = :interesses,
                    projetos_especializacoes = :projetos_especializacoes,
                    habilidades = :habilidades
                WHERE id_usuario = :id_usuario
            ");
        } else {
            // Insere um novo perfil
            $sql = $pdo->prepare("
                INSERT INTO Perfil (
                    id_usuario, idade, endereco, formacao, 
                    experiencia_profissional, interesses, 
                    projetos_especializacoes, habilidades
                ) VALUES (
                    :id_usuario, :idade, :endereco, :formacao, 
                    :experiencia_profissional, :interesses, 
                    :projetos_especializacoes, :habilidades
                )
            ");
        }
        
        // Vincula os parâmetros do perfil
        $sql->bindValue(":id_usuario", $id_usuario, PDO::PARAM_INT);
        $sql->bindValue(":idade", $idade, PDO::PARAM_INT);
        $sql->bindValue(":endereco", $endereco, PDO::PARAM_STR);
        $sql->bindValue(":formacao", $formacao, PDO::PARAM_STR);
        $sql->bindValue(":experiencia_profissional", $experiencia_profissional, PDO::PARAM_STR);
        $sql->bindValue(":interesses", $interesses, PDO::PARAM_STR);
        $sql->bindValue(":projetos_especializacoes", $projetos_especializacoes, PDO::PARAM_STR);
        $sql->bindValue(":habilidades", $habilidades, PDO::PARAM_STR);
        $sql->execute();
        
        $pdo->commit();
        $mensagem = "Perfil atualizado com sucesso!";
        
    } catch (Exception $erro) {
        $pdo->rollBack();
        $mensagem = "Erro ao atualizar perfil: " . $erro->getMessage();
    }
}
try {
    $pdo = conectar();

    // Busca os dados básicos do usuário incluindo a foto
    $sql = $pdo->prepare("SELECT nome, foto_perfil, email, telefone, dataNascimento FROM Usuario WHERE id_usuario = :id_usuario");
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        .detalhes,
        .projetos,
        .caixa-central {
            background-color: #fff;
            margin: 1em auto;
            padding: 1.5em;
            border-radius: 0.8em;
            max-width: 960px;
            box-shadow: 0 0.4em 1em rgba(0, 0, 0, 0.1);
            font-size: 0.95em;
        }

        .detalhes h2,
        .projetos h2,
        .caixa-central h2 {
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
            width: 110px;
            height: 110px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .perfil-imagem {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid white;
        }

        .footer-section {
            background-color: #2e2e2e;
            color: white;
            padding: 10px;
            text-align: center;
            position: relative;
            bottom: 0;
            width: 100%;
        }

        .footer-content {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .footer-logo {
            width: 40px;
            height: 40px;
        }

        /* Estilos para o modal de edição */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 700px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="date"],
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Montserrat', sans-serif;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .btn {
            background-color: #3b6ebb;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
        }

        .btn:hover {
            background-color: #2e5ca8;
        }

        .btn-editar {
            background-color: #3b6ebb;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn-editar:hover {
            background-color: #2e5ca8;
        }

        .mensagem {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            text-align: center;
        }

        .sucesso {
            background-color: #d4edda;
            color: #155724;
        }

        .erro {
            background-color: #f8d7da;
            color: #721c24;
        }

        .projetos ul, .caixa-central ul {
            list-style-type: none;
            padding-left: 0;
        }
        
        /* Remover margens/paddings extras das listas */
        .projetos li, .caixa-central li {
            margin-bottom: 8px;
        }
        .btn-gerar-pdf {
        background-color: #3b6ebb;
        color: white;
        border: none;
        padding: 8px 15px; /* Reduzido de 10px 20px */
        border-radius: 4px; /* Reduzido de 5px */
        cursor: pointer;
        font-size: 14px; /* Reduzido de 16px */
        margin-left: 8px; /* Reduzido de 10px */
        transition: background-color 0.2s; /* Tempo de transição reduzido */
        }

        .btn-gerar-pdf:hover {
        background-color: #e74c3c;
        }

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

            .modal-content {
                width: 95%;
                margin: 10% auto;
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
                <li><a href="#" class="btn-logout" onclick="logout(); return false;">Sair</a></li>
            </ul>
        </nav>
    </header>

    <?php if (!empty($mensagem)): ?>
        <div class="mensagem <?php echo strpos($mensagem, 'sucesso') !== false ? 'sucesso' : 'erro'; ?>">
            <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

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
            <button class="btn-editar" onclick="abrirModal()">Editar Perfil</button>
            <button class="btn-gerar-pdf" onclick="gerarPDF()">Gerar PDF</button>
        </div>
    </div>

    <div class="detalhes">
        <h2>Detalhes</h2>
        <div><strong>Nome:</strong>
            <p><?php echo htmlspecialchars($usuario['nome']); ?></p>
        </div>
        <div><strong>Idade:</strong>
            <p><?php echo $usuario['idade'] ?? 'Não informado'; ?></p>
        </div>
        <div><strong>Endereço:</strong>
            <p><?php echo htmlspecialchars($usuario['endereco']); ?></p>
        </div>
        <div><strong>Formação:</strong>
            <p><?php echo htmlspecialchars($usuario['formacao']); ?></p>
        </div>
        <div><strong>Experiência Profissional:</strong>
            <p><?php echo nl2br(htmlspecialchars($usuario['experiencia_profissional'])); ?></p>
        </div>
        <div><strong>Interesses:</strong>
            <p><?php echo nl2br(htmlspecialchars($usuario['interesses'])); ?></p>
        </div>
    </div>

    <div class="projetos">
        <h2>Projetos e Especializações</h2>
        <div class="conteudo">
            <ul>
                <li><?php echo nl2br(htmlspecialchars($usuario['projetos_especializacoes'])); ?></li>
            </ul>
            <img src="../assets/img/organizing-projects-animate.svg" class="imagem-projeto-perfil" alt="Projetos">
        </div>
    </div>

    <div class="caixa-central">
        <h2>Habilidades</h2>
        <ul>
            <li><?php echo nl2br(htmlspecialchars($usuario['habilidades'])); ?></li>
        </ul>
    </div>

    <div class="caixa-central">
        <h2>Contato</h2>
        <p><strong>E-mail:</strong> <?php echo htmlspecialchars($usuario['email']); ?></p>
    </div>

    <!-- Modal de Edição -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fecharModal()">&times;</span>
            <h2>Editar Perfil</h2>
            <form action="perfil.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="foto_perfil">Foto de Perfil:</label>
                    <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="idade">Idade:</label>
                    <input type="number" id="idade" name="idade" value="<?php echo htmlspecialchars($usuario['idade'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="dataNascimento">Data de Nascimento:</label>
                    <input type="date" id="dataNascimento" name="dataNascimento" value="<?php echo htmlspecialchars($usuario['dataNascimento'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="telefone">Telefone:</label>
                    <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="endereco">Endereço:</label>
                    <input type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($usuario['endereco']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="formacao">Formação:</label>
                    <input type="text" id="formacao" name="formacao" value="<?php echo htmlspecialchars($usuario['formacao']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="experiencia_profissional">Experiência Profissional:</label>
                    <textarea id="experiencia_profissional" name="experiencia_profissional"><?php echo htmlspecialchars($usuario['experiencia_profissional']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="interesses">Interesses:</label>
                    <textarea id="interesses" name="interesses"><?php echo htmlspecialchars($usuario['interesses']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="projetos_especializacoes">Projetos e Especializações:</label>
                    <textarea id="projetos_especializacoes" name="projetos_especializacoes"><?php echo htmlspecialchars($usuario['projetos_especializacoes']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="habilidades">Habilidades:</label>
                    <textarea id="habilidades" name="habilidades"><?php echo htmlspecialchars($usuario['habilidades']); ?></textarea>
                </div>
                
                <button type="submit" class="btn">Salvar Alterações</button>
            </form>
        </div>
    </div>

    <footer class="footer-section">
        <div class="footer-content">
            <img src="../assets/img/globo-mundial.png" alt="Logo da Empresa" class="footer-logo">
            <p>&copy; 2024 ProLink. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script>
        // Funções para controlar o modal
        function abrirModal() {
            document.getElementById('modalEditar').style.display = 'block';
        }

        function fecharModal() {
            document.getElementById('modalEditar').style.display = 'none';
        }

        // Fechar o modal se clicar fora dele
        window.onclick = function(event) {
            const modal = document.getElementById('modalEditar');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>

<script>
function gerarPDF() {
    // Mostra um alerta enquanto processa
    alert("Gerando PDF... Isso pode levar alguns instantes.");
    
    // Envia uma requisição para o servidor gerar o PDF
    window.location.href = "../php/gerar_pdf.php";
}
</script>

<script>
function logout() {
    if(confirm('Tem certeza que deseja sair?')) {
        window.location.href = '../php/logout.php';
    }
}
</script>
</body>

</html>