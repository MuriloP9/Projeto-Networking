<?php
function conectar()
{
    $local_server = "PC_NASA\SQLEXPRESS"; 
    $usuario_server = "sa";               
    $senha_server = "etesp";              
    $banco_de_dados = "prolink";         

    try {
       
        $pdo = new PDO("sqlsrv:server=$local_server;database=$banco_de_dados", $usuario_server, $senha_server);
        return $pdo;
    } catch (Exception $erro) {
        
        echo "ATENÇÃO - ERRO NA CONEXÃO: " . $erro->getMessage();
        die;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verifica se os dados foram enviados corretamente
    if (isset($_POST['titulo_vaga']) && isset($_POST['localizacao']) && isset($_POST['tipo_emprego'])) {

        $tituloVaga = $_POST['titulo_vaga'];
        $localizacao = $_POST['localizacao'];
        $tipoEmprego = $_POST['tipo_emprego'];

    
        $tiposValidos = ['full-time', 'part-time', 'internship'];
        if (!in_array($tipoEmprego, $tiposValidos)) {
            echo "Erro: Tipo de emprego inválido.";
            exit;
        }

      
        $pdo = conectar();

      
        $sql = $pdo->prepare("INSERT INTO Vagas (titulo_vaga, localizacao, tipo_emprego) 
                              VALUES (:titulo_vaga, :localizacao, :tipo_emprego)");

        $sql->bindValue(":titulo_vaga", $tituloVaga);
        $sql->bindValue(":localizacao", $localizacao);
        $sql->bindValue(":tipo_emprego", $tipoEmprego);

        if ($sql->execute()) {
            echo "Vaga cadastrada com sucesso!";
        } else {
            echo "Erro ao cadastrar vaga.";
        }
    } else {
        echo "Erro: Preencha todos os campos do formulário.";
    }
}
?>
