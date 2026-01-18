<?php
/**
 * Funções de Integração com Firebase para o Painel Administrativo
 * Arquivo: firebase_admin_functions.php
 */

require_once 'configs/config.php';
require_once 'auth_functions.php';

/**
 * Obtém dados do Firebase usando cURL
 */
function firebaseGetData($path) {
    $url = FIREBASE_URL . rtrim($path, '/') . '.json';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log("Firebase cURL Error for path '$path': " . $curlError);
        return null;
    }
    
    if ($httpCode != 200) {
        error_log("Firebase HTTP Error $httpCode for path '$path'");
        return null;
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Firebase JSON Decode Error for path '$path': " . json_last_error_msg());
        return null;
    }
    
    return $data;
}

/**
 * Salva dados no Firebase usando cURL
 */
function firebaseSaveData($path, $data) {
    $url = FIREBASE_URL . rtrim($path, '/') . '.json';
    $jsonData = json_encode($data);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log("Firebase Save cURL Error for path '$path': " . $curlError);
        return false;
    }
    
    if (!($httpCode == 200 || $httpCode == 201)) {
        error_log("Firebase Save HTTP Error $httpCode for path '$path'");
        return false;
    }
    
    return true;
}

/**
 * Obtém todas as rodadas com apostas do Firebase
 */
function firebaseGetAllRounds() {
    $data = firebaseGetData('apostas');
    if (!$data) return [];
    
    $rounds = [];
    foreach ($data as $roundName => $roundData) {
        $rounds[] = [
            'name' => $roundName,
            'participants' => count($roundData),
            'bets' => $roundData
        ];
    }
    
    return $rounds;
}

/**
 * Obtém todos os usuários únicos do Firebase (de todas as rodadas)
 */
function firebaseGetAllUniqueUsers() {
    $rounds = firebaseGetAllRounds();
    $uniqueUsers = [];
    
    foreach ($rounds as $round) {
        foreach ($round['bets'] as $userName => $userData) {
            if (!isset($uniqueUsers[$userName])) {
                $uniqueUsers[$userName] = [
                    'name' => $userName,
                    'first_seen' => null,
                    'last_activity' => null,
                    'total_bets' => 0,
                    'rounds_participated' => []
                ];
            }
            
            // Atualizar informações do usuário
            $uniqueUsers[$userName]['rounds_participated'][] = $round['name'];
            
            if (isset($userData['palpites'])) {
                $uniqueUsers[$userName]['total_bets'] += count($userData['palpites']);
            }
            
            if (isset($userData['timestamp_aposta'])) {
                $timestamp = $userData['timestamp_aposta'];
                if ($uniqueUsers[$userName]['first_seen'] === null || $timestamp < $uniqueUsers[$userName]['first_seen']) {
                    $uniqueUsers[$userName]['first_seen'] = $timestamp;
                }
                if ($uniqueUsers[$userName]['last_activity'] === null || $timestamp > $uniqueUsers[$userName]['last_activity']) {
                    $uniqueUsers[$userName]['last_activity'] = $timestamp;
                }
            }
        }
    }
    
    // Converter para array indexado
    return array_values($uniqueUsers);
}

/**
 * Obtém informações detalhadas de um usuário específico
 */
function firebaseGetUserDetails($userName) {
    $rounds = firebaseGetAllRounds();
    $userDetails = [
        'name' => $userName,
        'rounds' => [],
        'total_bets' => 0,
        'first_seen' => null,
        'last_activity' => null
    ];
    
    foreach ($rounds as $round) {
        if (isset($round['bets'][$userName])) {
            $userData = $round['bets'][$userName];
            
            $roundInfo = [
                'round_name' => $round['name'],
                'bets_count' => isset($userData['palpites']) ? count($userData['palpites']) : 0,
                'timestamp' => $userData['timestamp_aposta'] ?? null,
                'bets' => $userData['palpites'] ?? []
            ];
            
            $userDetails['rounds'][] = $roundInfo;
            $userDetails['total_bets'] += $roundInfo['bets_count'];
            
            if ($roundInfo['timestamp']) {
                if ($userDetails['first_seen'] === null || $roundInfo['timestamp'] < $userDetails['first_seen']) {
                    $userDetails['first_seen'] = $roundInfo['timestamp'];
                }
                if ($userDetails['last_activity'] === null || $roundInfo['timestamp'] > $userDetails['last_activity']) {
                    $userDetails['last_activity'] = $roundInfo['timestamp'];
                }
            }
        }
    }
    
    return $userDetails;
}

