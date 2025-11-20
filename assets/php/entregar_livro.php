<?php
session_start();
require_once 'config.php';

// Verificar se é admin
if (!isset($_SESSION['admin']) || $_SESSION['admin'] != 1) {
    die(json_encode(['success' => false, 'error' => 'Acesso negado.']));
}

$id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($id)) {
    die(json_encode(['success' => false, 'error' => 'ID não especificado.']));
}

try {
    $stmt = $pdo->prepare("UPDATE requisicoes 
                             SET 
                                 status = 'com_o_aluno',
                                 prazo_devolucao = DATE_ADD(NOW(), INTERVAL 15 DAY)
                            WHERE id = ?
                         ");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Nenhum registo afetado.']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e.getMessage()]);
}