<?php
// As diretivas ini_set de display_errors são melhor controladas no arquivo principal (index.php)
// ou diretamente no php.ini do servidor. Mantenha-as comentadas aqui.
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Configurações do Firebase
define('FIREBASE_URL', 'https://bolao-novo-2025-default-rtdb.firebaseio.com/');

// Configurações de cache
define('CACHE_FILE', 'jogos_cache.json');
define('CACHE_TIME', 300); // 5 minutos (5 minutos, para não sobrecarregar o site)

// URL da página HTML do Super Placar para scraping
define('SCRAPING_URL', 'https://superplacar.com.br/campeonato/2/brasileirao-serie-a/');

// Placeholder para logos
define('PLACEHOLDER_LOGO_URL', 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAiIGhlaWdodD0iMzAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjMwIiBoZWlnaHQ9IjMwIiBmaWxsPSIjZWVlIi8+PHRleHQgeD0iNTAuNSUiIHk9IjUwJSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTJweCIgZmlsbD0iI2NjYyI+PzwvdGV4dD48L3N2Zz4=');

/**
 * Função auxiliar para gerar uma abreviação de 3 letras para o nome do time.
 * Tenta usar regras comuns, senão pega as 3 primeiras letras.
 */
function getTeamAbbreviation($teamName) {
    $teamName = mb_strtoupper(trim($teamName), 'UTF-8'); // Converte para maiúsculas
    // Normaliza caracteres acentuados
    $teamName = str_replace(array('Á', 'À', 'Ã', 'Â', 'Ä'), 'A', $teamName);
    $teamName = str_replace(array('É', 'È', 'Ẽ', 'Ê', 'Ë'), 'E', $teamName);
    $teamName = str_replace(array('Í', 'Ì', 'Ĩ', 'Î', 'Ï'), 'I', $teamName);
    $teamName = str_replace(array('Ó', 'Ò', 'Õ', 'Ô', 'Ö'), 'O', $teamName);
    $teamName = str_replace(array('Ú', 'Ù', 'Ũ', 'Û', 'Ü'), 'U', $teamName);
    $teamName = str_replace(array('Ç'), 'C', $teamName);
    $teamName = str_replace(array('`', '´', '~', '^', '¨', '.'), '', $teamName); // Remove outros acentos e pontos

    // Mapeamento manual para abreviações comuns e problemáticas (ordem importa!)
    $manualAbbreviations = [
        'FLAMENGO' => 'FLA',
        'CORINTHIANS' => 'COR',
        'PALMEIRAS' => 'PAL',
        'SAO PAULO' => 'SAO', // ou SPFC
        'GRÊMIO' => 'GRE',
        'ATLETICO-MG' => 'CAM', // Atlético Mineiro
        'ATLÉTICO-MG' => 'CAM',
        'ATLETICO-GO' => 'AGO', // Atlético Goianiense
        'ATLÉTICO-GO' => 'AGO',
        'ATHLETICO-PR' => 'CAP', // Athletico Paranaense
        'INTERNACIONAL' => 'INT',
        'CRUZEIRO' => 'CRU',
        'FLUMINENSE' => 'FLU',
        'VASCO' => 'VAS',
        'BOTAFOGO' => 'BOT',
        'SANTOS' => 'SAN',
        'BAHIA' => 'BAH',
        'SPORT' => 'SPO',
        'FORTALEZA' => 'FOR',
        'CEARA' => 'CEA',
        'GOIAS' => 'GOI',
        'RED BULL BRAGANTINO' => 'RBB', // Especifico para o nome completo do Bragantino
        'BRAGANTINO' => 'BGT', // Para casos onde vem só Bragantino
        'JUVENTUDE' => 'JUV',
        'CUIABA' => 'CUI',
        'AMERICA-MG' => 'AMG', // América Mineiro
        'VITÓRIA' => 'VIT',
        'GUARANI' => 'GUA',
        'PONTE PRETA' => 'PON',
        'CHAPECOENSE' => 'CHA',
        'AVAI' => 'AVA',
        'CORITIBA' => 'CFC',
        'MIRASSOL' => 'MIR',
    ];

    if (isset($manualAbbreviations[$teamName])) {
        return $manualAbbreviations[$teamName];
    }
    
    // Tenta pegar a primeira letra de cada palavra, até 3 letras (para nomes compostos)
    $words = explode(' ', $teamName);
    $abbrev = '';
    foreach ($words as $word) {
        if (!empty($word) && strlen($abbrev) < 3) {
            $abbrev .= substr($word, 0, 1);
        }
    }
    if (strlen($abbrev) == 3) {
        return $abbrev;
    }

    // Fallback: primeiras 3 letras do nome original (se não tiver palavras suficientes ou não der 3)
    return mb_substr($teamName, 0, 3, 'UTF-8');
}


function obterDadosPlacar($forceUpdate = false) {
    $dadosRetorno = ['rodada' => 'Rodada Indisponível', 'jogos' => [], 'error' => null, 'cache_source' => false];

    // Tenta carregar do cache, a menos que uma atualização forçada seja solicitada
    if (!$forceUpdate && file_exists(CACHE_FILE) && (time() - filemtime(CACHE_FILE)) < CACHE_TIME) {
        $cachedData = json_decode(file_get_contents(CACHE_FILE), true);
        if ($cachedData && empty($cachedData['error']) && !empty($cachedData['jogos'])) {
            $cachedData['cache_source'] = true;
            error_log("DEBUG PHP (config): Dados do cache usados.");
            return $cachedData;
        }
        @unlink(CACHE_FILE); // Cache corrompido ou vazio, remove
    }

    error_log("DEBUG PHP (config): Buscando dados do HTML: " . SCRAPING_URL);
    // Configura o contexto da stream para incluir um User-Agent e timeout
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.0.0 Safari/537.36\r\nAccept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7\r\n",
            'timeout' => 15
        ]
    ]);

    // Tenta obter o HTML da URL
    $html = @file_get_contents(SCRAPING_URL, false, $context);
    if ($html === false) {
        $dadosRetorno['error'] = 'Erro ao acessar o site Super Placar. (Verifique sua conexão ou se o site está online).';
        // Tenta usar o cache antigo como fallback se a busca falhar
        if (file_exists(CACHE_FILE)) {
            $fallbackData = json_decode(file_get_contents(CACHE_FILE), true);
            if ($fallbackData && empty($fallbackData['error']) && !empty($fallbackData['jogos'])) {
                $fallbackData['cache_source'] = true;
                $fallbackData['error'] = $dadosRetorno['error'] . " (Cache usado como fallback)";
                return $fallbackData;
            }
        }
        return $dadosRetorno;
    }

    libxml_use_internal_errors(true); // Suprime erros de parsing HTML
    $doc = new DOMDocument();
    @$doc->loadHTML($html); // Carrega o HTML
    $xpath = new DOMXPath($doc); // Cria um objeto XPath para consultas

    // Extrai o nome da rodada (ou título da página de jogos)
    $rodadaNode = $xpath->query('//h2[contains(@class, "text-center") and contains(@class, "h5")]/small')->item(0);
    $dadosRetorno['rodada'] = ($rodadaNode !== null) ? trim(str_replace(['(', ')'], '', $rodadaNode->textContent)) : 'Rodada Atual';

    // Seleciona todos os nós de jogo com a classe "jogo" e atributo "data-id"
    $jogoNodes = $xpath->query('//div[contains(@class, "jogo") and @data-id]');
    if ($jogoNodes->length === 0) {
        $dadosRetorno['error'] = 'Nenhum jogo encontrado na página HTML.';
        return $dadosRetorno;
    }

    $baseUrlLogo = 'https://superplacar.com.br/imagem/times/';
    $jogos = [];
    foreach ($jogoNodes as $node) {
        $jogo = [];
        // Extrai o ID do jogo (compatível com PHP < 7.0)
        $jogo['id'] = $node->hasAttribute('data-id') ? $node->getAttribute('data-id') : uniqid('jogo_');

        // Extrai o status do jogo (compatível com PHP < 8.0)
        $statusNode = $xpath->query('.//span[contains(@class, "status")]', $node)->item(0);
        $jogo['status'] = ($statusNode !== null) ? trim($statusNode->textContent) : 'N/A';
        
        // Extrai a data e horário do jogo (compatível com PHP < 8.0)
        $datetimeNode = $xpath->query('.//span[contains(@class, "data-horario")]', $node)->item(0);
        $jogo['datetime'] = ($datetimeNode !== null) ? trim($datetimeNode->textContent) : 'N/A';

        // Extrai informações do time 1 (logo e nome)
        $time1ImgNode = $xpath->query('.//div[contains(@class, "time-1")]//img', $node)->item(0);
        $time1LinkNode = $xpath->query('.//div[contains(@class, "time-1")]//a', $node)->item(0);
        
        $fullTeam1Name = ($time1LinkNode !== null) ? trim($time1LinkNode->textContent) : 'Time 1';
        $logo1_filename = ($time1ImgNode !== null) ? basename($time1ImgNode->getAttribute('src')) : '';

        $jogo['team1'] = [
            'logo' => $logo1_filename ? $baseUrlLogo . $logo1_filename : PLACEHOLDER_LOGO_URL,
            'name' => $fullTeam1Name, // Nome completo
            'abbrev' => getTeamAbbreviation($fullTeam1Name) // Abreviação
        ];

        // Extrai informações do time 2 (logo e nome)
        $time2ImgNode = $xpath->query('.//div[contains(@class, "time-2")]//img', $node)->item(0);
        $time2LinkNode = $xpath->query('.//div[contains(@class, "time-2")]//a', $node)->item(0);
        
        $fullTeam2Name = ($time2LinkNode !== null) ? trim($time2LinkNode->textContent) : 'Time 2';
        $logo2_filename = ($time2ImgNode !== null) ? basename($time2ImgNode->getAttribute('src')) : '';
        
        $jogo['team2'] = [
            'logo' => $logo2_filename ? $baseUrlLogo . $logo2_filename : PLACEHOLDER_LOGO_URL,
            'name' => $fullTeam2Name, // Nome completo
            'abbrev' => getTeamAbbreviation($fullTeam2Name) // Abreviação
        ];

        // Extrai o placar
        $scoreNode = $xpath->query(".//div[contains(@class, \"placar\")]", $node)->item(0);
        $score = "- x -"; // Placar padrão
        if ($scoreNode !== null) {
            $scoreText = trim($scoreNode->textContent);
            // Tenta extrair placar "NxN"
            if (preg_match("/(\\d+)\\s*x\\s*(\\d+)/i", $scoreText, $matches)) {
                $score = trim($matches[1]) . "x" . trim($matches[2]);
            } else {
                 // Fallback para pegar dois números se a primeira regex falhar
                 if (preg_match_all("/\\d+/", $scoreText, $numMatches)) {
                     if (count($numMatches[0]) >= 2) {
                         $score = $numMatches[0][0] . "x" . $numMatches[0][1];
                         error_log("DEBUG PHP (config): Placar extraído com regex alternativo para Jogo ID {$jogo["id"]}: '$score' (Texto original: '$scoreText')");
                     }
                 }
            }
        }
        $jogo["score"] = $score;

        // Adiciona um log se o jogo estiver "Encerrado" mas sem placar numérico
        if ($jogo["status"] === "Encerrado" && !preg_match("/^\d+x\d+$/", $score)) {
             error_log("ALERTA PHP (config): Jogo Encerrado (ID: {$jogo["id"]}) mas placar final não foi extraído como 'NxN'. Texto original: '$scoreText'");
        }

        // REMOVENDO a extração de gols_info para evitar a linha de comentário indesejada
        $jogo['gols_info'] = []; 

        // Extrai link de transmissão e texto
        $linkTransmissaoNode = $xpath->query('.//a[contains(@class, "jogo_transmissao--link")]', $node)->item(0);
        $jogo['linkTransmissao'] = ($linkTransmissaoNode !== null) ? $linkTransmissaoNode->getAttribute('href') : '';
        $jogo['textoLinkTransmissao'] = ($linkTransmissaoNode !== null) ? trim($linkTransmissaoNode->textContent) : '';
        // Se o texto estiver vazio mas o link existe, usa um texto padrão
        if (empty($jogo['textoLinkTransmissao']) && !empty($jogo['linkTransmissao'])) {
            $jogo['textoLinkTransmissao'] = 'Ver Transmissão';
        }


        // Adiciona o jogo à lista apenas se tiver nomes de times válidos
        if ($jogo['team1']['name'] !== 'Time 1' && $jogo['team2']['name'] !== 'Time 2') {
            $jogos[] = $jogo;
        }
    }

    if (!empty($jogos)) {
        $dadosRetorno['jogos'] = $jogos;
        // Salva no cache apenas se não houver erro na extração dos jogos
        if (empty($dadosRetorno['error'])) {
            file_put_contents(CACHE_FILE, json_encode($dadosRetorno));
        }
    } else {
        $dadosRetorno['error'] = 'Nenhum jogo válido encontrado na página HTML.';
    }
    libxml_clear_errors(); // Limpa quaisquer erros de parsing HTML
    return $dadosRetorno;
}

