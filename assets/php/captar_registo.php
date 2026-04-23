<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../logins/registo_com_validacao.php');
    exit;
}

$username = trim($_POST['nome_completo'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$genero = $_POST['genero'] ?? '';

if (mb_strlen($username) < 5) {
    $_SESSION['erro_geral'] = 'O nome completo deve ter pelo menos 5 caracteres.';
    header('Location: ../../logins/registo_com_validacao.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['erro_geral'] = 'Introduza um email válido.';
    header('Location: ../../logins/registo_com_validacao.php');
    exit;
}

if (strlen($password) < 8) {
    $_SESSION['erro_geral'] = 'A password deve ter pelo menos 8 caracteres.';
    header('Location: ../../logins/registo_com_validacao.php');
    exit;
}

if ($password !== $confirmPassword) {
    $_SESSION['erro_geral'] = 'As passwords não coincidem.';
    header('Location: ../../logins/registo_com_validacao.php');
    exit;
}

if (!in_array($genero, ['m', 'f', 'o'], true)) {
    $_SESSION['erro_geral'] = 'Selecione um género válido.';
    header('Location: ../../logins/registo_com_validacao.php');
    exit;
}

try {
    $verificar = $pdo->prepare("SELECT id FROM utilizadores WHERE email = ? LIMIT 1");
    $verificar->execute([$email]);

    if ($verificar->fetch()) {
        $_SESSION['erro_email'] = 'Este email já está registado!';
        header('Location: ../../logins/registo_com_validacao.php');
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $query = "
        INSERT INTO utilizadores (nome_completo, email, password, genero)
        VALUES (:nome_completo, :email, :password, :genero)
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':nome_completo' => $username,
        ':email' => $email,
        ':password' => $hashedPassword,
        ':genero' => $genero
    ]);

    $_SESSION['login_success'] = 'Registo concluído com sucesso. Já pode iniciar sessão.';
    header('Location: ../../logins/login.php');
    exit;
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $_SESSION['erro_email'] = 'Este email já está registado!';
    } else {
        $_SESSION['erro_geral'] = 'Ocorreu um erro ao registar o utilizador.';
    }

    header('Location: ../../logins/registo_com_validacao.php');
    exit;
}