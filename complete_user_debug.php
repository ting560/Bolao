<?php
/**
 * Diagnóstico Completo do Sistema de Usuários e Firebase
 * Revisão profunda para identificar problemas com botões
 */

session_start();
$_SESSION['admin_logado'] = true;

require_once 'admin_ajax.php';

echo "<h1>🔍 Diagnóstico Completo - Sistema de Usuários e Firebase</h1>";

// Teste 1: Verificar estrutura do Firebase
echo "<h2>📋 Teste 1: Estrutura do Firebase</h2>";

echo "<div style='background:#f8f9fa;padding:20px;margin:20px 0;border-radius:10px;'>";

// Verificar se as funções Firebase estão disponíveis
if (function_exists('firebaseGetData')) {
    echo "<div style='background:#d4edda;padding:15px;margin:10px 0;border-radius:5px;'>";
    echo "✅ Funções Firebase disponíveis";
    echo "</div>";
    
    // Testar acesso ao nó de usuários
    $usersData = firebaseGetData('usuarios');
    echo "<h3>Dados brutos de /usuarios:</h3>";
    if ($usersData) {
        echo "<div style='background:#fff;padding:15px;border-radius:5px;margin:10px 0;'>";
        echo "<pre>" . print_r($usersData, true) . "</pre>";
        echo "</div>";
    } else {
        echo "<div style='background:#f8d7da;padding:15px;margin:10px 0;border-radius:5px;'>";
        echo "❌ Não foi possível acessar /usuarios";
        echo "</div>";
    }
    
    // Testar acesso ao nó de apostas para extrair usuários
    $betsData = firebaseGetData('apostas');
    echo "<h3>Dados brutos de /apostas:</h3>";
    if ($betsData) {
        echo "<div style='background:#fff;padding:15px;border-radius:5px;margin:10px 0;'>";
        echo "<pre>" . print_r(array_slice($betsData, 0, 2), true) . "</pre>"; // Mostrar apenas 2 primeiros
        echo "<p><strong>Total de rodadas:</strong> " . count($betsData) . "</p>";
        echo "</div>";
    } else {
        echo "<div style='background:#f8d7da;padding:15px;margin:10px 0;border-radius:5px;'>";
        echo "❌ Não foi possível acessar /apostas";
        echo "</div>";
    }
    
} else {
    echo "<div style='background:#f8d7da;padding:15px;margin:10px 0;border-radius:5px;'>";
    echo "❌ Funções Firebase NÃO disponíveis - verifique firebase_admin_functions.php";
    echo "</div>";
}

echo "</div>";

// Teste 2: Função firebaseGetCombinedUsersList
echo "<h2>📋 Teste 2: Função firebaseGetCombinedUsersList()</h2>";

echo "<div style='background:#e8f5e8;padding:20px;margin:20px 0;border-radius:10px;'>";

