<?php
/**
 * Teste Específico de Extração de ID do SofaScore
 * Arquivo: test_id_extraction.php
 */

session_start();
$_SESSION['admin_logado'] = true;

require_once 'admin_ajax.php';

echo "<h1>🔬 Teste de Extração de ID do SofaScore</h1>";

// Teste 1: Função de extração disponível
echo "<h2>Teste 1: Função Disponível</h2>";

if (function_exists('extractEventIdFromUrl')) {
    echo "<div style='background:#d4edda;padding:15px;margin:10px 0;border-radius:5px;'>";
    echo "✅ Função <code>extractEventIdFromUrl</code> encontrada";
    
    $reflection = new ReflectionFunction('extractEventIdFromUrl');
    $params = implode(', ', array_map(fn($p) => $p->getName(), $reflection->getParameters()));
    echo "<br><strong>Parâmetros:</strong> {$params}";
    echo "</div>";
} else {
    echo "<div style='background:#f8d7da;padding:15px;margin:10px 0;border-radius:5px;'>";
    echo "❌ Função <code>extractEventIdFromUrl</code> NÃO encontrada";
    echo "</div>";
    exit;
}

// Teste 2: URLs de teste variadas
echo "<h2>Teste 2: Extração de IDs de URLs</h2>";

$testUrls = [
    // URLs válidas normais
    "https://www.sofascore.com/event/1234567" => "1234567",
    "https://sofascore.com/event/7654321" => "7654321",
    "https://www.sofascore.com/match/1111111" => "1111111",
    "https://www.sofascore.com/game/2222222" => "2222222",
    "https://www.sofascore.com/fixture/3333333" => "3333333",
    
    // URLs com parâmetros
    "https://www.sofascore.com/event/4444444?id=5555555" => "4444444",
    "https://www.sofascore.com/page?event=6666666" => "6666666",
    "https://www.sofascore.com/page?eventId=7777777" => "7777777",
    "https://www.sofascore.com/page?matchId=8888888" => "8888888",
    
    // URLs complexas
    "https://www.sofascore.com/tournament/football/brazil/serie-a/12345/event/9999999" => "9999999",
    "https://www.sofascore.com/competition/football/spain/laliga/event/1010101" => "1010101",
    
    // URLs problemáticas
    "https://www.sofascore.com/" => null,
    "https://www.google.com/event/1234567" => null,
    "https://www.sofascore.com/event/abc123" => null,
    "" => null,
    "texto aleatório" => null,
    "https://www.sofascore.com/event/12" => null, // Muito curto
    "https://www.sofascore.com/event/123456789012345" => null // Muito longo
];

echo "<div style='display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:20px;'>";
foreach ($testUrls as $url => $expectedId) {
    $extractedId = extractEventIdFromUrl($url);
    $success = ($extractedId === $expectedId);
    
    $bgColor = $success ? '#d4edda' : '#f8d7da';
    $status = $success ? '✅ CORRETO' : '❌ ERRO';
    
    echo "<div style='background:{$bgColor};padding:20px;border-radius:8px;'>";
    echo "<h4>" . ($url ?: '<em>URL vazia</em>') . "</h4>";
    echo "<p><strong>Esperado:</strong> " . ($expectedId ?: '<em>null</em>') . "</p>";
    echo "<p><strong>Extraído:</strong> " . ($extractedId ?: '<em>null</em>') . "</p>";
    echo "<p><strong>Status:</strong> {$status}</p>";
    echo "</div>";
}
echo "</div>";

// Teste 3: Teste direto no browser (frontend)
echo "<h2>Teste 3: Teste Frontend (Browser)</h2>";

?>
<script>
// Função JavaScript equivalente
function extractEventIdJS(url) {
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
            console.log('ID extraído (JS):', eventId, 'com padrão:', pattern);
            
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
        console.log('ID potencial encontrado (JS):', potentialId);
        return potentialId;
    }
    
    console.error('Nenhum ID encontrado na URL (JS):', url);
    return null;
}

