<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../logins/login.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    $_SESSION['login_error'] = 'Pedido inválido. Tente novamente.';
    header('Location: ../../logins/login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$codigo_secreto = trim($_POST['cod_secreto'] ?? '');
$data_entrada = $_POST['data_entrada'] ?? date('Y-m-d');
$atividades = $_POST['oquefazer'] ?? [];

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    $_SESSION['login_error'] = 'Credenciais inválidas.';
    header('Location: ../../logins/login.php');
    exit;
}

try {
    $sql = "SELECT * FROM utilizadores WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $utilizador = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$utilizador || !password_verify($password, $utilizador['password'])) {
        $_SESSION['login_error'] = 'Email ou password incorretos.';
        header('Location: ../../logins/login.php');
        exit;
    }

    session_regenerate_id(true);

    $_SESSION['loggedin'] = true;
    $_SESSION['email'] = $utilizador['email'];
    $_SESSION['id'] = (int) $utilizador['id'];
    $_SESSION['username'] = $utilizador['nome_completo'];

    // Define o papel apenas para esta sessão, conforme o código secreto
    $_SESSION['admin'] = ($codigo_secreto === '1234') ? 1 : 0;

    // Registar atividades selecionadas
    if (is_array($atividades) && !empty($atividades)) {
        $atividadesPermitidas = ['ler', 'estudar', 'fazer_trabalhos', 'requisitar_livros', 'outros'];

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_entrada)) {
            $data_entrada = date('Y-m-d');
        }

        $data_registo = $data_entrada . ' ' . date('H:i:s');

        $sqlInsert = "
            INSERT INTO atividades (id_utilizador, atividade, data_registo)
            VALUES (:id_utilizador, :atividade, :data_registo)
        ";
        $stmtInsert = $pdo->prepare($sqlInsert);

        foreach ($atividades as $atividade) {
            $atividade_valida = in_array($atividade, $atividadesPermitidas, true) ? $atividade : 'outros';

            $stmtInsert->execute([
                ':id_utilizador' => $utilizador['id'],
                ':atividade' => $atividade_valida,
                ':data_registo' => $data_registo
            ]);
        }
    }

    unset($_SESSION['csrf_token']);

    if ((int) $_SESSION['admin'] === 1) {
        header('Location: ../../index.php');
        exit;
    }

    header('Location: ../../index_user.php');
    exit;
} catch (PDOException $e) {
    $_SESSION['login_error'] = 'Erro interno ao iniciar sessão. Tente novamente.';
    header('Location: ../../logins/login.php');
    exit;
}