// Função para verificar se o jogo está no futuro (usada em aposta.php)
function isGameFuture($dateTimeString) {
    if (empty($dateTimeString) || $dateTimeString === 'N/A') {
        return true; // Se não tem data, assume que é futuro ou jogo não iniciado
    }
    // Tenta extrair o ano da string de data/hora. Se não encontrar, assume o ano atual.
    $currentYear = date('Y');
    // Adapta para o formato "DD/MM - HH:MM" e adiciona o ano atual para criar o objeto DateTime
    $dateTimeFullString = trim($dateTimeString);
    if (!strpos($dateTimeFullString, '/20')) { // Se o ano não estiver explicitamente na string (e.g., "24/05 - 18:30")
        $dateTimeObject = DateTime::createFromFormat('d/m - H:i', $dateTimeFullString);
        if ($dateTimeObject) {
            $dateTimeObject->setDate($currentYear, $dateTimeObject->format('m'), $dateTimeObject->format('d'));
        }
    } else { // Se o ano já estiver na string (e.g., "24/05/2025 - 18:30")
        $dateTimeObject = DateTime::createFromFormat('d/m/Y - H:i', $dateTimeFullString);
    }
    
    if ($dateTimeObject === false) {
        error_log("DEBUG PHP (config): Falha ao parsear datetime para isGameFuture: '$dateTimeString'. Assumindo jogo futuro.");
        return true; // Em caso de falha no parsing, assume que o jogo ainda não começou
    }
    return $dateTimeObject > new DateTime(); // Retorna true se a data do jogo for no futuro
}


