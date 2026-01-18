<?php
/**
 * Teste da API do SofaScore
 * Arquivo: debug_sofascore_api.php
 */

// Habilitar erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🧪 Teste da API do SofaScore</h1>";

// Teste 1: Extrair ID de URLs
echo "<h2>Teste 1: Extração de IDs de URLs</h2>";

$testUrls = [
    'https://www.sofascore.com/event/1234567',
    'https://www.sofascore.com/match/7654321',
    'https://www.sofascore.com/event/9876543?tab=about',
    'https://sofascore.com/tournament/football/brazil/serie-a/12345/event/1111111'
];

function extractEventIdFromUrl($url) {
    $patterns = [
        '/\/event\/(\d+)/',
        '/\/match\/(\d+)/',
        '[?&]id=(\d+)',
        '[?&]event=(\d+)'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

foreach ($testUrls as $url) {
    $eventId = extractEventIdFromUrl($url);
    $status = $eventId ? "✅ ID: {$eventId}" : "❌ Não encontrado";
    echo "<p><strong>URL:</strong> {$url}<br><strong>Resultado:</strong> {$status}</p>";
}

// Teste 2: Testar API do SofaScore
echo "<h2>Teste 2: Chamada à API do SofaScore</h2>";

function testSofaScoreApi($eventId = '1234567') {
    $apiUrl = "https://www.sofascore.com/api/v1/event/{$eventId}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "<div style='background:#f8f9fa;padding:15px;margin:10px 0;border-radius:5px;'>";
    echo "<p><strong>URL requisitada:</strong> {$apiUrl}</p>";
    echo "<p><strong>HTTP Code:</strong> {$httpCode}</p>";
    
    if ($curlError) {
        echo "<p style='color:red'><strong>Erro cURL:</strong> {$curlError}</p>";
        return false;
    }
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p style='color:green'>✅ Resposta JSON válida recebida</p>";
            if (isset($data['event'])) {
                $event = $data['event'];
                echo "<p><strong>Evento encontrado:</strong> {$event['homeTeam']['name']} vs {$event['awayTeam']['name']}</p>";
                echo "<p><strong>Status:</strong> {$event['status']['type']}</p>";
                echo "<p><strong>Data:</strong> " . (isset($event['startTimestamp']) ? date('d/m/Y H:i', $event['startTimestamp']) : 'N/A') . "</p>";
                
                // Mostrar estrutura básica
                echo "<details>";
                echo "<summary>Estrutura completa do evento (clique para expandir)</summary>";
                echo "<pre>" . print_r($event, true) . "</pre>";
                echo "</details>";
                
                return true;
            } else {
                echo "<p style='color:orange'>⚠️ Estrutura de evento não encontrada na resposta</p>";
                echo "<pre>" . print_r(array_keys($data), true) . "</pre>";
            }
        } else {
            echo "<p style='color:red'>❌ Erro ao decodificar JSON: " . json_last_error_msg() . "</p>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
        }
    } else {
        echo "<p style='color:red'>❌ HTTP Error: {$httpCode}</p>";
        if ($response) {
            echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
        }
    }
    
    return false;
}

// Testar com um ID de evento conhecido (você pode substituir por um ID real)
echo "<p>Testando com ID de evento: 1234567</p>";
$apiSuccess = testSofaScoreApi('1234567');

// Teste 3: Web Scraping como fallback
echo "<h2>Teste 3: Web Scraping (Fallback)</h2>";

function scrapeSofaScorePage($eventId) {
    $url = "https://www.sofascore.com/event/{$eventId}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<div style='background:#f8f9fa;padding:15px;margin:10px 0;border-radius:5px;'>";
    echo "<p><strong>URL:</strong> {$url}</p>";
    echo "<p><strong>HTTP Code:</strong> {$httpCode}</p>";
    
    if ($httpCode === 200 && $html) {
        echo "<p style='color:green'>✅ Página carregada com sucesso</p>";
        
        // Tentar extrair título da página
        if (preg_match('/<title>(.*?)<\/title>/i', $html, $matches)) {
            echo "<p><strong>Título da página:</strong> " . htmlspecialchars($matches[1]) . "</p>";
        }
        
        // Procurar por dados de times no HTML
        $teamsFound = [];
        if (preg_match_all('/([A-Za-zÀ-ú\s\-]+)\s+vs\s+([A-Za-zÀ-ú\s\-]+)/i', $html, $teamMatches)) {
            for ($i = 0; $i < min(3, count($teamMatches[0])); $i++) {
                $teamsFound[] = [
                    'match' => $teamMatches[0][$i],
                    'team1' => $teamMatches[1][$i],
                    'team2' => $teamMatches[2][$i]
                ];
            }
        }
        
        if (!empty($teamsFound)) {
            echo "<p style='color:green'>⚽ Times encontrados:</p>";
            echo "<ul>";
            foreach ($teamsFound as $teamMatch) {
                echo "<li>" . htmlspecialchars($teamMatch['team1']) . " vs " . htmlspecialchars($teamMatch['team2']) . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color:orange'>⚠️ Nenhum time encontrado no HTML</p>";
        }
        
        return true;
    } else {
        echo "<p style='color:red'>❌ Falha ao carregar página (HTTP {$httpCode})</p>";
        return false;
    }
}

if (!$apiSuccess) {
    echo "<p>API falhou, tentando web scraping...</p>";
    scrapeSofaScorePage('1234567');
}

// Teste 4: Verificar funções do sistema
echo "<h2>Teste 4: Funções do Sistema</h2>";

// Verificar se as funções existem
$requiredFunctions = [
    'extractEventIdFromUrl',
    'loadSofaScoreEventData',
    'updateGameFromSofaScore'
];

echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
echo "<tr><th>Função</th><th>Status</th><th>Observações</th></tr>";

foreach ($requiredFunctions as $function) {
    if (function_exists($function)) {
        $reflection = new ReflectionFunction($function);
        $params = implode(', ', array_map(function($param) {
            return $param->getName();
        }, $reflection->getParameters()));
        
        echo "<tr style='background:#d4edda'>";
        echo "<td>{$function}</td>";
        echo "<td>✅ Disponível</td>";
        echo "<td>Parâmetros: {$params}</td>";
        echo "</tr>";
    } else {
        echo "<tr style='background:#f8d7da'>";
        echo "<td>{$function}</td>";
        echo "<td>❌ Não encontrada</td>";
        echo "<td>Função precisa ser implementada</td>";
        echo "</tr>";
    }
}
echo "</table>";

// Estilos
echo "
<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
h2 { color: #34495e; margin-top: 30px; }
table { margin: 15px 0; background: white; }
th { background: #3498db; color: white; padding: 10px; }
td { padding: 8px; border: 1px solid #ddd; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 300px; }
details { margin: 10px 0; }
summary { cursor: pointer; font-weight: bold; }
</style>
";

echo "<hr><p><small>🧪 Teste executado em " . date('d/m/Y H:i:s') . "</small></p>";
?>