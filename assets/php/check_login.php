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
    header("Location: " . BASE_URL . "/logins/login.php");
    exit;
}

try {
    // Buscar dados do utilizador autenticado
    $stmt = $pdo->prepare("
        SELECT id, nome_completo, email
        FROM utilizadores
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
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
        header("Location: " . BASE_URL . "/logins/login.php");
        exit;
    }

    // Atualizar dados relevantes da sessão sem mexer no papel admin atual
    $_SESSION['id'] = (int) $user['id'];
    $_SESSION['username'] = $user['nome_completo'];
    $_SESSION['email'] = $user['email'];

    // Garante que a flag admin existe na sessão
    if (!isset($_SESSION['admin'])) {
        $_SESSION['admin'] = 0;
    }
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
    header("Location: " . BASE_URL . "/logins/login.php");
    exit;
}