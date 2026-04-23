<?php
require_once 'assets/php/config.php';
require_once 'assets/php/check_login.php';
require_once 'assets/php/carrinho_header.php';
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/index_style.css">
    <link rel="stylesheet" href="./assets/css/livros.css">
    <title>BOOKhub | Livros</title>
</head>
<body>
    <?php include 'header.php'; ?>

    <main>
        <div class="book-list-container" id="book-list"></div>
    </main>

    <footer>
        <p>&copy; 2025 BOOKhub. Todos os direitos reservados.</p>
    </footer>

    <?php if ($_SESSION['admin'] == 0): ?>
        <script src="./assets/js/carregar_livros_user.js"></script>
    <?php elseif ($_SESSION['admin'] == 1): ?>
        <script src="./assets/js/carregar_livros.js"></script>
    <?php endif; ?>
</body>
</html>