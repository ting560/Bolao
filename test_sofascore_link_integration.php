<?php
/**
 * Teste da Integração Completa com Link do SofaScore
 * Arquivo: test_sofascore_link_integration.php
 */

session_start();
$_SESSION['admin_logado'] = true; // Simular login admin para testes

require_once 'admin_ajax.php';

echo "<h1>🧪 Teste da Integração com Link do SofaScore</h1>";

// Teste 1: Extrair ID do link
echo "<h2>Teste 1: Extração de ID do Link</h2>";
$testLinks = [
    'https://www.sofascore.com/event/1234567',
    'https://www.sofascore.com/match/7654321',
    'https://www.sofascore.com/event/9876543?tab=about',
    'https://www.sofascore.com/tournament/football/brazil/serie-a/12345/event/1111111'
];

foreach ($testLinks as $link) {
    $eventId = extractEventIdFromUrl($link);
    echo "<p>Link: {$link}<br>ID extraído: " . ($eventId ?: '<span style="color:red">NÃO ENCONTRADO</span>') . "</p>";
}

// Teste 2: Processar link real (se disponível)
echo "<h2>Teste 2: Processamento de Evento Real</h2>";
$realEventId = '1234567'; // Substituir por um ID real para testes

if ($realEventId) {
    echo "<p>Processando evento ID: {$realEventId}</p>";
    
    $eventData = loadSofaScoreEventData($realEventId);
    
    if ($eventData) {
        echo "<div style='background:#e8f5e8;padding:15px;margin:10px 0;border-radius:5px;'>";
        echo "<h3>Dados Carregados com Sucesso:</h3>";
        echo "<pre>" . print_r($eventData, true) . "</pre>";
        echo "</div>";
    } else {
        echo "<div style='background:#ffe8e8;padding:15px;margin:10px 0;border-radius:5px;'>";
        echo "<p style='color:red'>❌ Falha ao carregar dados do evento</p>";
        echo "</div>";
    }
} else {
    echo "<p><em>Nenhum ID de evento real configurado para testes</em></p>";
}

// Teste 3: Simular formulário com link do SofaScore
echo "<h2>Teste 3: Simulação de Formulário</h2>";
?>
<form method="POST" action="admin_ajax.php" style="background:#f0f8ff;padding:20px;margin:20px 0;border-radius:8px;">
    <h3>Simular Adição de Jogo com Link do SofaScore</h3>
    
    <div style="margin:10px 0;">
        <label>Link do SofaScore:</label><br>
        <input type="url" name="sofaScoreLink" value="https://www.sofascore.com/event/1234567" style="width:400px;padding:8px;">
    </div>
    
    <div style="margin:10px 0;">
        <input type="hidden" name="action" value="load_sofascore_event">
        <button type="submit" style="background:#4CAF50;color:white;padding:10px 20px;border:none;border-radius:4px;cursor:pointer;">
            📥 Carregar Dados do Link
        </button>
    </div>
</form>

<?php
// Teste 4: Verificar funções disponíveis
echo "<h2>Teste 4: Funções Disponíveis</h2>";
$requiredFunctions = [
    'extractEventIdFromUrl',
    'loadSofaScoreEventData',
    'mapSofaScoreStatus',
    'scrapeSofaScoreEvent',
    'updateGameFromSofaScore'
];

echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
echo "<tr><th>Função</th><th>Status</th></tr>";

foreach ($requiredFunctions as $function) {
    $exists = function_exists($function) ? '✅ Disponível' : '❌ Não encontrada';
    $color = function_exists($function) ? '#d4edda' : '#f8d7da';
    echo "<tr style='background:{$color}'><td>{$function}</td><td>{$exists}</td></tr>";
}

echo "</table>";

// Teste 5: Demonstração da interface
echo "<h2>Teste 5: Demonstração da Interface</h2>";
?>
<div style="background:#fff3cd;padding:20px;margin:20px 0;border-radius:8px;border:1px solid #ffeaa7;">
    <h3>📋 Como funciona na prática:</h3>
    <ol>
        <li><strong>Adicionar Jogo</strong> → Clicar no botão "Adicionar Jogo"</li>
        <li><strong>Preencher Link</strong> → Colar link do SofaScore no campo específico</li>
        <li><strong>Carregar Dados</strong> → Clicar em "Carregar Dados do Link"</li>
        <li><strong>Ver Pré-visualização</strong> → Confirmar dados carregados</li>
        <li><strong>Salvar</strong> → Jogo é salvo com integração automática</li>
        <li><strong>Atualização Automática</strong> → Sistema atualiza resultados a cada minuto</li>
    </ol>
    
    <div style="margin-top:15px;padding:15px;background:#fff;border-left:4px solid #ffc107;">
        <strong>💡 Exemplo de link válido:</strong><br>
        <code>https://www.sofascore.com/event/1234567</code>
    </div>
</div>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #2c3e50; }
h2 { color: #34495e; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
table { margin: 15px 0; }
td, th { padding: 10px; text-align: left; }
</style>

<?php
echo "<hr><p><small>🧪 Teste concluído em " . date('d/m/Y H:i:s') . "</small></p>";
?>