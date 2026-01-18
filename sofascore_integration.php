<?php
/**
 * Integração com API do SofaScore
 * Arquivo: sofascore_integration.php
 * 
 * Carrega jogos automaticamente da API do SofaScore
 */

require_once 'configs/config.php';
require_once 'firebase_admin_functions.php';

class SofaScoreIntegration {
    private $baseUrl = 'https://www.sofascore.com/api/v1';
    private $cacheFile = 'temp/sofascore_games.json';
    private $cacheTime = 60; // 1 minuto para atualização frequente
    
    /**
     * Obtém jogos programados para uma data específica
     */
    public function getScheduledEvents($date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        // Verificar cache primeiro
        $cachedData = $this->getCachedData($date);
        if ($cachedData && !$this->isCacheExpired($date)) {
            error_log("SOFA_API: Usando dados em cache para $date");
            return $cachedData;
        }
        
        try {
            $url = "{$this->baseUrl}/sport/football/scheduled-events/{$date}";
            
            $context = stream_context_create([
                'http' => [
                    'header' => [
                        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'Accept: application/json',
                        'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7'
                    ],
                    'timeout' => 15
                ]
            ]);
            
            error_log("SOFA_API: Buscando dados da API para $date");
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                error_log("SOFA_API: Erro ao acessar API do SofaScore");
                return $this->getFallbackData($date);
            }
            
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("SOFA_API: Erro ao decodificar JSON: " . json_last_error_msg());
                return $this->getFallbackData($date);
            }
            
            // Processar e formatar os dados
            $processedGames = $this->processSofaScoreData($data, $date);
            
            // Salvar no cache
            $this->saveToCache($date, $processedGames);
            
