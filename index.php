<?php
// Ativar exibição de erros PHP para depuração (REMOVER EM PRODUÇÃO)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inicia a sessão
session_start();

// Inclui o arquivo de configuração e funções essenciais.
// ESTA LINHA É CRUCIAL E DEVE VIR ANTES DE QUALQUER CHAMADA A FUNÇÕES DEFINIDAS EM CONFIG.PHP
require_once 'configs/config.php';
require_once 'auth_functions.php';

// Configura cabeçalhos HTTP para evitar cache no navegador
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Obtém os dados do placar, forçando atualização se o parâmetro 'update=1' estiver na URL
$dadosPlacar = obterDadosPlacar(isset($_GET['update']) && $_GET['update'] == '1');
$rodadaAtual = $dadosPlacar['rodada'] ?? 'Rodada Indisponível';

// Verifica se é uma requisição AJAX para retornar dados JSON
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || (isset($_GET['ajax']) && $_GET['ajax'] == '1')) {
    header('Content-Type: application/json');
    echo json_encode($dadosPlacar);
    exit;
}

// Prepara mensagem de sucesso após salvar um palpite (redirecionamento de aposta.php)
$mensagemSucesso = '';
$mensagemAviso = '';

if (isset($_GET['palpite_salvo']) && $_GET['palpite_salvo'] == '1') {
    $apostador = $_GET['apostador'] ?? 'Apostador';
    $rodada = $_GET['rodada'] ?? 'esta rodada';
    $mensagemSucesso = "Palpite de " . htmlspecialchars($apostador) . " para $rodada salvo com sucesso!";
}

if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $mensagemSucesso = "Cadastro realizado com sucesso! Você já está logado.";
}

if (isset($_GET['logged_out']) && $_GET['logged_out'] == '1') {
    $mensagemAviso = "Você foi desconectado com sucesso.";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bolão entre Amigos</title>
    <!-- Adiciona um timestamp ao final do href para forçar o recarregamento do CSS -->
    <link rel="stylesheet" href="estilo.css?v=<?php echo time(); ?>">
    <style>
        /* Estilos adicionais para melhorar o layout */
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .welcome-section h1 {
            margin: 0 0 15px 0;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .auth-buttons {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
            margin: 20px 0;
        }
        
        .auth-buttons .atualizar-link {
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .auth-buttons .atualizar-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .user-welcome {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .user-welcome strong {
            color: #ffeb3b;
            font-size: 1.2em;
        }
        
        .public-actions {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin: 25px 0;
            border-left: 5px solid #667eea;
        }
        
        .public-actions h3 {
            color: #495057;
            margin-top: 0;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .action-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .action-card i {
            font-size: 2.5em;
            margin-bottom: 15px;
            display: block;
        }
        
        .action-card h4 {
            margin: 0 0 10px 0;
            color: #495057;
        }
        
        .action-card p {
            color: #6c757d;
            margin: 0;
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            .welcome-section h1 {
                font-size: 2em;
            }
            
            .auth-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .auth-buttons .atualizar-link {
                width: 80%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Seção de Boas-vindas -->
    <div class="welcome-section">
        <h1>⚽ Bolão entre Amigos</h1>
        <p style="font-size: 1.2em; opacity: 0.9;">O lugar certo para seus palpites esportivos!</p>
    </div>
    
    <!-- Área de Autenticação -->
    <div class="auth-area">
        <?php if (isLoggedIn()): ?>
            <!-- Usuário Logado -->
            <div class="user-welcome">
                <h2 style="margin: 0 0 15px 0;">👋 Olá, <strong><?php echo htmlspecialchars(getCurrentUser()['name']); ?></strong>!</h2>
                <p>Você está logado e pronto para fazer suas apostas.</p>
            </div>
            
            <div class="auth-buttons">
                <a href="aposta.php" class="atualizar-link" style="background: #4CAF50; color: white;">🎯 Fazer Aposta</a>
                <a href="?update=1" class="atualizar-link" style="background: #2196F3; color: white;">🔄 Atualizar Dados</a>
                <a href="palpites.php" class="atualizar-link" style="background: #FF9800; color: white;">📊 Classificação</a>
                <a href="logout.php" class="atualizar-link" style="background: #f44336; color: white;">🚪 Sair</a>
            </div>
        <?php else: ?>
            <!-- Usuário Não Logado -->
            <div class="user-welcome">
                <h2 style="margin: 0 0 15px 0;">🌟 Participe do nosso bolão!</h2>
                <p>Faça login ou cadastre-se para começar a fazer suas apostas.</p>
            </div>
            
            <div class="auth-buttons">
                <a href="login.php" class="atualizar-link" style="background: #4CAF50; color: white;">🔑 Entrar</a>
                <a href="register.php" class="atualizar-link" style="background: #2196F3; color: white;">📝 Cadastrar</a>
                <a href="?update=1" class="atualizar-link" style="background: #FF9800; color: white;">🔄 Atualizar Dados</a>
                <a href="palpites.php" class="atualizar-link" style="background: #9C27B0; color: white;">📊 Classificação</a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Ações Públicas -->
    <div class="public-actions">
        <h3>📋 Ações Disponíveis</h3>
        <div class="action-grid">
            <div class="action-card">
                <div>🔄</div>
                <h4>Atualizar Dados</h4>
                <p>Mantenha as informações dos jogos sempre atualizadas</p>
                <a href="?update=1" class="atualizar-link" style="margin-top: 10px; display: inline-block;">Atualizar</a>
            </div>
            
            <div class="action-card">
                <div>📊</div>
                <h4>Classificação</h4>
                <p>Veja como estão os apostadores nesta rodada</p>
                <a href="palpites.php" class="atualizar-link" style="margin-top: 10px; display: inline-block;">Ver Ranking</a>
            </div>
            

        </div>
    </div>
     <div id="rodada-info" class="rodada-info"><?= htmlspecialchars($rodadaAtual) ?></div>
    
    <?php if ($mensagemSucesso): ?>
        <div class="message-box" style="background-color: #e7f3fe; border-color: #d0eaff; color: #0c5460;">
            <?= $mensagemSucesso ?>
        </div>
    <?php endif; ?>
    
    <?php if ($mensagemAviso): ?>
        <div class="message-box warning-message">
            <?= $mensagemAviso ?>
        </div>
    <?php endif; ?>
    <div id="main-error-box" class="message-box error-message" style="display: none;"></div>
    <div id="warning-box" class="message-box warning-message" style="display: none;"></div>
    <div id="loading-indicator" class="loading-indicator">Carregando jogos...</div>

    <ul id="jogos-lista" class="jogos-lista"></ul>
    <div id="cache-notice" class="cache-notice"></div>

    <div class="classificacao-apostadores">
        <h2>Classificação dos Apostadores</h2>
        <table class="classificacao-table">
            <thead>
                <tr>
                    <th>Pos</th>
                    <th>Apostador</th>
                    <th>Pontos</th>
                </tr>
            </thead>
            <tbody id="tabela-classificacao-body">
                <tr><td colspan="3">Carregando classificação...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Adiciona um timestamp ao final do src para forçar o recarregamento do JS -->
<script src="script.js?v=<?php echo time(); ?>"></script>
<script>
    const initialData = <?php echo json_encode($dadosPlacar); ?>;
    window.initialData = initialData;
    // Passa a URL do placeholder de logo para o JavaScript
    window.placeholderLogoUrl = '<?php echo PLACEHOLDER_LOGO_URL; ?>';
</script>
</body>
</html>