// Testar as mesmas URLs
const testUrls = [
    "https://www.sofascore.com/event/1234567",
    "https://sofascore.com/event/7654321", 
    "https://www.sofascore.com/tournament/football/brazil/serie-a/12345/event/9999999",
    "https://www.google.com/event/1234567", // Deve falhar
    "" // Deve falhar
];

console.log("=== Teste de Extração de ID no Browser ===");
testUrls.forEach(url => {
    const extracted = extractEventIdJS(url);
    console.log(`URL: ${url || '(vazia)'}`);
    console.log(`ID extraído: ${extracted || 'null'}`);
    console.log("---");
});
</script>

<?php
// Teste 4: Simular chamada AJAX completa
echo "<h2>Teste 4: Simulação Completa com Erro</h2>";

// Simular o cenário de erro relatado
$problematicUrl = "https://www.sofascore.com/event/1234567";

echo "<div style='background:#fff3cd;padding:25px;margin:25px 0;border-radius:10px;'>";
echo "<h3>Cenário de Erro Relatado:</h3>";

echo "<p><strong>URL:</strong> {$problematicUrl}</p>";

// Etapa 1: Extração de ID
$eventId = extractEventIdFromUrl($problematicUrl);
echo "<p><strong>ID Extraído:</strong> " . ($eventId ?: '<span style="color:red">NULO</span>') . "</p>";

if ($eventId) {
    // Etapa 2: Chamada à API
    echo "<p><strong>Chamando API para evento:</strong> {$eventId}</p>";
    
    $apiResult = loadSofaScoreEventData($eventId);
    
    if ($apiResult) {
        echo "<div style='background:#d4edda;padding:15px;margin:10px 0;border-radius:5px;'>";
        echo "<h4>✅ SUCESSO TOTAL</h4>";
        echo "<p>Times: {$apiResult['team1']['name']} vs {$apiResult['team2']['name']}</p>";
        echo "</div>";
    } else {
        echo "<div style='background:#f8d7da;padding:15px;margin:10px 0;border-radius:5px;'>";
        echo "<h4>❌ FALHA NA API - TESTANDO FALLBACK</h4>";
        
        // Etapa 3: Fallback
        $fallbackResult = getFallbackEventData($eventId, $problematicUrl);
        if ($fallbackResult) {
            echo "<div style='background:#fff3cd;padding:15px;margin:10px 0;border-radius:5px;'>";
            echo "<h4>⚠️ FALLBACK FUNCIONOU</h4>";
            echo "<p>Times: {$fallbackResult['team1']['name']} vs {$fallbackResult['team2']['name']}</p>";
            echo "</div>";
        } else {
            echo "<p style='color:red'>❌ FALLBACK TAMBÉM FALHOU</p>";
        }
        echo "</div>";
    }
} else {
    echo "<div style='background:#f8d7da;padding:15px;margin:10px 0;border-radius:5px;'>";
    echo "<h4>❌ FALHA NA EXTRAÇÃO DE ID</h4>";
    echo "<p>Não foi possível extrair ID da URL</p>";
    echo "</div>";
}

echo "</div>";

// Teste 5: Formulário interativo
echo "<h2>Teste 5: Teste Interativo</h2>";

