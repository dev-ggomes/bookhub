<?php
    session_start();
    require_once 'config.php';
    $cartCount = 0;
    if (isset($_SESSION['id'])) {
        $conn = new mysqli($host, $dbusername, $dbpassword, $dbname);
        $stmt = $conn->prepare("SELECT SUM(quantidade) AS total FROM carrinho WHERE id_utilizador = ?");
        $stmt->bind_param("i", $_SESSION['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $cartCount = isset($row['total']) ? $row['total'] : 0;
        $stmt->close();
        $conn->close();
    }
?>