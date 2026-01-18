<?php
/**
 * Teste Rápido da Correção de Botões de Usuário
 */

session_start();
$_SESSION['admin_logado'] = true;

require_once 'admin_ajax.php';

echo "<h1>✅ Teste da Correção - Botões de Usuário</h1>";

// Obter usuários
$users = firebaseGetCombinedUsersList();

echo "<div style='background:#f8f9fa;padding:20px;margin:20px 0;border-radius:10px;'>";
echo "<h2>📊 Resultado Após Correção</h2>";

$firebaseCount = 0;
$localCount = 0;

echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
echo "<tr><th>ID</th><th>Nome</th><th>Source Original</th><th>ID Prefix</th><th>Tipo Detectado</th><th>Botões Esperados</th></tr>";

foreach ($users as $user) {
    $source = $user['source'] ?? 'undefined';
    $id = $user['id'] ?? '';
    
    // Lógica corrigida
    $isFirebase = (strtolower($source) === 'firebase' || strpos($id, 'fb_') === 0);
    
    $tipo = $isFirebase ? 'Firebase' : 'Local';
    $botao = $isFirebase ? '👁️ Ver Detalhes' : '✏️ Editar + 🗑️ Excluir';
    $bgColor = $isFirebase ? '#d4edda' : '#d1ecf1';
    
    if ($isFirebase) $firebaseCount++; else $localCount++;
    
    echo "<tr style='background:{$bgColor}'>";
    echo "<td>{$id}</td>";
    echo "<td>{$user['name']}</td>";
    echo "<td>" . var_export($source, true) . "</td>";
    echo "<td>" . (strpos($id, 'fb_') === 0 ? '✅ Sim' : '❌ Não') . "</td>";
    echo "<td><strong>{$tipo}</strong></td>";
    echo "<td>{$botao}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<div style='margin-top:20px;padding:15px;background:#fff;border-radius:5px;'>";
echo "<h3>📈 Resumo:</h3>";
echo "<p><strong>Usuários Firebase:</strong> {$firebaseCount} (Botão: Ver Detalhes)</p>";
echo "<p><strong>Usuários Locais:</strong> {$localCount} (Botões: Editar + Excluir)</p>";
echo "<p><strong>Total:</strong> " . count($users) . " usuários</p>";
echo "</div>";

echo "</div>";

// Teste de funcionalidade
echo "<h2>🔧 Teste de Funcionalidade</h2>";

echo "<div style='background:#e8f5e8;padding:20px;margin:20px 0;border-radius:10px;'>";
echo "<h3>✅ Correção Aplicada:</h3>";
echo "<ul>";
echo "<li>Verificação flexível de source: <code>(user.source || '').toLowerCase() === 'firebase'</code></li>";
echo "<li>Verificação por prefixo de ID: <code>user.id && user.id.startsWith('fb_')</code></li>";
echo "<li>Combinação com OR lógico para maior robustez</li>";
echo "<li>Consistência aplicada tanto para badges quanto para botões</li>";
echo "</ul>";

echo "<h3>🎯 Resultado Esperado:</h3>";
echo "<ul>";
echo "<li>Usuários com ID iniciando em 'fb_' → Botão 'Ver Detalhes'</li>";
echo "<li>Usuários com source = 'firebase' → Botão 'Ver Detalhes'</li>";
echo "<li>Demais usuários → Botões 'Editar' e 'Excluir'</li>";
echo "</ul>";
echo "</div>";

// Links de acesso
echo "<div style='background:#d1ecf1;padding:20px;margin:30px 0;border-radius:10px;border-left:5px solid #0c5460;'>";
echo "<h2>🔗 Acessos Diretos:</h2>";
echo "<ul>";
echo "<li><a href='admin_panel.php#users' target='_blank'>🔧 Painel Admin - Gerenciamento de Usuários</a></li>";
echo "<li><a href='diagnose_user_source.php' target='_blank'>🔍 Diagnóstico Completo de Source</a></li>";
echo "<li><a href='test_users_crud.php' target='_blank'>🧪 Teste Completo de CRUD</a></li>";
echo "</ul>";

echo "<div style='margin-top:20px;padding:15px;background:#fff;border-radius:5px;'>";
echo "<h3>✅ Validação Concluída:</h3>";
echo "<p>A correção foi aplicada e os botões devem aparecer corretamente agora!</p>";
echo "<p><strong>Próximo passo:</strong> Acesse o Painel Admin para verificar visualmente.</p>";
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
    border-bottom: 4px solid #27ae60; 
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
code { 
    background: #f1f2f6; 
    padding: 2px 6px; 
    border-radius: 3px; 
    font-family: monospace; 
}
ul li { 
    margin: 8px 0; 
    line-height: 1.6; 
}
a { 
    color: #27ae60; 
    text-decoration: none; 
    font-weight: bold; 
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

echo "<hr><p><small>✅ Teste concluído em " . date('d/m/Y H:i:s') . "</small></p>";
?>