/**
 * Combina usuários do Firebase com usuários locais
 */
function firebaseGetCombinedUsersList() {
    // Obter usuários do Firebase
    $firebaseUsers = firebaseGetAllUniqueUsers();
    
    // Obter usuários locais
    $localUsers = loadUsers(); // Função do auth_functions.php
    
    // Criar mapa de usuários locais por email
    $localUsersByEmail = [];
    foreach ($localUsers as $localUser) {
        if (isset($localUser['email'])) {
            $localUsersByEmail[strtolower($localUser['email'])] = $localUser;
        }
    }
    
    // Combinar usuários
    $combinedUsers = [];
    
    // Adicionar usuários do Firebase
    foreach ($firebaseUsers as $firebaseUser) {
        $combinedUser = [
            'id' => 'fb_' . md5($firebaseUser['name']), // ID temporário
            'name' => $firebaseUser['name'],
            'email' => null, // Firebase não armazena email
            'phone' => null, // Firebase não armazena telefone
            'created_at' => $firebaseUser['first_seen'] ? date('Y-m-d H:i:s', $firebaseUser['first_seen']) : null,
            'last_login' => $firebaseUser['last_activity'] ? date('Y-m-d H:i:s', $firebaseUser['last_activity']) : null,
            'is_active' => true,
            'source' => 'firebase',
            'total_bets' => $firebaseUser['total_bets'],
            'rounds_participated' => count($firebaseUser['rounds_participated'])
        ];
        
        $combinedUsers[] = $combinedUser;
    }
    
    // Adicionar usuários locais que não estão no Firebase
    foreach ($localUsers as $localUser) {
        $existsInFirebase = false;
        foreach ($firebaseUsers as $firebaseUser) {
            if (strtolower($firebaseUser['name']) === strtolower($localUser['name'])) {
                $existsInFirebase = true;
                break;
            }
        }
        
        if (!$existsInFirebase) {
            $localUser['source'] = 'local';
            $localUser['total_bets'] = 0;
            $localUser['rounds_participated'] = 0;
            $combinedUsers[] = $localUser;
        }
    }
    
    return $combinedUsers;
}

/**
 * Obtém apostas de uma rodada específica
 */
function firebaseGetRoundBets($roundName) {
    $data = firebaseGetData('apostas/' . rawurlencode($roundName));
    return $data ?: [];
}

/**
 * Obtém todas as apostas de todos os usuários
 */
function firebaseGetAllBets() {
    $rounds = firebaseGetAllRounds();
    $allBets = [];
    
    foreach ($rounds as $round) {
        foreach ($round['bets'] as $user => $userData) {
            if (isset($userData['palpites'])) {
                foreach ($userData['palpites'] as $gameId => $bet) {
                    $allBets[] = [
                        'round' => $round['name'],
                        'user' => $user,
                        'game_id' => $gameId,
                        'bet' => $bet,
                        'timestamp' => $userData['timestamp_aposta'] ?? null
                    ];
                }
            }
        }
    }
    
    return $allBets;
}

/**
 * Obtém estatísticas das apostas
 */
function firebaseGetBetStats() {
    $rounds = firebaseGetAllRounds();
    $totalBets = 0;
    $totalUsers = 0;
    $roundStats = [];
    
    foreach ($rounds as $round) {
        $userCount = count($round['bets']);
        $betCount = 0;
        
        foreach ($round['bets'] as $userBets) {
            if (isset($userBets['palpites'])) {
                $betCount += count($userBets['palpites']);
            }
        }
        
        $roundStats[] = [
            'round' => $round['name'],
            'users' => $userCount,
            'bets' => $betCount
        ];
        
        $totalUsers += $userCount;
        $totalBets += $betCount;
    }
    
    return [
        'total_bets' => $totalBets,
        'total_users' => $totalUsers,
        'rounds' => $roundStats,
        'average_bets_per_user' => $totalUsers > 0 ? round($totalBets / $totalUsers, 2) : 0
    ];
}

/**
 * Obtém jogos da cache principal (para referência)
 */
function firebaseGetGamesFromCache() {
    if (file_exists(CACHE_FILE)) {
        $cachedData = json_decode(file_get_contents(CACHE_FILE), true);
        if ($cachedData && !empty($cachedData['jogos'])) {
            return [
                'round' => $cachedData['rodada'] ?? 'Rodada Atual',
                'games' => $cachedData['jogos']
            ];
        }
    }
    return null;
}

