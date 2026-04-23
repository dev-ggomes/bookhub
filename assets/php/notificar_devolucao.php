<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/check_login.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Apenas administradores
if ((int) $_SESSION['admin'] !== 1) {
    die('Acesso negado.');
}

$idRequisicao = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idRequisicao || $idRequisicao <= 0) {
    die('ID de requisição inválido.');
}

try {
    $pdo->beginTransaction();

    // Buscar dados do utilizador e do livro
    $stmt = $pdo->prepare("
        SELECT r.id, r.status, r.data_devolucao, u.email, u.nome_completo, l.titulo
        FROM requisicoes r
        JOIN utilizadores u ON u.id = r.id_utilizador
        JOIN livros l ON l.cod_isbn = r.cod_isbn
        WHERE r.id = ?
        LIMIT 1
    ");
    $stmt->execute([$idRequisicao]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dados) {
        throw new Exception('Requisição não encontrada.');
    }

    if ($dados['status'] !== 'com_o_aluno') {
        throw new Exception('Só é possível pedir devolução de livros que estão com o aluno.');
    }

    // Configurar PHPMailer
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = 'tls';
    $mail->Port = SMTP_PORT;
    $mail->CharSet = 'UTF-8';

    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];

    $mail->setFrom(SMTP_USER, 'BOOKhub');
    $mail->addAddress($dados['email'], $dados['nome_completo']);
    $mail->Subject = 'Solicitação de Devolução de Livro';
    $mail->isHTML(false);
    $mail->Body =
        "Olá {$dados['nome_completo']},\n\n" .
        "Solicitamos a devolução do livro '{$dados['titulo']}'.\n" .
        "Por favor, dirija-se à biblioteca para efetuar a devolução.\n\n" .
        "Atenciosamente,\nEquipa BOOKhub";

    $mail->send();

    // Marcar como devolução solicitada
    $updateStmt = $pdo->prepare("
        UPDATE requisicoes
        SET data_devolucao = '1970-01-01 00:00:01'
        WHERE id = ?
    ");
    $updateStmt->execute([$idRequisicao]);

    $pdo->commit();

    header('Location: ' . BASE_URL . '/gerir-requisicoes.php?success=2');
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die('Erro: ' . $e->getMessage());
}
?>