if (function_exists('firebaseGetCombinedUsersList')) {
    echo "<div style='background:#d4edda;padding:15px;margin:10px 0;border-radius:5px;'>";
    echo "✅ Função firebaseGetCombinedUsersList() disponível";
    echo "</div>";
    
    $users = firebaseGetCombinedUsersList();
    echo "<h3>Resultado da função:</h3>";
    echo "<p><strong>Total de usuários retornados:</strong> " . count($users) . "</p>";
    
    if (!empty($users)) {
        echo "<div style='max-height:400px;overflow-y:auto;background:#fff;padding:15px;border-radius:5px;'>";
        foreach ($users as $index => $user) {
            $source = $user['source'] ?? 'undefined';
            $id = $user['id'] ?? 'undefined';
            $name = $user['name'] ?? 'undefined';
            
            $isFirebase = (strtolower($source) === 'firebase' || strpos($id, 'fb_') === 0);
            $bgColor = $isFirebase ? '#d4edda' : '#d1ecf1';
            
            echo "<div style='background:{$bgColor};padding:10px;margin:5px 0;border-radius:5px;'>";
            echo "<strong>Usuário #" . ($index + 1) . ":</strong> {$name}<br>";
            echo "<strong>ID:</strong> {$id}<br>";
            echo "<strong>Source:</strong> " . var_export($source, true) . "<br>";
            echo "<strong>Identificado como Firebase:</strong> " . ($isFirebase ? '✅ Sim' : '❌ Não') . "<br>";
            echo "<details><summary>Ver todos os dados</summary>";
            echo "<pre>" . print_r($user, true) . "</pre>";
            echo "</details>";
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<div style='background:#f8d7da;padding:15px;margin:10px 0;border-radius:5px;'>";
        echo "❌ Nenhum usuário retornado pela função";
        echo "</div>";
    }
} else {
    echo "<div style='background:#f8d7da;padding:15px;margin:10px 0;border-radius:5px;'>";
    echo "❌ Função firebaseGetCombinedUsersList() NÃO disponível";
    echo "</div>";
}

echo "</div>";

// Teste 3: AJAX endpoint
echo "<h2>📋 Teste 3: Endpoint AJAX get_users</h2>";

echo "<div style='background:#fff3cd;padding:20px;margin:20px 0;border-radius:10px;'>";

// Simular chamada AJAX
$action = 'get_users';
$_POST['action'] = $action;

ob_start();
include 'admin_ajax.php';
$ajaxOutput = ob_get_clean();

echo "<h3>Resposta do endpoint AJAX:</h3>";
echo "<div style='background:#fff;padding:15px;border-radius:5px;margin:10px 0;'>";
echo "<pre>" . htmlspecialchars($ajaxOutput) . "</pre>";
echo "</div>";

// Parse JSON response
$jsonResponse = json_decode($ajaxOutput, true);
if ($jsonResponse && isset($jsonResponse['success']) && $jsonResponse['success']) {
    echo "<div style='background:#d4edda;padding:15px;margin:10px 0;border-radius:5px;'>";
    echo "✅ Endpoint AJAX funcionando corretamente<br>";
    echo "<strong>Usuários retornados:</strong> " . count($jsonResponse['users']) . "<br>";
    echo "<strong>Primeiro usuário:</strong> " . ($jsonResponse['users'][0]['name'] ?? 'N/A');
    echo "</div>";
} else {
    echo "<div style='background:#f8d7da;padding:15px;margin:10px 0;border-radius:5px;'>";
    echo "❌ Problema no endpoint AJAX";
    echo "</div>";
}

echo "</div>";

// Teste 4: Renderização JavaScript
echo "<h2>📋 Teste 4: Simulação da Renderização JavaScript</h2>";

echo "<div style='background:#e8f4f8;padding:20px;margin:20px 0;border-radius:10px;'>";

if (isset($jsonResponse['users']) && !empty($jsonResponse['users'])) {
    $users = $jsonResponse['users'];
    
    echo "<h3>Lógica de renderização aplicada:</h3>";
    echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
    echo "<tr><th>Nome</th><th>ID</th><th>Source</th><th>ID Prefix</th><th>Lógica Corrigida</th><th>Botões Esperados</th></tr>";
    
    foreach ($users as $user) {
        $source = $user['source'] ?? '';
        $id = $user['id'] ?? '';
        
        // Lógica corrigida
        $isFirebase = (strtolower($source) === 'firebase' || strpos($id, 'fb_') === 0);
        $buttons = $isFirebase ? '👁️ Ver Detalhes' : '✏️ Editar + 🗑️ Excluir';
        $bgColor = $isFirebase ? '#d4edda' : '#d1ecf1';
        
        echo "<tr style='background:{$bgColor}'>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$id}</td>";
        echo "<td>" . var_export($source, true) . "</td>";
        echo "<td>" . (strpos($id, 'fb_') === 0 ? '✅ fb_' : '❌ Não') . "</td>";
        echo "<td>" . ($isFirebase ? 'Firebase' : 'Local') . "</td>";
        echo "<td>{$buttons}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='margin-top:20px;padding:15px;background:#fff;border-radius:5px;'>";
    $firebaseCount = count(array_filter($users, function($u) {
        $source = $u['source'] ?? '';
        $id = $u['id'] ?? '';
        return (strtolower($source) === 'firebase' || strpos($id, 'fb_') === 0);
    }));
    echo "<h4>📊 Resumo:</h4>";
    echo "<p><strong>Usuários Firebase identificados:</strong> {$firebaseCount}</p>";
    echo "<p><strong>Usuários Locais identificados:</strong> " . (count($users) - $firebaseCount) . "</p>";
    echo "</div>";
    
} else {
    echo "<div style='background:#f8d7da;padding:15px;margin:10px 0;border-radius:5px;'>";
    echo "❌ Não há dados de usuários para testar a renderização";
    echo "</div>";
}

echo "</div>";

// Teste 5: Verificação do código atual
echo "<h2>📋 Teste 5: Código Atual do Admin Panel</h2>";

echo "<div style='background:#f8d7da;padding:20px;margin:20px 0;border-radius:10px;'>";

// Ler parte relevante do admin_panel.php
$adminPanelContent = file_get_contents('admin_panel.php');
if ($adminPanelContent) {
    // Procurar a linha da renderização de botões
    $lines = explode("\n", $adminPanelContent);
    $buttonLines = [];
    
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, 'user.source') !== false || strpos($line, 'startsWith') !== false) {
            $buttonLines[] = [
                'line' => $lineNum + 1,
                'content' => trim($line)
            ];
        }
    }
    
    if (!empty($buttonLines)) {
        echo "<h3>Linhas relevantes encontradas no admin_panel.php:</h3>";
        foreach ($buttonLines as $btnLine) {
            echo "<div style='background:#fff;padding:10px;margin:5px 0;border-radius:5px;'>";
            echo "<strong>Linha {$btnLine['line']}:</strong><br>";
            echo "<code>" . htmlspecialchars($btnLine['content']) . "</code>";
            echo "</div>";
        }
    } else {
        echo "<div style='background:#f8d7da;padding:15px;margin:10px 0;border-radius:5px;'>";
        echo "❌ Não foram encontradas linhas com lógica de botões";
        echo "</div>";
    }
} else {
    echo "<div style='background:#f8d7da;padding:15px;margin:10px 0;border-radius:5px;'>";
    echo "❌ Não foi possível ler o arquivo admin_panel.php";
    echo "</div>";
}

