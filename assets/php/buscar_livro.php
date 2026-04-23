<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$isbn = trim($_GET['isbn'] ?? '');

if ($isbn === '') {
    http_response_code(400);
    echo json_encode([
        'error' => 'ISBN não especificado.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT titulo, autor
        FROM livros
        WHERE cod_isbn = ?
        LIMIT 1
    ");
    $stmt->execute([$isbn]);
    $livro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$livro) {
        http_response_code(404);
        echo json_encode([
            'error' => 'Livro não encontrado.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'titulo' => $livro['titulo'],
        'autor' => $livro['autor']
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro interno ao buscar livro.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}