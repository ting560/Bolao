<?php
// Defina a constante FIREBASE_URL se não estiver definida (importante para acesso direto)
if (!defined('FIREBASE_URL')) {
    define('FIREBASE_URL', 'https://bolao-novo-2025-default-rtdb.firebaseio.com/');
}
// Incluir config.php para obter funções de acesso ao Firebase e getTeamAbbreviation, se necessário
require_once 'configs/config.php'; // Garante que obterDadosFirebase e outras configs estejam disponíveis

// Função para buscar dados de um palpite específico (copiada/adaptada de admin.php se necessário)
// Se você já tem uma função em config.php ou admin.php para buscar dados do Firebase, use-a.
// Caso contrário, aqui está uma simples (esta já deve existir no seu config.php via admin.php ou em admin.php diretamente):
if (!function_exists('obterDadosFirebase')) {
    function obterDadosFirebase($path) {
        $url = FIREBASE_URL . rtrim($path, '/') . '.json';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        // Para desenvolvimento local, pode ser necessário desabilitar a verificação SSL
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("Firebase cURL Error for path '$path': " . $curlError);
            return null;
        }
        if ($httpCode != 200) {
            error_log("Firebase HTTP Error $httpCode for path '$path'. Response: $response");
            return null;
        }
        $data = json_decode($response, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $data : null;
    }
}


// Obter parâmetros da URL
$rodadaNome = $_GET['rodada'] ?? null;
$apostadorNome = $_GET['apostador'] ?? null; // Corrigido para 'apostador' como no link gerado

if (!$rodadaNome || !$apostadorNome) {
    die("Erro: Rodada ou nome do apostador não fornecidos.");
}

// Buscar dados dos palpites do apostador
$pathAposta = 'apostas/' . rawurlencode($rodadaNome) . '/' . rawurlencode($apostadorNome);
$dadosAposta = obterDadosFirebase($pathAposta);

$palpitesDoApostador = [];
if ($dadosAposta && isset($dadosAposta['palpites'])) {
    $palpitesDoApostador = $dadosAposta['palpites'];
}

// Buscar dados dos jogos para obter nomes dos times (da cache do admin ou da cache principal)
// Usaremos a cache principal (jogos_cache.json) pois ela tem a lista de jogos.
$jogosInfo = [];
if (file_exists(CACHE_FILE)) { // CACHE_FILE de config.php
    $cachedJogos = json_decode(file_get_contents(CACHE_FILE), true);
    if ($cachedJogos && isset($cachedJogos['jogos']) && is_array($cachedJogos['jogos'])) {
        // Reindexar pelo ID do jogo para fácil acesso
        foreach ($cachedJogos['jogos'] as $jogo) {
            if (isset($jogo['id'])) {
                $jogosInfo[$jogo['id']] = $jogo;
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Bilhete de Aposta - <?= htmlspecialchars($apostadorNome) ?> - Rodada: <?= htmlspecialchars($rodadaNome) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; color: #333; }
        .bilhete-container { background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 500px; margin: 20px auto; }
        h1 { text-align: center; color: #1877f2; margin-top: 0; margin-bottom: 10px; font-size: 1.6em; }
        h2 { text-align: center; color: #555; margin-top: 0; margin-bottom: 20px; font-size: 1.2em; border-bottom: 1px solid #eee; padding-bottom: 10px;}
        .info-apostador { text-align: center; margin-bottom: 25px; font-size: 1.1em; }
        .info-apostador strong { color: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; font-size: 0.95em; }
        th { background-color: #f0f2f5; font-weight: 600; color: #333; }
        .time-logo-bilhete { width: 25px; height: 25px; vertical-align: middle; margin: 0 5px; object-fit: contain; }
        .vs { font-weight: bold; color: #e74c3c; }
        .palpite { font-weight: bold; font-size: 1.1em; }
        .footer-bilhete { text-align: center; margin-top: 30px; font-size: 0.8em; color: #777; }
        @media print {
            body { margin: 0; background-color: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .bilhete-container { box-shadow: none; border: 1px solid #ccc; margin: 0; max-width: 100%; border-radius: 0;}
            .no-print { display: none; }
        }
        .no-print button {
            display: block;
            width: 150px;
            margin: 20px auto;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }
        .no-print button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <div class="bilhete-container">
        <h1>Bolão entre Amigos</h1>
        <h2>Comprovante de Palpites</h2>
        <div class="info-apostador">
            Apostador: <strong><?= htmlspecialchars($apostadorNome) ?></strong><br>
            Rodada: <strong><?= htmlspecialchars($rodadaNome) ?></strong>
        </div>

        <?php if (!empty($palpitesDoApostador)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Jogo</th>
                        <th>Seu Palpite</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($palpitesDoApostador as $jogoId => $palpite): ?>
                        <?php
                            $time1Nome = 'Time 1';
                            $time2Nome = 'Time 2';
                            $time1Logo = PLACEHOLDER_LOGO_URL; // Definido em config.php
                            $time2Logo = PLACEHOLDER_LOGO_URL; // Definido em config.php

                            if (isset($jogosInfo[$jogoId])) {
                                $time1Nome = htmlspecialchars($jogosInfo[$jogoId]['team1']['name']);
                                $time2Nome = htmlspecialchars($jogosInfo[$jogoId]['team2']['name']);
                                $time1Logo = htmlspecialchars($jogosInfo[$jogoId]['team1']['logo']);
                                $time2Logo = htmlspecialchars($jogosInfo[$jogoId]['team2']['logo']);
                            }

                            $palpiteCasaNum = null;
                            $palpiteForaNum = null;
                            if (is_array($palpite) && isset($palpite['time1']) && isset($palpite['time2'])) {
                                $palpiteCasaNum = htmlspecialchars($palpite['time1']);
                                $palpiteForaNum = htmlspecialchars($palpite['time2']);
                            } elseif (is_string($palpite) && preg_match("/^(\d+)\s*x\s*(\d+)$/i", trim($palpite), $matches)) {
                                $palpiteCasaNum = htmlspecialchars($matches[1]);
                                $palpiteForaNum = htmlspecialchars($matches[2]);
                            }
                        ?>
                        <tr>
                            <td>
                                <img src="<?= $time1Logo ?>" alt="<?= $time1Nome ?>" class="time-logo-bilhete">
                                <?= $time1Nome ?>
                                <span class="vs">vs</span>
                                <?= $time2Nome ?>
                                <img src="<?= $time2Logo ?>" alt="<?= $time2Nome ?>" class="time-logo-bilhete">
                            </td>
                            <td class="palpite">
                                <?php if ($palpiteCasaNum !== null && $palpiteForaNum !== null): ?>
                                    <?= $palpiteCasaNum ?> x <?= $palpiteForaNum ?>
                                <?php else: ?>
                                    Palpite Inválido
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align:center;">Nenhum palpite encontrado para este apostador nesta rodada.</p>
        <?php endif; ?>
        <div class="footer-bilhete">
            Este é um comprovante dos seus palpites. Boa sorte!
        </div>
    </div>
    <div class="no-print">
        <button onclick="window.print()">Imprimir / Salvar PDF</button>
    </div>
</body>
</html>