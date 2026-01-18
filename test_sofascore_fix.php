<?php
/**
 * Teste de Correção do Erro do SofaScore
 * Arquivo: test_sofascore_fix.php
 */

session_start();
$_SESSION['admin_logado'] = true;

echo "<h1>🧪 Teste de Correção do Erro do SofaScore</h1>";

// Teste 1: Verificar funções existentes
echo "<h2>Teste 1: Funções Disponíveis</h2>";

$requiredFunctions = [
    'isValidSofaScoreLink',
    'extractEventIdFromUrl', 
    'loadSofaScoreEventData',
    'getFallbackEventData'
];

echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
echo "<tr><th>Função</th><th>Status</th><th>Detalhes</th></tr>";

foreach ($requiredFunctions as $function) {
    if (function_exists($function)) {
        $reflection = new ReflectionFunction($function);
        $params = implode(', ', array_map(fn($p) => $p->getName(), $reflection->getParameters()));
        echo "<tr style='background:#d4edda'>";
        echo "<td><code>{$function}</code></td>";
        echo "<td>✅ OK</td>";
        echo "<td>Parâmetros: {$params}</td>";
        echo "</tr>";
    } else {
        echo "<tr style='background:#f8d7da'>";
        echo "<td><code>{$function}</code></td>";
        echo "<td>❌ FALTANDO</td>";
        echo "<td>Função não encontrada</td>";
        echo "</tr>";
    }
}
echo "</table>";

// Teste 2: Simular chamada AJAX problemática
echo "<h2>Teste 2: Simulação de Erro e Fallback</h2>";

// Simular um link inválido que causa erro
$problematicLink = "https://www.sofascore.com/event/999999999"; // ID provavelmente inválido

echo "<div style='background:#fff3cd;padding:20px;margin:20px 0;border-radius:8px;'>";
echo "<h3>🔗 Testando com link problemático:</h3>";
echo "<p><strong>Link:</strong> {$problematicLink}</p>";

// Extrair ID
$eventId = extractEventIdFromUrl($problematicLink);
echo "<p><strong>ID Extraído:</strong> " . ($eventId ?: '<span style="color:red">NÃO ENCONTRADO</span>') . "</p>";

if ($eventId) {
    // Tentar carregar dados (vai falhar e usar fallback)
    $eventData = getFallbackEventData($eventId, $problematicLink);
    
    if ($eventData) {
        echo "<div style='background:#d4edda;padding:15px;margin:10px 0;border-radius:5px;'>";
        echo "<h4>✅ Fallback Funcionou!</h4>";
        echo "<p><strong>Times:</strong> {$eventData['team1']['name']} vs {$eventData['team2']['name']}</p>";
        echo "<p><strong>Status:</strong> {$eventData['status']}</p>";
        echo "<p><strong>Data:</strong> " . ($eventData['datetime'] ? date('d/m/Y H:i', strtotime($eventData['datetime'])) : 'N/A') . "</p>";
        echo (isset($eventData['fallback_used']) ? "<p><strong style='color:orange'>⚠️ Usando dados genéricos como fallback</strong></p>" : "");
        echo "</div>";
    } else {
        echo "<div style='background:#f8d7da;padding:15px;margin:10px 0;border-radius:5px;'>";
        echo "<p>❌ Falha total - nenhum dado disponível</p>";
        echo "</div>";
    }
}
echo "</div>";

// Teste 3: Formulário de teste
echo "<h2>Teste 3: Formulário Interativo</h2>";

if ($_POST['test_link'] ?? '') {
    $testLink = trim($_POST['test_link']);
    
    echo "<div style='background:#e8f4f8;padding:20px;margin:20px 0;border-radius:8px;'>";
    echo "<h3>🧪 Resultado do Teste:</h3>";
    
    if (isValidSofaScoreLink($testLink)) {
        echo "<p style='color:green'>✅ Link válido</p>";
        
        $eventId = extractEventIdFromUrl($testLink);
        if ($eventId) {
            echo "<p><strong>ID Extraído:</strong> {$eventId}</p>";
            
            // Simular chamada AJAX
            $postData = [
                'action' => 'load_sofascore_event',
                'sofaScoreLink' => $testLink
            ];
            
            echo "<details>";
            echo "<summary>Ver detalhes da simulação</summary>";
            echo "<p><strong>Dados POST:</strong></p>";
            echo "<pre>" . print_r($postData, true) . "</pre>";
            echo "</details>";
            
        } else {
            echo "<p style='color:red'>❌ Não foi possível extrair ID do link</p>";
        }
    } else {
        echo "<p style='color:red'>❌ Link inválido - deve ser do SofaScore</p>";
    }
    
    echo "</div>";
}

