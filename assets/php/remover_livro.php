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
    $pdo->beginTransaction();

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
        $pdo->rollBack();
        $_SESSION['message'] = 'Livro não encontrado.';
        header('Location: ' . BASE_URL . '/livros.php');
        exit;
    }

    // Verificar se o livro está em carrinhos
    $sqlCarrinho = "
        SELECT COUNT(*) 
        FROM carrinho
        WHERE cod_isbn = :cod_isbn
    ";
    $stmtCarrinho = $pdo->prepare($sqlCarrinho);
    $stmtCarrinho->execute([':cod_isbn' => $cod_isbn]);
    $existeNoCarrinho = (int) $stmtCarrinho->fetchColumn();

    if ($existeNoCarrinho > 0) {
        $pdo->rollBack();
        $_SESSION['message'] = 'Não é possível remover este livro porque ainda existe em carrinhos de utilizadores.';
        header('Location: ' . BASE_URL . '/livros.php');
        exit;
    }

    // Verificar se o livro tem requisições associadas
    $sqlRequisicoes = "
        SELECT COUNT(*)
        FROM requisicoes
        WHERE cod_isbn = :cod_isbn
    ";
    $stmtRequisicoes = $pdo->prepare($sqlRequisicoes);
    $stmtRequisicoes->execute([':cod_isbn' => $cod_isbn]);
    $existeEmRequisicoes = (int) $stmtRequisicoes->fetchColumn();

    if ($existeEmRequisicoes > 0) {
        $pdo->rollBack();
        $_SESSION['message'] = 'Não é possível remover este livro porque já tem requisições associadas.';
        header('Location: ' . BASE_URL . '/livros.php');
        exit;
    }

    // Remover o livro
    $sqlDelete = "
        DELETE FROM livros
        WHERE cod_isbn = :cod_isbn
        LIMIT 1
    ";
    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->execute([':cod_isbn' => $cod_isbn]);

    if ($stmtDelete->rowCount() <= 0) {
        $pdo->rollBack();
        $_SESSION['message'] = 'Não foi possível remover o livro.';
        header('Location: ' . BASE_URL . '/livros.php');
        exit;
    }

    $pdo->commit();
    $_SESSION['message'] = 'Livro removido com sucesso!';
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['message'] = 'Erro ao remover o livro.';
}

header('Location: ' . BASE_URL . '/livros.php');
exit;
?>