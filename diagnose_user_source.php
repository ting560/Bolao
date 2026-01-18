<?php
/**
 * Diagnóstico de Usuários - Verificar Propriedade Source
 */

session_start();
$_SESSION['admin_logado'] = true;

require_once 'admin_ajax.php';

echo "<h1>🔍 Diagnóstico de Usuários - Propriedade Source</h1>";

// Teste 1: Obter usuários diretamente
echo "<h2>Teste 1: Dados Brutos dos Usuários</h2>";

$users = firebaseGetCombinedUsersList();

echo "<div style='background:#f8f9fa;padding:20px;margin:20px 0;border-radius:10px;'>";
echo "<p><strong>Total de usuários encontrados:</strong> " . count($users) . "</p>";

if (!empty($users)) {
    echo "<h3>Detalhes de cada usuário:</h3>";
    echo "<div style='max-height:400px;overflow-y:auto;'>";
    
    foreach ($users as $index => $user) {
        $source = $user['source'] ?? 'undefined';
        $id = $user['id'] ?? 'undefined';
        $name = $user['name'] ?? 'undefined';
        
        $bgColor = ($source === 'firebase') ? '#d4edda' : '#f8d7da';
        
        echo "<div style='background:{$bgColor};padding:15px;margin:10px 0;border-radius:5px;'>";
        echo "<h4>Usuário #" . ($index + 1) . "</h4>";
        echo "<p><strong>ID:</strong> {$id}</p>";
        echo "<p><strong>Nome:</strong> {$name}</p>";
        echo "<p><strong>Source:</strong> <code>" . var_export($source, true) . "</code></p>";
        echo "<p><strong>Tipo:</strong> " . gettype($source) . "</p>";
        echo "<details>";
        echo "<summary>Ver todos os dados</summary>";
        echo "<pre>" . print_r($user, true) . "</pre>";
        echo "</details>";
        echo "</div>";
    }
    
    echo "</div>";
} else {
    echo "<div style='background:#f8d7da;padding:15px;margin:10px 0;border-radius:5px;'>";
    echo "<p>❌ Nenhum usuário encontrado</p>";
    echo "</div>";
}
echo "</div>";

// Teste 2: Simular renderização da tabela
echo "<h2>Teste 2: Simulação da Renderização da Tabela</h2>";

echo "<div style='background:#fff3cd;padding:20px;margin:20px 0;border-radius:10px;'>";
echo "<h3>Lógica Atual de Renderização:</h3>";
echo "<pre>";
echo "if (user.source === 'firebase') {\n";
echo "    // Mostrar botão 'Ver Detalhes'\n";
echo "} else {\n";
echo "    // Mostrar botões 'Editar' e 'Excluir'\n";
echo "}";
echo "</pre>";

echo "<h3>Resultado para cada usuário:</h3>";

foreach ($users as $index => $user) {
    $source = $user['source'] ?? null;
    $condition = ($source === 'firebase');
    
    $result = $condition ? 'Ver Detalhes (Firebase)' : 'Editar + Excluir (Local)';
    $icon = $condition ? '👁️' : '✏️🗑️';
    $bgColor = $condition ? '#d1ecf1' : '#d4edda';
    
    echo "<div style='background:{$bgColor};padding:10px;margin:5px 0;border-radius:5px;'>";
    echo "<strong>Usuário {$user['name']}:</strong> ";
    echo "{$icon} {$result} ";
    echo "<small>(source: " . var_export($source, true) . ")</small>";
    echo "</div>";
}
echo "</div>";

// Teste 3: Correção proposta
echo "<h2>Teste 3: Correção Proposta</h2>";

echo "<div style='background:#e8f5e8;padding:20px;margin:20px 0;border-radius:10px;'>";
echo "<h3>Solução 1: Verificação mais flexível</h3>";
echo "<pre>";
echo "// Antes:\n";
echo "if (user.source === 'firebase')\n\n";
echo "// Depois:\n";
echo "if ((user.source || '').toLowerCase() === 'firebase')";
echo "</pre>";

