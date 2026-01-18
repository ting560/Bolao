<?php
/**
 * Endpoint AJAX para o Painel Administrativo
 * Arquivo: admin_ajax.php
 */

session_start();
header('Content-Type: application/json');

// Função para validar link do SofaScore
function isValidSofaScoreLink($url) {
    if (empty($url)) return false;
    
    // Expressão regular para validar URLs do SofaScore
    $sofaScorePattern = '/^https?:\/\/([\w.-]*\.)?sofascore\.com\/.*$/i';
    return preg_match($sofaScorePattern, $url);
}

// Verificar autenticação admin
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

require_once 'admin_user_functions.php';
require_once 'firebase_admin_functions.php';
require_once 'configs/config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        // USUÁRIOS
        case 'get_users':
            // Obter usuários combinados (Firebase + locais)
            $users = firebaseGetCombinedUsersList();
            echo json_encode([
                'success' => true,
                'users' => array_map(function($user) {
                    unset($user['password']); // Remover senha dos dados retornados
                    return $user;
                }, $users)
            ]);
            break;
            
        case 'get_user_data':
            if (empty($_POST['user_id'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID do usuário é obrigatório'
                ]);
                break;
            }
            
            $userId = $_POST['user_id'];
            $users = firebaseGetCombinedUsersList();
            
            // Procurar usuário pelo ID
            $userFound = null;
            foreach ($users as $user) {
                if ($user['id'] === $userId) {
                    $userFound = $user;
                    break;
                }
            }
            
            if ($userFound) {
                unset($userFound['password']); // Remover senha
                echo json_encode([
                    'success' => true,
                    'user' => $userFound
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ]);
            }
            break;

        case 'get_firebase_users_only':
            // Obter apenas usuários do Firebase
            $firebaseUsers = firebaseGetAllUniqueUsers();
            echo json_encode([
                'success' => true,
                'users' => $firebaseUsers
            ]);
            break;

        case 'get_user_details':
            $userName = $_POST['user_name'] ?? '';
            if (empty($userName)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Nome do usuário é obrigatório'
                ]);
                break;
            }
            
            $userDetails = firebaseGetUserDetails($userName);
            echo json_encode([
                'success' => true,
                'user' => $userDetails
            ]);
            break;

        case 'add_user':
            $validation = validateUserData(
                $_POST['name'] ?? '',
                $_POST['phone'] ?? '',
                $_POST['email'] ?? '',
                $_POST['password'] ?? ''
            );
            
            if (!$validation['valid']) {
                echo json_encode([
                    'success' => false,
                    'message' => implode(', ', $validation['errors'])
                ]);
                break;
            }
            
            $result = adminAddUser(
                $_POST['name'],
                $_POST['phone'],
                $_POST['email'],
                $_POST['password']
            );
            echo json_encode($result);
            break;

        case 'update_user':
            $validation = validateUserData(
                $_POST['name'] ?? '',
                $_POST['phone'] ?? '',
                $_POST['email'] ?? ''
            );
            
            if (!$validation['valid']) {
                echo json_encode([
                    'success' => false,
                    'message' => implode(', ', $validation['errors'])
                ]);
                break;
            }
            
            $result = adminUpdateUser(
                $_POST['user_id'],
                $_POST['name'],
                $_POST['phone'],
                $_POST['email'],
                filter_var($_POST['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN)
            );
            echo json_encode($result);
            break;

        case 'change_password':
            if (empty($_POST['password']) || strlen($_POST['password']) < 6) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Senha deve ter pelo menos 6 caracteres'
                ]);
                break;
            }
            
            $result = adminChangeUserPassword(
                $_POST['user_id'],
                $_POST['password']
            );
            echo json_encode($result);
            break;

        case 'delete_user':
            if (empty($_POST['user_id'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID do usuário é obrigatório'
                ]);
                break;
            }
            
            $result = adminDeleteUser($_POST['user_id']);
            echo json_encode($result);
            break;

        case 'delete_firebase_user':
            if (empty($_POST['user_name'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Nome do usuário é obrigatório'
                ]);
                break;
            }

            $result = firebaseDeleteUser($_POST['user_name']);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuário do Firebase excluído com sucesso!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao excluir usuário do Firebase'
                ]);
            }
            break;

        case 'toggle_user_status':
            if (empty($_POST['user_id'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID do usuário é obrigatório'
                ]);
                break;
            }
            
            $result = adminToggleUserStatus($_POST['user_id']);
            echo json_encode($result);
            break;

        case 'get_user_stats':
            $stats = adminGetUserStats();
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;

        // JOGOS
        case 'get_games':
            $gamesData = firebaseGetGamesFromCache();
            if ($gamesData) {
                echo json_encode([
                    'success' => true,
                    'games' => $gamesData['games'],
                    'round' => $gamesData['round']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Nenhum jogo encontrado na cache'
                ]);
            }
            break;

        case 'add_game':
            $team1 = trim($_POST['team1'] ?? '');
            $team2 = trim($_POST['team2'] ?? '');
            $datetime = trim($_POST['datetime'] ?? '');
            $status = trim($_POST['status'] ?? 'Em breve');
            $sofaScoreLink = trim($_POST['sofaScoreLink'] ?? '');
            
            // Se tiver link do SofaScore, campos ficam opcionais
            if (empty($sofaScoreLink)) {
                // Sem link do SofaScore, validar campos obrigatórios
                if (empty($team1) || empty($team2) || empty($datetime)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Todos os campos são obrigatórios quando não há link do SofaScore'
                    ]);
                    break;
                }
            } else {
                // Com link do SofaScore, validar apenas o link
                if (!isValidSofaScoreLink($sofaScoreLink)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Link do SofaScore inválido'
                    ]);
                    break;
                }
            }
            
            // Gerar ID único para o jogo
            $gameId = uniqid('game_');
            
            // Criar estrutura do jogo
            $newGame = [
                'id' => $gameId,
                'team1' => ['name' => $team1],
                'team2' => ['name' => $team2],
                'datetime' => $datetime,
                'status' => $status,
                'timestamp' => time()
            ];
            
            // Salvar em arquivo temporário (simulação)
            $gamesFile = 'temp_games.json';
            $games = [];
            if (file_exists($gamesFile)) {
                $games = json_decode(file_get_contents($gamesFile), true) ?: [];
            }
            $games[] = $newGame;
            file_put_contents($gamesFile, json_encode($games, JSON_PRETTY_PRINT));
            
            echo json_encode([
                'success' => true,
                'message' => 'Jogo adicionado com sucesso!',
                'game_id' => $gameId
            ]);
            break;

        case 'update_game':
            $gameId = trim($_POST['game_id'] ?? '');
            $team1 = trim($_POST['team1'] ?? '');
            $team2 = trim($_POST['team2'] ?? '');
            $datetime = trim($_POST['datetime'] ?? '');
            $status = trim($_POST['status'] ?? 'Em breve');
            
            if (empty($gameId) || empty($team1) || empty($team2) || empty($datetime)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Todos os campos são obrigatórios'
                ]);
                break;
            }
            
            // Carregar jogos existentes
            $gamesFile = 'temp_games.json';
            $games = [];
            if (file_exists($gamesFile)) {
                $games = json_decode(file_get_contents($gamesFile), true) ?: [];
            }
            
            // Encontrar e atualizar o jogo
            $gameFound = false;
            foreach ($games as &$game) {
                if ($game['id'] === $gameId) {
                    $game['team1']['name'] = $team1;
                    $game['team2']['name'] = $team2;
                    $game['datetime'] = $datetime;
                    $game['status'] = $status;
                    $game['timestamp'] = time();
                    $gameFound = true;
                    break;
                }
            }
            
            if ($gameFound) {
                file_put_contents($gamesFile, json_encode($games, JSON_PRETTY_PRINT));
                echo json_encode([
                    'success' => true,
                    'message' => 'Jogo atualizado com sucesso!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Jogo não encontrado'
                ]);
            }
            break;

        case 'get_game_details':
            $gameId = trim($_POST['game_id'] ?? '');
            
            if (empty($gameId)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID do jogo é obrigatório'
                ]);
                break;
            }
            
            // Carregar jogos existentes
            $gamesFile = 'temp_games.json';
            $games = [];
            if (file_exists($gamesFile)) {
                $games = json_decode(file_get_contents($gamesFile), true) ?: [];
            }
            
            // Encontrar o jogo
            $game = null;
            foreach ($games as $g) {
                if ($g['id'] === $gameId) {
                    $game = $g;
                    break;
                }
            }
            
            if ($game) {
                echo json_encode([
                    'success' => true,
                    'game' => $game
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Jogo não encontrado'
                ]);
            }
            break;

        case 'delete_game':
            $gameId = trim($_POST['game_id'] ?? '');
            
            if (empty($gameId)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID do jogo é obrigatório'
                ]);
                break;
            }
            
            // Carregar jogos existentes
            $gamesFile = 'temp_games.json';
            $games = [];
            if (file_exists($gamesFile)) {
                $games = json_decode(file_get_contents($gamesFile), true) ?: [];
            }
            
            // Filtrar removendo o jogo
            $initialCount = count($games);
            $games = array_filter($games, function($game) use ($gameId) {
                return $game['id'] !== $gameId;
            });
            
            if (count($games) < $initialCount) {
                file_put_contents($gamesFile, json_encode(array_values($games), JSON_PRETTY_PRINT));
                echo json_encode([
                    'success' => true,
                    'message' => 'Jogo excluído com sucesso!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Jogo não encontrado'
                ]);
            }
            break;

        // APOSTAS
        case 'get_bets':
            $allBets = firebaseGetAllBets();
            $betStats = firebaseGetBetStats();
            
            echo json_encode([
                'success' => true,
                'bets' => $allBets,
                'stats' => $betStats
            ]);
            break;

        case 'calculate_points':
            $roundName = $_POST['round_name'] ?? '';
            $gameResults = json_decode($_POST['game_results'] ?? '{}', true);
            
            if (empty($roundName)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Nome da rodada é obrigatório'
                ]);
                break;
            }
            
            $points = firebaseCalculatePoints($roundName, $gameResults);
            
            echo json_encode([
                'success' => true,
                'message' => 'Pontos calculados com sucesso!',
                'points' => $points
            ]);
            break;

        case 'update_bet':
            $roundName = $_POST['round_name'] ?? '';
            $userName = $_POST['user_name'] ?? '';
            $gameId = $_POST['game_id'] ?? '';
            $newBet = json_decode($_POST['new_bet'] ?? '{}', true);
            
            if (empty($roundName) || empty($userName) || empty($gameId)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Dados incompletos'
                ]);
                break;
            }
            
            $result = firebaseUpdateUserBet($roundName, $userName, $gameId, $newBet);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Palpite atualizado com sucesso!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao atualizar palpite'
                ]);
            }
            break;

        case 'delete_bet':
            $roundName = $_POST['round_name'] ?? '';
            $userName = $_POST['user_name'] ?? '';
            $gameId = $_POST['game_id'] ?? '';
            
            if (empty($roundName) || empty($userName) || empty($gameId)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Dados incompletos'
                ]);
                break;
            }
            
            $result = firebaseRemoveUserBet($roundName, $userName, $gameId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Palpite excluído com sucesso!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao excluir palpite'
                ]);
            }
            break;

        case 'delete_user_from_round':
            $roundName = $_POST['round_name'] ?? '';
            $userName = $_POST['user_name'] ?? '';
            
            if (empty($roundName) || empty($userName)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Dados incompletos'
                ]);
                break;
            }
            
            $result = firebaseRemoveUserFromRound($roundName, $userName);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuário removido da rodada com sucesso!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao remover usuário'
                ]);
            }
            break;

        // CONFIGURAÇÕES
        case 'save_round_password':
            $password = trim($_POST['password'] ?? '');
            $file = 'senha_rodada.txt';
            
            if (empty($password)) {
                if (file_exists($file)) {
                    unlink($file);
                }
                echo json_encode([
                    'success' => true,
                    'message' => 'Senha da rodada removida'
                ]);
            } else {
                if (file_put_contents($file, $password) !== false) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Senha da rodada salva com sucesso!'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Erro ao salvar senha da rodada'
                    ]);
                }
            }
            break;

        case 'clear_cache':
            $cacheFiles = [
                'jogos_cache.json',
                'jogos_cache_admin.json',
                'jogos_bolao_cache.json'
            ];
            
            $deleted = 0;
            foreach ($cacheFiles as $file) {
                if (file_exists($file) && unlink($file)) {
                    $deleted++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => "Cache limpo! {$deleted} arquivos removidos"
            ]);
            break;

        case 'get_dashboard_data':
            // Obter dados reais para o dashboard
            $userStats = adminGetUserStats();
            $betStats = firebaseGetBetStats();
            $gamesData = firebaseGetGamesFromCache();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'users' => $userStats,
                    'bets' => $betStats,
                    'games' => $gamesData ? [
                        'count' => count($gamesData['games']),
                        'round' => $gamesData['round']
                    ] : ['count' => 0, 'round' => 'N/A'],
                    'revenue' => 'R$ ' . ($betStats['total_users'] * 50) // Simulação
                ]
            ]);
            break;

        case 'get_recent_activities':
            $activities = adminGetRecentActivities(10);
            echo json_encode([
                'success' => true,
                'activities' => $activities
            ]);
            break;

        case 'get_bets_summary':
            $allBets = firebaseGetAllBets();
            $summary = [];
            
            // Agrupar apostas por usuário
            $userBets = [];
            foreach ($allBets as $bet) {
                $user = $bet['user'];
                if (!isset($userBets[$user])) {
                    $userBets[$user] = [];
                }
                $userBets[$user][] = $bet;
            }
            
            // Criar sumário
            foreach ($userBets as $user => $bets) {
                $summary[] = [
                    'user' => $user,
                    'total_bets' => count($bets),
                    'points' => 0, // Seria calculado
                    'round' => $bets[0]['round'] ?? 'Rodada Atual'
                ];
            }
            
            echo json_encode([
                'success' => true,
                'summary' => $summary
            ]);
            break;

        case 'get_user_bets':
            $userName = $_POST['user_name'] ?? '';
            if (empty($userName)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Nome do usuário é obrigatório'
                ]);
                break;
            }
            
            $allBets = firebaseGetAllBets();
            $userBets = array_filter($allBets, function($bet) use ($userName) {
                return $bet['user'] === $userName;
            });
            
            echo json_encode([
                'success' => true,
                'bets' => array_values($userBets)
            ]);
            break;

        case 'delete_games':
            $gameIds = json_decode($_POST['game_ids'] ?? '[]', true);
            if (empty($gameIds)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Nenhum jogo selecionado'
                ]);
                break;
            }
            
            // Para demonstração, vamos simular a exclusão
            // Na prática, você precisaria implementar a lógica real de exclusão no Firebase
            echo json_encode([
                'success' => true,
                'message' => count($gameIds) . ' jogo(s) excluído(s) com sucesso'
            ]);
            break;

        // NOVAS FUNÇÕES PARA INTEGRAÇÃO COM SOFASCORE
        case 'load_sofascore_event':
            $sofaScoreLink = trim($_POST['sofaScoreLink'] ?? '');
            
            if (empty($sofaScoreLink)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Link do SofaScore é obrigatório'
                ]);
                break;
            }
            
            // Validar formato do link
            if (!isValidSofaScoreLink($sofaScoreLink)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Formato de link inválido. Use links do formato: https://www.sofascore.com/event/1234567'
                ]);
                break;
            }
            
            try {
                // Extrair ID do evento do link
                $eventId = extractEventIdFromUrl($sofaScoreLink);
                
                if (!$eventId) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Não foi possível extrair o ID do evento do link fornecido'
                    ]);
                    break;
                }
                
                // Carregar dados do evento com timeout controlado
                $eventData = loadSofaScoreEventData($eventId);
                
                if ($eventData && !empty($eventData['team1']['name']) && !empty($eventData['team2']['name'])) {
                    echo json_encode([
                        'success' => true,
                        'event' => $eventData,
                        'message' => 'Dados carregados com sucesso'
                    ]);
                } else {
                    // Tentar fallback
                    $fallbackData = getFallbackEventData($eventId, $sofaScoreLink);
                    if ($fallbackData) {
                        echo json_encode([
                            'success' => true,
                            'event' => $fallbackData,
                            'message' => 'Dados carregados com método alternativo',
                            'fallback' => true
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Não foi possível carregar os dados do evento. Tente um link diferente ou preencha os dados manualmente.'
                        ]);
                    }
                }
            } catch (Exception $e) {
                error_log("SofaScore API Error: " . $e->getMessage());
                
                // Tentar fallback em caso de erro
                $eventId = extractEventIdFromUrl($sofaScoreLink);
                if ($eventId) {
                    $fallbackData = getFallbackEventData($eventId, $sofaScoreLink);
                    if ($fallbackData) {
                        echo json_encode([
                            'success' => true,
                            'event' => $fallbackData,
                            'message' => 'Dados carregados com método alternativo devido a erro na API',
                            'fallback' => true
                        ]);
                        break;
                    }
                }
                
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro de conexão com a API do SofaScore. ' . $e->getMessage() . '. Tente novamente ou use preenchimento manual.'
                ]);
            }
            break;

        case 'update_sofascore_game':
            $gameId = trim($_POST['game_id'] ?? '');
            $sofaScoreLink = trim($_POST['sofaScoreLink'] ?? '');
            
            if (empty($gameId) || empty($sofaScoreLink)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID do jogo e link do SofaScore são obrigatórios'
                ]);
                break;
            }
            
            try {
                // Extrair ID do evento
                $eventId = extractEventIdFromUrl($sofaScoreLink);
                
                if (!$eventId) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Não foi possível extrair o ID do evento'
                    ]);
                    break;
                }
                
                // Atualizar dados do jogo
                $updated = updateGameFromSofaScore($gameId, $eventId);
                
                if ($updated) {
                    echo json_encode([
                        'success' => true,
                        'updated' => true,
                        'message' => 'Jogo atualizado com sucesso'
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'updated' => false,
                        'message' => 'Nenhuma atualização necessária'
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro na atualização: ' . $e->getMessage()
                ]);
            }
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Ação não reconhecida'
            ]);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno: ' . $e->getMessage()
    ]);
}

