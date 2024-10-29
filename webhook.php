<?php
require_once 'config/database.php';
header('Content-Type: application/json');

function logWebhook($pdo, $transaction_id, $payload, $status) {
    $stmt = $pdo->prepare("
        INSERT INTO webhook_logs 
        (transaction_id, payload, status) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$transaction_id, $payload, $status]);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido. Use POST.', 405);
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['requestBody'])) {
        throw new Exception('Payload inválido', 400);
    }

    $requestBody = $data['requestBody'];
    logWebhook($pdo, $requestBody['transactionId'], $input, $requestBody['status']);

    $stmt = $pdo->prepare("
        UPDATE transactions 
        SET status = ?, 
            updated_at = CURRENT_TIMESTAMP 
        WHERE transaction_id = ?
    ");
    $stmt->execute([$requestBody['status'], $requestBody['transactionId']]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Transação não encontrada', 404);
    }

    if ($requestBody['status'] === 'PAID') {
        $stmt = $pdo->prepare("
            SELECT user_id, amount FROM transactions 
            WHERE transaction_id = ?
        ");
        $stmt->execute([$requestBody['transactionId']]);
        $transaction = $stmt->fetch();

        if ($transaction) {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET balance = balance + ? 
                WHERE id = ?
            ");
            $stmt->execute([$transaction['amount'], $transaction['user_id']]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Status atualizado com sucesso',
        'transaction_id' => $requestBody['transactionId'],
        'status' => $requestBody['status']
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>