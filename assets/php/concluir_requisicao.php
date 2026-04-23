<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/check_login.php';

// Apenas administradores
if ((int) $_SESSION['admin'] !== 1) {
    header('Location: ' . BASE_URL . '/index_user.php');
    exit;
}

$idRequisicao = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idRequisicao || $idRequisicao <= 0) {
    header('Location: ' . BASE_URL . '/gerir-requisicoes.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Buscar dados da requisição
    $stmt = $pdo->prepare("
        SELECT r.id, r.status, u.email, u.nome_completo, l.titulo
        FROM requisicoes r
        JOIN utilizadores u ON u.id = r.id_utilizador
        JOIN livros l ON l.cod_isbn = r.cod_isbn
        WHERE r.id = ?
        LIMIT 1
    ");
    $stmt->execute([$idRequisicao]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dados) {
        throw new Exception('Requisição não encontrada.');
    }

    if ($dados['status'] !== 'pendente') {
        throw new Exception('Só é possível concluir requisições pendentes.');
    }

    // Atualizar status
    $updateStmt = $pdo->prepare("
        UPDATE requisicoes
        SET status = 'pronto_para_levantar',
            data_conclusao = NOW()
        WHERE id = ?
    ");
    $updateStmt->execute([$idRequisicao]);

    $pdo->commit();

    header('Location: ' . BASE_URL . '/gerir-requisicoes.php?success=1');
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header('Location: ' . BASE_URL . '/gerir-requisicoes.php');
    exit;
}
?>