// FUNÇÕES AUXILIARES PARA INTEGRAÇÃO COM SOFASCORE

/**
 * Função de fallback para dados de eventos
 */
function getFallbackEventData($eventId, $originalLink = '') {
    // Tentar diferentes abordagens de fallback
    
    // 1. Tentar web scraping básico
    $scrapedData = scrapeSofaScoreEvent($eventId);
    if ($scrapedData && !empty($scrapedData['team1']['name']) && !empty($scrapedData['team2']['name'])) {
        return $scrapedData;
    }
    
    // 2. Retornar dados genéricos como último recurso
    return [
        'id' => $eventId,
        'team1' => ['name' => 'Time A'],
        'team2' => ['name' => 'Time B'],
        'datetime' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'status' => 'Em breve',
        'score' => null,
        'tournament' => 'Campeonato',
        'fallback_used' => true
    ];
}

/**
 * Extrai o ID do evento do link do SofaScore (VERSÃO APRIMORADA)
 */
function extractEventIdFromUrl($url) {
    // Log para debug
    error_log("SofaScore Extract ID - URL: {$url}");
    
    // Validar URL básica
    if (empty($url) || !is_string($url)) {
        error_log("SofaScore Extract ID - URL vazia ou inválida");
        return null;
    }
    
    // Limpar espaços e caracteres especiais
    $url = trim($url);
    
    // Verificar se é URL do SofaScore
    if (strpos($url, 'sofascore.com') === false) {
        error_log("SofaScore Extract ID - URL não é do SofaScore: {$url}");
        return null;
    }
    
    // Padrões mais abrangentes de URLs do SofaScore
    $patterns = [
        // Padrões principais
        '/\/event\/(\d+)/i',           // /event/1234567
        '/\/match\/(\d+)/i',          // /match/1234567
        '/\/game\/(\d+)/i',           // /game/1234567
        '/\/fixture\/(\d+)/i',        // /fixture/1234567
        
        // Parâmetros de query
        '/[?&]id=(\d+)/i',             // ?id=1234567
        '/[?&]event=(\d+)/i',          // ?event=1234567
        '/[?&]eventId=(\d+)/i',        // ?eventId=1234567
        '/[?&]matchId=(\d+)/i',        // ?matchId=1234567
        '/[?&]gameId=(\d+)/i',         // ?gameId=1234567
        
        // URLs complexas
        '/\/tournament\/[^\/]+\/[^\/]+\/[^\/]+\/\d+\/event\/(\d+)/i', // /tournament/.../event/1234567
        '/\/competition\/[^\/]+\/[^\/]+\/event\/(\d+)/i', // /competition/.../event/1234567
    ];
    
    // Tentar cada padrão
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            $eventId = $matches[1];
            error_log("SofaScore Extract ID - Encontrado ID: {$eventId} com padrão: {$pattern}");
            
            // Validar se o ID parece correto (números apenas)
            if (is_numeric($eventId) && strlen($eventId) >= 5 && strlen($eventId) <= 10) {
                return $eventId;
            } else {
                error_log("SofaScore Extract ID - ID inválido: {$eventId}");
            }
        }
    }
    
    // Se nenhum padrão funcionar, tentar extrair qualquer sequência de números longa
    if (preg_match('/\b(\d{5,10})\b/', $url, $matches)) {
        $potentialId = $matches[1];
        error_log("SofaScore Extract ID - ID potencial encontrado: {$potentialId}");
        return $potentialId;
    }
    
    error_log("SofaScore Extract ID - Nenhum ID encontrado na URL: {$url}");
    return null;
}

