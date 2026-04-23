<?php
require_once 'assets/php/config.php';
require_once 'assets/php/check_login.php';
require_once 'assets/php/carrinho_header.php';

// Apenas utilizadores normais podem aceder a esta página
if ((int) $_SESSION['admin'] !== 0) {
    header('Location: ./index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/index_style.css">
    <link rel="stylesheet" href="./assets/css/apresentar_livro.css">
    <link rel="stylesheet" href="./assets/css/index_slider.css">
    <title>BOOKhub</title>
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="first-section">
        <form action="./assets/php/captar_livro.php" method="POST" id="bookForm">
        </form>
    </section>

    <div class="slide">
        <img src="/ModuloProjeto/assets/img/Bookhub_v3.gif" alt="Banner Bookhub">
    </div><!-- slide -->
    
    <div class="ultimo-lancamento">
        <p>Últimos livros adicionados:</p>
        <!-- ADICIONAR CÓDIGO PARA METER AQUI OS ÚLTIMOS 3 A 5 LIVROS ADICIONADOS AO SITE -->
    </div> <!-- ultimo-lancamento-->

    <footer>
        <p>&copy; 2025 BOOKhub. Todos os direitos reservados.</p>
    </footer>

    <script src="./assets/js/modal_livros.js"></script>
    <script src="./assets/js/carregar_livros_user.js"></script>
    <script src="./assets/js/remover_livros.js"></script>
    <script src="./assets/js/slider.js"></script>
</body>
</html>