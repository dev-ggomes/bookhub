<?php
session_start();
require_once '../assets/php/config.php';

$error = '';
$success = '';

if (empty($_SESSION['csrf_token_reset'])) {
    $_SESSION['csrf_token_reset'] = bin2hex(random_bytes(32));
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {
        $token = trim($_GET['token']);

        $query = $pdo->prepare("
            SELECT email
            FROM password_resets
            WHERE token = ?
              AND expira_em > NOW()
            LIMIT 1
        ");
        $query->execute([$token]);
        $reset = $query->fetch(PDO::FETCH_ASSOC);

        if (!$reset) {
            $error = 'Link inválido ou expirado!';
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrfToken = $_POST['csrf_token'] ?? '';
        $token = trim($_POST['token'] ?? '');
        $nova_password = $_POST['nova_password'] ?? '';

        if (!hash_equals($_SESSION['csrf_token_reset'], $csrfToken)) {
            $error = 'Pedido inválido. Tente novamente.';
        } elseif (empty($token)) {
            $error = 'Token inválido!';
        } elseif (strlen($nova_password) < 8) {
            $error = 'A password deve ter no mínimo 8 caracteres.';
        } else {
            $query = $pdo->prepare("
                SELECT email
                FROM password_resets
                WHERE token = ?
                  AND expira_em > NOW()
                LIMIT 1
            ");
            $query->execute([$token]);
            $reset = $query->fetch(PDO::FETCH_ASSOC);

            if ($reset) {
                $hash = password_hash($nova_password, PASSWORD_DEFAULT);

                $updatePassword = $pdo->prepare("
                    UPDATE utilizadores
                    SET password = ?
                    WHERE email = ?
                ");
                $updatePassword->execute([$hash, $reset['email']]);

                $deleteTokens = $pdo->prepare("
                    DELETE FROM password_resets
                    WHERE email = ?
                ");
                $deleteTokens->execute([$reset['email']]);

                $success = 'Password alterada com sucesso!';
                unset($_SESSION['csrf_token_reset']);
            } else {
                $error = 'Token inválido ou expirado!';
            }
        }
    }
} catch (PDOException $e) {
    $error = 'Erro de base de dados. Tente novamente mais tarde.';
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>BOOKhub | Nova Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/login_style.css">
</head>
<body>
    <div class="glow"></div>

    <div class="password-reset-container">
        <h1 class="password-reset-header">Definir Nova Password</h1>

        <?php if ($error): ?>
            <div class="password-message password-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php elseif ($success): ?>
            <div class="password-message password-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php else: ?>
            <form method="POST" class="password-reset-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token_reset'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                <div class="password-input-group">
                    <i class="fas fa-lock"></i>
                    <input id="nova_password" type="password" name="nova_password" placeholder="Nova password" required minlength="8">
                    <span id="togglePassword" style="position:absolute; top:50%; right:60px; transform:translateY(-50%); cursor:pointer;">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>

                <button type="submit" class="password-reset-btn">Alterar Password</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('nova_password');
            const toggle = document.getElementById('togglePassword');

            if (input && toggle) {
                toggle.addEventListener('click', () => {
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    toggle.querySelector('i').classList.toggle('fa-eye');
                    toggle.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }
        });
    </script>
</body>
</html>