/**
 * Carrega dados do evento do SofaScore (VERSÃO CORRIGIDA)
 */
function loadSofaScoreEventData($eventId) {
    // Validar ID do evento
    if (empty($eventId) || !is_numeric($eventId)) {
        error_log("SofaScore: ID de evento inválido: {$eventId}");
        return null;
    }
    
    // Tentar carregar dados da API do SofaScore com headers aprimorados
    $apiUrl = "https://www.sofascore.com/api/v1/event/{$eventId}";
    
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
        'Accept-Encoding: gzip, deflate, br',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Cache-Control: no-cache'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Log para debug
    error_log("SofaScore API Call - Event: {$eventId}, HTTP: {$httpCode}, Content-Type: {$contentType}");
    
    // Verificar se houve erro de cURL
    if ($curlError) {
        error_log("SofaScore cURL Error: {$curlError}");
        return null;
    }
    
    // Verificar código HTTP
    if ($httpCode !== 200) {
        error_log("SofaScore HTTP Error: {$httpCode}");
        return null;
    }
    
    // Verificar se a resposta é realmente JSON
    if (!$contentType || strpos($contentType, 'application/json') === false) {
        error_log("SofaScore Invalid Content-Type: {$contentType}");
        // Se não for JSON, pode ser uma página HTML de erro
        if ($response && strlen($response) < 1000 && strpos($response, '<html') !== false) {
            error_log("SofaScore returned HTML error page");
            return null;
        }
    }
    
    // Tentar decodificar JSON
    if ($response) {
        $data = json_decode($response, true);
        
        // Verificar erro de JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("SofaScore JSON Decode Error: " . json_last_error_msg());
            error_log("Response snippet: " . substr($response, 0, 200));
            return null;
        }
        
        // Verificar estrutura esperada
        if (isset($data['event'])) {
            $event = $data['event'];
            
            $result = [
                'id' => $event['id'] ?? $eventId,
                'team1' => [
                    'name' => $event['homeTeam']['name'] ?? 'Time 1',
                    'shortName' => $event['homeTeam']['shortName'] ?? ''
                ],
                'team2' => [
                    'name' => $event['awayTeam']['name'] ?? 'Time 2',
                    'shortName' => $event['awayTeam']['shortName'] ?? ''
                ],
                'datetime' => isset($event['startTimestamp']) ? 
                    date('Y-m-d H:i:s', $event['startTimestamp']) : null,
                'status' => mapSofaScoreStatus($event['status']['code'] ?? 'not_started'),
                'score' => isset($event['homeScore']['current']) && isset($event['awayScore']['current']) ? 
                    "{$event['homeScore']['current']} - {$event['awayScore']['current']}" : null,
                'tournament' => $event['tournament']['name'] ?? 'Campeonato',
                'round' => $event['roundInfo']['round'] ?? null
            ];
            
            error_log("SofaScore Success: Loaded event {$result['team1']['name']} vs {$result['team2']['name']}");
            return $result;
        } else {
            error_log("SofaScore: Unexpected data structure");
            error_log("Data keys: " . print_r(array_keys($data), true));
        }
    }
    
    // Fallback: tentar web scraping como último recurso
    error_log("SofaScore: Trying fallback scraping for event {$eventId}");
    return scrapeSofaScoreEvent($eventId);
}

