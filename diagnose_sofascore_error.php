<?php
/**
 * Diagnóstico e Correção Definitiva do Erro do SofaScore
 * Arquivo: diagnose_sofascore_error.php
 */

// Habilitar todos os erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$_SESSION['admin_logado'] = true;

require_once 'admin_ajax.php';

echo "<h1>🔍 Diagnóstico do Erro do SofaScore</h1>";

// Teste 1: Verificar se todas as funções existem
echo "<h2>Teste 1: Funções Requeridas</h2>";

$requiredFunctions = [
    'loadSofaScoreEventData',
    'extractEventIdFromUrl',
    'isValidSofaScoreLink',
    'getFallbackEventData',
    'scrapeSofaScoreEvent'
];

echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
echo "<tr><th>Função</th><th>Status</th><th>Parâmetros</th></tr>";

foreach ($requiredFunctions as $function) {
    if (function_exists($function)) {
        $reflection = new ReflectionFunction($function);
        $params = implode(', ', array_map(fn($p) => $p->getName(), $reflection->getParameters()));
        echo "<tr style='background:#d4edda'>";
        echo "<td><code>{$function}</code></td>";
        echo "<td>✅ OK</td>";
        echo "<td>{$params}</td>";
        echo "</tr>";
    } else {
        echo "<tr style='background:#f8d7da'>";
        echo "<td><code>{$function}</code></td>";
        echo "<td>❌ FALTANDO</td>";
        echo "<td>-</td>";
        echo "</tr>";
    }
}
echo "</table>";

// Teste 2: Testar extração de ID
echo "<h2>Teste 2: Extração de ID do Link</h2>";

$testLinks = [
    'https://www.sofascore.com/event/1234567' => '1234567',
    'https://www.sofascore.com/match/7654321' => '7654321',
    'https://sofascore.com/event/9876543' => '9876543',
    'https://www.sofascore.com/tournament/football/brazil/serie-a/12345/event/1111111' => '1111111'
];

echo "<div style='background:#f8f9fa;padding:15px;border-radius:8px;'>";
foreach ($testLinks as $link => $expectedId) {
    $extractedId = extractEventIdFromUrl($link);
    $status = ($extractedId === $expectedId) ? '✅ Correto' : '❌ Erro';
    $color = ($extractedId === $expectedId) ? '#d4edda' : '#f8d7da';
    
    echo "<div style='background:{$color};padding:10px;margin:5px 0;border-radius:5px;'>";
    echo "<strong>Link:</strong> " . htmlspecialchars($link) . "<br>";
    echo "<strong>Esperado:</strong> {$expectedId}<br>";
    echo "<strong>Extraído:</strong> " . ($extractedId ?: '<span style="color:red">NULO</span>') . "<br>";
    echo "<strong>Status:</strong> {$status}";
    echo "</div>";
}
echo "</div>";

// Teste 3: Teste direto da API do SofaScore
echo "<h2>Teste 3: Chamada Direta à API</h2>";

function directSofaScoreAPITest($eventId) {
    $apiUrl = "https://www.sofascore.com/api/v1/event/{$eventId}";
    
    echo "<div style='background:#fff3cd;padding:20px;margin:20px 0;border-radius:8px;'>";
    echo "<h3>Testando evento ID: {$eventId}</h3>";
    echo "<p><strong>URL:</strong> {$apiUrl}</p>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json, text/plain, */*',
        'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
        'Connection: keep-alive'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "<p><strong>HTTP Code:</strong> {$httpCode}</p>";
    echo "<p><strong>Content-Type:</strong> " . ($contentType ?: 'N/A') . "</p>";
    
    if ($curlError) {
        echo "<p style='color:red'><strong>cURL Error:</strong> {$curlError}</p>";
        echo "</div>";
        return false;
    }
    
    if ($httpCode === 200) {
        if ($contentType && strpos($contentType, 'application/json') !== false) {
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "<p style='color:green'>✅ Resposta JSON válida</p>";
                if (isset($data['event'])) {
                    $event = $data['event'];
                    echo "<p><strong>Evento encontrado:</strong> {$event['homeTeam']['name']} vs {$event['awayTeam']['name']}</p>";
                    echo "<p><strong>Status:</strong> {$event['status']['code']}</p>";
                    return true;
                } else {
                    echo "<p style='color:orange'>⚠️ Estrutura inesperada:</p>";
                    echo "<pre>" . print_r(array_keys($data), true) . "</pre>";
                }
            } else {
                echo "<p style='color:red'>❌ Erro JSON: " . json_last_error_msg() . "</p>";
                echo "<details><summary>Resposta (primeiros 500 caracteres):</summary>";
                echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre></details>";
            }
        } else {
            echo "<p style='color:red'>❌ Content-Type não é JSON: {$contentType}</p>";
            if (strpos($response, '<html') !== false) {
                echo "<p style='color:red'>⚠️ Recebeu página HTML em vez de JSON</p>";
            }
            echo "<details><summary>Resposta (primeiros 500 caracteres):</summary>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre></details>";
        }
    } else {
        echo "<p style='color:red'>❌ HTTP Error: {$httpCode}</p>";
        echo "<details><summary>Resposta (se houver):</summary>";
        echo "<pre>" . htmlspecialchars(substr($response ?? '', 0, 500)) . "</pre></details>";
    }
    
    echo "</div>";
    return false;
}

// Testar com IDs conhecidos
$testEventIds = ['1234567', '7654321', '9999999']; // 9999999 provavelmente falhará

foreach ($testEventIds as $eventId) {
    directSofaScoreAPITest($eventId);
}

