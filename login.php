<?php
session_start();
require_once 'auth_functions.php';

// Redireciona se já estiver logado
redirectIfLoggedIn();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $message = 'Por favor, preencha todos os campos';
        $messageType = 'error';
    } else {
        $result = authenticateUser($email, $password);
        
        if ($result['success']) {
            loginUser($result['user']);
            
            // Redireciona para a página anterior ou para o index
            $redirect = $_GET['redirect'] ?? 'index.php';
            header("Location: $redirect");
            exit;
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bolão entre Amigos</title>
    <link rel="stylesheet" href="estilo.css?v=<?php echo time(); ?>">
    <style>
        .auth-container {
            max-width: 450px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .auth-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .auth-header p {
            color: #7f8c8d;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2c3e50;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-login:hover {
            background: #2980b9;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #7f8c8d;
        }
        .register-link a {
            color: #3498db;
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .forgot-password {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }
        .forgot-password a {
            color: #7f8c8d;
            text-decoration: none;
        }
        .forgot-password a:hover {
            color: #3498db;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Voltar para o início</a>
        
        <div class="auth-container">
            <div class="auth-header">
                <h1>🎯 Login do Apostador</h1>
                <p>Acesse sua conta para fazer apostas</p>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           placeholder="seu@email.com">
                </div>
                
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Sua senha">
                </div>
                
                <button type="submit" class="btn-login">Entrar</button>
            </form>
            
            <div class="register-link">
                Não tem conta? <a href="register.php">Cadastre-se aqui</a>
            </div>
            
            <div class="forgot-password">
                <!-- Futuramente podemos adicionar recuperação de senha -->
                <a href="#">Esqueceu sua senha?</a>
            </div>
        </div>
    </div>
</body>
</html>