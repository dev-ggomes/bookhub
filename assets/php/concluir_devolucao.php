<?php
session_start();
require_once 'config.php';

// Verificar admin
if (!isset($_SESSION['admin']) || (int) $_SESSION['admin'] !== 1) {
    $_SESSION['cart_error'] = 'Acesso negado. Somente administradores podem executar esta ação.';
    header('Location: ' . BASE_URL . '/gerir-requisicoes.php');
    exit;
}

// Verificar se o ID foi fornecido
$idRequisicao = trim($_GET['id'] ?? '');

if ($idRequisicao === '' || !ctype_digit($idRequisicao)) {
    header('Location: ' . BASE_URL . '/gerir-requisicoes.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Verificar se a devolução foi solicitada
    $checkStmt = $pdo->prepare("
        SELECT cod_isbn
        FROM requisicoes
        WHERE id = ?
          AND status = 'com_o_aluno'
          AND data_devolucao = '1970-01-01 00:00:01'
        LIMIT 1
    ");
    $checkStmt->execute([(int) $idRequisicao]);
    $requisicao = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$requisicao) {
        throw new Exception('Não é possível concluir a devolução desta requisição.');
    }

    // 2. Atualizar status e data de devolução
    $updateStmt = $pdo->prepare("
        UPDATE requisicoes
        SET status = 'devolvido',
            data_devolucao = NOW()
        WHERE id = ?
    ");
    $updateStmt->execute([(int) $idRequisicao]);

    if ($updateStmt->rowCount() <= 0) {
        throw new Exception('Não foi possível atualizar o estado da requisição.');
    }

    // 3. Atualizar stock disponível
    $stockStmt = $pdo->prepare("
        UPDATE livros
        SET disponivel = disponivel + 1
        WHERE cod_isbn = ?
    ");
    $stockStmt->execute([$requisicao['cod_isbn']]);

    if ($stockStmt->rowCount() <= 0) {
        throw new Exception('Não foi possível atualizar o stock do livro.');
    }

    $pdo->commit();

    header('Location: ' . BASE_URL . '/gerir-requisicoes.php?success=3');
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header('Location: ' . BASE_URL . '/gerir-requisicoes.php');
    exit;
}