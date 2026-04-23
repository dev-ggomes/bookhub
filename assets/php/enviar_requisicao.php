<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/check_login.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Apenas utilizadores normais podem requisitar livros
if ((int) $_SESSION['admin'] !== 0) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/cart.php');
    exit;
}

$userId = (int) $_SESSION['id'];

try {
    $pdo->beginTransaction();

    // Buscar itens do carrinho
    $stmt = $pdo->prepare("
        SELECT c.cod_isbn, c.quantidade, l.titulo, l.autor
        FROM carrinho c
        JOIN livros l ON c.cod_isbn = l.cod_isbn
        WHERE c.id_utilizador = ?
    ");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) {
        $_SESSION['cart_error'] = 'O seu carrinho está vazio.';
        $pdo->rollBack();
        header('Location: ' . BASE_URL . '/cart.php');
        exit;
    }

    // Verificar disponibilidade
    foreach ($cartItems as $item) {
        $checkStmt = $pdo->prepare("
            SELECT disponivel
            FROM livros
            WHERE cod_isbn = ?
            LIMIT 1
        ");
        $checkStmt->execute([$item['cod_isbn']]);
        $livro = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$livro || (int) $livro['disponivel'] < (int) $item['quantidade']) {
            $_SESSION['cart_error'] = "O livro '{$item['titulo']}' não tem unidades suficientes disponíveis.";
            $pdo->rollBack();
            header('Location: ' . BASE_URL . '/cart.php');
            exit;
        }
    }

    // Criar requisições
    $requisicoes = [];

    foreach ($cartItems as $item) {
        for ($i = 0; $i < (int) $item['quantidade']; $i++) {
            $stmtReq = $pdo->prepare("
                INSERT INTO requisicoes (id_utilizador, cod_isbn, data_requisicao, status)
                VALUES (?, ?, NOW(), 'pendente')
            ");
            $stmtReq->execute([$userId, $item['cod_isbn']]);
            $requisicoes[] = $pdo->lastInsertId();
        }

        // Atualizar stock disponível
        $updateStmt = $pdo->prepare("
            UPDATE livros
            SET disponivel = disponivel - ?
            WHERE cod_isbn = ?
        ");
        $updateStmt->execute([(int) $item['quantidade'], $item['cod_isbn']]);
    }

    // Limpar carrinho
    $stmtDelete = $pdo->prepare("DELETE FROM carrinho WHERE id_utilizador = ?");
    $stmtDelete->execute([$userId]);

    // Buscar dados do utilizador
    $userStmt = $pdo->prepare("
        SELECT nome_completo, email
        FROM utilizadores
        WHERE id = ?
        LIMIT 1
    ");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Utilizador não encontrado.');
    }

    // Preparar lista de livros
    $livrosLista = array_map(function ($item) {
        return "• {$item['titulo']} - {$item['autor']} (ISBN: {$item['cod_isbn']}) - {$item['quantidade']} unidade(s)";
    }, $cartItems);

    $livrosTexto = implode('<br>', $livrosLista);

    // Configurar PHPMailer
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = 'tls';
    $mail->Port = SMTP_PORT;
    $mail->CharSet = 'UTF-8';

    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];

    $mail->setFrom(SMTP_USER, 'BOOKhub - Suporte');
    $mail->addAddress('bookhub.adm1@gmail.com', 'Administrador BOOKhub');
    $mail->Subject = 'Nova Requisição de Livros';
    $mail->isHTML(true);

    $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                h2, h3, h4 { color: #007bff; }
            </style>
        </head>
        <body>
            <h2>Nova Requisição realizada por:</h2>
            <p><b>Nome:</b> " . htmlspecialchars($user['nome_completo']) . "</p>
            <p><b>Email:</b> " . htmlspecialchars($user['email']) . "</p>

            <h3>Livros Requisitados:</h3>
            <p>{$livrosTexto}</p>

            <h4>Total de itens: " . count($requisicoes) . "</h4>
            <h4>IDs das Requisições: " . implode(', ', $requisicoes) . "</h4>
        </body>
        </html>
    ";

    $mail->send();

    $pdo->commit();

    $_SESSION['cart_success'] = 'Requisição realizada com sucesso! Um email foi enviado para o administrador.';
    header('Location: ' . BASE_URL . '/cart.php');
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['cart_error'] = 'Erro ao processar requisição: ' . $e->getMessage();
    header('Location: ' . BASE_URL . '/cart.php');
    exit;
}
?>