<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

if (
    !isset($_SESSION["loggedin"]) ||
    $_SESSION["loggedin"] !== true ||
    !isset($_SESSION['id'])
) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

try {
    // Buscar dados do utilizador autenticado
    $stmt = $pdo->prepare("
        SELECT id, nome_completo, email, admin
        FROM utilizadores
        WHERE id = ? 
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['id']]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION = [];

        if (ini_git("session.use_cookies")) {
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

        session_destroy();
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }

    // Atualizar dados relevantes da sessão
    $_SESSION['id'] = $user['id'];
    $_SESSION['admin'] = (int) $user['admin'];
    $_SESSION['username'] = $user['nome_completo'];
    $_SESSION['email'] = $user['email'];
} catch (PDOException $e) {
    $_SESSION = [];

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

    session_destroy();
    header("Location: " . BASE_URL . "/login.php");
    exit;
}