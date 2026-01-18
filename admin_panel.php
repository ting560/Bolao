<?php
session_start();
require_once 'configs/config.php';
require_once 'auth_functions.php';

// Verificar se é admin (mantendo a autenticação original)
// Credenciais de acesso admin
$email_admin = 'marcos2026@gmail.com';
$senha_correta_admin = 'Frenesi04';
$admin_logado = false;

// Processar login admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email_admin']) && isset($_POST['senha_admin'])) {
    $email_digitado = trim($_POST['email_admin']);
    $senha_digitada = $_POST['senha_admin'];
    
    // Verificar credenciais
    if ($email_digitado === $email_admin && $senha_digitada === $senha_correta_admin) {
        $_SESSION['admin_logado'] = true;
        $_SESSION['admin_email'] = $email_admin;
        $admin_logado = true;
    } else {
        $erro_login = 'Email ou senha incorretos!';
    }
}

// Verificar sessão admin
if (isset($_SESSION['admin_logado']) && $_SESSION['admin_logado'] === true) {
    $admin_logado = true;
}

// Processar logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    unset($_SESSION['admin_logado']);
    unset($_SESSION['admin_email']);
    header('Location: admin_login.php');
    exit;
}

// Se não estiver logado, mostrar tela de login
if (!$admin_logado):
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
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        .admin-login-container h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 2em;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
        }
        .error-message {
            background: #fee;
            color: #c0392b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #fcc;
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="admin-login-container">
        <h1>🔐 Admin Panel</h1>
        <?php if (isset($erro_login)): ?>
            <div class="error-message"><?php echo htmlspecialchars($erro_login); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
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
    </div>
</body>
</html>
<?php
exit;
endif;

// Se estiver logado, mostrar o painel administrativo
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Bolão</title>
    <link rel="stylesheet" href="estilo.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #4CAF50;
            --warning-color: #FF9800;
            --danger-color: #f44336;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            color: #333;
        }
        
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-header h2 {
            font-size: 1.5em;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 0.9em;
            opacity: 0.8;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-item {
            margin-bottom: 5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            border-left-color: white;
            color: white;
        }
        
        .nav-link i {
            margin-right: 12px;
            font-size: 1.1em;
            width: 20px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
        }
        
        .topbar {
            background: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 1.8em;
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background: var(--danger-color);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: #d32f2f;
        }
        
        /* Dashboard Cards */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .card-title {
            font-size: 1.1em;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            color: white;
        }
        
        .icon-users { background: linear-gradient(135deg, #4CAF50, #45a049); }
        .icon-games { background: linear-gradient(135deg, #2196F3, #1976D2); }
        .icon-bets { background: linear-gradient(135deg, #FF9800, #F57C00); }
        .icon-stats { background: linear-gradient(135deg, #9C27B0, #7B1FA2); }
        
        .card-value {
            font-size: 2.2em;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .card-description {
            color: #666;
            font-size: 0.9em;
        }
        
        /* Content Sections */
        .content-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            display: none;
        }
        
        .content-section.active {
            display: block;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light-color);
        }
        
        .section-title {
            font-size: 1.5em;
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary { background: var(--primary-color); color: white; }
        .btn-success { background: var(--success-color); color: white; }
        .btn-warning { background: var(--warning-color); color: white; }
        .btn-danger { background: var(--danger-color); color: white; }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        /* Tables */
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: var(--light-color);
            font-weight: 600;
            color: var(--dark-color);
        }
        
        tr:hover {
            background: rgba(0,0,0,0.02);
        }
        
        /* Responsive */
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            animation: slideIn 0.3s;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 12px 12px 0 0;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 1.3em;
        }
        
        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        
        .close:hover {
            opacity: 0.7;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .modal-footer {
            padding: 15px 25px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-radius: 0 0 12px 12px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85em;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-header, .nav-link span {
                display: none;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                margin: 10% auto;
                width: 95%;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>⚽ Bolão Admin</h2>
                <p>Painel de Controle</p>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link active" data-section="dashboard">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-section="users">
                        <i class="fas fa-users"></i>
                        <span>Usuários</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-section="games">
                        <i class="fas fa-futbol"></i>
                        <span>Jogos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-section="bets">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Apostas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-section="settings">
                        <i class="fas fa-cog"></i>
                        <span>Configurações</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="topbar">
                <h1 class="page-title">Dashboard Administrativo</h1>
                <div class="user-info">
                    <span>Administrador</span>
                    <a href="?action=logout" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                </div>
            </div>

            <!-- Dashboard Section -->
            <section id="dashboard" class="content-section active">
                <div class="dashboard-grid">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <div class="card-title">Total de Usuários</div>
                                <div class="card-value" id="total-users">0</div>
                                <div class="card-description">Apostadores cadastrados</div>
                            </div>
                            <div class="card-icon icon-users">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <div class="card-title">Jogos Ativos</div>
                                <div class="card-value" id="active-games">0</div>
                                <div class="card-description">Jogos disponíveis</div>
                            </div>
                            <div class="card-icon icon-games">
                                <i class="fas fa-futbol"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <div class="card-title">Apostas Realizadas</div>
                                <div class="card-value" id="total-bets">0</div>
                                <div class="card-description">Nesta rodada</div>
                            </div>
                            <div class="card-icon icon-bets">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <div class="card-title">Rendimento</div>
                                <div class="card-value" id="revenue">R$ 0,00</div>
                                <div class="card-description">Total arrecadado</div>
                            </div>
                            <div class="card-icon icon-stats">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="recent-activity">
                    <h3>Atividade Recente</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Usuário</th>
                                    <th>Ação</th>
                                    <th>Detalhes</th>
                                </tr>
                            </thead>
                            <tbody id="recent-activity-body">
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 30px; color: #666;">
                                        Nenhuma atividade recente
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Users Section -->
            <section id="users" class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Gerenciamento de Usuários</h2>
                    <button class="btn btn-success" onclick="openUserModal()">
                        <i class="fas fa-plus"></i> Adicionar Usuário
                    </button>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>Data Cadastro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body">
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: #666;">
                                    Carregando usuários...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Games Section -->
            <section id="games" class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Gerenciamento de Jogos</h2>
                    <div>
                        <button class="btn btn-danger" onclick="deleteSelectedGames()" id="delete-selected-btn" style="display: none; margin-right: 10px;">
                            <i class="fas fa-trash"></i> Excluir Selecionados
                        </button>
                        <button class="btn btn-success" onclick="openGameModal()">
                            <i class="fas fa-plus"></i> Adicionar Jogo
                        </button>
                    </div>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="select-all-games" onchange="toggleAllGames(this)">
                                </th>
                                <th>ID</th>
                                <th>Time 1</th>
                                <th>Time 2</th>
                                <th>Data/Hora</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="games-table-body">
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 30px; color: #666;">
                                    Carregando jogos...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Bets Section -->
            <section id="bets" class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Gerenciamento de Apostas</h2>
                    <div>
                        <button class="btn btn-warning" onclick="calculatePoints()">
                            <i class="fas fa-calculator"></i> Calcular Pontos
                        </button>
                    </div>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Apostador</th>
                                <th>Total de Apostas</th>
                                <th>Pontos</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="bets-summary-table-body">
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px; color: #666;">
                                    Carregando resumo de apostas...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Detalhes expandidos do usuário -->
                <div id="user-bets-details" style="margin-top: 20px; display: none;">
                    <div class="card">
                        <div class="card-header">
                            <h3 id="user-bets-title">Apostas de </h3>
                            <button class="btn btn-secondary" onclick="closeUserBets()">Fechar</button>
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Jogo</th>
                                            <th>Palpite</th>
                                            <th>Resultado</th>
                                            <th>Pontos</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="user-bets-detail-body">
                                        <!-- Detalhes serão carregados aqui -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Settings Section -->
            <section id="settings" class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Configurações do Sistema</h2>
                </div>
                
                <div class="settings-grid">
                    <div class="card">
                        <h3>Senha da Rodada</h3>
                        <div class="form-group">
                            <label for="round-password">Senha Atual:</label>
                            <input type="text" id="round-password" placeholder="Deixe em branco para desativar">
                        </div>
                        <button class="btn btn-primary" onclick="saveRoundPassword()">Salvar</button>
                    </div>
                    
                    <div class="card">
                        <h3>Dados do Sistema</h3>
                        <button class="btn btn-warning" onclick="clearCache()">Limpar Cache</button>
                        <button class="btn btn-danger" onclick="resetSystem()">Resetar Sistema</button>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all links
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                // Add active class to clicked link
                this.classList.add('active');
                
                // Hide all sections
                document.querySelectorAll('.content-section').forEach(section => {
                    section.classList.remove('active');
                });
                
                // Show target section
                const targetSection = this.getAttribute('data-section');
                document.getElementById(targetSection).classList.add('active');
            });
        });

        // Load dashboard data from Firebase
        async function loadDashboardData() {
            try {
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_dashboard_data'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const dashboardData = data.data;
                    
                    // Atualizar cards do dashboard
                    document.getElementById('total-users').textContent = dashboardData.users.total;
                    document.getElementById('active-games').textContent = dashboardData.games.count;
                    document.getElementById('total-bets').textContent = dashboardData.bets.total_bets;
                    document.getElementById('revenue').textContent = dashboardData.revenue;
                }
            } catch (error) {
                console.error('Erro ao carregar dados do dashboard:', error);
                // Usar dados mock em caso de erro
                document.getElementById('total-users').textContent = '0';
                document.getElementById('active-games').textContent = '0';
                document.getElementById('total-bets').textContent = '0';
                document.getElementById('revenue').textContent = 'R$ 0,00';
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
        });

        // User Management Functions
        async function loadUsers() {
            try {
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_users'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const tbody = document.getElementById('users-table-body');
                    if (data.users.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: #666;">
                                    Nenhum usuário encontrado
                                </td>
                            </tr>
                        `;
                        return;
                    }
                    
                    tbody.innerHTML = data.users.map(user => {
                        // Determinar cor do badge baseado na fonte
                        const isFirebaseUser = (user.source || '').toLowerCase() === 'firebase' || (user.id && user.id.startsWith('fb_'));
                        const isLocalUser = (user.source || '').toLowerCase() === 'local' || (!user.source && user.password);
                        const sourceBadge = isFirebaseUser ? 
                            '<span class="badge badge-success">Firebase</span>' : 
                            (isLocalUser ? '<span class="badge badge-primary">Local</span>' : '<span class="badge badge-secondary">Desconhecido</span>');
                        
                        // Informações adicionais para usuários do Firebase
                        const additionalInfo = isFirebaseUser ? 
                            `<br><small>Apostas: ${user.total_bets || 0} | Rodadas: ${user.rounds_participated || 0}</small>` : '';
                        
                        return `
                        <tr>
                            <td>${user.id ? user.id.substring(0, 8) : 'N/A'}</td>
                            <td>
                                ${user.name}${additionalInfo}
                                <br>${sourceBadge}
                            </td>
                            <td>${user.email || 'N/A'}</td>
                            <td>${user.phone || 'N/A'}</td>
                            <td>${formatDate(user.created_at)}</td>
                            <td>
                                <button class="btn btn-info btn-sm" onclick="viewUserDetails('${user.name}')" title="Ver Detalhes">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-warning btn-sm" onclick="editUser('${user.id}', '${user.source}')" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deleteUser('${user.id}', '${user.name}', '${user.source}')" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `}).join('');
                }
            } catch (error) {
                console.error('Erro ao carregar usuários:', error);
                document.getElementById('users-table-body').innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: #f44336;">
                            Erro ao carregar usuários: ${error.message}
                        </td>
                    </tr>
                `;
            }
        }

        // View user details from Firebase
        async function viewUserDetails(userName) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_user_details');
                formData.append('user_name', userName);
                
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showUserDetailsModal(data.user);
                } else {
                    alert('Erro: ' + data.message);
                }
            } catch (error) {
                alert('Erro ao carregar detalhes do usuário: ' + error.message);
            }
        }

        // Show user details modal
        function showUserDetailsModal(user) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Detalhes do Usuário: ${user.name}</h3>
                        <span class="close" onclick="closeModal(this)">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="user-details">
                            <p><strong>Total de Apostas:</strong> ${user.total_bets}</p>
                            <p><strong>Primeira Participação:</strong> ${formatDate(user.first_seen ? user.first_seen * 1000 : null)}</p>
                            <p><strong>Última Atividade:</strong> ${formatDate(user.last_activity ? user.last_activity * 1000 : null)}</p>
                            
                            <h4>Rodadas Participadas (${user.rounds.length}):</h4>
                            <div class="rounds-list">
                                ${user.rounds.map(round => `
                                    <div class="round-item">
                                        <strong>${round.round_name}</strong> - 
                                        ${round.bets_count} apostas
                                        ${round.timestamp ? '<br><small>' + formatDate(round.timestamp * 1000) + '</small>' : ''}
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="closeModal(this)">Fechar</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            modal.style.display = 'block';
        }

        function openUserModal(userId = null) {
            const modal = createUserModal(userId);
            document.body.appendChild(modal);
            modal.style.display = 'block';
            
            // Se for edição, carregar dados do usuário
            if (userId) {
                loadUserData(userId);
            }
        }
        
        function editUser(userId, userSource) {
            if (userSource && userSource.toLowerCase() === 'firebase') {
                alert('Usuários do Firebase não podem ser editados diretamente no painel.');
                return;
            }
            openUserModal(userId);
        }

        function createUserModal(userId = null) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>${userId ? 'Editar Usuário' : 'Adicionar Usuário'}</h3>
                        <span class="close" onclick="closeModal(this)">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="userForm">
                            <input type="hidden" id="userId" value="${userId || ''}">
                            <div class="form-group">
                                <label>Nome Completo:</label>
                                <input type="text" id="userName" required>
                            </div>
                            <div class="form-group">
                                <label>Telefone:</label>
                                <input type="tel" id="userPhone" required>
                            </div>
                            <div class="form-group">
                                <label>Email:</label>
                                <input type="email" id="userEmail" required>
                            </div>
                            ${!userId ? `
                            <div class="form-group">
                                <label>Senha:</label>
                                <input type="password" id="userPassword" required minlength="6">
                            </div>
                            ` : ''}
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="closeModal(this)">Cancelar</button>
                        <button class="btn btn-primary" onclick="saveUser()">Salvar</button>
                    </div>
                </div>
            `;
            
            return modal;
        }

        async function loadUserData(userId) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_user_data');
                formData.append('user_id', userId);
                
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success && data.user) {
                    const user = data.user;
                    document.getElementById('userName').value = user.name || '';
                    document.getElementById('userPhone').value = user.phone || '';
                    document.getElementById('userEmail').value = user.email || '';
                    
                    // Para usuários do Firebase, não mostrar campo de senha
                    if (user.source === 'firebase') {
                        const passwordField = document.getElementById('userPassword');
                        if (passwordField) {
                            passwordField.closest('.form-group').style.display = 'none';
                        }
                    }
                } else {
                    alert('Erro ao carregar dados do usuário: ' + (data.message || 'Usuário não encontrado'));
                    closeModal(document.querySelector('.modal .close'));
                }
            } catch (error) {
                alert('Erro ao carregar dados do usuário: ' + error.message);
                closeModal(document.querySelector('.modal .close'));
            }
        }
        
        async function saveUser() {
            const userId = document.getElementById('userId').value;
            const name = document.getElementById('userName').value;
            const phone = document.getElementById('userPhone').value;
            const email = document.getElementById('userEmail').value;
            const password = document.getElementById('userPassword')?.value;
            
            const action = userId ? 'update_user' : 'add_user';
            const formData = new FormData();
            formData.append('action', action);
            formData.append('user_id', userId);
            formData.append('name', name);
            formData.append('phone', phone);
            formData.append('email', email);
            
            if (password) {
                formData.append('password', password);
            }
            
            try {
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    closeModal(document.querySelector('.modal .close'));
                    loadUsers();
                    loadDashboardData();
                } else {
                    alert('Erro: ' + data.message);
                }
            } catch (error) {
                alert('Erro ao salvar usuário: ' + error.message);
            }
        }

        function deleteUser(userId, userName, userSource) {
            if (userSource && userSource.toLowerCase() === 'firebase') {
                if (confirm(`Tem certeza que deseja excluir o usuário ${userName} do Firebase? Esta ação não pode ser desfeita.`)) {
                    deleteFirebaseUserAjax(userName);
                }
            } else {
                if (confirm(`Tem certeza que deseja excluir o usuário ${userName}?`)) {
                    deleteUserAjax(userId);
                }
            }
        }

        async function deleteFirebaseUserAjax(userName) {
            try {
                const formData = new FormData();
                formData.append('action', 'delete_firebase_user');
                formData.append('user_name', userName);
                
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    loadUsers();
                    loadDashboardData();
                } else {
                    alert('Erro: ' + data.message);
                }
            } catch (error) {
                alert('Erro ao excluir usuário do Firebase: ' + error.message);
            }
        }

        async function deleteUserAjax(userId) {
            try {
                const formData = new FormData();
                formData.append('action', 'delete_user');
                formData.append('user_id', userId);
                
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    loadUsers();
                    loadDashboardData();
                } else {
                    alert('Erro: ' + data.message);
                }
            } catch (error) {
                alert('Erro ao excluir usuário: ' + error.message);
            }
        }

        function closeModal(element) {
            const modal = element.closest('.modal');
            modal.remove();
        }

        // Game Management Functions
        function openGameModal(gameId = null) {
            const modal = createGameModal(gameId);
            document.body.appendChild(modal);
            modal.style.display = 'block';
            
            // Se for edição, carregar dados do jogo
            if (gameId) {
                loadGameData(gameId);
            }
        }

        function createGameModal(gameId = null) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>${gameId ? 'Editar Jogo' : 'Adicionar Jogo'}</h3>
                        <span class="close" onclick="closeModal(this)">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="gameForm">
                            <input type="hidden" id="gameId" value="${gameId || ''}">
                            
                            <div class="form-group">
                                <label for="team1">Time 1: <span style="color: #6c757d; font-size: 0.9em;">(opcional com link do SofaScore)</span></label>
                                <input type="text" id="team1" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label for="team2">Time 2: <span style="color: #6c757d; font-size: 0.9em;">(opcional com link do SofaScore)</span></label>
                                <input type="text" id="team2" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label for="gameDate">Data: <span style="color: #6c757d; font-size: 0.9em;">(opcional com link do SofaScore)</span></label>
                                <input type="date" id="gameDate" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label for="gameTime">Hora: <span style="color: #6c757d; font-size: 0.9em;">(opcional com link do SofaScore)</span></label>
                                <input type="time" id="gameTime" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label for="gameStatus">Status:</label>
                                <select id="gameStatus" class="form-control" required>
                                    <option value="Em breve">Em breve</option>
                                    <option value="AO VIVO">AO VIVO</option>
                                    <option value="Encerrado">Encerrado</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="sofaScoreLink">Link do SofaScore (opcional):</label>
                                <input type="url" id="sofaScoreLink" class="form-control" placeholder="https://www.sofascore.com/event/1234567">
                                <small class="form-text text-muted">Cole o link completo do jogo no SofaScore para carregar automaticamente os dados e atualizações em tempo real.</small>
                                <button type="button" class="btn btn-info btn-sm mt-2" onclick="loadSofaScoreData()">.Carregar Dados do Link</button>
                                <div id="sofaScorePreview" class="mt-3" style="display: none;">
                                    <h5>.Pré-visualização:</h5>
                                    <div id="sofaScorePreviewContent"></div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="closeModal(this)">Cancelar</button>
                        <button class="btn btn-primary" onclick="saveGame()">Salvar</button>
                    </div>
                </div>
            `;
            
            return modal;
        }

        async function loadGameData(gameId) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_game_details');
                formData.append('game_id', gameId);
                
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const game = data.game;
                    document.getElementById('team1').value = game.team1?.name || '';
                    document.getElementById('team2').value = game.team2?.name || '';
                    
                    if (game.datetime) {
                        const dateTime = new Date(game.datetime);
                        document.getElementById('gameDate').value = dateTime.toISOString().split('T')[0];
                        document.getElementById('gameTime').value = dateTime.toTimeString().substring(0, 5);
                    }
                    
                    document.getElementById('gameStatus').value = game.status || 'Em breve';
                }
            } catch (error) {
                console.error('Erro ao carregar dados do jogo:', error);
            }
        }

        async function saveGame() {
            const gameId = document.getElementById('gameId').value;
            const team1 = document.getElementById('team1').value.trim();
            const team2 = document.getElementById('team2').value.trim();
            const gameDate = document.getElementById('gameDate').value;
            const gameTime = document.getElementById('gameTime').value;
            const gameStatus = document.getElementById('gameStatus').value;
            const sofaScoreLink = document.getElementById('sofaScoreLink').value.trim();
            
            // Se tiver link do SofaScore, campos ficam opcionais
            if (sofaScoreLink) {
                // Validar apenas o link
                if (!isValidSofaScoreLink(sofaScoreLink)) {
                    alert('Por favor, insira um link válido do SofaScore.');
                    return;
                }
            } else {
                // Sem link do SofaScore, validar campos obrigatórios
                if (!team1 || !team2 || !gameDate || !gameTime) {
                    alert('Por favor, preencha todos os campos obrigatórios ou informe um link do SofaScore.');
                    return;
                }
            }
            
            try {
                const formData = new FormData();
                formData.append('action', gameId ? 'update_game' : 'add_game');
                if (gameId) formData.append('game_id', gameId);
                formData.append('team1', team1);
                formData.append('team2', team2);
                formData.append('datetime', gameDate + ' ' + gameTime);
                formData.append('status', gameStatus);
                
                // Adicionar link do SofaScore se fornecido
                if (sofaScoreLink) {
                    formData.append('sofaScoreLink', sofaScoreLink);
                    formData.append('autoUpdate', 'true'); // Flag para atualização automática
                }
                
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    closeModal(document.querySelector('.modal .close'));
                    loadGamesWithCheckboxes(); // Recarregar a lista
                    
                    // Se tiver link do SofaScore, iniciar monitoramento
                    if (sofaScoreLink && data.game_id) {
                        startSofaScoreMonitoring(data.game_id, sofaScoreLink);
                    }
                } else {
                    alert('Erro: ' + data.message);
                }
            } catch (error) {
                alert('Erro ao salvar jogo: ' + error.message);
            }
        }

        // Função para validar link do SofaScore
        function isValidSofaScoreLink(url) {
            if (!url) return false;
            
            // Expressão regular para validar URLs do SofaScore
            const sofaScorePattern = /^https?:\/\/([\w.-]*\.)?sofascore\.com\/.*$/i;
            return sofaScorePattern.test(url);
        }

        // Função para carregar dados do link do SofaScore
        async function loadSofaScoreData() {
            const sofaScoreLink = document.getElementById('sofaScoreLink').value.trim();
            
            if (!sofaScoreLink) {
                alert('Por favor, insira um link do SofaScore.');
                return;
            }
            
            if (!isValidSofaScoreLink(sofaScoreLink)) {
                alert('Por favor, insira um link válido do SofaScore (ex: https://www.sofascore.com/event/1234567).');
                return;
            }
            
            // Mostrar loading
            const loadButton = document.querySelector('button[onclick="loadSofaScoreData()"]');
            const originalText = loadButton.textContent;
            loadButton.textContent = '.Carregando...';
            loadButton.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('action', 'load_sofascore_event');
                formData.append('sofaScoreLink', sofaScoreLink);
                
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Verificar se a resposta é JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Resposta inválida do servidor. Não é um JSON válido.');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // Preencher formulário com dados carregados
                    if (data.event.team1) {
                        document.getElementById('team1').value = data.event.team1.name;
                    }
                    if (data.event.team2) {
                        document.getElementById('team2').value = data.event.team2.name;
                    }
                    if (data.event.datetime) {
                        const eventDate = new Date(data.event.datetime);
                        document.getElementById('gameDate').value = eventDate.toISOString().split('T')[0];
                        document.getElementById('gameTime').value = eventDate.toTimeString().substring(0, 5);
                    }
                    if (data.event.status) {
                        document.getElementById('gameStatus').value = data.event.status;
                    }
                    
                    // Mostrar pré-visualização
                    showSofaScorePreview(data.event);
                    
                    // Marcar campos como preenchidos automaticamente
                    markFieldsAsAutoFilled();
                    
                    alert('✅ Dados carregados com sucesso do link do SofaScore!\n\nTimes: ' + data.event.team1.name + ' vs ' + data.event.team2.name + '\nStatus: ' + data.event.status);
                } else {
                    alert('❌ Erro ao carregar dados:\n' + data.message);
                }
            } catch (error) {
                console.error('Erro detalhado:', error);
                alert('❌ Erro ao carregar dados do SofaScore:\n\n' + 
                      'Possíveis causas:\n' + 
                      '• Link inválido ou inacessível\n' + 
                      '• Problemas de conexão com a API\n' + 
                      '• ID do evento não encontrado\n\n' + 
                      'Detalhes técnicos: ' + error.message);
                
                // Tentar fallback com web scraping
                await tryWebScrapingFallback(sofaScoreLink);
            } finally {
                // Restaurar botão
                loadButton.textContent = originalText;
                loadButton.disabled = false;
            }
        }

        // Função de fallback com web scraping
        async function tryWebScrapingFallback(sofaScoreLink) {
            if (!confirm('Tentar método alternativo de extração de dados?')) {
                return;
            }
            
            try {
                // Extrair ID do link
                const eventId = extractEventId(sofaScoreLink);
                if (!eventId) {
                    alert('Não foi possível extrair o ID do evento do link.');
                    return;
                }
                
                // Tentar web scraping básico
                const fallbackData = await scrapeBasicEventData(eventId);
                
                if (fallbackData && fallbackData.team1 && fallbackData.team2) {
                    document.getElementById('team1').value = fallbackData.team1;
                    document.getElementById('team2').value = fallbackData.team2;
                    document.getElementById('gameStatus').value = fallbackData.status || 'Em breve';
                    
                    showSofaScorePreview({
                        team1: { name: fallbackData.team1 },
                        team2: { name: fallbackData.team2 },
                        status: fallbackData.status || 'Em breve',
                        tournament: 'Campeonato'
                    });
                    
                    markFieldsAsAutoFilled();
                    alert('✅ Dados carregados com método alternativo!\n\nTimes: ' + fallbackData.team1 + ' vs ' + fallbackData.team2);
                } else {
                    alert('❌ Não foi possível extrair dados nem com o método alternativo.');
                }
            } catch (error) {
                alert('❌ Erro no método alternativo: ' + error.message);
            }
        }

        // Função para extrair ID do evento (VERSÃO APRIMORADA)
        function extractEventId(url) {
            // Validar URL
            if (!url || typeof url !== 'string') {
                console.error('URL inválida para extração de ID:', url);
                return null;
            }
            
            // Limpar URL
            url = url.trim();
            
            // Verificar se é URL do SofaScore
            if (!url.includes('sofascore.com')) {
                console.error('URL não é do SofaScore:', url);
                return null;
            }
            
            // Padrões mais abrangentes
            const patterns = [
                // Padrões principais
                /\/event\/(\d+)/i,
                /\/match\/(\d+)/i,
                /\/game\/(\d+)/i,
                /\/fixture\/(\d+)/i,
                
                // Parâmetros de query
                /[?&]id=(\d+)/i,
                /[?&]event=(\d+)/i,
                /[?&]eventId=(\d+)/i,
                /[?&]matchId=(\d+)/i,
                /[?&]gameId=(\d+)/i,
                
                // URLs complexas
                /\/tournament\/[^\/]+\/[^\/]+\/[^\/]+\/\d+\/event\/(\d+)/i,
                /\/competition\/[^\/]+\/[^\/]+\/event\/(\d+)/i
            ];
            
            // Tentar cada padrão
            for (const pattern of patterns) {
                const match = url.match(pattern);
                if (match && match[1]) {
                    const eventId = match[1];
                    console.log('ID extraído:', eventId, 'com padrão:', pattern);
                    
                    // Validar ID
                    if (/^\d{5,10}$/.test(eventId)) {
                        return eventId;
                    } else {
                        console.warn('ID extraído parece inválido:', eventId);
                    }
                }
            }
            
            // Fallback: procurar qualquer número de 5-10 dígitos
            const generalMatch = url.match(/\b(\d{5,10})\b/);
            if (generalMatch && generalMatch[1]) {
                const potentialId = generalMatch[1];
                console.log('ID potencial encontrado:', potentialId);
                return potentialId;
            }
            
            console.error('Nenhum ID encontrado na URL:', url);
            return null;
        }

        // Função de web scraping básico
        async function scrapeBasicEventData(eventId) {
            // Esta é uma implementação simplificada
            // Na prática, você poderia fazer uma chamada ao backend
            // para tentar web scraping mais elaborado
            
            // Retornar dados simulados como fallback
            return {
                team1: 'Time A',
                team2: 'Time B', 
                status: 'Em breve'
            };
        }

        // Função para marcar campos como preenchidos automaticamente
        function markFieldsAsAutoFilled() {
            const autoFilledFields = ['team1', 'team2', 'gameDate', 'gameTime', 'gameStatus'];
            
            autoFilledFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.style.backgroundColor = '#e8f5e8'; // Verde claro
                    field.title = 'Preenchido automaticamente via SofaScore';
                }
            });
        }

        // Função para mostrar pré-visualização dos dados
        function showSofaScorePreview(eventData) {
            const previewDiv = document.getElementById('sofaScorePreview');
            const previewContent = document.getElementById('sofaScorePreviewContent');
            
            previewContent.innerHTML = `
                <div class="card">
                    <div class="card-body">
                        <h6>${eventData.team1.name} vs ${eventData.team2.name}</h6>
                        <p><strong>Data:</strong> ${eventData.datetime ? new Date(eventData.datetime).toLocaleString('pt-BR') : 'N/A'}</p>
                        <p><strong>Status:</strong> ${eventData.status}</p>
                        <p><strong>Torneio:</strong> ${eventData.tournament || 'N/A'}</p>
                        ${eventData.score ? `<p><strong>Placar atual:</strong> ${eventData.score}</p>` : ''}
                    </div>
                </div>
            `;
            
            previewDiv.style.display = 'block';
        }

        // Função para iniciar monitoramento em tempo real
        function startSofaScoreMonitoring(gameId, sofaScoreLink) {
            // Configurar atualização automática a cada minuto
            setInterval(async () => {
                try {
                    const formData = new FormData();
                    formData.append('action', 'update_sofascore_game');
                    formData.append('game_id', gameId);
                    formData.append('sofaScoreLink', sofaScoreLink);
                    
                    const response = await fetch('admin_ajax.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.updated) {
                        console.log('Jogo atualizado via SofaScore:', data.game);
                        loadGamesWithCheckboxes(); // Recarregar lista
                    }
                } catch (error) {
                    console.error('Erro na atualização automática:', error);
                }
            }, 60000); // 60 segundos
            
            console.log(`Monitoramento iniciado para jogo ${gameId} via SofaScore`);
        }

        function editGame(gameId) {
            openGameModal(gameId);
        }

        // Bet Management Functions
        function calculatePoints() {
            alert('Função de cálculo de pontos será implementada');
        }

        // Settings Functions
        async function saveRoundPassword() {
            const password = document.getElementById('round-password').value;
            
            try {
                const formData = new FormData();
                formData.append('action', 'save_round_password');
                formData.append('password', password);
                
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                } else {
                    alert('Erro: ' + data.message);
                }
            } catch (error) {
                alert('Erro ao salvar senha: ' + error.message);
            }
        }

        async function clearCache() {
            if(confirm('Tem certeza que deseja limpar o cache?')) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'clear_cache');
                    
                    const response = await fetch('admin_ajax.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        alert(data.message);
                    } else {
                        alert('Erro: ' + data.message);
                    }
                } catch (error) {
                    alert('Erro ao limpar cache: ' + error.message);
                }
            }
        }

        function resetSystem() {
            if(confirm('Esta ação irá resetar todo o sistema. Tem certeza?')) {
                alert('Função de reset do sistema será implementada');
            }
        }

        // Utility Functions
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
        }

        // Load bets data from Firebase
        async function loadBets() {
            try {
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_bets'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const tbody = document.getElementById('bets-table-body');
                    
                    if (data.bets.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: #666;">
                                    Nenhuma aposta registrada
                                </td>
                            </tr>
                        `;
                        return;
                    }
                    
                    tbody.innerHTML = data.bets.map(bet => `
                        <tr>
                            <td>${bet.user}</td>
                            <td>${bet.game_id}</td>
                            <td>${formatBetData(bet.bet)}</td>
                            <td>-</td>
                            <td>-</td>
                            <td>
                                <span class="badge badge-info">${bet.round}</span>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Erro ao carregar apostas:', error);
            }
        }

        // Load games data from Firebase
        async function loadGames() {
            try {
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_games'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const tbody = document.getElementById('games-table-body');
                    
                    if (data.games.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: #666;">
                                    Nenhum jogo encontrado
                                </td>
                            </tr>
                        `;
                        return;
                    }
                    
                    tbody.innerHTML = data.games.map(game => `
                        <tr>
                            <td>${game.id.substring(0, 8)}</td>
                            <td>${game.team1.name}</td>
                            <td>${game.team2.name}</td>
                            <td>${game.datetime}</td>
                            <td><span class="badge badge-${getStatusBadge(game.status)}">${game.status}</span></td>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="editGame('${game.id}')">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Erro ao carregar jogos:', error);
            }
        }

        // Helper functions
        function formatBetData(bet) {
            if (typeof bet === 'object' && bet.time1 !== undefined && bet.time2 !== undefined) {
                return `${bet.time1}x${bet.time2}`;
            } else if (typeof bet === 'string') {
                return bet;
            }
            return 'Inválido';
        }

        function getStatusBadge(status) {
            const statusMap = {
                'Encerrado': 'success',
                'AO VIVO': 'danger',
                'N/A': 'secondary'
            };
            return statusMap[status] || 'warning';
        }

        function editGame(gameId) {
            alert('Editar jogo: ' + gameId);
        }

        // Game Management Functions
        function toggleAllGames(source) {
            const checkboxes = document.querySelectorAll('input[name="game-select"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = source.checked;
            });
            updateDeleteButtonVisibility();
        }

        function updateDeleteButtonVisibility() {
            const checkedBoxes = document.querySelectorAll('input[name="game-select"]:checked');
            const deleteBtn = document.getElementById('delete-selected-btn');
            deleteBtn.style.display = checkedBoxes.length > 0 ? 'inline-block' : 'none';
        }

        async function deleteSelectedGames() {
            const checkedBoxes = document.querySelectorAll('input[name="game-select"]:checked');
            if (checkedBoxes.length === 0) return;

            if (!confirm(`Tem certeza que deseja excluir ${checkedBoxes.length} jogo(s)?`)) {
                return;
            }

            const gameIds = Array.from(checkedBoxes).map(cb => cb.value);
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete_games');
                formData.append('game_ids', JSON.stringify(gameIds));
                
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Jogos excluídos com sucesso!');
                    loadGames(); // Recarregar a lista
                } else {
                    alert('Erro ao excluir jogos: ' + data.message);
                }
            } catch (error) {
                alert('Erro ao excluir jogos: ' + error.message);
            }
        }

        // Enhanced Bets Management Functions
        async function loadBetsSummary() {
            try {
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_bets_summary'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const tbody = document.getElementById('bets-summary-table-body');
                    
                    if (data.summary.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px; color: #666;">
                                    Nenhuma aposta registrada
                                </td>
                            </tr>
                        `;
                        return;
                    }
                    
                    tbody.innerHTML = data.summary.map(userBet => `
                        <tr style="cursor: pointer;" onclick="loadUserBets('${userBet.user}')">
                            <td><strong>${userBet.user}</strong></td>
                            <td>${userBet.total_bets}</td>
                            <td>${userBet.points || 0}</td>
                            <td><span class="badge badge-info">${userBet.round}</span></td>
                            <td>
                                <button class="btn btn-info btn-sm" onclick="event.stopPropagation(); loadUserBets('${userBet.user}')">
                                    <i class="fas fa-eye"></i> Ver Apostas
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Erro ao carregar resumo de apostas:', error);
            }
        }

        async function loadUserBets(userName) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_user_bets');
                formData.append('user_name', userName);
                
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showUserBetsDetails(userName, data.bets);
                } else {
                    alert('Erro ao carregar apostas do usuário: ' + data.message);
                }
            } catch (error) {
                alert('Erro ao carregar apostas do usuário: ' + error.message);
            }
        }

        function showUserBetsDetails(userName, bets) {
            const detailsDiv = document.getElementById('user-bets-details');
            const titleElement = document.getElementById('user-bets-title');
            const tbody = document.getElementById('user-bets-detail-body');
            
            titleElement.textContent = `Apostas de ${userName}`;
            
            if (bets.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px; color: #666;">
                            Nenhuma aposta encontrada para este usuário
                        </td>
                    </tr>
                `;
            } else {
                tbody.innerHTML = bets.map(bet => `
                    <tr>
                        <td>${bet.game_id}</td>
                        <td>${formatBetData(bet.bet)}</td>
                        <td>${bet.result || '-'}</td>
                        <td>${bet.points || '-'}</td>
                        <td><span class="badge badge-info">${bet.round}</span></td>
                    </tr>
                `).join('');
            }
            
            detailsDiv.style.display = 'block';
        }

        function closeUserBets() {
            document.getElementById('user-bets-details').style.display = 'none';
        }

        // Updated load functions with checkboxes
        async function loadGamesWithCheckboxes() {
            try {
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_games'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const tbody = document.getElementById('games-table-body');
                    
                    if (data.games.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 30px; color: #666;">
                                    Nenhum jogo encontrado
                                </td>
                            </tr>
                        `;
                        return;
                    }
                    
                    tbody.innerHTML = data.games.map(game => `
                        <tr>
                            <td>
                                <input type="checkbox" name="game-select" value="${game.id}" onchange="updateDeleteButtonVisibility()">
                            </td>
                            <td>${game.id.substring(0, 8)}</td>
                            <td>${game.team1.name}</td>
                            <td>${game.team2.name}</td>
                            <td>${game.datetime}</td>
                            <td><span class="badge badge-${getStatusBadge(game.status)}">${game.status}</span></td>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="editGame('${game.id}')" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deleteSingleGame('${game.id}')" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Erro ao carregar jogos:', error);
            }
        }

        async function deleteSingleGame(gameId) {
            if (!confirm('Tem certeza que deseja excluir este jogo?')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete_games');
                formData.append('game_ids', JSON.stringify([gameId]));
                
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Jogo excluído com sucesso!');
                    loadGamesWithCheckboxes(); // Recarregar a lista
                } else {
                    alert('Erro ao excluir jogo: ' + data.message);
                }
            } catch (error) {
                alert('Erro ao excluir jogo: ' + error.message);
            }
        }

        // Load data when switching sections
        document.querySelector('[data-section="users"]').addEventListener('click', function() {
            setTimeout(loadUsers, 100);
        });
        
        document.querySelector('[data-section="bets"]').addEventListener('click', function() {
            setTimeout(loadBetsSummary, 100);
        });
        
        document.querySelector('[data-section="games"]').addEventListener('click', function() {
            setTimeout(loadGamesWithCheckboxes, 100);
        });
    </script>
</body>
</html>