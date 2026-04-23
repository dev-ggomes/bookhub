<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $sql = "
        SELECT cod_isbn, titulo, edicao, autor, numero_paginas, quantidade, resumo, disponivel
        FROM livros
        ORDER BY titulo ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $livros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($livros, JSON_UNESCAPED_UNICODE);
    exit;
} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        'error' => 'Erro ao buscar livros.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}