<!DOCTYPE html>
<html>
<head>
    <title>Teste de Webhook</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .response { padding: 15px; background: #f5f5f5; border-radius: 5px; margin-top: 20px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Teste de Webhook PIX</h1>
        
        <button onclick="testarWebhook()">Enviar Teste</button>
        
        <div id="response" class="response"></div>

        <script>
            function testarWebhook() {
                const payload = {
                    requestBody: {
                        transactionType: "RECEIVEPIX",
                        transactionId: "test_" + Date.now(),
                        external_id: "ext_" + Date.now(),
                        amount: 100.00,
                        paymentType: "PIX",
                        status: "PAID",
                        dateApproval: new Date().toISOString(),
                        creditParty: {
                            name: "Teste da Silva",
                            email: "teste@email.com",
                            taxId: "12345678900"
                        },
                        debitParty: {
                            bank: "BANCO TESTE",
                            taxId: "12345678000100"
                        }
                    }
                };

                fetch('/webhook.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('response').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    document.getElementById('response').innerHTML = '<pre>Erro: ' + error.message + '</pre>';
                });
            }
        </script>
    </div>
</body>
</html>