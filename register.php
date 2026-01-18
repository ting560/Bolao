<?php
session_start();
require_once 'auth_functions.php';

// Redireciona se já estiver logado
redirectIfLoggedIn();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validações
    if (empty($name)) {
        $message = 'Por favor, informe seu nome';
        $messageType = 'error';
    } elseif (empty($phone)) {
        $message = 'Por favor, informe seu telefone';
        $messageType = 'error';
    } elseif (empty($email)) {
        $message = 'Por favor, informe seu email';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Email inválido';
        $messageType = 'error';
    } elseif (empty($password)) {
        $message = 'Por favor, informe uma senha';
        $messageType = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'A senha deve ter pelo menos 6 caracteres';
        $messageType = 'error';
    } elseif ($password !== $confirmPassword) {
        $message = 'As senhas não conferem';
        $messageType = 'error';
    } else {
        // Tenta registrar o usuário
        $result = registerUserExtended($name, $phone, $email, $password);
        
        if ($result['success']) {
            $message = $result['message'];
            $messageType = 'success';
            
            // Login automático após registro
            loginUser($result['user']);
            header('Location: index.php?registered=1');
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
    <title>Cadastro - Bolão entre Amigos</title>
    <link rel="stylesheet" href="estilo.css?v=<?php echo time(); ?>">
    <style>
        .auth-container {
            max-width: 500px;
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
        .btn-register {
            width: 100%;
            padding: 12px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-register:hover {
            background: #219653;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #7f8c8d;
        }
        .login-link a {
            color: #3498db;
            text-decoration: none;
        }
        .login-link a:hover {
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
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Voltar para o início</a>
        
        <div class="auth-container">
            <div class="auth-header">
                <h1>🎯 Cadastro de Apostador</h1>
                <p>Crie sua conta para participar do bolão</p>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Nome Completo *</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                           placeholder="Digite seu nome completo">
                </div>
                
                <div class="form-group">
                    <label for="phone">Telefone *</label>
                    <input type="tel" id="phone" name="phone" required 
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                           placeholder="(00) 00000-0000">
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           placeholder="seu@email.com">
                </div>
                
                <div class="form-group">
                    <label for="password">Senha *</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Mínimo 6 caracteres">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar Senha *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Confirme sua senha">
                </div>
                
                <button type="submit" class="btn-register">Criar Conta</button>
            </form>
            
            <div class="login-link">
                Já tem conta? <a href="login.php">Faça login aqui</a>
            </div>
        </div>
    </div>
</body>
</html>