/**
 * Calcula pontos das apostas (simulação básica)
 */
function firebaseCalculatePoints($roundName, $gameResults) {
    $bets = firebaseGetRoundBets($roundName);
    $results = [];
    
    foreach ($bets as $user => $userData) {
        if (!isset($userData['palpites'])) continue;
        
        $userPoints = 0;
        $correctScores = 0;
        $correctWinners = 0;
        
        foreach ($userData['palpites'] as $gameId => $bet) {
            if (!isset($gameResults[$gameId])) continue;
            
            $actualResult = $gameResults[$gameId];
            $betResult = $bet;
            
            // Verificar se é array ou string
            if (is_string($betResult)) {
                if (preg_match('/(\d+)\s*x\s*(\d+)/i', $betResult, $matches)) {
                    $betTeam1 = (int)$matches[1];
                    $betTeam2 = (int)$matches[2];
                } else {
                    continue;
                }
            } else {
                $betTeam1 = $betResult['time1'] ?? 0;
                $betTeam2 = $betResult['time2'] ?? 0;
            }
            
            $actualTeam1 = $actualResult['time1'] ?? 0;
            $actualTeam2 = $actualResult['time2'] ?? 0;
            
            // Cálculo de pontos
            if ($betTeam1 == $actualTeam1 && $betTeam2 == $actualTeam2) {
                // Na mosca - 12 pontos
                $userPoints += 12;
                $correctScores++;
            } elseif (($betTeam1 > $betTeam2 && $actualTeam1 > $actualTeam2) ||
                      ($betTeam1 < $betTeam2 && $actualTeam1 < $actualTeam2) ||
                      ($betTeam1 == $betTeam2 && $actualTeam1 == $actualTeam2)) {
                // Acertou vencedor/empate - 5 pontos
                $userPoints += 5;
                $correctWinners++;
            }
        }
        
        $results[$user] = [
            'total_points' => $userPoints,
            'correct_scores' => $correctScores,
            'correct_winners' => $correctWinners,
            'total_bets' => count($userData['palpites'])
        ];
    }
    
    return $results;
}

/**
 * Atualiza palpites de um usuário
 */
function firebaseUpdateUserBet($roundName, $userName, $gameId, $newBet) {
    $path = 'apostas/' . rawurlencode($roundName) . '/' . rawurlencode($userName) . '/palpites/' . $gameId;
    return firebaseSaveData($path, $newBet);
}

/**
 * Remove palpite de um usuário
 */
function firebaseRemoveUserBet($roundName, $userName, $gameId) {
    $path = 'apostas/' . rawurlencode($roundName) . '/' . rawurlencode($userName) . '/palpites/' . $gameId;
    return firebaseSaveData($path, null);
}

/**
 * Remove usuário inteiro de uma rodada
 */
function firebaseRemoveUserFromRound($roundName, $userName) {
    $path = 'apostas/' . rawurlencode($roundName) . '/' . rawurlencode($userName);
    return firebaseSaveData($path, null);
}

/**
 * Formata data para exibição
 */
function formatFirebaseTimestamp($timestamp) {
    if (!$timestamp) return 'N/A';
    
    // Se for timestamp Unix
    if (is_numeric($timestamp)) {
        return date('d/m/Y H:i:s', $timestamp);
    }
    
    // Se for string de data
    $date = strtotime($timestamp);
    return $date ? date('d/m/Y H:i:s', $date) : 'N/A';
}

/**
 * Converte dados de apostas para formato amigável
 */
function formatBetData($bet) {
    if (is_array($bet) && isset($bet['time1']) && isset($bet['time2'])) {
        return $bet['time1'] . 'x' . $bet['time2'];
    } elseif (is_string($bet)) {
        return $bet;
    }
    return 'Inválido';
}

/**
 * Remove usuário de todas as rodadas no Firebase
 */
function firebaseDeleteUser($userName) {
    $rounds = firebaseGetAllRounds();
    $deletedCount = 0;

    foreach ($rounds as $round) {
        if (isset($round['bets'][$userName])) {
            if (firebaseRemoveUserFromRound($round['name'], $userName)) {
                $deletedCount++;
            }
        }
    }

    return $deletedCount > 0;
}

?>