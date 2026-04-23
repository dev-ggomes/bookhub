<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
require_once '../assets/php/config.php';

$message = '';

if (empty($_SESSION['csrf_token_forgot_password'])) {
    $_SESSION['csrf_token_forgot_password'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $email = trim($_POST['email'] ?? '');

    if (
        empty($_SESSION['csrf_token_forgot_password']) ||
        !hash_equals($_SESSION['csrf_token_forgot_password'], $csrfToken)
    ) {
        $message = "<div class='error'>Pedido inválido. Tente novamente.</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='error'>Introduza um endereço de email válido.</div>";
    } else {
        try {
            // Verifica se o email existe
            $query = $pdo->prepare("
                SELECT id
                FROM utilizadores
                WHERE email = ?
                LIMIT 1
            ");
            $query->execute([$email]);
            $user = $query->fetch(PDO::FETCH_ASSOC);

            // Mensagem genérica para evitar enumeração de contas
            $genericSuccessMessage = "<div class='success'>Se o email existir no sistema, irá receber instruções para redefinir a password.</div>";

            if ($user) {
                // Gera token seguro
                $token = bin2hex(random_bytes(32));
                $expira_em = date('Y-m-d H:i:s', strtotime('+1 day'));

                // Insere/atualiza token
                $stmt = $pdo->prepare("
                    REPLACE INTO password_resets (email, token, expira_em)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$email, $token, $expira_em]);

                // Configuração do email
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host = SMTP_HOST;
                    $mail->SMTPAuth = true;
                    $mail->Port = SMTP_PORT;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Username = SMTP_USER;
                    $mail->Password = SMTP_PASS;
                    $mail->CharSet = 'UTF-8';

                    $mail->setFrom(SMTP_USER, 'Suporte Bookhub');
                    $mail->addAddress($email);

                    $reset_link = BASE_URL . '/logins/reset_password.php?token=' . urlencode($token);

                    $mail->isHTML(true);
                    $mail->Subject = 'Redefinir password';
                    $mail->Body = "
                        <h3>Redefinição de Password</h3>
                        <p>Clique no link abaixo para redefinir a sua password:</p>
                        <p><a href='{$reset_link}'>Redefinir Password</a></p>
                        <p><small>Este link expira em 1 dia.</small></p>
                    ";

                    $mail->AltBody = "Use este link para redefinir a sua password: {$reset_link}";

                    $mail->send();
                } catch (Exception $e) {
                    // Não revelar erro técnico ao utilizador
                }
            }

            $message = $genericSuccessMessage;
            unset($_SESSION['csrf_token_forgot_password']);
        } catch (PDOException $e) {
            $message = "<div class='error'>Erro de sistema. Por favor tente mais tarde.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>BOOKhub | Recuperar password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/login_style.css">
</head>
<body>
    <div class="logo">
        <img src="../assets/img/Logotipo_Bookhub.png" alt="Bookhub Logo" class="logo-img">
    </div>

    <div class="password-container">
        <div class="password-header">
            <h1>Recuperação de password</h1>
            <p>Digite o seu email para receber instruções de redefinição de password</p>
        </div>

        <?= $message ?>

        <form class="password-form" method="POST" action="">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token_forgot_password'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >

            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input
                    type="email"
                    name="email"
                    placeholder="Seu endereço de email"
                    required
                >
            </div>

            <button type="submit" class="btn-submit">Enviar Link de Recuperação</button>
        </form>

        <div class="password-links">
            <p><a href="login.php">Voltar ao Login</a></p>
        </div>
    </div>
</body>
</html>