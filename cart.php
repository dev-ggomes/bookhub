<?php
require_once 'assets/php/config.php';
require_once 'assets/php/check_login.php';
require_once 'assets/php/carrinho_header.php';

// Verificar se o utilizador está autenticado
$loggedIn = isset($_SESSION['id']);
$cartItems = [];

// Mensagens de feedback
$error_message = '';
$success_message = '';

if (isset($_SESSION['cart_error'])) {
    $error_message = $_SESSION['cart_error'];
    unset($_SESSION['cart_error']);
}

if (isset($_SESSION['cart_success'])) {
    $success_message = $_SESSION['cart_success'];
    unset($_SESSION['cart_success']);
}

// Buscar itens do carrinho apenas se estiver logado
if ($loggedIn) {
    $userId = $_SESSION['id'];

    try {
        $stmt = $pdo->prepare("
            SELECT c.cod_isbn, c.quantidade, l.titulo, l.autor
            FROM carrinho c
            JOIN livros l ON c.cod_isbn = l.cod_isbn
            WHERE c.id_utilizador = ?
        ");
        $stmt->execute([$userId]);
        $cartItems = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error_message = "Erro ao carregar os itens do carrinho.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/cart_style.css">
    <link rel="stylesheet" href="./assets/css/index_style.css">
    <title>BOOKhub | Carrinho</title>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="cart-container">
        <p>| Carrinho</p>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <?php if (!$loggedIn): ?>
            <p class="empty-cart">
                <a href="./logins/login.php" class="login-link">Inicie sessão</a> para ver o seu carrinho.
            </p>
        <?php elseif (!empty($cartItems)): ?>
            <div class="cart-items">
                <?php foreach ($cartItems as $item): ?>
                    <div class="carrinho-item" data-isbn="<?= htmlspecialchars($item['cod_isbn']) ?>">
                        <div class="livro-esquerda">
                            <img
                                src="https://via.placeholder.com/60x80"
                                alt="Capa do livro"
                                class="capa-livro"
                                data-isbn="<?= htmlspecialchars($item['cod_isbn']) ?>"
                            >
                        </div>

                        <div class="info-livro">
                            <h3 class="titulo-livro"><?= htmlspecialchars($item['titulo']) ?></h3>
                            <p class="autor-livro"><?= htmlspecialchars($item['autor']) ?></p>
                        </div>

                        <a href="#" class="btn-remover" data-isbn="<?= htmlspecialchars($item['cod_isbn']) ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 6h18"/>
                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                <line x1="10" y1="11" x2="10" y2="17"/>
                                <line x1="14" y1="11" x2="14" y2="17"/>
                            </svg>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty-cart">O seu carrinho está vazio.</p>
        <?php endif; ?>

        <?php if ($loggedIn && !empty($cartItems)): ?>
            <form action="./assets/php/enviar_requisicao.php" method="POST" class="requisitar-form" id="requisitarForm">
                <button type="submit" class="btn-requisitar">
                    Requisitar Livros
                </button>
            </form>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 BOOKhub. Todos os direitos reservados</p>
    </footer>

    <!-- Modal de confirmação -->
    <div id="confirmModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <h2>Confirmação</h2>
            <p id="confirmText">Tem certeza que deseja finalizar a requisição?</p>
            <div class="modal-buttons">
                <button id="confirmYes" class="btn-confirm-yes">Sim</button>
                <button id="confirmNo" class="btn-confirm-no">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            async function fetchCartCovers() {
                const items = document.querySelectorAll('.carrinho-item');

                for (const item of items) {
                    const isbn = item.dataset.isbn;
                    const img = item.querySelector('.capa-livro');

                    try {
                        const response = await fetch(`https://www.googleapis.com/books/v1/volumes?q=isbn:${isbn}`);
                        const data = await response.json();

                        if (data.items && data.items[0].volumeInfo.imageLinks) {
                            img.src = data.items[0].volumeInfo.imageLinks.thumbnail;
                            img.style.opacity = 1;
                        }
                    } catch (error) {
                        console.error(`Erro ao buscar capa para ISBN ${isbn}:`, error);
                    }
                }
            }

            fetchCartCovers();

            document.querySelectorAll('.btn-remover').forEach(button => {
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    const isbn = this.dataset.isbn;

                    fetch(`assets/php/remove_from_cart.php?isbn=${encodeURIComponent(isbn)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                const item = this.closest('.carrinho-item');
                                if (item) {
                                    item.remove();
                                }

                                const badge = document.querySelector('.cart-badge');
                                if (badge) {
                                    badge.textContent = data.cartCount;
                                    if (data.cartCount <= 0) {
                                        badge.style.display = 'none';
                                    }
                                }

                                if (document.querySelectorAll('.carrinho-item').length === 0) {
                                    location.reload();
                                }
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(() => {
                            alert('Erro ao remover o item do carrinho.');
                        });
                });
            });

            const modal = document.getElementById('confirmModal');
            const btnYes = document.getElementById('confirmYes');
            const btnNo = document.getElementById('confirmNo');
            const requisitarBtn = document.querySelector('.btn-requisitar');
            const requisitarForm = document.getElementById('requisitarForm');

            if (requisitarBtn && requisitarForm && modal && btnYes && btnNo) {
                requisitarBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    modal.style.display = 'flex';
                });

                btnNo.addEventListener('click', function () {
                    modal.style.display = 'none';
                });

                btnYes.addEventListener('click', function () {
                    modal.style.display = 'none';
                    requisitarForm.submit();
                });
            }
        });
    </script>
</body>
</html>