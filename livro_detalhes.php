<?php
require_once 'assets/php/config.php';
require_once 'assets/php/check_login.php';
require_once 'assets/php/carrinho_header.php';

$isbn = isset($_GET['isbn']) ? trim($_GET['isbn']) : '';
$livro = null;

if ($isbn !== '') {
    try {
        $stmt = $pdo->prepare("
            SELECT cod_isbn, titulo, autor, resumo, edicao, numero_paginas
            FROM livros
            WHERE cod_isbn = ?
            LIMIT 1
        ");
        $stmt->execute([$isbn]);
        $livro = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $livro = null;
    }
}

// Função auxiliar para buscar capa
function obterCapa($isbn) {
    if (empty($isbn)) {
        return 'assets/img/capa-padrao.jpg';
    }

    $url = "https://www.googleapis.com/books/v1/volumes?q=isbn:" . urlencode($isbn);
    $context = stream_context_create([
        'http' => [
            'timeout' => 5
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response !== false) {
        $data = json_decode($response, true);

        if (!empty($data['items'][0]['volumeInfo']['imageLinks']['thumbnail'])) {
            return $data['items'][0]['volumeInfo']['imageLinks']['thumbnail'];
        }
    }

    return 'assets/img/capa-padrao.jpg';
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOOKhub | <?= $livro ? htmlspecialchars($livro['titulo']) : 'Livro não encontrado'; ?></title>
    <link rel="stylesheet" href="./assets/css/livro_detalhes.css">
    <link rel="stylesheet" href="./assets/css/index_style.css">
</head>
<body>

    <?php include 'header.php'; ?>

    <?php if ($livro): ?>
        <div class="livro-detalhes">
            <img src="<?= htmlspecialchars(obterCapa($livro['cod_isbn'])) ?>" alt="Capa do livro">

            <div class="livro-info">
                <h1><?= htmlspecialchars($livro['titulo']) ?></h1>
                <p><strong>Autor:</strong> <?= htmlspecialchars($livro['autor']) ?></p>
                <div class="resumo"><?= nl2br(htmlspecialchars($livro['resumo'])) ?></div>
            </div>

            <div class="detalhes-adicionais">
                <div><strong>ISBN:</strong> <?= htmlspecialchars($livro['cod_isbn']) ?></div>
                <div><strong>Edição:</strong> <?= htmlspecialchars($livro['edicao']) ?></div>
                <div><strong>Páginas:</strong> <?= htmlspecialchars($livro['numero_paginas']) ?></div>
            </div>
        </div>
    <?php else: ?>
        <main class="livro-detalhes">
            <div class="livro-info">
                <h1>Livro não encontrado!</h1>
                <p>O livro que procura não existe ou o ISBN fornecido é inválido.</p>
            </div>
        </main>
    <?php endif; ?>

</body>
</html>