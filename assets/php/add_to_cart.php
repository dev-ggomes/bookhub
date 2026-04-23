<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id']) || !is_numeric($_SESSION['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Utilizador não autenticado.'
    ]);
    exit;
}

$isbn = trim($_POST['isbn'] ?? '');
$quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;
$userId = (int) $_SESSION['id'];

if ($isbn === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'ISBN inválido.'
    ]);
    exit;
}

if ($quantity <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Quantidade inválida.'
    ]);
    exit;
}

try {
    // Verificar se o livro existe e se há stock disponível
    $checkBookStmt = $pdo->prepare("
        SELECT cod_isbn, disponivel
        FROM livros
        WHERE cod_isbn = ?
        LIMIT 1
    ");
    $checkBookStmt->execute([$isbn]);
    $book = $checkBookStmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Livro não encontrado.'
        ]);
        exit;
    }

    $disponivel = isset($book['disponivel']) ? (int) $book['disponivel'] : 0;

    if ($disponivel <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Este livro está indisponível.'
        ]);
        exit;
    }

    // Verificar se já está no carrinho
    $checkCartStmt = $pdo->prepare("
        SELECT quantidade
        FROM carrinho
        WHERE id_utilizador = ? AND cod_isbn = ?
        LIMIT 1
    ");
    $checkCartStmt->execute([$userId, $isbn]);
    $existing = $checkCartStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $newQuantity = (int) $existing['quantidade'] + $quantity;

        if ($newQuantity > $disponivel) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Não pode adicionar mais unidades do que as disponíveis.'
            ]);
            exit;
        }

        $updateStmt = $pdo->prepare("
            UPDATE carrinho
            SET quantidade = ?
            WHERE id_utilizador = ? AND cod_isbn = ?
        ");
        $updateStmt->execute([$newQuantity, $userId, $isbn]);
    } else {
        if ($quantity > $disponivel) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Quantidade pedida superior ao stock disponível.'
            ]);
            exit;
        }

        $insertStmt = $pdo->prepare("
            INSERT INTO carrinho (id_utilizador, cod_isbn, quantidade)
            VALUES (?, ?, ?)
        ");
        $insertStmt->execute([$userId, $isbn, $quantity]);
    }

    // Obter contagem atualizada do carrinho
    $countStmt = $pdo->prepare("
        SELECT SUM(quantidade) AS total
        FROM carrinho
        WHERE id_utilizador = ?
    ");
    $countStmt->execute([$userId]);
    $countData = $countStmt->fetch(PDO::FETCH_ASSOC);
    $cartCount = !empty($countData['total']) ? (int) $countData['total'] : 0;

    echo json_encode([
        'status' => 'success',
        'cartCount' => $cartCount,
        'message' => 'Livro adicionado ao carrinho.'
    ]);
    exit;
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao adicionar ao carrinho.'
    ]);
    exit;
}