            error_log("SOFA_API: {$processedGames['count']} jogos carregados para $date");
            return $processedGames;
            
        } catch (Exception $e) {
            error_log("SOFA_API ERRO: " . $e->getMessage());
            return $this->getFallbackData($date);
        }
    }
    
    /**
     * Processa dados brutos da API do SofaScore
     */
    private function processSofaScoreData($rawData, $date) {
        $games = [];
        $count = 0;
        
        if (isset($rawData['events']) && is_array($rawData['events'])) {
            foreach ($rawData['events'] as $event) {
                // Filtrar apenas jogos de futebol
                if (isset($event['sport']['name']) && $event['sport']['name'] === 'Football') {
                    $game = $this->formatGame($event);
                    if ($game) {
                        $games[] = $game;
                        $count++;
                    }
                }
            }
        }
        
        return [
            'date' => $date,
            'games' => $games,
            'count' => $count,
            'source' => 'sofascore_api',
            'timestamp' => time()
        ];
    }
    
    /**
     * Formata um evento do SofaScore para o formato do sistema
     */
    private function formatGame($event) {
        try {
            // Extrair informações básicas
            $homeTeam = $event['homeTeam']['name'] ?? 'Time Casa';
            $awayTeam = $event['awayTeam']['name'] ?? 'Time Fora';
            
            // Formatar data/hora
            $startTime = $event['startTimestamp'] ?? time();
            $dateTime = date('d/m/Y - H:i', $startTime);
            
            // Status do jogo
            $status = $this->mapStatus($event['status']['type'] ?? 'notstarted');
            
            // Placar (se disponível)
            $score = '-';
            if (isset($event['homeScore']['current']) && isset($event['awayScore']['current'])) {
                $score = $event['homeScore']['current'] . 'x' . $event['awayScore']['current'];
            }
            
            return [
                'id' => 'sofa_' . ($event['id'] ?? uniqid()),
                'team1' => [
                    'name' => $homeTeam,
                    'logo' => $this->getTeamLogo($event['homeTeam']['id'] ?? 0)
                ],
                'team2' => [
                    'name' => $awayTeam,
                    'logo' => $this->getTeamLogo($event['awayTeam']['id'] ?? 0)
                ],
                'datetime' => $dateTime,
                'status' => $status,
                'score' => $score,
                'sofascore_id' => $event['id'] ?? null,
                'tournament' => $event['tournament']['name'] ?? 'Campeonato',
                'country' => $event['tournament']['category']['name'] ?? 'Brasil'
            ];
            
        } catch (Exception $e) {
            error_log("SOFA_API: Erro ao formatar jogo: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Mapeia status do SofaScore para status do sistema
     */
    private function mapStatus($sofaStatus) {
        $statusMap = [
            'notstarted' => 'Em breve',
            'inprogress' => 'AO VIVO',
            'finished' => 'Encerrado',
            'interrupted' => 'Interrompido',
            'postponed' => 'Adiado',
            'canceled' => 'Cancelado'
        ];
        
        return $statusMap[$sofaStatus] ?? 'Em breve';
    }
    
    /**
     * Obtém logo do time (placeholder por enquanto)
     */
    private function getTeamLogo($teamId) {
        // Futuramente pode ser integrado com API de logos
        return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAiIGhlaWdodD0iMzAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjMwIiBoZWlnaHQ9IjMwIiBmaWxsPSIjZWVlIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZvbnQtZmFtaWx5PSJzYW5zLXNlcmlmIiBmb250LXNpemU9IjEycHgiIGZpbGw9IiNjY2MiPlQ8L3RleHQ+PC9zdmc+';
    }
    
    /**
     * Verifica se o cache expirou
     */
    private function isCacheExpired($date) {
        $cacheFile = $this->getCacheFileName($date);
        if (!file_exists($cacheFile)) {
            return true;
        }
        
        return (time() - filemtime($cacheFile)) > $this->cacheTime;
    }
    
    /**
     * Obtém dados do cache
     */
    private function getCachedData($date) {
        $cacheFile = $this->getCacheFileName($date);
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            return $data ?: null;
        }
        return null;
    }
    
    /**
     * Salva dados no cache
     */
    private function saveToCache($date, $data) {
        $cacheFile = $this->getCacheFileName($date);
        file_put_contents($cacheFile, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    /**
     * Nome do arquivo de cache
     */
    private function getCacheFileName($date) {
        return "temp/sofascore_{$date}.json";
    }
    
    /**
     * Dados de fallback caso API falhe
     */
    private function getFallbackData($date) {
        error_log("SOFA_API: Usando dados de fallback para $date");
        
        // Retornar dados do cache principal se existirem
        $mainCache = firebaseGetGamesFromCache();
        if ($mainCache && !empty($mainCache['games'])) {
            return [
                'date' => $date,
                'games' => $mainCache['games'],
                'count' => count($mainCache['games']),
                'source' => 'main_cache_fallback',
                'timestamp' => time()
            ];
        }
        
        // Retornar array vazio se nada encontrado
        return [
            'date' => $date,
            'games' => [],
            'count' => 0,
            'source' => 'empty',
            'timestamp' => time()
        ];
    }
    
    /**
     * Atualiza jogos no sistema principal
     */
    public function updateMainGamesCache($date = null) {
        $sofaData = $this->getScheduledEvents($date);
        
        if (empty($sofaData['games'])) {
            error_log("SOFA_API: Nenhum jogo encontrado para atualizar cache principal");
            return false;
        }
        
        // Formatar para o formato do cache principal
        $mainCacheFormat = [
            'rodada' => "Jogos de " . date('d/m/Y', strtotime($sofaData['date'])),
            'jogos' => array_map(function($game) {
                return [
                    'id' => $game['id'],
                    'status' => $game['status'],
                    'datetime' => $game['datetime'],
                    'team1' => $game['team1'],
                    'team2' => $game['team2'],
                    'score' => $game['score']
                ];
            }, $sofaData['games']),
            'timestamp' => time(),
            'source' => 'sofascore_integration'
        ];
        
        // Salvar no cache principal
        if (file_put_contents(CACHE_FILE, json_encode($mainCacheFormat, JSON_PRETTY_PRINT))) {
            error_log("SOFA_API: Cache principal atualizado com {$sofaData['count']} jogos");
            return true;
        }
        
        error_log("SOFA_API: Erro ao atualizar cache principal");
        return false;
    }
}

// Função auxiliar para uso no sistema
function loadGamesFromSofaScore($date = null) {
    $integration = new SofaScoreIntegration();
    return $integration->getScheduledEvents($date);
}

// Endpoint para atualização automática
if (isset($_GET['update']) || php_sapi_name() === 'cli') {
    $integration = new SofaScoreIntegration();
    $date = $_GET['date'] ?? null;
    
    echo "Atualizando jogos do SofaScore para " . ($date ?: 'hoje') . "...\n";
    
    $result = $integration->updateMainGamesCache($date);
    
    if ($result) {
        $data = $integration->getScheduledEvents($date);
        echo "Sucesso! {$data['count']} jogos carregados.\n";
        echo "Fonte: {$data['source']}\n";
    } else {
        echo "Erro ao atualizar jogos.\n";
    }
}
?>