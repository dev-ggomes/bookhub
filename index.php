<?php
require_once 'assets/php/config.php';
require_once 'assets/php/check_login.php';
require_once 'assets/php/carrinho_header.php';

// Apenas administradores podem aceder a esta página
if ((int) $_SESSION['admin'] !== 1) {
    header('Location: ./index_user.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/index_style.css">
    <link rel="stylesheet" href="./assets/css/modal.css">
    <link rel="stylesheet" href="./assets/css/apresentar_livro.css">
    <link rel="stylesheet" href="./assets/css/index_slider.css">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather&display=swap" rel="stylesheet">
    <title>BOOKhub</title>
</head>
<body>
    <?php include 'header.php'; ?>

    <main>
        <section class="first-section"></section>

        <section class="second-section">
            <!-- <p>esta é a parte </p> -->
        </section>

        <section class="third-section">
            <!-- <p>esta é a parte </p> -->
        </section>
    </main>

    <div class="slider">
        <div class="slides">
            <div class="slide">
                <img src="/ModuloProjeto/assets/img/Bookhub_v3.gif" alt="Slide 1">
            </div>
        </div>
    </div>
    
    <div class="ultimo-lancamento">
        <p>Últimos livros adicionados:</p>
    </div>

    <button id="openModal" class="add-books">Adicionar livro</button>

    <form action="./assets/php/captar_livro.php" method="POST" id="bookForm">
        <dialog class="modal">
            <h2>Adicionar Livro</h2>
            <div class="modal-content">
                <div class="modal-left">
                    <div class="book-image-container">
                        <img id="bookImage" src="https://via.placeholder.com/128x186" alt="Imagem do Livro">
                    </div>
                    <div class="book-summary">
                        <label for="summary" class="summary-label">Resumo</label>
                        <textarea id="summary" name="summary" rows="4" placeholder="Resumo..." required></textarea>
                        <button type="button" id="viewFullText" class="view-icon-btn" title="Ver resumo completo">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="modal-right">
                    <div class="inputUser">
                        <input type="text" id="isbn" name="isbn" class="modal-input" required>
                        <label for="isbn" class="labelInput">Código ISBN</label>
                    </div>
                
                    <div class="inputUser">
                        <input type="text" id="title" name="title" class="modal-input" required>
                        <label for="title" class="labelInput">Título do Livro</label>
                    </div>
                
                    <div class="inputUser">
                        <input type="text" id="edition" name="edition" class="modal-input" required>
                        <label for="edition" class="labelInput">Edição</label>
                    </div>
                
                    <div class="inputUser">
                        <input type="text" id="author" name="author" class="modal-input" required>
                        <label for="author" class="labelInput">Autor</label>
                    </div>
                
                    <div class="inputUser">
                        <input type="text" id="numero_paginas" name="numero_paginas" class="modal-input" required>
                        <label for="numero_paginas" class="labelInput">Nº de páginas</label>
                    </div>

                    <div class="inputUser">
                        <input type="number" id="quantity" name="quantity" class="modal-input" min="1" value="1" required>
                        <label for="quantity" class="labelInput">Quantidade</label>
                    </div>
                </div>
                
                <div class="modal-buttons-container">
                    <button type="submit" name="submit" id="saveBook" class="modal-buttons">Guardar livro</button>
                    <button type="button" name="submit" id="closeModal" class="modal-buttons1">Fechar</button>
                </div>
            </div>
        </dialog>
    </form>

    <dialog id="textModal" class="text-modal">
        <h2>Resumo completo do livro</h2>
        <p id="fullTextContent"></p>
        <button id="saveTextModal" class="modal-save-btn">Salvar alterações</button>
        <button id="closeTextModal" class="modal-close-btn">Fechar</button>
    </dialog>
        
    <footer>
        <!-- <p>&copy; 2025 BOOKhub. Todos os direitos reservados.</p> -->
    </footer>

    <script src="./assets/js/modal_livros.js"></script>
    <script src="./assets/js/carregar_livros.js"></script>
    <script src="./assets/js/remover_livros.js"></script>
</body>
</html>