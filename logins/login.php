<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$erro = '';
$sucesso = '';

if (!empty($_SESSION['login_error'])) {
    $erro = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

if (!empty($_SESSION['login_success'])) {
    $sucesso = $_SESSION['login_success'];
    unset($_SESSION['login_success']);
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | BH</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/login_style.css">
</head>
<body>
    <div class="logo">
        <img src="../assets/img/Logotipo_Bookhub.png" alt="Logotipo" class="logo-img">
    </div>

    <div class="login-container">
        <h1>Login📚</h1>

        <?php if ($erro): ?>
            <div class="error-message" style="color: red; margin-bottom: 15px;">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="success-message" style="color: green; margin-bottom: 15px;">
                <?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="../assets/php/captar_login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="input-container">
                <i class="fa fa-user"></i>
                <input type="email" placeholder="E-mail" class="inputs required" name="email" required>
            </div>

            <div class="input-container">
                <i class="fa fa-key"></i>
                <input type="password" placeholder="Password" class="inputs required" id="password" name="password" required>
            </div>

            <div class="show-password">
                <input type="checkbox" id="showPassword" onclick="togglePasswordVisibility()">
                <label for="showPassword">Mostrar password</label>
            </div>

            <div class="forgot-password">
                <a href="../logins/esqueci-me_da_password.php">Esqueci-me da password</a>
            </div>

            <div class="input-container">
                <i class="fa fa-key"></i>
                <input type="password" placeholder="Código Secreto" class="inputs required" id="codigo_secreto" name="cod_secreto">
            </div>

            <div class="show-password">
                <input type="checkbox" id="showSecretCode" onclick="toggleSecretCodeVisibility()">
                <label for="showSecretCode">Mostrar Código Secreto</label>
            </div>

            <p class="tarefa">O que vem fazer à biblioteca?</p>
            <div class="box-select">
                <div>
                    <input type="checkbox" id="ler" value="ler" name="oquefazer[]">
                    <label for="ler" class="checkbox-label">Ler</label>
                </div>
                <div>
                    <input type="checkbox" id="estudar" value="estudar" name="oquefazer[]">
                    <label for="estudar" class="checkbox-label">Estudar</label>
                </div>
                <div>
                    <input type="checkbox" id="fazer_trabalhos" value="fazer_trabalhos" name="oquefazer[]">
                    <label for="fazer_trabalhos" class="checkbox-label">Fazer trabalhos</label>
                </div>
                <div>
                    <input type="checkbox" id="requisitar_livros" value="requisitar_livros" name="oquefazer[]">
                    <label for="requisitar_livros" class="checkbox-label">Requisitar livros</label>
                </div>
                <div>
                    <input type="checkbox" id="outros" value="outros" name="oquefazer[]">
                    <label for="outros" class="checkbox-label">Outros</label>
                </div>
            </div>

            <p class="data">Data:</p>
            <input type="date" class="inputs date-input" name="data_entrada" id="data_entrada" required>

            <button type="submit">Entrar</button>

            <p class="registo">Ainda não é membro? <a href="../logins/registo_com_validacao.php">Registe-se</a></p>
        </form>
    </div>

    <script>
        const today = new Date().toISOString().split('T')[0];
        const dateInput = document.getElementById('data_entrada');
        dateInput.min = today;
        dateInput.max = today;
        dateInput.value = today;

        function togglePasswordVisibility() {
            const passwordField = document.getElementById('password');
            passwordField.type = passwordField.type === 'password' ? 'text' : 'password';
        }

        function toggleSecretCodeVisibility() {
            const secretCodeField = document.getElementById('codigo_secreto');
            secretCodeField.type = secretCodeField.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>