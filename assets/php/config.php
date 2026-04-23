<?php
$host = "localhost";
$dbusername = "root";
$dbpassword = "usbw";
$dbname = "bookhubjb";

if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', 'smtp.gmail.com');
}

if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', 587);
}

if (!defined('SMTP_USER')) {
    define('SMTP_USER', 'suporte.bookhub@gmail.com');
}

if (!defined('SMTP_PASS')) {
    define('SMTP_PASS', 'mxmkqzyajniojvpa'); // Senha de app
}

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost:8080/ModuloProjeto');
}

if (!defined('API_TOKEN')) {
    define('API_TOKEN', 'bookhub_secret_token_123');
}

try {
    $pdo = new PDO(
        "mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4",
        $dbusername,
        $dbpassword
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao ligar à base de dados.");
}