<?php
/**
 * Diagnóstico de Usuários no Firebase
 * Arquivo: debug_users_firebase.php
 */

// Habilitar erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$_SESSION['admin_logado'] = true;

require_once 'firebase_admin_functions.php';
require_once 'admin_user_functions.php';

echo "<h1>🔍 Diagnóstico de Usuários no Firebase</h1>";

// Teste 1: Verificar conexão com Firebase
echo "<h2>Teste 1: Conexão com Firebase</h2>";
$data = firebaseGetData('apostas');
if ($data) {
    echo "<div style='background:#d4edda;padding:10px;margin:10px 0;border-radius:5px;'>";
    echo "✅ Conexão bem-sucedida com Firebase<br>";
    echo "Estrutura encontrada: " . print_r(array_keys($data), true);
    echo "</div>";
} else {
    echo "<div style='background:#f8d7da;padding:10px;margin:10px 0;border-radius:5px;'>";
    echo "❌ Falha na conexão com Firebase";
    echo "</div>";
    exit;
}

// Teste 2: Listar todas as rodadas
echo "<h2>Teste 2: Rodadas Encontradas</h2>";
$rounds = firebaseGetAllRounds();
echo "<p>Total de rodadas: " . count($rounds) . "</p>";

foreach ($rounds as $round) {
    $userCount = count($round['bets']);
    echo "<div style='background:#e2e3e5;padding:10px;margin:5px 0;border-radius:5px;'>";
    echo "<strong>Rodada:</strong> {$round['name']}<br>";
    echo "<strong>Usuários:</strong> {$userCount}<br>";
    echo "<strong>Detalhes:</strong> " . print_r(array_keys($round['bets']), true);
    echo "</div>";
}

// Teste 3: Usuários únicos do Firebase
echo "<h2>Teste 3: Usuários Únicos do Firebase</h2>";
$firebaseUsers = firebaseGetAllUniqueUsers();
echo "<p>Total de usuários únicos no Firebase: " . count($firebaseUsers) . "</p>";

if (!empty($firebaseUsers)) {
    echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
    echo "<tr><th>Nome</th><th>Primeira Atividade</th><th>Última Atividade</th><th>Total de Apostas</th><th>Rodadas</th></tr>";
    
    foreach ($firebaseUsers as $user) {
        $firstSeen = $user['first_seen'] ? date('d/m/Y H:i:s', $user['first_seen']) : 'N/A';
        $lastActivity = $user['last_activity'] ? date('d/m/Y H:i:s', $user['last_activity']) : 'N/A';
        $roundsCount = count($user['rounds_participated']);
        
        echo "<tr>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$firstSeen}</td>";
        echo "<td>{$lastActivity}</td>";
        echo "<td>{$user['total_bets']}</td>";
        echo "<td>{$roundsCount}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='background:#fff3cd;padding:15px;margin:10px 0;border-radius:5px;'>";
    echo "⚠️ Nenhum usuário encontrado no Firebase";
    echo "</div>";
}

// Teste 4: Usuários locais
echo "<h2>Teste 4: Usuários Locais</h2>";
$localUsers = loadUsers();
echo "<p>Total de usuários locais: " . count($localUsers) . "</p>";

if (!empty($localUsers)) {
    echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
    echo "<tr><th>Nome</th><th>Email</th><th>Telefone</th><th>Criado em</th></tr>";
    
    foreach ($localUsers as $user) {
        $createdAt = $user['created_at'] ?? 'N/A';
        echo "<tr>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['phone']}</td>";
        echo "<td>{$createdAt}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Teste 5: Usuários combinados (como aparecem no painel)
echo "<h2>Teste 5: Usuários Combinados (Painel Admin)</h2>";
$combinedUsers = firebaseGetCombinedUsersList();
echo "<p>Total de usuários combinados: " . count($combinedUsers) . "</p>";

if (!empty($combinedUsers)) {
    echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
    echo "<tr><th>Nome</th><th>Origem</th><th>Total de Apostas</th><th>Rodadas Participadas</th></tr>";
    
    foreach ($combinedUsers as $user) {
        $source = $user['source'] ?? 'desconhecida';
        $totalBets = $user['total_bets'] ?? 0;
        $roundsParticipated = $user['rounds_participated'] ?? 0;
        
        echo "<tr>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$source}</td>";
        echo "<td>{$totalBets}</td>";
        echo "<td>{$roundsParticipated}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='background:#f8d7da;padding:15px;margin:10px 0;border-radius:5px;'>";
    echo "❌ Nenhum usuário combinado encontrado";
    echo "</div>";
}

// Teste 6: Verificar chamada AJAX
echo "<h2>Teste 6: Simulação da Chamada AJAX</h2>";
echo "<form method='POST' action='admin_ajax.php'>";
echo "<input type='hidden' name='action' value='get_users'>";
echo "<button type='submit' style='background:#007bff;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;'>";
echo "📤 Executar get_users (AJAX)";
echo "</button>";
echo "</form>";

// Estilos
echo "
<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
h2 { color: #34495e; margin-top: 30px; }
table { margin: 15px 0; background: white; }
th { background: #3498db; color: white; padding: 10px; }
td { padding: 8px; border: 1px solid #ddd; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>
";

echo "<hr><p><small>🔍 Diagnóstico executado em " . date('d/m/Y H:i:s') . "</small></p>";
?>