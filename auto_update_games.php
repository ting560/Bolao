<?php
/**
 * Sistema de Atualização Automática de Jogos
 * Arquivo: auto_update_games.php
 * 
 * Atualiza resultados dos jogos a cada minuto e calcula pontos automaticamente
 */

require_once 'configs/config.php';
require_once 'firebase_admin_functions.php';

// Função para atualizar resultados dos jogos
function updateGameResults() {
    try {
        // Obter jogos atuais do cache
        $gamesData = firebaseGetGamesFromCache();
        if (!$gamesData || empty($gamesData['games'])) {
            error_log("AUTO_UPDATE: Nenhum jogo encontrado para atualizar");
            return false;
        }
        
        $updatedGames = 0;
        $calculatedPoints = 0;
        
        foreach ($gamesData['games'] as $game) {
            // Verificar se o jogo está encerrado e tem resultado
            if ($game['status'] === 'Encerrado' && $game['score'] !== '- x -') {
                // Extrair placar final
                if (preg_match('/(\d+)\s*x\s*(\d+)/i', $game['score'], $matches)) {
                    $finalScore = [
                        'time1_goals' => (int)$matches[1],
                        'time2_goals' => (int)$matches[2]
                    ];
                    
                    // Atualizar resultados no Firebase
                    $result = updateGameResultInFirebase($game['id'], $finalScore);
                    if ($result) {
                        $updatedGames++;
                        
                        // Calcular pontos automaticamente para este jogo
                        $pointsResult = calculatePointsForGame($game['id'], $finalScore);
                        if ($pointsResult) {
                            $calculatedPoints++;
                        }
                    }
                }
            }
        }
        
        error_log("AUTO_UPDATE: Atualizados $updatedGames jogos e calculados pontos para $calculatedPoints jogos");
        return true;
        
    } catch (Exception $e) {
        error_log("AUTO_UPDATE ERRO: " . $e->getMessage());
        return false;
    }
}

// Função para atualizar resultado no Firebase
function updateGameResultInFirebase($gameId, $finalScore) {
    try {
        $path = "resultados_jogos/$gameId";
        $data = [
            'game_id' => $gameId,
            'final_score' => $finalScore,
            'updated_at' => time(),
            'status' => 'finalizado'
        ];
        
        return firebaseSaveData($path, $data);
    } catch (Exception $e) {
        error_log("ERRO ao atualizar resultado do jogo $gameId: " . $e->getMessage());
        return false;
    }
}

// Função para calcular pontos automaticamente
function calculatePointsForGame($gameId, $finalScore) {
    try {
        // Obter todas as apostas para este jogo
        $allBets = firebaseGetAllBets();
        $gameBets = array_filter($allBets, function($bet) use ($gameId) {
            return $bet['game_id'] == $gameId;
        });
        
        if (empty($gameBets)) {
            return false;
        }
        
        $pointsAwarded = 0;
        
        foreach ($gameBets as $bet) {
            // Calcular pontos para cada aposta
            $points = calculateBetPoints($bet['bet'], $finalScore);
            
            // Atualizar pontos no Firebase
            $pointsPath = "apostas/{$bet['round']}/{$bet['user']}/pontos/{$gameId}";
            firebaseSaveData($pointsPath, [
                'points' => $points,
                'calculated_at' => time(),
                'final_score' => $finalScore
            ]);
            
            $pointsAwarded += $points;
        }
        
        error_log("AUTO_POINTS: Calculados $pointsAwarded pontos para o jogo $gameId");
        return true;
        
    } catch (Exception $e) {
        error_log("ERRO ao calcular pontos para jogo $gameId: " . $e->getMessage());
        return false;
    }
}

// Função para calcular pontos de uma aposta específica
function calculateBetPoints($bet, $finalScore) {
    // Regras de pontuação:
    // - 3 pontos: acertar exatamente o placar
    // - 1 ponto: acertar o vencedor (vitória/derrota/empate)
    // - 0 pontos: errar completamente
    
    $betTime1 = $bet['time1'] ?? 0;
    $betTime2 = $bet['time2'] ?? 0;
    
    // Acertou exatamente o placar?
    if ($betTime1 == $finalScore['time1_goals'] && $betTime2 == $finalScore['time2_goals']) {
        return 3;
    }
    
    // Acertou o resultado (vitória/derrota/empate)?
    $betResult = ($betTime1 > $betTime2) ? 'win' : (($betTime1 < $betTime2) ? 'loss' : 'draw');
    $actualResult = ($finalScore['time1_goals'] > $finalScore['time2_goals']) ? 'win' : 
                   (($finalScore['time1_goals'] < $finalScore['time2_goals']) ? 'loss' : 'draw');
    
    if ($betResult == $actualResult) {
        return 1;
    }
    
    // Errou completamente
    return 0;
}

// Executar atualização automática
if (php_sapi_name() === 'cli' || isset($_GET['auto_update'])) {
    echo "Iniciando atualização automática de jogos...\n";
    $result = updateGameResults();
    echo $result ? "Atualização concluída com sucesso!\n" : "Erro na atualização!\n";
} else {
    // Endpoint HTTP para chamadas automáticas
    header('Content-Type: application/json');
    $result = updateGameResults();
    echo json_encode([
        'success' => $result,
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $result ? 'Atualização automática executada' : 'Erro na atualização automática'
    ]);
}
?>