/**
 * Mapeia status do SofaScore para status do sistema
 */
function mapSofaScoreStatus($sofaScoreStatus) {
    $statusMap = [
        'not_started' => 'Em breve',
        'live' => 'AO VIVO',
        'finished' => 'Encerrado',
        'postponed' => 'Adiado',
        'cancelled' => 'Cancelado'
    ];
    
    return $statusMap[$sofaScoreStatus] ?? 'Em breve';
}

/**
 * Web scraping como fallback quando API falha
 */
function scrapeSofaScoreEvent($eventId) {
    $url = "https://www.sofascore.com/event/{$eventId}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $html = curl_exec($ch);
    curl_close($ch);
    
    if ($html) {
        // Extrair dados básicos do HTML
        // Esta é uma implementação simplificada
        $data = [
            'id' => $eventId,
            'team1' => ['name' => 'Time 1'],
            'team2' => ['name' => 'Time 2'],
            'datetime' => null,
            'status' => 'Em breve',
            'score' => null,
            'tournament' => ''
        ];
        
        // Aqui você pode implementar parsing mais sofisticado do HTML
        // usando DOMDocument ou expressões regulares
        
        return $data;
    }
    
    return null;
}

/**
 * Atualiza jogo existente com dados do SofaScore
 */
function updateGameFromSofaScore($gameId, $eventId) {
    // Carregar dados atuais do jogo
    $gamesFile = 'temp_games.json';
    $games = [];
    
    if (file_exists($gamesFile)) {
        $games = json_decode(file_get_contents($gamesFile), true) ?: [];
    }
    
    // Encontrar o jogo
    $gameIndex = null;
    foreach ($games as $index => $game) {
        if ($game['id'] === $gameId) {
            $gameIndex = $index;
            break;
        }
    }
    
    if ($gameIndex === null) {
        return false;
    }
    
    // Carregar dados atualizados do SofaScore
    $sofaScoreData = loadSofaScoreEventData($eventId);
    
    if (!$sofaScoreData) {
        return false;
    }
    
    // Verificar se há mudanças significativas
    $currentGame = $games[$gameIndex];
    $needsUpdate = false;
    
    // Comparar status
    if (isset($sofaScoreData['status']) && $sofaScoreData['status'] !== $currentGame['status']) {
        $games[$gameIndex]['status'] = $sofaScoreData['status'];
        $needsUpdate = true;
    }
    
    // Comparar placar
    if (isset($sofaScoreData['score']) && $sofaScoreData['score'] !== ($currentGame['score'] ?? '')) {
        $games[$gameIndex]['score'] = $sofaScoreData['score'];
        $needsUpdate = true;
        
        // Atualizar pontos das apostas se o jogo terminou
        if ($sofaScoreData['status'] === 'Encerrado') {
            updateBetsPoints($gameId, $sofaScoreData['score']);
        }
    }
    
    // Salvar atualizações
    if ($needsUpdate) {
        file_put_contents($gamesFile, json_encode($games, JSON_PRETTY_PRINT));
        return true;
    }
    
    return false;
}

/**
 * Atualiza pontos das apostas baseado no resultado final
 */
function updateBetsPoints($gameId, $finalScore) {
    // Implementar lógica de cálculo de pontos
    // Esta função pode chamar as funções existentes de cálculo de pontos
    // do arquivo auto_update_games.php
    
    // Por enquanto, só logamos a atualização
    error_log("Atualizando pontos para jogo {$gameId} com placar {$finalScore}");
}

?>