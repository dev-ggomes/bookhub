<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpa todas as variáveis de sessão
$_SESSION = [];

// Apaga o cookie da sessão (se existir)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destrói a sessão
session_destroy();

// Iniciar nova sessão limpa
session_start();
session_regenerate_id(true);

// Redireciona para login (mais lógico que index_user)
header("Location: ../logins/login.php");
exit;