echo "</div>";

// Conclusão e próximos passos
echo "<h2>✅ Conclusão e Próximos Passos</h2>";

echo "<div style='background:#d1ecf1;padding:20px;margin:30px 0;border-radius:10px;border-left:5px solid #0c5460;'>";
echo "<h3>📋 Checklist de Verificação:</h3>";
echo "<ul>";
echo "<li>✅ Verificar se funções Firebase estão carregando corretamente</li>";
echo "<li>✅ Confirmar que firebaseGetCombinedUsersList() retorna dados</li>";
echo "<li>✅ Validar endpoint AJAX get_users</li>";
echo "<li>✅ Testar lógica de detecção de usuários Firebase</li>";
echo "<li>✅ Verificar código atual do admin_panel.php</li>";
echo "</ul>";

echo "<h3>🔗 Links de Acesso:</h3>";
echo "<ul>";
echo "<li><a href='admin_panel.php#users' target='_blank'>🔧 Painel Admin - Gerenciamento de Usuários</a></li>";
echo "<li><a href='test_users_crud.php' target='_blank'>🧪 Teste Completo de CRUD</a></li>";
echo "</ul>";

echo "<div style='margin-top:20px;padding:15px;background:#fff;border-radius:5px;'>";
echo "<h3>⚠️ Se os botões ainda não aparecerem:</h3>";
echo "<ol>";
echo "<li>Limpe o cache do navegador (Ctrl+F5)</li>";
echo "<li>Verifique o Console do navegador por erros JavaScript</li>";
echo "<li>Confirme que está logado como admin</li>";
echo "<li>Recarregue a página do painel admin</li>";
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
code { 
    background: #f1f2f6; 
    padding: 2px 6px; 
    border-radius: 3px; 
    font-family: monospace; 
    font-size: 12px; 
}
pre { 
    background: #f8f9fa; 
    padding: 15px; 
    border-radius: 5px; 
    overflow-x: auto; 
    font-size: 11px; 
    max-height: 300px; 
}
details { 
    margin: 10px 0; 
}
summary { 
    cursor: pointer; 
    font-weight: bold; 
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
table th { 
    background: #3498db; 
    color: white; 
    padding: 12px; 
    text-align: left; 
}
table td { 
    padding: 10px; 
    border: 1px solid #ddd; 
}
</style>
";

echo "<hr><p><small>🔍 Diagnóstico completo executado em " . date('d/m/Y H:i:s') . "</small></p>";
?>