<?php
// Este arquivo é um endpoint AJAX para buscar os palpites de um jogo específico.
require_once 'configs/config.php';

// Supressão de erros para não poluir a resposta JSON com mensagens de erro PHP
error_reporting(0);
ini_set('display_errors', 0);

// Define o cabeçalho para indicar que a resposta é JSON
header('Content-Type: application/json');

// Obtém os parâmetros da URL (rodada e ID do jogo)
$rodada = $_GET['rodada'] ?? '';
$jogoId = $_GET['jogo_id'] ?? '';

// Validação básica dos parâmetros
if (empty($rodada) || empty($jogoId)) {
    // Loga a falta de parâmetros para depuração
    error_log("DEBUG PHP (buscar_palpites): Parâmetros inválidos recebidos. Rodada: '$rodada', JogoID: '$jogoId'");
    // Retorna um erro JSON para o cliente
    echo json_encode(['error' => 'Parâmetros inválidos']);
    exit; // Encerra o script
}

// Constrói a URL para buscar todas as apostas da rodada no Firebase
$url = FIREBASE_URL . '/apostas/' . rawurlencode($rodada) . '.json';

// Inicializa cURL para buscar dados do Firebase
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Retorna a resposta como string
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Define um timeout de 10 segundos
// Opcional para desenvolvimento local: desabilitar verificação SSL
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch); // Executa a requisição
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Obtém o código HTTP da resposta
$curlError = curl_error($ch); // Obtém qualquer erro do cURL
curl_close($ch); // Fecha a sessão cURL

// Verifica se houve erro no cURL
if ($curlError) {
    error_log("DEBUG PHP (buscar_palpites): Erro cURL ao buscar palpites. Rodada: '$rodada'. Erro: $curlError");
    echo json_encode(['error' => 'Erro de conexão ao buscar palpites.']);
    exit;
}

// Verifica se a requisição foi bem sucedida (código HTTP 200)
if ($httpCode === 200) {
    $data = json_decode($response, true); // Decodifica a resposta JSON em um array PHP
    $palpites = []; // Array para armazenar os palpites encontrados para o jogo específico

    // Verifica se os dados decodificados não são nulos e são um array
    if ($data && is_array($data)) {
        // Itera sobre cada apostador nos dados recebidos
        foreach ($data as $apostador => $info) {
            // Verifica se o apostador tem palpites e se tem um palpite para o jogo ID solicitado
            // A estrutura esperada no Firebase é apostas/Rodada/Apostador/palpites/JogoID
            if (isset($info['palpites']) && is_array($info['palpites']) && isset($info['palpites'][$jogoId])) {
                $palpiteData = $info['palpites'][$jogoId]; // Obtém o palpite do apostador para o jogo
                $palpiteCasa = null;
                $palpiteFora = null;

                // === CORREÇÃO: Lida com os dois formatos possíveis do palpite ===
                if (is_array($palpiteData) || is_object($palpiteData)) {
                    // Formato salvo por aposta.php: { "time1": X, "time2": Y }
                    if (isset($palpiteData['time1']) && isset($palpiteData['time2']) && is_numeric($palpiteData['time1']) && is_numeric($palpiteData['time2'])) {
                         $palpiteCasa = $palpiteData['time1'];
                         $palpiteFora = $palpiteData['time2'];
                    } else {
                        error_log("DEBUG PHP (buscar_palpites): Palpite em array/objeto incompleto ou inválido para Apostador '$apostador', JogoID '$jogoId' na Rodada '$rodada'. Palpite: " . json_encode($palpiteData));
                    }
                } elseif (is_string($palpiteData)) {
                    // Formato salvo por admin.php: "XxY"
                     if (preg_match("/^(\d+)\s*x\s*(\d+)$/i", trim($palpiteData), $palpiteMatches)) {
                         $palpiteCasa = (int)$palpiteMatches[1];
                         $palpiteFora = (int)$palpiteMatches[2];
                     } else {
                          error_log("DEBUG PHP (buscar_palpites): Palpite em string com formato inválido para Apostador '$apostador', JogoID '$jogoId' na Rodada '$rodada'. Palpite: '$palpiteData'.");
                     }
                } else {
                    error_log("DEBUG PHP (buscar_palpites): Formato de palpite inesperado para Apostador '$apostador', JogoID '$jogoId' na Rodada '$rodada'. Tipo: " . gettype($palpiteData) . ". Palpite: " . json_encode($palpiteData));
                }
                 // === FIM CORREÇÃO ===

                // Se conseguimos extrair os gols do palpite, adiciona à lista
                if ($palpiteCasa !== null && $palpiteFora !== null) {
                    $palpites[] = [
                        'apostador' => $apostador,
                        'palpiteCasa' => $palpiteCasa,
                        'palpiteFora' => $palpiteFora
                    ];
                }
            }
        }
    } else {
        // Loga se os dados do Firebase não vieram no formato esperado ou estão vazios
         error_log("DEBUG PHP (buscar_palpites): Dados do Firebase para rodada '$rodada' não são um array ou estão vazios após decode. Response: " . substr($response, 0, 200));
    }

    // Retorna a lista de palpites (pode estar vazia) como JSON
    // O script.js espera um array, mesmo que vazio.
    echo json_encode($palpites);

} else {
    // Loga erro se a requisição Firebase falhou com código HTTP diferente de 200
    error_log("ERRO PHP (buscar_palpites): Falha ao buscar palpites do Firebase. Rodada: '$rodada'. HTTP Code: $httpCode. Response: " . substr($response, 0, 200));
    // Retorna um erro JSON para o cliente
    echo json_encode(['error' => 'Erro ao buscar palpites do servidor. Código: ' . $httpCode]);
}

exit; // Garante que o script termine após o output JSON
?>