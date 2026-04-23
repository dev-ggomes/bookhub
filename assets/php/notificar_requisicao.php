<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/check_login.php';
require_once __DIR__ . '/carrinho_header.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Apenas administradores podem aceder
if ((int) $_SESSION['admin'] !== 1) {
    header('Location: ' . BASE_URL . '/index_user.php');
    exit;
}

$userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$reqIdsRaw = $_GET['req_ids'] ?? '';

$reqIds = [];

if (is_string($reqIdsRaw) && trim($reqIdsRaw) !== '') {
    $partes = explode(',', $reqIdsRaw);

    foreach ($partes as $id) {
        $id = trim($id);

        if ($id !== '' && ctype_digit($id) && (int) $id > 0) {
            $reqIds[] = (int) $id;
        }
    }

    $reqIds = array_values(array_unique($reqIds));
}

if (!$userId || empty($reqIds)) {
    header('Location: ' . BASE_URL . '/gerir-requisicoes.php');
    exit;
}

$emailEnviado = false;
$erro = null;
$user = null;
$livros = [];

try {
    $userStmt = $pdo->prepare("
        SELECT id, nome_completo, email
        FROM utilizadores
        WHERE id = ?
        LIMIT 1
    ");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Utilizador não encontrado.');
    }

    $placeholders = implode(',', array_fill(0, count($reqIds), '?'));

    $reqStmt = $pdo->prepare("
        SELECT r.id, r.status, l.titulo
        FROM requisicoes r
        JOIN livros l ON l.cod_isbn = r.cod_isbn
        WHERE r.id IN ($placeholders)
          AND r.id_utilizador = ?
    ");
    $reqStmt->execute([...$reqIds, $userId]);
    $requisicoes = $reqStmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($requisicoes) !== count($reqIds)) {
        throw new Exception('Uma ou mais requisições são inválidas ou não pertencem ao utilizador indicado.');
    }

    $livros = array_values(array_unique(array_column($requisicoes, 'titulo')));

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pdo->beginTransaction();

        $updateStmt = $pdo->prepare("
            UPDATE requisicoes
            SET status = 'pronto_para_levantar',
                data_conclusao = NOW()
            WHERE id IN ($placeholders)
              AND id_utilizador = ?
        ");
        $updateStmt->execute([...$reqIds, $userId]);

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

        $mail->setFrom(SMTP_USER, 'BOOKhub - Biblioteca');
        $mail->addAddress($user['email'], $user['nome_completo']);
        $mail->Subject = 'Os seus livros estão prontos para levantamento!';
        $mail->isHTML(true);

        $livrosTexto = '• ' . implode('<br>• ', array_map(function ($livro) {
            return htmlspecialchars($livro, ENT_QUOTES, 'UTF-8');
        }, $livros));

        $nomeUtilizador = htmlspecialchars($user['nome_completo'], ENT_QUOTES, 'UTF-8');

        $mail->Body = "
            <html>
            <body>
                <h2>Olá, {$nomeUtilizador}!</h2>
                <p>Os seguintes livros estão prontos para serem levantados na biblioteca:</p>
                <p>{$livrosTexto}</p>
                <p><b>Local:</b> Biblioteca Escolar</p>
                <p><b>Horário:</b> 09:00 - 18:00 (Segunda a Sexta)</p>
                <p>Por favor, traga um documento de identificação quando vier recolher os livros.</p>
            </body>
            </html>
        ";

        $mail->send();

        $pdo->commit();
        $emailEnviado = true;

        header('Location: ' . BASE_URL . '/gerir-requisicoes.php?success=5');
        exit;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $erro = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/index_style.css">
    <link rel="stylesheet" href="../css/notificar_requisicao.css">
    <title>BOOKhub | Notificar Utilizador</title>
</head>
<body>
    <header>
        <div class="box-img-header">
            <?php if ($_SESSION['admin'] == 1): ?>
                <a href="../../index.php">
                    <img class="img-logo" src="../img/bookhubFavicon.png" height="80" width="80" alt="Logo BOOKhub">
                </a>
            <?php else: ?>
                <a href="../../index_user.php">
                    <img class="img-logo" src="../img/bookhubFavicon.png" height="80" width="80" alt="Logo BOOKhub">
                </a>
            <?php endif; ?>
        </div>

        <nav>
            <?php if ($_SESSION['admin'] == 1): ?>
                <a href="../../index.php" class="nav-links">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-house" viewBox="0 0 16 16">
                        <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V7.207l5-5z"/>
                    </svg>
                </a>
            <?php else: ?>
                <a href="../../index_user.php" class="nav-links">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-house" viewBox="0 0 16 16">
                        <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V7.207l5-5z"/>
                    </svg>
                </a>
            <?php endif; ?>

            <a href="../../livros.php" class="nav-links">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-book" viewBox="0 0 16 16">
                    <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/>
                </svg>
            </a>

            <a href="../../gerir-requisicoes.php" class="nav-links">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-gear" viewBox="0 0 16 16">
                    <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0"/>
                    <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115z"/>
                </svg>
            </a>
        </nav>

        <a href="../../detalhes_conta.php" class="btn-action-ref">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16">
                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
            </svg>
        </a>

        <a href="../../logins/logout.php" class="btn-action-logout">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/>
                <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/>
            </svg>
        </a>
    </header>

    <div class="container">
        <h1>Notificar Utilizador</h1>

        <?php if ($erro): ?>
            <div class="error">
                <p><strong>Erro:</strong> <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php endif; ?>

        <?php if ($user): ?>
            <p>Vai notificar o utilizador <strong><?= htmlspecialchars($user['nome_completo'], ENT_QUOTES, 'UTF-8') ?></strong> de que os seguintes livros estão prontos para levantamento:</p>

            <?php if (!empty($livros)): ?>
                <ul>
                    <?php foreach ($livros as $livro): ?>
                        <li><?= htmlspecialchars($livro, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST">
            <button type="submit" class="btn">✔ Confirmar e Enviar Notificação</button>
        </form>
    </div>
</body>
</html>