if ($_POST['test_url'] ?? '') {
    $testUrl = trim($_POST['test_url']);
    
    echo "<div style='background:#e8f4f8;padding:25px;margin:25px 0;border-radius:10px;'>";
    echo "<h3>Resultado do Teste:</h3>";
    echo "<p><strong>URL testada:</strong> " . htmlspecialchars($testUrl) . "</p>";
    
    $extractedId = extractEventIdFromUrl($testUrl);
    
    if ($extractedId) {
        echo "<div style='background:#d4edda;padding:15px;margin:15px 0;border-radius:5px;'>";
        echo "<p style='color:green;font-weight:bold'>✅ ID EXTRAÍDO: {$extractedId}</p>";
        
        // Testar chamada à API
        $apiTest = loadSofaScoreEventData($extractedId);
        if ($apiTest) {
            echo "<p style='color:green'>✅ API funcionou - Times: {$apiTest['team1']['name']} vs {$apiTest['team2']['name']}</p>";
        } else {
            echo "<p style='color:orange'>⚠️ API falhou, testando fallback...</p>";
            $fallbackTest = getFallbackEventData($extractedId, $testUrl);
            if ($fallbackTest) {
                echo "<p style='color:blue'>✅ Fallback funcionou - Times: {$fallbackTest['team1']['name']} vs {$fallbackTest['team2']['name']}</p>";
            } else {
                echo "<p style='color:red'>❌ Ambos falharam</p>";
            }
        }
        echo "</div>";
    } else {
        echo "<div style='background:#f8d7da;padding:15px;margin:15px 0;border-radius:5px;'>";
        echo "<p style='color:red;font-weight:bold'>❌ NÃO FOI POSSÍVEL EXTRAIR ID</p>";
        echo "</div>";
    }
    
    echo "</div>";
}

?>

<form method="POST" style="background:#f8f9fa;padding:25px;margin:25px 0;border-radius:10px;">
    <h3>🔗 Testar Extração de ID:</h3>
    
    <div style="margin:15px 0;">
        <label for="test_url">URL do SofaScore:</label><br>
        <input type="url" id="test_url" name="test_url" value="<?= htmlspecialchars($_POST['test_url'] ?? 'https://www.sofascore.com/event/1234567') ?>" 
               style="width:100%;padding:12px;margin:8px 0;border:2px solid #ddd;border-radius:5px;" 
               placeholder="https://www.sofascore.com/event/1234567">
    </div>
    
    <button type="submit" style="background:#28a745;color:white;padding:12px 25px;border:none;border-radius:5px;cursor:pointer;font-size:16px;">
        🧪 Testar Extração
    </button>
</form>

<?php
// Conclusão
echo "<div style='background:#e8f5e8;padding:25px;margin:30px 0;border-radius:10px;border-left:5px solid #28a745;'>";
echo "<h2>📋 Conclusão</h2>";

echo "<h3>Melhorias Implementadas:</h3>";
echo "<ul>";
echo "<li>✅ Padrões de extração expandidos para diversos formatos de URL</li>";
echo "<li>✅ Validação rigorosa do ID extraído</li>";
echo "<li>✅ Logging detalhado para debug</li>";
echo "<li>✅ Fallback mais robusto quando extração falha</li>";
echo "<li>✅ Tratamento de casos extremos e URLs inválidas</li>";
echo "</ul>";

echo "<h3>Próximos Passos:</h3>";
echo "<ol>";
echo "<li>Teste com URLs reais do SofaScore</li>";
echo "<li>Verifique o error_log do PHP para detalhes técnicos</li>";
echo "<li>Use o formulário acima para testes interativos</li>";
echo "<li>Confirme que o fallback funciona quando API falha</li>";
echo "</ol>";

echo "</div>";

// Estilos
echo "
<style>
body { font-family: 'Segoe UI', Arial, sans-serif; margin: 20px; background: #f5f7fa; }
h1 { color: #2c3e50; border-bottom: 4px solid #3498db; padding-bottom: 15px; }
h2 { color: #34495e; margin-top: 40px; }
h3 { color: #2c3e50; }
h4 { color: #34495e; margin-bottom: 10px; }
code { background: #f1f2f6; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>
";

echo "<hr><p><small>🔬 Teste de extração executado em " . date('d/m/Y H:i:s') . "</small></p>";

// Executar JavaScript no console
echo "<script>console.log('=== Teste de Extração de ID concluído ===');</script>";
?>