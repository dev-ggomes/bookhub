<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/check_login.php';

// Apenas administradores podem adicionar/atualizar livros
if ((int) $_SESSION['admin'] !== 1) {
    header('Location: ' . BASE_URL . '/index_user.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$cod_isbn = trim($_POST['isbn'] ?? '');
$titulo = trim($_POST['title'] ?? '');
$edicao = trim($_POST['edition'] ?? '');
$autor = trim($_POST['author'] ?? '');
$numero_paginas = trim($_POST['numero_paginas'] ?? '');
$quantidade = trim($_POST['quantity'] ?? '');
$resumo = trim($_POST['summary'] ?? '');

if (
    $cod_isbn === '' ||
    $titulo === '' ||
    $edicao === '' ||
    $autor === '' ||
    $numero_paginas === '' ||
    $quantidade === '' ||
    $resumo === ''
) {
    $_SESSION['message'] = 'Preencha todos os campos obrigatórios.';
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if (!filter_var($numero_paginas, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
    $_SESSION['message'] = 'O número de páginas deve ser um número inteiro positivo.';
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if (!filter_var($quantidade, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
    $_SESSION['message'] = 'A quantidade deve ser um número inteiro positivo.';
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Verificar se o livro já existe
    $sqlCheck = "SELECT quantidade FROM livros WHERE cod_isbn = :cod_isbn LIMIT 1";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([':cod_isbn' => $cod_isbn]);
    $livroExistente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($livroExistente) {
        $nova_quantidade = (int) $livroExistente['quantidade'] + (int) $quantidade;

        $sqlUpdate = "
            UPDATE livros
            SET titulo = :titulo,
                edicao = :edicao,
                autor = :autor,
                numero_paginas = :numero_paginas,
                quantidade = :nova_quantidade,
                resumo = :resumo
            WHERE cod_isbn = :cod_isbn
        ";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':titulo' => $titulo,
            ':edicao' => $edicao,
            ':autor' => $autor,
            ':numero_paginas' => (int) $numero_paginas,
            ':nova_quantidade' => $nova_quantidade,
            ':resumo' => $resumo,
            ':cod_isbn' => $cod_isbn
        ]);

        $_SESSION['message'] = 'Quantidade do livro atualizada com sucesso!';
    } else {
        $sqlInsert = "
            INSERT INTO livros (cod_isbn, titulo, edicao, autor, numero_paginas, quantidade, resumo)
            VALUES (:cod_isbn, :titulo, :edicao, :autor, :numero_paginas, :quantidade, :resumo)
        ";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            ':cod_isbn' => $cod_isbn,
            ':titulo' => $titulo,
            ':edicao' => $edicao,
            ':autor' => $autor,
            ':numero_paginas' => (int) $numero_paginas,
            ':quantidade' => (int) $quantidade,
            ':resumo' => $resumo
        ]);

        $_SESSION['message'] = 'Novo livro registado com sucesso!';
    }

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['message'] = 'Erro ao guardar o livro.';
}

header('Location: ' . BASE_URL . '/index.php');
exit;
?>