echo "<h3>Solução 2: Verificação por prefixo de ID</h3>";
echo "<pre>";
echo "if (user.id && user.id.startsWith('fb_'))";
echo "</pre>";

echo "<h3>Testando correção:</h3>";

$correctionCount = 0;
foreach ($users as $user) {
    $source = $user['source'] ?? '';
    $id = $user['id'] ?? '';
    
    // Método 1: Verificação flexível
    $isFirebase1 = strtolower($source) === 'firebase';
    
    // Método 2: Verificação por prefixo
    $isFirebase2 = strpos($id, 'fb_') === 0;
    
    // Método 3: Combinado
    $isFirebaseFinal = $isFirebase1 || $isFirebase2;
    
    if ($isFirebaseFinal) {
        $correctionCount++;
        echo "<div style='background:#d4edda;padding:10px;margin:5px 0;border-radius:5px;'>";
        echo "✅ {$user['name']}: Identificado como Firebase";
        echo " (source: '{$source}', id: '{$id}')";
        echo "</div>";
    } else {
        echo "<div style='background:#f8d7da;padding:10px;margin:5px 0;border-radius:5px;'>";
        echo "❌ {$user['name']}: Identificado como Local";
        echo " (source: '{$source}', id: '{$id}')";
        echo "</div>";
    }
}

echo "<div style='margin-top:15px;padding:15px;background:#fff;border-radius:5px;'>";
echo "<h4>Resumo:</h4>";
echo "<p><strong>Usuários Firebase identificados:</strong> {$correctionCount}</p>";
echo "<p><strong>Usuários Local identificados:</strong> " . (count($users) - $correctionCount) . "</p>";
echo "</div>";

echo "</div>";

// Implementação da correção
echo "<h2>Implementação da Correção</h2>";

$correctionCode = '
// Código corrigido para admin_panel.php
<td>
    ${((user.source || "").toLowerCase() === "firebase" || (user.id && user.id.startsWith("fb_"))) ? 
        `<button class="btn btn-info btn-sm" onclick="viewUserDetails(\'${user.name}\')" title="Ver Detalhes">
            <i class="fas fa-eye"></i>
        </button>` : 
        `<button class="btn btn-warning btn-sm" onclick="editUser(\'${user.id}\')" title="Editar">
            <i class="fas fa-edit"></i>
        </button>
        <button class="btn btn-danger btn-sm" onclick="deleteUser(\'${user.id}\', \'${user.name}\')" title="Excluir">
            <i class="fas fa-trash"></i>
        </button>`
    }
</td>
';

echo "<div style='background:#e8f4f8;padding:20px;margin:20px 0;border-radius:10px;'>";
echo "<h3>Código Corrigido:</h3>";
echo "<pre style='background:#f8f9fa;padding:15px;border-radius:5px;overflow-x:auto;'>" . htmlspecialchars($correctionCode) . "</pre>";
echo "</div>";

// Links úteis
echo "<div style='background:#d1ecf1;padding:20px;margin:30px 0;border-radius:10px;border-left:5px solid #0c5460;'>";
echo "<h2>🔗 Links de Acesso:</h2>";
echo "<ul>";
echo "<li><a href='admin_panel.php#users' target='_blank'>🔧 Painel Admin - Gerenciamento de Usuários</a></li>";
echo "<li><a href='test_users_crud.php' target='_blank'>🧪 Teste Completo de CRUD</a></li>";
echo "</ul>";

echo "<div style='margin-top:20px;padding:15px;background:#fff;border-radius:5px;'>";
echo "<h3>📋 Próximos Passos:</h3>";
echo "<ol>";
echo "<li>Aplicar correção no arquivo admin_panel.php</li>";
echo "<li>Testar visualmente no painel admin</li>";
echo "<li>Verificar se botões aparecem corretamente</li>";
echo "<li>Confirmar funcionamento do CRUD</li>";
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
}
pre { 
    background: #f8f9fa; 
    padding: 15px; 
    border-radius: 5px; 
    overflow-x: auto; 
    font-size: 12px; 
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
</style>
";

echo "<hr><p><small>🔍 Diagnóstico executado em " . date('d/m/Y H:i:s') . "</small></p>";
?>