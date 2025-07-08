<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['id'])) {
    header("Location: /ModuloProjeto/logins/login.php");
    exit;
}

$idRequisicao = $_GET['id'];

try {
    // Verificar se o utilizador é o dono da requisição
    $stmt = $pdo->prepare("
        SELECT id_utilizador 
        FROM requisicoes 
        WHERE id = ?
    ");
    $stmt->execute([$idRequisicao]);
    $requisicao = $stmt->fetch();
    
    if (!$requisicao || $requisicao['id_utilizador'] != $_SESSION['id']) {
        die("Acesso negado.");
    }

    // Buscar dados para email
    $stmt = $pdo->prepare("
        SELECT u.nome AS usuario, l.titulo, l.cod_isbn 
        FROM requisicoes r 
        JOIN utilizadores u ON u.id = r.id_utilizador 
        JOIN livros l ON l.cod_isbn = r.cod_isbn 
        WHERE r.id = ?
    ");
    $stmt->execute([$idRequisicao]);
    $dados = $stmt->fetch();

    // Configurar PHPMailer
    require '../../vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = 'tls';
    $mail->Port = SMTP_PORT;
    
    $mail->setFrom(SMTP_USER, 'BOOKhub');
    $mail->addAddress(SMTP_USER); // Para o admin
    
    $linkConclusao = SITE_URL . "../assets/php/concluir_devolucao.php?id=" . $idRequisicao;
    
    $mail->isHTML(true);
    $mail->Subject = 'Devolução Solicitada: ' . $dados['titulo'];
    $mail->Body = "
        <h3>Devolução Solicitada:</h3>
        <p><strong>Utilizador:</strong> {$dados['usuario']}</p>
        <p><strong>Livro:</strong> {$dados['titulo']} (ISBN: {$dados['cod_isbn']})</p>
        <a href='$linkConclusao'>Concluir Devolução</a>
    ";

    $mail->send();

    header("Location: /ModuloProjeto/confirmacao_devolucao.php");
} catch (Exception $e) {
    die("Erro: " . $e->getMessage());
}