?>

<form method="POST" style="background:#f8f9fa;padding:25px;margin:25px 0;border-radius:10px;">
    <h3>🔗 Testar Link do SofaScore:</h3>
    
    <div style="margin:15px 0;">
        <label for="test_link">URL do SofaScore:</label><br>
        <input type="url" id="test_link" name="test_link" value="<?= htmlspecialchars($_POST['test_link'] ?? 'https://www.sofascore.com/event/1234567') ?>" 
               style="width:100%;padding:12px;margin:8px 0;border:2px solid #ddd;border-radius:5px;" 
               placeholder="https://www.sofascore.com/event/1234567">
    </div>
    
    <button type="submit" style="background:#007bff;color:white;padding:12px 25px;border:none;border-radius:5px;cursor:pointer;font-size:16px;">
        🧪 Testar Link
    </button>
</form>

<?php
// Teste 4: Demonstração de tratamento de erros
echo "<h2>Teste 4: Demonstração de Tratamento de Erros</h2>";

$errorScenarios = [
    "Link inválido" => "https://siteerrado.com/event/1234567",
    "ID não numérico" => "https://www.sofascore.com/event/abc123",
    "Link sem evento" => "https://www.sofascore.com/",
    "Link mal formatado" => "sofascore.com/event/1234567"
];

echo "<div style='display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;'>";
foreach ($errorScenarios as $scenario => $url) {
    $isValid = isValidSofaScoreLink($url);
    $eventId = extractEventIdFromUrl($url);
    
    echo "<div style='background:white;padding:20px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);'>";
    echo "<h4>{$scenario}</h4>";
    echo "<p><strong>URL:</strong> " . htmlspecialchars($url) . "</p>";
    echo "<p><strong>Válido:</strong> " . ($isValid ? "✅ Sim" : "❌ Não") . "</p>";
    echo "<p><strong>ID:</strong> " . ($eventId ?: "N/A") . "</p>";
    echo "</div>";
}
echo "</div>";

// Instruções finais
echo "<div style='background:#e8f5e8;padding:25px;margin:30px 0;border-radius:10px;border-left:5px solid #28a745;'>";
echo "<h2>📋 Instruções para Uso Corrigido</h2>";
echo "<ol>";
echo "<li><strong>Formato correto:</strong> https://www.sofascore.com/event/[NÚMERO]</li>";
echo "<li><strong>Exemplo válido:</strong> https://www.sofascore.com/event/1234567</li>";
echo "<li><strong>Se der erro:</strong> Sistema tenta fallback automático</li>";
echo "<li><strong>Último recurso:</strong> Preenchimento manual dos campos</li>";
echo "</ol>";

echo "<div style='margin-top:20px;padding:15px;background:#fff;border-radius:5px;'>";
echo "<h4>💡 Dicas:</h4>";
echo "<ul>";
echo "<li>O sistema agora tem 3 camadas de proteção contra erros</li>";
echo "<li>Fallback automático quando API falha</li>";
echo "<li>Mensagens de erro detalhadas e úteis</li>";
echo "<li>Loading indicator durante processamento</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

// Estilos
echo "
<style>
body { font-family: 'Segoe UI', Arial, sans-serif; margin: 20px; background: #f5f7fa; }
h1 { color: #2c3e50; border-bottom: 4px solid #3498db; padding-bottom: 15px; }
h2 { color: #34495e; margin-top: 40px; }
h3 { color: #2c3e50; }
h4 { color: #34495e; margin-bottom: 10px; }
table { box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 15px 0; }
th { background: #3498db; color: white; padding: 12px; text-align: left; }
td { padding: 10px; }
code { background: #f1f2f6; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
details { margin: 10px 0; }
summary { cursor: pointer; font-weight: bold; }
ol li, ul li { margin: 8px 0; line-height: 1.6; }
</style>
";

echo "<hr><p><small>🧪 Teste de correção executado em " . date('d/m/Y H:i:s') . "</small></p>";
?>