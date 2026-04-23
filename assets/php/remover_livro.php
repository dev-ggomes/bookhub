<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/check_login.php';

// Apenas administradores podem remover livros
if ((int) $_SESSION['admin'] !== 1) {
    header('Location: ' . BASE_URL . '/index_user.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/livros.php');
    exit;
}

$cod_isbn = trim($_POST['isbn'] ?? '');

if ($cod_isbn === '') {
    $_SESSION['message'] = 'ISBN não fornecido.';
    header('Location: ' . BASE_URL . '/livros.php');
    exit;
}

try {
    // Verificar se o livro existe
    $sqlCheck = "
        SELECT cod_isbn
        FROM livros
        WHERE cod_isbn = :cod_isbn
        LIMIT 1
    ";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([':cod_isbn' => $cod_isbn]);
    $livro = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$livro) {
        $_SESSION['message'] = 'Livro não encontrado.';
        header('Location: ' . BASE_URL . '/livros.php');
        exit;
    }

    // Remover o livro
    $sqlDelete = "
        DELETE FROM livros
        WHERE cod_isbn = :cod_isbn
    ";
    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->execute([':cod_isbn' => $cod_isbn]);

    if ($stmtDelete->rowCount() > 0) {
        $_SESSION['message'] = 'Livro removido com sucesso!';
    } else {
        $_SESSION['message'] = 'Não foi possível remover o livro.';
    }
} catch (PDOException $e) {
    $_SESSION['message'] = 'Erro ao remover o livro.';
}

header('Location: ' . BASE_URL . '/livros.php');
exit;
?>