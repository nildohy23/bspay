<?php
require_once 'config/database.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Não autorizado']));
}

// Configurar cabeçalhos
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

try {
    // Validar transaction_id
    $transaction_id = filter_input(INPUT_GET, 'transaction_id', FILTER_SANITIZE_STRING);
    
    if (empty($transaction_id)) {
        throw new Exception('Transaction ID não fornecido');
    }

    // Buscar transação
    $stmt = $pdo->prepare("
        SELECT 
            t.status,
            t.amount,
            t.created_at,
            t.updated_at,
            t.payer_name,
            t.external_id
        FROM transactions t
        WHERE t.transaction_id = ?
        AND t.user_id = ?
        LIMIT 1
    ");

    $stmt->execute([$transaction_id, $_SESSION['user_id']]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        throw new Exception('Transação não encontrada');
    }

    // Registrar verificação
    logAccess($_SESSION['user_id'], 'check_status', [
        'transaction_id' => $transaction_id,
        'status' => $transaction['status']
    ]);

    // Formatar resposta
    $response = [
        'status' => $transaction['status'],
        'amount' => floatval($transaction['amount']),
        'formatted_amount' => formatMoney($transaction['amount']),
        'created_at' => formatDate($transaction['created_at']),
        'updated_at' => formatDate($transaction['updated_at']),
        'payer_name' => $transaction['payer_name'],
        'external_id' => $transaction['external_id']
    ];

    // Se estiver pago, adicionar informações extras
    if ($transaction['status'] === 'PAID') {
        $response['message'] = 'Pagamento confirmado';
        $response['redirect'] = SITE_URL . '/sucesso?tid=' . $transaction_id;
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    
    logError('Erro ao verificar status', [
        'error' => $e->getMessage(),
        'transaction_id' => $transaction_id ?? null
    ]);

    echo json_encode([
        'error' => $e->getMessage(),
        'status' => 'ERROR'
    ]);
}