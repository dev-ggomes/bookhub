<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/check_login.php';

// Apenas administradores
if ((int) $_SESSION['admin'] !== 1) {
    header('Location: ' . BASE_URL . '/index_user.php');
    exit;
}

$idRequisicao = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idRequisicao) {
    die('ID inválido.');
}

try {
    $stmt = $pdo->prepare("
        UPDATE requisicoes
        SET status = 'pronto_para_levantar',
            data_conclusao = NOW()
        WHERE id = ?
    ");

    $stmt->execute([$idRequisicao]);

    header('Location: ' . BASE_URL . '/gerir-requisicoes.php?success=1');
    exit;
} catch (PDOException $e) {
    die('Erro ao concluir requisição.');
}