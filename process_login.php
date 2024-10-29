<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        die("Erro: Todos os campos são obrigatórios.");
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_balance'] = $user['balance'];
            $_SESSION['user_daily_balance'] = $user['daily_balance'];
            $_SESSION['user_withdrawals'] = $user['withdrawals'];
            header("Location: dashboard");
            exit;
        } else {
            echo "Email ou senha incorretos.";
        }
    } catch (PDOException $e) {
        die("Erro ao fazer login: " . $e->getMessage());
    }
} else {
    die("Acesso inválido.");
}
?>