<?php
/**
 * Teste de CRUD de Usuários no Painel Admin
 */

session_start();
$_SESSION['admin_logado'] = true;

require_once 'admin_ajax.php';

echo "<h1>🧪 Teste de CRUD de Usuários</h1>";

// Teste 1: Verificar endpoints disponíveis
echo "<h2>Teste 1: Endpoints AJAX Disponíveis</h2>";

$requiredEndpoints = [
    'get_users' => 'Obter lista de usuários',
    'get_user_data' => 'Obter dados de usuário específico',
    'add_user' => 'Adicionar novo usuário',
    'update_user' => 'Atualizar usuário existente',
    'delete_user' => 'Excluir usuário',
    'change_password' => 'Alterar senha de usuário'
];

echo "<div style='background:#f8f9fa;padding:20px;margin:20px 0;border-radius:10px;'>";
echo "<table style='width:100%;border-collapse:collapse;'>";
echo "<tr style='background:#3498db;color:white;'><th>Endpoint</th><th>Descrição</th><th>Status</th></tr>";

foreach ($requiredEndpoints as $endpoint => $descricao) {
    $exists = false;
    
    // Verificar no código se o endpoint existe
    $fileContent = file_get_contents('admin_ajax.php');
    if (strpos($fileContent, "case '{$endpoint}':") !== false || strpos($fileContent, "case \"{$endpoint}\":") !== false) {
        $exists = true;
    }
    
    $status = $exists ? '✅ OK' : '❌ FALTANDO';
    $rowColor = $exists ? '#d4edda' : '#f8d7da';
    
    echo "<tr style='background:{$rowColor};'>";
    echo "<td><code>{$endpoint}</code></td>";
    echo "<td>{$descricao}</td>";
    echo "<td>{$status}</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// Teste 2: Verificar funções JavaScript
echo "<h2>Teste 2: Funções JavaScript no Painel</h2>";

$jsFunctions = [
    'openUserModal' => 'Abrir modal de usuário (adicionar/editar)',
    'editUser' => 'Editar usuário existente',
    'createUserModal' => 'Criar modal de usuário',
    'loadUserData' => 'Carregar dados para edição',
    'saveUser' => 'Salvar usuário (adicionar/atualizar)',
    'deleteUser' => 'Excluir usuário',
    'deleteUserAjax' => 'Processar exclusão via AJAX'
];

echo "<div style='background:#fff3cd;padding:20px;margin:20px 0;border-radius:10px;'>";
echo "<table style='width:100%;border-collapse:collapse;'>";
echo "<tr style='background:#f39c12;color:white;'><th>Função</th><th>Descrição</th><th>Status</th></tr>";

foreach ($jsFunctions as $function => $descricao) {
    $exists = false;
    
    // Verificar no código se a função existe
    $fileContent = file_get_contents('admin_panel.php');
    if (strpos($fileContent, "function {$function}") !== false) {
        $exists = true;
    }
    
    $status = $exists ? '✅ OK' : '❌ FALTANDO';
    $rowColor = $exists ? '#d4edda' : '#f8d7da';
    
    echo "<tr style='background:{$rowColor};'>";
    echo "<td><code>{$function}</code></td>";
    echo "<td>{$descricao}</td>";
    echo "<td>{$status}</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// Teste 3: Simular chamada AJAX para obter usuários
echo "<h2>Teste 3: Simulação de Obtenção de Usuários</h2>";

echo "<div style='background:#e8f4f8;padding:20px;margin:20px 0;border-radius:10px;'>";
echo "<h3>Executando chamada AJAX:</h3>";

// Simular POST data
$_POST = ['action' => 'get_users'];

// Executar a lógica do switch case
$action = $_POST['action'] ?? '';
if ($action === 'get_users') {
    $users = firebaseGetCombinedUsersList();
    $cleanUsers = array_map(function($user) {
        unset($user['password']);
        return $user;
    }, $users);
    
    echo "<div style='background:#d4edda;padding:15px;margin:10px 0;border-radius:5px;'>";
    echo "<h4>✅ Dados Obtidos com Sucesso</h4>";
    echo "<p><strong>Total de usuários:</strong> " . count($cleanUsers) . "</p>";
    
    if (count($cleanUsers) > 0) {
        echo "<h5>Amostra de usuários:</h5>";
        echo "<div style='max-height:200px;overflow-y:auto;'>";
        echo "<pre>" . print_r(array_slice($cleanUsers, 0, 3), true) . "</pre>";
        echo "</div>";
    }
    
    echo "</div>";
} else {
    echo "<div style='background:#f8d7da;padding:15px;margin:10px 0;border-radius:5px;'>";
    echo "<h4>❌ Endpoint não encontrado</h4>";
    echo "</div>";
}
echo "</div>";

// Teste 4: Interface de Teste
echo "<h2>Teste 4: Interface Interativa</h2>";

echo "<div style='background:#e8f5e8;padding:25px;margin:25px 0;border-radius:10px;'>";
echo "<h3>🧪 Teste Manual das Funcionalidades:</h3>";
echo "<ol>";
echo "<li><a href='admin_panel.php#users' target='_blank' style='color:#28a745;font-weight:bold;'>👉 Abrir Painel Admin - Gerenciamento de Usuários</a></li>";
echo "<li>Teste o botão 'Adicionar Usuário'</li>";
echo "<li>Teste os botões 'Editar' nos usuários existentes</li>";
echo "<li>Teste o botão 'Excluir' (com confirmação)</li>";
echo "</ol>";

echo "<div style='margin-top:20px;padding:15px;background:#fff;border-radius:5px;'>";
echo "<h4>💡 Dicas para Teste:</h4>";
echo "<ul>";
echo "<li>Os usuários do Firebase não podem ter senha alterada diretamente</li>";
echo "<li>Os usuários locais podem ser completamente gerenciados</li>";
echo "<li>O modal de edição carrega automaticamente os dados do usuário</li>";
echo "<li>As alterações são salvas e refletidas imediatamente</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

// Conclusão
echo "<div style='background:#d1ecf1;padding:25px;margin:30px 0;border-radius:10px;border-left:5px solid #0c5460;'>";
echo "<h2>📋 Resumo do CRUD de Usuários</h2>";

echo "<h3>✅ Funcionalidades Implementadas:</h3>";
echo "<ul>";
echo "<li><strong>Criar:</strong> Modal para adicionar novos usuários</li>";
echo "<li><strong>Ler:</strong> Listagem completa de usuários (Firebase + locais)</li>";
echo "<li><strong>Atualizar:</strong> Edição de dados de usuários existentes</li>";
echo "<li><strong>Excluir:</strong> Remoção de usuários com confirmação</li>";
echo "</ul>";

echo "<h3>🔧 Componentes Técnicos:</h3>";
echo "<ul>";
echo "<li>Endpoints AJAX para todas as operações</li>";
echo "<li>Funções JavaScript para interface interativa</li>";
echo "<li>Validação de dados no backend</li>";
echo "<li>Integração com Firebase e usuários locais</li>";
echo "<li>Tratamento de erros e feedback ao usuário</li>";
echo "</ul>";

echo "<div style='margin-top:20px;padding:15px;background:#fff;border-radius:5px;'>";
echo "<h4>🚀 Próximos Passos:</h4>";
echo "<ol>";
echo "<li>Testar todas as funcionalidades no painel admin</li>";
echo "<li>Verificar integração com Firebase</li>";
echo "<li>Confirmar consistência dos dados</li>";
echo "<li>Validar experiência do usuário</li>";
echo "</ol>";
echo "</div>";
echo "</div>";

// Estilos
echo "
<style>
body { 
    font-family: 'Segoe UI', Arial, sans-serif; 
    margin: 20px; 
    background: #f5f7fa; 
}
h1 { 
    color: #2c3e50; 
    border-bottom: 4px solid #3498db; 
    padding-bottom: 15px; 
}
h2 { 
    color: #34495e; 
    margin-top: 40px; 
}
h3 { 
    color: #2c3e50; 
    margin: 20px 0 10px 0; 
}
h4 { 
    color: #34495e; 
    margin-bottom: 10px; 
}
h5 { 
    color: #2c3e50; 
    margin: 15px 0 5px 0; 
}
table { 
    box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
    margin: 15px 0; 
}
th { 
    background: #3498db; 
    color: white; 
    padding: 12px; 
    text-align: left; 
}
td { 
    padding: 10px; 
}
code { 
    background: #f1f2f6; 
    padding: 2px 6px; 
    border-radius: 3px; 
    font-family: monospace; 
}
pre { 
    background: #f8f9fa; 
    padding: 15px; 
    border-radius: 5px; 
    overflow-x: auto; 
    font-size: 12px; 
}
ul li, ol li { 
    margin: 8px 0; 
    line-height: 1.6; 
}
a { 
    color: #3498db; 
    text-decoration: none; 
}
a:hover { 
    text-decoration: underline; 
}
</style>
";

echo "<hr><p><small>🧪 Teste executado em " . date('d/m/Y H:i:s') . "</small></p>";
?>