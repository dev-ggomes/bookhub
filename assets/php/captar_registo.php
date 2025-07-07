<?php
session_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["nome_completo"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $genero = $_POST["genero"];

    try{
        require_once "config.php";

        $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname", $dbusername, $dbpassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $query = "INSERT INTO utilizadores (nome_completo, email, password, genero)
                  VALUES (:nome_completo, :email, :password, :genero);";

        $stmt = $pdo->prepare($query);

        $stmt->bindParam(":nome_completo", $username);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $hashedPassword);
        $stmt->bindParam(":genero", $genero);

        $stmt->execute();

        $pdo = null;
        $stmt = null;

        header("Location: ../../logins/login.php");

        exit();
    } catch (PDOException $e) {

        // Verifica se é erro de email duplicado
        if ($e->getCode() == '23000') {
            $_SESSION['erro_email'] = "Este email já está registado!";
        } else {
            $_SESSION['erro_geral'] = "Ocorreu um erro: " . $e->getMessage();
        }

        // Redireciona de volta para o formulário
        header("Location: ../../logins/registo_com_validacao.php");
        exit();
    }
} else {
    header("Location: ../../logins/registo_com_validacao.php");
    exit();
}