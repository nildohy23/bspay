<?php

require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

$pageTitle = "Recarga";
include TEMPLATE_PATH . '/header.php';
?>

<div class="container">
    <div class="recarga-form">
        <h2>Nova Recarga</h2>
        <form action="/gerar_qr" method="POST">
            <div class="form-group">
                <label for="amount">Valor (R$):</label>
                <input type="number" id="amount" name="amount" step="0.01" min="0.01" required>
            </div>

            <div class="form-group">
                <label for="name">Nome:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" readonly>
            </div>

            <div class="form-group">
                <label for="document">Documento (CPF/CNPJ):</label>
                <input type="text" id="document" name="document" required>
            </div>

            <button type="submit" class="btn btn-primary">Gerar QR Code</button>
        </form>
    </div>
</div>

<style>
.recarga-form {
    max-width: 500px;
    margin: 40px auto;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.form-group input[readonly] {
    background-color: #f8f9fa;
}

.btn-primary {
    width: 100%;
    padding: 12px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.btn-primary:hover {
    background: #0056b3;
}
</style>

<?php include TEMPLATE_PATH . '/footer.php'; ?>