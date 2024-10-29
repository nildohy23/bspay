<?php
session_start();

// Se já estiver logado, redireciona para dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: /dashboard");
    exit;
}

$pageTitle = "Registro";
include __DIR__ . '/../templates/header.php';
?>

<div class="container">
    <div class="register-form">
        <h2>Criar Conta</h2>
        <form action="/process_register" method="POST">
            <div class="form-group">
                <label for="name">Nome:</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Senha:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmar Senha:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn btn-primary">Registrar</button>
        </form>
        <p class="login-link">
            Já tem uma conta? <a href="/login">Faça login</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>