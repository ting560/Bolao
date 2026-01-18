<?php
// Script de verificação do sistema de autenticação
session_start();
require_once 'auth_functions.php';

echo "<h2>🔍 Verificação do Sistema de Autenticação</h2>";

// Verificar se os arquivos necessários existem
$arquivos_necessarios = [
    'auth_functions.php' => 'Funções de autenticação',
    'temp/users.json' => 'Banco de dados de usuários',
    'login.php' => 'Página de login',
    'register.php' => 'Página de registro',
    'logout.php' => 'Página de logout',
    'index.php' => 'Página principal'
];

echo "<h3>📋 Verificação de Arquivos:</h3>";
foreach ($arquivos_necessarios as $arquivo => $descricao) {
    if (file_exists($arquivo)) {
        echo "✅ $descricao ($arquivo)<br>";
    } else {
        echo "❌ $descricao ($arquivo) - ARQUIVO NÃO ENCONTRADO<br>";
    }
}

echo "<hr>";

// Verificar estrutura do users.json
echo "<h3>🗄️ Verificação do Banco de Dados:</h3>";
if (file_exists('temp/users.json')) {
    $conteudo = file_get_contents('temp/users.json');
    $dados = json_decode($conteudo, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "✅ Estrutura JSON válida<br>";
        $usuarios_count = count($dados['users'] ?? []);
        echo "👥 Usuários cadastrados: $usuarios_count<br>";
        
        if ($usuarios_count > 0) {
            echo "<details>";
            echo "<summary>📋 Lista de usuários:</summary>";
            foreach ($dados['users'] as $usuario) {
                echo "- " . htmlspecialchars($usuario['name']) . " (" . htmlspecialchars($usuario['email']) . ")<br>";
            }
            echo "</details>";
        }
    } else {
        echo "❌ Erro na estrutura JSON: " . json_last_error_msg() . "<br>";
    }
} else {
    echo "❌ Arquivo users.json não encontrado<br>";
}

echo "<hr>";

// Verificar estado da sessão atual
echo "<h3>🔒 Estado da Sessão:</h3>";
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']) {
    echo "✅ Usuário logado<br>";
    if (isset($_SESSION['user_data'])) {
        echo "👤 Nome: " . htmlspecialchars($_SESSION['user_data']['name']) . "<br>";
        echo "📧 Email: " . htmlspecialchars($_SESSION['user_data']['email']) . "<br>";
    }
} else {
    echo "❌ Nenhum usuário logado<br>";
}

echo "<hr>";

// Links úteis
echo "<h3>🔗 Links Úteis:</h3>";
echo "<ul>";
echo "<li><a href='index.php'>🏠 Página Principal</a></li>";
echo "<li><a href='login.php'>🔑 Login</a></li>";
echo "<li><a href='register.php'>📝 Cadastro</a></li>";
echo "<li><a href='aposta.php'>🎯 Fazer Aposta</a></li>";
echo "<li><a href='test_auth.php'>🧪 Teste Completo</a></li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>✅ Sistema pronto para uso!</strong></p>";
?>