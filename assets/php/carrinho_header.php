<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

$cartCount = 0;

if (isset($_SESSION['id'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT SUM(quantidade) AS total
            FROM carrinho
            WHERE id_utilizador = ?
        ");
        $stmt->execute([$_SESSION['id']]);
        $row = $stmt->fetch();

        $cartCount = isset($row['total']) ? (int) $row['total'] : 0;
    } catch (PDOException $e) {
        $cartCount = 0;
    }
}