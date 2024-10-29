<?php
session_start();
require_once 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Buscar dados atualizados do usuário
try {
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            COALESCE(SUM(CASE 
                WHEN t.type = 'deposit' AND t.status = 'completed' 
                THEN t.amount ELSE 0 
            END), 0) as balance,
            COALESCE(SUM(CASE 
                WHEN t.type = 'deposit' AND t.status = 'completed' 
                AND DATE(t.created_at) = CURDATE() 
                THEN t.amount ELSE 0 
            END), 0) as daily_balance,
            COALESCE(SUM(CASE 
                WHEN t.type = 'withdrawal' AND t.status = 'completed' 
                THEN t.amount ELSE 0 
            END), 0) as withdrawals
        FROM users u
        LEFT JOIN transactions t ON u.id = t.user_id
        WHERE u.id = ?
        GROUP BY u.id
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("Usuário não encontrado");
    }

} catch (Exception $e) {
    error_log("Erro no dashboard: " . $e->getMessage());
    $error = "Erro ao carregar dados do usuário";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .balance-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .balance-value {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }
        .balance-label {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <?php include TEMPLATE_PATH . '/header.php'; ?>

    <div class="container mt-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12 mb-4">
                <h2>Bem-vindo, <?php echo htmlspecialchars($user['name']); ?>!</h2>
            </div>

            <!-- Saldo Total -->
            <div class="col-md-4">
                <div class="balance-card">
                    <div class="balance-label">Saldo Total</div>
                    <div class="balance-value">
                        R$ <?php echo number_format($user['balance'], 2, ',', '.'); ?>
                    </div>
                </div>
            </div>

            <!-- Saldo do Dia -->
            <div class="col-md-4">
                <div class="balance-card">
                    <div class="balance-label">Saldo do Dia</div>
                    <div class="balance-value">
                        R$ <?php echo number_format($user['daily_balance'], 2, ',', '.'); ?>
                    </div>
                </div>
            </div>

            <!-- Total de Saques -->
            <div class="col-md-4">
                <div class="balance-card">
                    <div class="balance-label">Total de Saques</div>
                    <div class="balance-value">
                        R$ <?php echo number_format($user['withdrawals'], 2, ',', '.'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="row mt-4">
            <div class="col-12">
                <a href="gerar_qr.php" class="btn btn-primary me-2">Gerar QR Code</a>
                <a href="recarg.php" class="btn btn-success">Fazer Recarga</a>
            </div>
        </div>

        <!-- Últimas Transações -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Últimas Transações</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <?php
                            try {
                                $stmt = $pdo->prepare("
                                    SELECT * FROM transactions 
                                    WHERE user_id = ? 
                                    ORDER BY created_at DESC 
                                    LIMIT 10
                                ");
                                $stmt->execute([$_SESSION['user_id']]);
                                $transactions = $stmt->fetchAll();
                            } catch (Exception $e) {
                                $transactions = [];
                            }
                            ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Tipo</th>
                                        <th>Valor</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $t): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($t['created_at'])); ?></td>
                                            <td><?php echo $t['type'] === 'deposit' ? 'Depósito' : 'Saque'; ?></td>
                                            <td>R$ <?php echo number_format($t['amount'], 2, ',', '.'); ?></td>
                                            <td><?php echo ucfirst($t['status']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($transactions)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Nenhuma transação encontrada</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include TEMPLATE_PATH . '/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>