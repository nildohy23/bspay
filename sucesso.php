<?php
require_once 'config/database.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit;
}

// Verificar transação
$transaction_id = $_GET['tid'] ?? '';
if (empty($transaction_id)) {
    header("Location: index");
    exit;
}

try {
    // Buscar detalhes da transação
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            u.email,
            u.username
        FROM transactions t
        JOIN users u ON u.id = t.user_id
        WHERE t.transaction_id = ?
        AND t.user_id = ?
        AND t.status = 'PAID'
        LIMIT 1
    ");

    $stmt->execute([$transaction_id, $_SESSION['user_id']]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        header("Location: index");
        exit;
    }

    // Registrar visualização
    logAccess($_SESSION['user_id'], 'view_success', [
        'transaction_id' => $transaction_id,
        'amount' => $transaction['amount']
    ]);

} catch (Exception $e) {
    logError("Erro ao processar página de sucesso", ['error' => $e->getMessage()]);
    header("Location: index");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Confirmado - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --success-color: #198754;
            --bg-dark: #212529;
            --bg-darker: #1a1d20;
            --text-light: #f8f9fa;
            --border-color: #2d3238;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            background-color: var(--bg-darker);
            border: 1px solid var(--border-color);
            border-radius: 15px;
        }

        .success-icon {
            font-size: 5rem;
            color: var(--success-color);
            margin-bottom: 1rem;
        }

        .amount {
            font-size: 2rem;
            font-weight: bold;
            color: var(--success-color);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body text-center p-5">
                        <i class="fas fa-check-circle success-icon"></i>
                        
                        <h2 class="mb-4">Pagamento Confirmado!</h2>
                        
                        <div class="amount mb-4">
                            <?php echo formatMoney($transaction['amount']); ?>
                        </div>

                        <div class="mb-4">
                            <div class="text-muted">ID da Transação</div>
                            <div><?php echo $transaction['transaction_id']; ?></div>
                        </div>

                        <div class="mb-4">
                            <div class="text-muted">Data da Confirmação</div>
                            <div><?php echo formatDate($transaction['updated_at']); ?></div>
                        </div>

                        <div class="alert alert-success">
                            <i class="fas fa-info-circle"></i>
                            O valor já foi creditado em sua conta!
                        </div>

                        <div class="mt-4">
                            <a href="index" class="btn btn-primary">
                                <i class="fas fa-home"></i> Voltar ao Início
                            </a>
                            
                            <a href="gerar_qr" class="btn btn-success ms-2">
                                <i class="fas fa-plus"></i> Novo Depósito
                            </a>
                