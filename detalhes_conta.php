<?php
require_once './assets/php/config.php';
require_once './assets/php/check_login.php';
require_once './assets/php/carrinho_header.php';

// Obter dados do utilizador
$dados = [];
$erro = '';

try {
    $stmt = $pdo->prepare("
        SELECT nome_completo, email, genero, admin
        FROM utilizadores
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['id']]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dados) {
        $erro = "Utilizador não encontrado!";
    }
} catch (PDOException $e) {
    $erro = "Erro ao carregar os dados da conta.";
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOOKhub | Conta</title>
    <link rel="stylesheet" href="./assets/css/index_style.css">
    <link rel="stylesheet" href="./assets/css/detalhes_conta.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="conta-container">
        <?php if ($erro): ?>
            <section class="profile-section">
                <h2 class="user-name"><?= htmlspecialchars($erro) ?></h2>
            </section>
        <?php else: ?>
            <section class="profile-section">
                <div class="user-avatar">
                    <?= strtoupper(substr(htmlspecialchars($dados['nome_completo']), 0, 1)) ?>
                </div>
                <h2 class="user-name"><?= htmlspecialchars($dados['nome_completo']) ?></h2>
                <p class="user-role">
                    <?= ($dados['admin'] ? 'Administrador' : 'Membro') ?>
                    <?php if ($dados['admin']): ?>
                        <span class="admin-tag"></span>
                    <?php endif; ?>
                </p>
            </section>

            <section class="details-section">
                <!-- Email -->
                <div class="detail-card">
                    <div class="detail-header-email">
                        <div class="detail-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-envelope" viewBox="0 0 16 16">
                                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741M1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                            </svg>
                        </div>
                        <div class="details">
                            <div class="detail-title">Email</div>
                            <div class="detail-content-email"><?= htmlspecialchars($dados['email']) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Género -->
                <div class="detail-card">
                    <div class="detail-header">
                        <div class="detail-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-gender-ambiguous" viewBox="0 0 16 16">
                                <path d="M11.5 1a.5.5 0 0 1 0-1h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V1.707l-4.45 4.45A4 4 0 1 1 10.5 8H9V6.5a.5.5 0 0 1 1 0V8h1.5a5 5 0 1 0-1.479-3.536l.647-.646z"/>
                                <path d="M10 .5a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="detail-title">Género</div>
                            <div class="detail-content-genre">
                                <?php
                                switch ($dados['genero']) {
                                    case 'm':
                                        echo 'Masculino';
                                        break;
                                    case 'f':
                                        echo 'Feminino';
                                        break;
                                    case 'o':
                                        echo 'Outro';
                                        break;
                                    default:
                                        echo 'Não especificado';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tipo de Conta -->
                <div class="detail-card">
                    <div class="detail-header">
                        <div class="detail-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-shield-lock" viewBox="0 0 16 16">
                                <path d="M5.338 1.59a61 61 0 0 0-2.837.856.48.48 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.7 10.7 0 0 0 2.287 2.233c.346.244.652.42.893.533q.18.085.293.118a1 1 0 0 0 .101.025 1 1 0 0 0 .1-.025q.114-.034.294-.118c.24-.113.547-.29.893-.533a10.7 10.7 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.8 11.8 0 0 1-2.517 2.453 7 7 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7 7 0 0 1-1.048-.625 11.8 11.8 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 63 63 0 0 0 5.072.56"/>
                                <path d="M9.5 6.5a1.5 1.5 0 0 1-1 1.415l.385 1.99a.5.5 0 0 1-.491.595h-.788a.5.5 0 0 1-.49-.595l.384-1.99a1.5 1.5 0 1 1 2-1.415"/>
                            </svg>
                        </div>
                        <div>
                            <div class="detail-title">Tipo de Conta</div>
                            <div class="detail-content-accountType">
                                <?= ($dados['admin'] ? 'Administrador' : 'Utilizador') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 BOOKhub. Todos os direitos reservados.</p>
    </footer>
</body>
</html>