// Função para salvar dados no Firebase (usada em aposta.php e admin.php)
function salvarApostaFirebase($rodadaNome, $apostadorNome, $dadosAposta) {
    // rawurlencode para garantir que nomes com caracteres especiais sejam corretamente codificados para a URL
    $path = 'apostas/' . rawurlencode($rodadaNome) . '/' . rawurlencode($apostadorNome) . '.json';
    $url = FIREBASE_URL . $path;

    error_log("DEBUG PHP (config): Salvando aposta para '$apostadorNome' em '$rodadaNome'. URL: $url");
    error_log("DEBUG PHP (config): Dados a salvar: " . json_encode($dadosAposta));

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Retorna a resposta como string
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT"); // Usa PUT para sobrescrever/atualizar dados no caminho exato
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dadosAposta)); // Envia os dados como JSON
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); // Define o cabeçalho Content-Type
    curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Timeout para a requisição
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Timeout para a conexão

    $response = curl_exec($ch); // Executa a requisição
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Obtém o código HTTP da resposta
    $curlError = curl_error($ch); // Obtém erros do cURL
    curl_close($ch); // Fecha a sessão cURL

    if ($curlError) {
        error_log("DEBUG PHP (config): Erro cURL: $curlError. URL: $url");
        return ['success' => false, 'message' => 'Erro de conexão: ' . $curlError];
    }

    error_log("DEBUG PHP (config): HTTP Code: $httpCode, Response: " . substr($response, 0, 500));

    // Verifica se o código HTTP indica sucesso (2xx)
    if ($httpCode >= 200 && $httpCode < 300) {
        return ['success' => true, 'message' => 'Aposta salva com sucesso!'];
    } else {
        $errorMessage = "Erro ao salvar no Firebase. Código HTTP: $httpCode";
        // Tenta obter detalhes do erro da resposta JSON do Firebase
        $responseData = json_decode($response, true);
        if ($responseData && isset($responseData['error'])) {
            $errorMessage .= ' Erro Firebase: ' . $responseData['error'];
        }
        error_log("DEBUG PHP (config): $errorMessage");
        return ['success' => false, 'message' => $errorMessage];
    }
}
?>