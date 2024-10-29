<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Carrega o arquivo de configuração do banco de dados
require_once __DIR__ . '/config/database.php';

// Remove a extensão .php da URL
$request = $_SERVER['REQUEST_URI'];
$request = rtrim($request, '/');
$request = strtok($request, '?'); // Remove query string se houver

// Roteamento básico
switch ($request) {
    // Página inicial
    case '':
    case '/':
        if (isset($_SESSION['user_id'])) {
            header("Location: /dashboard");
        } else {
            header("Location: /login");
        }
        exit;
        break;

    // Autenticação
    case '/login':
        if (isset($_SESSION['user_id'])) {
            header("Location: /dashboard");
            exit;
        }
        require __DIR__ . '/login.php';
        break;

    case '/register':
        if (isset($_SESSION['user_id'])) {
            header("Location: /dashboard");
            exit;
        }
        require __DIR__ . '/register.php';
        break;

    case '/logout':
        require __DIR__ . '/logout.php';
        break;

    // Dashboard e funcionalidades do usuário
    case '/dashboard':
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit;
        }
        require __DIR__ . '/dashboard.php';
        break;

    case '/recarg':
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit;
        }
        require __DIR__ . '/recarg.php';
        break;

    case '/gerar_qr':
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit;
        }
        require __DIR__ . '/gerar_qr.php';
        break;

    case '/check_status':
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit;
        }
        require __DIR__ . '/check_status.php';
        break;

    case '/sucesso':
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit;
        }
        require __DIR__ . '/sucesso.php';
        break;

    // Rotas de processamento
    case '/process_login':
        require __DIR__ . '/process_login.php';
        break;

    case '/process_register':
        require __DIR__ . '/process_register.php';
        break;

    // Rotas administrativas
// ... código anterior ...

// Rotas administrativas
case '/admin':
case '/admin/dashboard':
    requireAdmin(); // Usa a nova função
    require __DIR__ . '/admin/dashboard.php';
    break;

case '/admin/config':
    requireAdmin();
    require __DIR__ . '/admin/config.php';
    break;

case '/admin/gateway':
    requireAdmin();
    require __DIR__ . '/admin/gateway.php';
    break;

case '/admin/users':
    requireAdmin();
    require __DIR__ . '/admin/users.php';
    break;

case '/admin/transactions':
    requireAdmin();
    require __DIR__ . '/admin/transactions.php';
    break;

case '/admin/edit_user':
    requireAdmin();
    require __DIR__ . '/admin/edit_user.php';
    break;

case '/admin/delete_user':
    requireAdmin();
    require __DIR__ . '/admin/delete_user.php';
    break;

    // Webhook
    case '/webhook':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require __DIR__ . '/webhook/index.php';
        } else {
            require __DIR__ . '/webhook/docs.php';
        }
        break;

    // API endpoints
    case '/api/check_balance':
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        require __DIR__ . '/api/check_balance.php';
        break;

    case '/api/update_balance':
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        require __DIR__ . '/api/update_balance.php';
        break;

    // Página 404
// ... resto do código ...

// Página 404
default:
    http_response_code(404);
    try {
        $pageTitle = "404 - Página não encontrada";
        include __DIR__ . '/templates/header.php';
        
        echo '<div class="container mt-5">';
        echo '<div class="error-404 text-center">';
        echo '<h1>404</h1>';
        echo '<h2>Página não encontrada!</h2>';
        echo '<p>A página que você está procurando não existe ou foi movida.</p>';
        echo '<a href="/" class="btn btn-primary">Voltar para o início</a>';
        echo '</div>';
        echo '</div>';
        
        include __DIR__ . '/templates/footer.php';
    } catch (Exception $e) {
        debug_log("Erro ao renderizar 404", [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        echo "Erro ao carregar a página. Por favor, tente novamente mais tarde.";
    }
    break;
}

// Log de acesso (opcional)
if (isset($_SESSION['user_id'])) {
    logAccess($request, $_SESSION['user_id']);
}
?>

<style>
.error-404 {
    padding: 50px 0;
}

.error-404 h1 {
    font-size: 72px;
    color: #dc3545;
    margin: 0;
}

.error-404 h2 {
    font-size: 24px;
    color: #6c757d;
    margin: 20px 0;
}

.error-404 p {
    color: #6c757d;
    margin-bottom: 30px;
}

.error-404 .btn {
    padding: 10px 20px;
    background-color: var(--primary-color, #007bff);
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.error-404 .btn:hover {
    background-color: var(--primary-color-dark, #0056b3);
}
</style>