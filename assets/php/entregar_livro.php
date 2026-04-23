<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/check_login.php';

header('Content-Type: application/json; charset=utf-8');

// Apenas administradores
if ((int) $_SESSION['admin'] !== 1) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Acesso negado.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$id = trim($_GET['id'] ?? '');

if ($id === '' || !ctype_digit($id)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'ID inválido.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE requisicoes
        SET
            status = 'com_o_aluno',
            prazo_devolucao = DATE_ADD(NOW(), INTERVAL 15 DAY)
        WHERE id = ?
          AND status = 'pronto_para_levantar'
    ");
    $stmt->execute([(int) $id]);

    if ($stmt->rowCount() <= 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Nenhum registo foi atualizado.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'success' => true
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno ao entregar livro.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}