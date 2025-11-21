<?php
session_start();
require_once 'config.php';

// Verificar admin
if (!isset($_SESSION['admin']) || $_SESSION['admin'] != 1) {
    die("Acesso negado.");
}

$idRequisicao = $_GET['id'];

try {
    $pdo->beginTransaction();

    // Atualizar status
    $stmt = $pdo->prepare("
        UPDATE requisicoes 
        SET status = 'pronto_para_levantar', data_conclusao = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$idRequisicao]);

    // Buscar dados do utilizador
    $stmt = $pdo->prepare("
        SELECT u.email, u.nome_completo, l.titulo 
        FROM requisicoes r 
        JOIN utilizadores u ON u.id = r.id_utilizador 
        JOIN livros l ON l.cod_isbn = r.cod_isbn 
        WHERE r.id = ?
    ");
    $stmt->execute([$idRequisicao]);
    $dados = $stmt->fetch();

    // Enviar email para o utilizador
    if ($dados) {
        $to = $dados['email'];
        $subject = "Livro Pronto para Levantamento";
        $message = "Olá {$dados['nome_completo']},\n\n";
        $message .= "O livro '{$dados['titulo']}' está pronto para levantamento na biblioteca.\n\n";
        $message .= "Por favor, dirija-se à biblioteca para recolher o livro.\n\n";
        $message .= "Atenciosamente,\nEquipa BOOKhub";
        
        $headers = "From: bookhub.adm1@gmail.com" . "\r\n" .
                   "Reply-To: bookhub.adm1@gmail.com";
        
        mail($to, $subject, $message, $headers);
    }

    $pdo->commit();
    header("Location: ../../gerir-requisicoes.php?success=1");
} catch (Exception $e) {
    $pdo->rollBack();
    die("Erro: " . $e->getMessage());
}