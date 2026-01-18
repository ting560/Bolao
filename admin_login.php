<?php
/**
 * Página de Login Admin - Acesso Restrito
 * Email: marcos2026@gmail.com
 * Senha: Frenesi04
 */

session_start();

// Credenciais de acesso admin
$email_admin = 'marcos2026@gmail.com';
$senha_correta_admin = 'Frenesi04';

$admin_logado = false;
$erro_login = '';

// Processar login admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email_admin']) && isset($_POST['senha_admin'])) {
    $email_digitado = trim($_POST['email_admin']);
    $senha_digitada = $_POST['senha_admin'];
    
    // Verificar credenciais
    if ($email_digitado === $email_admin && $senha_digitada === $senha_correta_admin) {
        $_SESSION['admin_logado'] = true;
        $_SESSION['admin_email'] = $email_admin;
        $admin_logado = true;
        
        // Redirecionar para painel admin
        header('Location: admin_panel.php');
        exit;
    } else {
        $erro_login = 'Email ou senha incorretos!';
    }
}

// Verificar sessão admin
if (isset($_SESSION['admin_logado']) && $_SESSION['admin_logado'] === true) {
    $admin_logado = true;
    // Redirecionar se já estiver logado
    header('Location: admin_panel.php');
    exit;
}

// Processar logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    unset($_SESSION['admin_logado']);
    unset($_SESSION['admin_email']);
    header('Location: admin_login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Bolão</title>
    <link rel="stylesheet" href="estilo.css?v=<?php echo time(); ?>">
    <style>
        .admin-login-container {
            max-width: 450px;
            margin: 100px auto;
            padding: 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.1);
            text-align: center;
            animation: slideIn 0.5s ease-out;
        }
        
        .admin-logo {
            font-size: 3.5em;
            margin-bottom: 20px;
            color: #667eea;
        }
        
        .admin-login-container h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2em;
        }
        
        .admin-login-container p {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        
        .login-form {
            text-align: left;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #fcc;
            display: <?php echo $erro_login ? 'block' : 'none'; ?>;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-logo">🔒</div>
        <h1>Área Administrativa</h1>
        <p>Acesso restrito - Apenas administradores autorizados</p>
        
        <?php if ($erro_login): ?>
            <div class="error-message">
                ❌ <?php echo htmlspecialchars($erro_login); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="email_admin">📧 Email:</label>
                <input type="email" id="email_admin" name="email_admin" required 
                       placeholder="marcos2026@gmail.com" value="<?php echo htmlspecialchars($_POST['email_admin'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="senha_admin">🔑 Senha:</label>
                <input type="password" id="senha_admin" name="senha_admin" required 
                       placeholder="Digite sua senha">
            </div>
            
            <button type="submit" class="btn-login">🔐 Acessar Painel</button>
        </form>
        
        <a href="index.php" class="back-link">← Voltar para página inicial</a>
    </div>
</body>
</html>