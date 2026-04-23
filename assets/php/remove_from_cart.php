<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/check_login.php';

header('Content-Type: application/json; charset=UTF-8');

// Apenas utilizadores normais podem mexer no carrinho
if ((int) $_SESSION['admin'] !== 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Acesso negado.'
    ]);
    exit;
}

$isbn = trim($_GET['isbn'] ?? '');

if ($isbn === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'ISBN inválido.'
    ]);
    exit;
}

$userId = (int) $_SESSION['id'];

try {
    // Confirmar se o item existe no carrinho do utilizador
    $getItemStmt = $pdo->prepare("
        SELECT quantidade
        FROM carrinho
        WHERE id_utilizador = ? AND cod_isbn = ?
        LIMIT 1
    ");
    $getItemStmt->execute([$userId, $isbn]);
    $item = $getItemStmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Item não encontrado no carrinho.'
        ]);
        exit;
    }

    // Remover item do carrinho
    $deleteStmt = $pdo->prepare("
        DELETE FROM carrinho
        WHERE id_utilizador = ? AND cod_isbn = ?
    ");
    $deleteStmt->execute([$userId, $isbn]);

    // Nova contagem do carrinho
    $countStmt = $pdo->prepare("
        SELECT COALESCE(SUM(quantidade), 0) AS total
        FROM carrinho
        WHERE id_utilizador = ?
    ");
    $countStmt->execute([$userId]);
    $countData = $countStmt->fetch(PDO::FETCH_ASSOC);
    $cartCount = (int) ($countData['total'] ?? 0);

    echo json_encode([
        'status' => 'success',
        'cartCount' => $cartCount,
        'message' => 'Item removido do carrinho.'
    ]);
    exit;
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao remover item do carrinho.'
    ]);
    exit;
}
?>