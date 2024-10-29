<?php
require_once 'config/database.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit;
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitização e validação
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        $name = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $document = htmlspecialchars(trim($_POST['document'] ?? ''), ENT_QUOTES, 'UTF-8');

        if (!$amount || $amount <= 0 || empty($name) || empty($document)) {
            throw new Exception("Todos os campos são obrigatórios.");
        }

        // Validações adicionais
        if ($amount < MIN_DEPOSIT || $amount > MAX_DEPOSIT) {
            throw new Exception("Valor deve ser entre " . formatMoney(MIN_DEPOSIT) . " e " . formatMoney(MAX_DEPOSIT));
        }

        // Criar pagamento PIX
        $result = createPixPayload($amount, $name, $document);
        
        if (!$result['success']) {
            throw new Exception($result['error']);
        }

        $payment_data = $result['data'];
        $external_id = $result['external_id'];

        // Salvar transação
        $stmt = $pdo->prepare("
            INSERT INTO transactions 
            (user_id, transaction_id, external_id, amount, status, qrcode_data, payer_name, payer_document) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $payment_data['transactionId'],
            $external_id,
            $payment_data['amount'],
            $payment_data['status'],
            $payment_data['qrcode'],
            $name,
            $document
        ]);

        // Registrar log
        logError("Transação PIX criada", [
            'transaction_id' => $payment_data['transactionId'],
            'external_id' => $external_id,
            'amount' => $amount
        ]);

    } catch (Exception $e) {
        logError("Erro ao processar pagamento", ['error' => $e->getMessage()]);
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerar PIX - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
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

        .form-control, .form-select {
            background-color: var(--bg-dark);
            border: 1px solid var(--border-color);
            color: var(--text-light);
        }

        .form-control:focus, .form-select:focus {
            background-color: var(--bg-dark);
            border-color: var(--primary-color);
            color: var(--text-light);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .qr-container {
            background-color: white;
            padding: 20px;
            border-radius: 15px;
            max-width: 350px;
            margin: 20px auto;
        }

        .status-badge {
            font-size: 1.1em;
            padding: 8px 16px;
            border-radius: 20px;
        }

        .copy-button {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .copy-button:hover {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="text-center mb-4">
                            <i class="fas fa-qrcode"></i> Gerar PIX
                        </h2>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($payment_data)): ?>
                            <div class="text-center">
                                <div class="row mb-4">
                                    <div class="col-sm-6">
                                        <div class="text-muted small">Transação</div>
                                        <div class="fw-bold"><?php echo $payment_data['transactionId']; ?></div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="text-muted small">Valor</div>
                                        <div class="fw-bold"><?php echo formatMoney($payment_data['amount']); ?></div>
                                    </div>
                                </div>

                                <div class="qr-container">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?data=<?php echo urlencode($payment_data['qrcode']); ?>&size=300x300" 
                                         alt="QR Code"
                                         class="img-fluid">
                                </div>

                                <div class="mt-3">
                                    <div class="input-group mb-3">
                                        <input type="text" 
                                               class="form-control" 
                                               value="<?php echo htmlspecialchars($payment_data['qrcode']); ?>" 
                                               readonly>
                                        <button class="btn btn-outline-secondary copy-button" 
                                                type="button" 
                                                onclick="copyToClipboard(this)">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="alert alert-info mt-4">
                                    <h5 class="alert-heading">
                                        <i class="fas fa-info-circle"></i> Instruções
                                    </h5>
                                    <ol class="text-start mb-0">
                                        <li>Abra o app do seu banco</li>
                                        <li>Escolha pagar via PIX</li>
                                        <li>Escaneie o QR Code acima</li>
                                        <li>Confirme as informações e faça o pagamento</li>
                                    </ol>
                                </div>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Valor</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" 
                                               class="form-control" 
                                               id="amount" 
                                               name="amount" 
                                               step="0.01" 
                                               min="<?php echo MIN_DEPOSIT; ?>" 
                                               max="<?php echo MAX_DEPOSIT; ?>" 
                                               required>
                                    </div>
                                    <div class="form-text">
                                        Mínimo: <?php echo formatMoney(MIN_DEPOSIT); ?> - 
                                        Máximo: <?php echo formatMoney(MAX_DEPOSIT); ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="name" class="form-label">Nome Completo</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="name" 
                                           name="name" 
                                           required>
                                </div>

                                <div class="mb-3">
                                    <label for="document" class="form-label">CPF/CNPJ</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="document" 
                                           name="document" 
                                           required>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-qrcode"></i> Gerar QR Code
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-mask-plugin@1.14.16/dist/jquery.mask.min.js"></script>
    <script>
    // Máscaras de input
    $(document).ready(function() {
        $('#document').mask('000.000.000-00');
    });

    // Copiar para clipboard
    function copyToClipboard(button) {
        const input = button.previousElementSibling;
        input.select();
        document.execCommand('copy');
        
        const icon = button.querySelector('i');
        icon.className = 'fas fa-check';
        setTimeout(() => {
            icon.className = 'fas fa-copy';
        }, 2000);
    }

    // Validação do formulário
    (function() {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
    // Função para verificar status do pagamento
function checkPaymentStatus(transactionId) {
    fetch(`check_status.php?transaction_id=${transactionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'PAID') {
                window.location.href = `sucesso.php?tid=${transactionId}`;
            }
        })
        .catch(error => console.error('Erro:', error));
}

// Se existir transação, iniciar verificação
<?php if (isset($payment_data['transactionId'])): ?>
    // Verificar a cada 5 segundos
    const intervalId = setInterval(() => {
        checkPaymentStatus('<?php echo $payment_data['transactionId']; ?>');
    }, 5000);

    // Parar de verificar após 30 minutos
    setTimeout(() => {
        clearInterval(intervalId);
    }, 30 * 60 * 1000);
<?php endif; ?>
</script>
</body>
</html>