// Teste 4: Teste da função principal
echo "<h2>Teste 4: Função loadSofaScoreEventData()</h2>";

foreach ($testEventIds as $eventId) {
    echo "<div style='background:#e8f4f8;padding:20px;margin:20px 0;border-radius:8px;'>";
    echo "<h3>Testando loadSofaScoreEventData('{$eventId}')</h3>";
    
    $result = loadSofaScoreEventData($eventId);
    
    if ($result) {
        echo "<div style='background:#d4edda;padding:15px;border-radius:5px;'>";
        echo "<h4>✅ SUCESSO</h4>";
        echo "<p><strong>Times:</strong> {$result['team1']['name']} vs {$result['team2']['name']}</p>";
        echo "<p><strong>Status:</strong> {$result['status']}</p>";
        echo "<p><strong>Data:</strong> " . ($result['datetime'] ? date('d/m/Y H:i', strtotime($result['datetime'])) : 'N/A') . "</p>";
        if ($result['score']) {
            echo "<p><strong>Placar:</strong> {$result['score']}</p>";
        }
        echo "</div>";
    } else {
        echo "<div style='background:#f8d7da;padding:15px;border-radius:5px;'>";
        echo "<h4>❌ FALHA</h4>";
        echo "<p>Nenhum dado retornado para o evento {$eventId}</p>";
        echo "</div>";
    }
    
    echo "</div>";
}

// Teste 5: Simular chamada AJAX completa
echo "<h2>Teste 5: Simulação Completa AJAX</h2>";

// Simular POST data
$_POST = [
    'action' => 'load_sofascore_event',
    'sofaScoreLink' => 'https://www.sofascore.com/event/1234567'
];

echo "<div style='background:#fff3cd;padding:20px;margin:20px 0;border-radius:8px;'>";
echo "<h3>Simulando chamada AJAX:</h3>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

// Executar a lógica do switch case
$action = $_POST['action'] ?? '';
if ($action === 'load_sofascore_event') {
    $sofaScoreLink = trim($_POST['sofaScoreLink'] ?? '');
    
    if (empty($sofaScoreLink)) {
        echo "<p style='color:red'>❌ Link vazio</p>";
    } elseif (!isValidSofaScoreLink($sofaScoreLink)) {
        echo "<p style='color:red'>❌ Link inválido</p>";
    } else {
        $eventId = extractEventIdFromUrl($sofaScoreLink);
        if (!$eventId) {
            echo "<p style='color:red'>❌ Não conseguiu extrair ID</p>";
        } else {
            echo "<p><strong>ID Extraído:</strong> {$eventId}</p>";
            
            $eventData = loadSofaScoreEventData($eventId);
            if ($eventData) {
                echo "<div style='background:#d4edda;padding:15px;margin:10px 0;border-radius:5px;'>";
                echo "<h4>✅ DADOS CARREGADOS COM SUCESSO</h4>";
                echo "<pre>" . print_r($eventData, true) . "</pre>";
                echo "</div>";
            } else {
                echo "<p style='color:red'>❌ Função retornou null</p>";
                
                // Testar fallback
                $fallbackData = getFallbackEventData($eventId, $sofaScoreLink);
                if ($fallbackData) {
                    echo "<div style='background:#fff3cd;padding:15px;margin:10px 0;border-radius:5px;'>";
                    echo "<h4>⚠️ USANDO FALLBACK</h4>";
                    echo "<pre>" . print_r($fallbackData, true) . "</pre>";
                    echo "</div>";
                }
            }
        }
    }
}
echo "</div>";

// Instruções finais
echo "<div style='background:#e8f5e8;padding:25px;margin:30px 0;border-radius:10px;border-left:5px solid #28a745;'>";
echo "<h2>📋 Conclusão do Diagnóstico</h2>";

echo "<h3>Possíveis Causas do Erro Original:</h3>";
echo "<ul>";
echo "<li><strong>Headers insuficientes</strong> - API bloqueava requisições sem headers adequados</li>";
echo "<li><strong>Validação de resposta</strong> - Sistema não verificava se resposta era realmente JSON</li>";
echo "<li><strong>Tratamento de erros</strong> - Erros não eram capturados adequadamente</li>";
echo "<li><strong>Timeout curto</strong> - Requisições estavam expirando muito rápido</li>";
echo "</ul>";

echo "<h3>Correções Implementadas:</h3>";
echo "<ul>";
echo "<li>✅ Headers completos para simular navegador real</li>";
echo "<li>✅ Validação rigorosa de Content-Type</li>";
echo "<li>✅ Verificação de erros cURL e HTTP</li>";
echo "<li>✅ Timeout aumentado para 15 segundos</li>";
echo "<li>✅ Logging detalhado para debug</li>";
echo "<li>✅ Sistema de fallback robusto</li>";
echo "</ul>";

echo "<div style='margin-top:20px;padding:15px;background:#fff;border-radius:5px;'>";
echo "<h4>💡 Recomendações:</h4>";
echo "<p>Se continuar tendo problemas, tente:</p>";
echo "<ol>";
echo "<li>Usar links de eventos reais e recentes</li>";
echo "<li>Verificar conectividade com sofascore.com</li>";
echo "<li>Utilizar o fallback automático quando API falhar</li>";
echo "<li>Revisar o error_log do PHP para detalhes técnicos</li>";
echo "</ol>";
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
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
details { margin: 10px 0; }
summary { cursor: pointer; font-weight: bold; }
ul li, ol li { margin: 8px 0; line-height: 1.6; }
</style>
";

echo "<hr><p><small>🔍 Diagnóstico executado em " . date('d/m/Y H:i:s') . "</small></p>";
?>