<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function obterLivescore() {
    // URL do endpoint AJAX que retorna o HTML dos jogos
    $url = 'https://www.placardefutebol.com.br/includes/ajax/get_matches_livescore.php';
    $cacheFile = 'livescore_cache.json';
    $cacheTime = 60; // 1 minuto

    $dadosRetorno = ['secoes' => [], 'error' => null, 'cache_source' => false];

    // Verificar cache
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
        $cachedData = json_decode(file_get_contents($cacheFile), true);
        if ($cachedData && empty($cachedData['error']) && !empty($cachedData['secoes'])) {
            $cachedData['cache_source'] = true;
            // error_log("DEBUG: Dados do cache usados.");
            return $cachedData;
        }
        @unlink($cacheFile);
    }

    // Requisição HTTP
    $contextOptions = [
        'http' => [
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.0.0 Safari/537.36\r\n" .
                        "Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7\r\n" .
                        "X-Requested-With: XMLHttpRequest\r\n", // Importante para simular AJAX
            'timeout' => 20,
            'follow_location' => 1,
            'max_redirects' => 5
        ],
        'ssl' => [
            'verify_peer' => false, // Tente remover ou setar para true se tiver problemas de SSL
            'verify_peer_name' => false,
        ]
    ];
    // Para PHP 8+, pode ser necessário definir um cipher específico se houver problemas de SSL
    // if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
    //     $contextOptions['ssl']['ciphers'] = 'DEFAULT@SECLEVEL=1';
    // }
    $context = stream_context_create($contextOptions);


    $html = @file_get_contents($url, false, $context);
    if ($html === false) {
        $lastError = error_get_last();
        $errorMessage = 'Erro ao acessar o endpoint AJAX do Placar de Futebol.';
        if ($lastError) {
            $errorMessage .= ' Detalhes: ' . $lastError['message'];
        }
        $dadosRetorno['error'] = $errorMessage;
        // error_log("DEBUG: Falha ao buscar AJAX: " . $errorMessage);

        if (file_exists($cacheFile)) {
            $fallbackData = json_decode(file_get_contents($cacheFile), true);
            if ($fallbackData && empty($fallbackData['error']) && !empty($fallbackData['secoes'])) {
                $fallbackData['cache_source'] = true;
                $fallbackData['error'] = $dadosRetorno['error'] . ' (Dados do cache antigo foram usados como fallback)';
                return $fallbackData;
            }
        }
        return $dadosRetorno;
    }
    // error_log("DEBUG: HTML do AJAX recebido, tamanho: " . strlen($html));


    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    // O HTML do AJAX é um fragmento, pode não ter doctype ou html/body tags.
    // Envolver em um body pode ajudar o parser.
    @$doc->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>');
    $xpath = new DOMXPath($doc);

    $secoes = [];

    // Encontrar todos os nós de título de liga
    $leagueTitleNodes = $xpath->query('//h3[contains(@class, "match-list_league-name")]');

    if ($leagueTitleNodes->length === 0) {
        $dadosRetorno['error'] = 'Nenhum título de liga (h3.match-list_league-name) encontrado no HTML do AJAX.';
        // error_log("DEBUG: " . $dadosRetorno['error']);
        libxml_clear_errors();
        return $dadosRetorno;
    }
    // error_log("DEBUG: Encontrados " . $leagueTitleNodes->length . " títulos de liga.");


    foreach ($leagueTitleNodes as $tituloNode) {
        $tituloSecao = trim($tituloNode->textContent);
        $jogosDaSecao = [];

        // O container de jogos é o próximo irmão <div class="container content">
        // do elemento pai do <h3> (que é <div class="col ..."> ou <div class="row row-fix"> ou <a>)
        $parentNodeOfH3 = $tituloNode->parentNode; // <div class="col ...">
        $titleBlockWrapper = $parentNodeOfH3->parentNode; // <div class="row row-fix"> ou <a>

        $gamesContainerNode = $xpath->query('following-sibling::div[contains(@class, "container") and contains(@class, "content")][1]', $titleBlockWrapper)->item(0);

        if (!$gamesContainerNode) {
            // error_log("DEBUG: Container de jogos não encontrado para a liga: " . $tituloSecao);
            continue;
        }

        // Os jogos são os links <a> diretos dentro do gamesContainerNode
        $matchNodes = $xpath->query('./a', $gamesContainerNode);

        // error_log("DEBUG: Liga '" . $tituloSecao . "' - Encontrados " . $matchNodes->length . " nós de jogos.");

        foreach ($matchNodes as $matchNode) {
            $jogo = [];

            // Status
            $statusNode = $xpath->query('.//span[contains(@class, "status-name")]', $matchNode)->item(0);
            $jogo['status'] = $statusNode ? trim($statusNode->textContent) : 'N/A';

            // Time 1
            $team1Node = $xpath->query('.//h5[contains(@class, "text-right") and contains(@class, "team_link")]', $matchNode)->item(0);
            $jogo['time1'] = $team1Node ? trim($team1Node->textContent) : 'Time 1';

            // Placar Time 1
            // O placar está em: <div class="w-25 p-1 match-score d-flex justify-content-end"> <h4> <span class="badge badge-default">PLACAR</span> </h4> </div>
            $score1Node = $xpath->query('.//div[contains(@class, "match-score") and contains(@class, "justify-content-end")]//span[contains(@class, "badge-default")]', $matchNode)->item(0);
            $jogo['placar1'] = $score1Node ? trim($score1Node->textContent) : '-';

            // Placar Time 2
            $score2Node = $xpath->query('.//div[contains(@class, "match-score") and contains(@class, "justify-content-start")]//span[contains(@class, "badge-default")]', $matchNode)->item(0);
            $jogo['placar2'] = $score2Node ? trim($score2Node->textContent) : '-';

            // Time 2
            $team2Node = $xpath->query('.//h5[contains(@class, "text-left") and contains(@class, "team_link")]', $matchNode)->item(0);
            $jogo['time2'] = $team2Node ? trim($team2Node->textContent) : 'Time 2';

            if (empty($jogo['placar1']) && $jogo['status'] !== 'N/A' && !preg_match('/\d{2}:\d{2}/', $jogo['status']) && stripos($jogo['status'], 'min') === false && stripos($jogo['status'], 'encerrado') === false ) {
                 // Se não tem placar e não é um horário ou jogo em andamento/encerrado, pode não ser um jogo válido, ou os placares não existem ainda.
                 // No entanto, para jogos futuros, o placar será '-'
            }
             if (empty($jogo['placar1'])) $jogo['placar1'] = '-';
             if (empty($jogo['placar2'])) $jogo['placar2'] = '-';


            if (($jogo['time1'] !== 'Time 1' && !empty($jogo['time1'])) || ($jogo['time2'] !== 'Time 2' && !empty($jogo['time2']))) {
                $jogosDaSecao[] = $jogo;
            } else {
                // error_log("DEBUG: Jogo descartado por falta de nome de time: " . json_encode($jogo));
            }
        }

        if (!empty($jogosDaSecao)) {
            $secoes[] = [
                'titulo' => $tituloSecao,
                'jogos' => $jogosDaSecao
            ];
        }
    }

    if (!empty($secoes)) {
        $dadosRetorno['secoes'] = $secoes;
        if (empty($dadosRetorno['error'])) {
            if (!file_put_contents($cacheFile, json_encode($dadosRetorno, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                 $dadosRetorno['error'] = ( $dadosRetorno['error'] ? $dadosRetorno['error'] . ' / ' : '' ) . 'Não foi possível salvar os dados no cache.';
                 // error_log("DEBUG: Falha ao salvar no cache.");
            } else {
                // error_log("DEBUG: Dados salvos no cache com sucesso.");
            }
        }
    } else {
        if (empty($dadosRetorno['error'])) {
            $dadosRetorno['error'] = 'Nenhum campeonato ou jogo encontrado após o parsing do HTML do AJAX. Verifique os seletores XPath ou o conteúdo do endpoint.';
            // error_log("DEBUG: " . $dadosRetorno['error']);
        }
    }

    libxml_clear_errors();
    return $dadosRetorno;
}

$dados = obterLivescore();
// O restante do HTML para exibição permanece o mesmo
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="60">
    <title>Livescore - Placar de Futebol</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f4f4f9;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(90deg, #1a252f, #2ecc71);
            color: #fff;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        .header h1 {
            margin: 0;
            font-size: 1.8em;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }
        .nav-tabs {
            display: flex;
            justify-content: center;
            background: #ecf0f1;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }
        .nav-tabs button {
            background: none;
            border: none;
            padding: 10px 20px;
            margin: 0 5px;
            cursor: pointer;
            color: #7f8c8d;
            font-weight: 500;
            transition: color 0.3s;
            font-size: 0.9em;
        }
        .nav-tabs button.active {
            color: #2ecc71;
            border-bottom: 2px solid #2ecc71;
        }
        .tab-content {
            padding: 15px;
        }
        .secao-card {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
            transition: box-shadow 0.3s;
        }
        .secao-card:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
        }
        .secao-title {
            background: #2c3e50;
            color: #fff;
            padding: 8px 12px;
            font-size: 1.1em;
            font-weight: 600;
        }
        .jogo-list {
            padding: 0;
        }
        .jogo-item {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.9em;
        }
        .jogo-item:last-child {
            border-bottom: none;
        }

        .status {
            font-weight: 500;
            color: #e74c3c; /* Default para status não reconhecidos ou horários */
            text-align: center;
            flex-basis: 70px; /* Aumentado para caber "ENCERRADO" ou "XX MIN" */
            flex-shrink: 0;
            margin-right: 10px;
            font-size: 0.85em;
            line-height: 1.2; /* Para status com duas palavras como "77 MIN" */
        }
        /* Estilos específicos para status */
        .status.status-encerrado { color: #7f8c8d; }
        .status.status-hoje, .status.status-amanha { color: #3498db; } /* Azul para agendados */
        .status.status-aovivo, .status.status-intervalo, .status[class*="min"] { /* Se contiver "min" no nome da classe */
            color: #27ae60 !important; /* Verde para ao vivo/intervalo */
            font-weight: bold;
        }


        .match-details {
            display: flex;
            align-items: center;
            flex-grow: 1;
        }
        .team-info {
            flex: 1;
            display: flex;
            align-items: center;
        }
        .team-info.home {
            justify-content: flex-end;
            text-align: right;
        }
        .team-info.away {
            justify-content: flex-start;
            text-align: left;
        }
        .team-name {
            font-weight: 500;
            margin: 0 5px;
        }
        .score-box {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-basis: 70px;
            flex-shrink: 0;
            font-weight: bold;
        }
        .score {
            font-size: 1em;
            color: #1a252f;
            padding: 2px 5px;
            background-color: #f0f0f0;
            border-radius: 3px;
            min-width: 20px;
            text-align: center;
        }
        .vs {
            font-size: 0.9em;
            color: #7f8c8d;
            margin: 0 4px;
        }
        .error-message {
            color: #c0392b;
            text-align: center;
            padding: 15px;
            background: #fadbd8;
            border: 1px solid #f5b7b1;
            border-radius: 8px;
            margin: 15px;
        }
        .cache-info {
            text-align: center;
            color: #7f8c8d;
            font-size: 0.85em;
            margin-top: 10px;
            padding: 8px;
            background-color: #f9f9f9;
            border-top: 1px solid #eee;
        }
        @media (max-width: 600px) {
            .container { padding: 10px; margin: 10px auto; }
            .header h1 { font-size: 1.5em; }
            .jogo-item { flex-wrap: wrap; font-size: 0.85em; }
            .status { flex-basis: 100%; margin-bottom: 5px; text-align: center; }
            .match-details { flex-basis: 100%; justify-content: space-around; }
            .team-name { font-size: 0.95em; }
            .score-box { flex-basis: 60px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Livescore - Placar de Futebol</h1>
        </div>
        <div class="nav-tabs">
            <button class="active">Todos</button>
        </div>
        <div class="tab-content">
            <?php if (!empty($dados['error'])): ?>
                <div class="error-message"><?php echo htmlspecialchars($dados['error']); ?></div>
            <?php endif; ?>

            <?php if (empty($dados['error']) && empty($dados['secoes'])): ?>
                <div class="error-message">Nenhum jogo disponível no momento ou o site de origem não retornou dados.</div>
            <?php elseif (!empty($dados['secoes'])): ?>
                <?php foreach ($dados['secoes'] as $secao): ?>
                    <div class="secao-card">
                        <div class="secao-title"><?php echo htmlspecialchars($secao['titulo']); ?></div>
                        <div class="jogo-list">
                            <?php if (empty($secao['jogos'])): ?>
                                <div style="padding: 10px; text-align: center; color: #7f8c8d;">Nenhum jogo disponível para esta seção.</div>
                            <?php else: ?>
                                <?php foreach ($secao['jogos'] as $jogo): ?>
                                    <?php
                                        $statusLimp = preg_replace('/[^a-zA-Z0-9\s-]/', '', $jogo['status']); // Remove caracteres especiais
                                        $statusLimp = str_replace(' ', '-', strtolower(trim($statusLimp))); // minúsculas, troca espaço por hífen
                                        $statusClass = 'status-' . $statusLimp;
                                    ?>
                                    <div class="jogo-item">
                                        <div class="status <?php echo htmlspecialchars($statusClass); ?>">
                                            <?php echo htmlspecialchars($jogo['status']); ?>
                                        </div>
                                        <div class="match-details">
                                            <div class="team-info home">
                                                <span class="team-name"><?php echo htmlspecialchars($jogo['time1']); ?></span>
                                            </div>
                                            <div class="score-box">
                                                <span class="score"><?php echo htmlspecialchars($jogo['placar1']); ?></span>
                                                <span class="vs">x</span>
                                                <span class="score"><?php echo htmlspecialchars($jogo['placar2']); ?></span>
                                            </div>
                                            <div class="team-info away">
                                                <span class="team-name"><?php echo htmlspecialchars($jogo['time2']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($dados['cache_source']): ?>
                <div class="cache-info">
                    <?php
                    $cacheFile = 'livescore_cache.json';
                    if (file_exists($cacheFile)) {
                        echo 'Dados carregados do cache (gerado em: ' . date("d/m/Y H:i:s", filemtime($cacheFile)) . ')';
                    } else {
                        echo 'Dados carregados do cache.';
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        const tabButtons = document.querySelectorAll('.nav-tabs button');
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                tabButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
            });
        });
    </script>
</body>
</html>