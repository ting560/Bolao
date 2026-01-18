<?php
// --- CONFIGURAÇÕES ---
$url = 'https://www.placardefutebol.com.br/jogos-de-hoje'; 
$intervaloRefreshSegundos = 90;
// --- FIM CONFIGURAÇÕES ---

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_ENCODING, "");
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
    'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
    'Cache-Control: no-cache',
    'Pragma: no-cache'
));
$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<!DOCTYPE html>";
echo "<html lang='pt-BR'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<meta http-equiv='refresh' content='" . htmlspecialchars($intervaloRefreshSegundos) . "'>";
echo "<title>Placar de Jogos de Hoje</title>";

echo "<style>
    * {
        box-sizing: border-box;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        margin: 0;
        padding: 0;
        color: #333;
        background-color: #f0f2f5;
    }

    .container {
        max-width: 100%;
        margin: 10px auto;
        padding: 10px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }

    h1 {
        text-align: center;
        color: #1d8a24;
        margin-bottom: 15px;
        font-size: 1.4em;
        font-weight: 600;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        overflow-x: auto;
        font-size: 0.9em;
    }

    th, td {
        border: 1px solid #e0e0e0;
        padding: 8px 6px;
        text-align: center;
    }

    th {
        background-color: #4CAF50;
        color: white;
        text-transform: uppercase;
        font-size: 0.8em;
        font-weight: 600;
    }

    .campeonato-header {
        background-color: #343a40;
        color: white;
        font-weight: bold;
        text-align: center;
        font-size: 1em;
        padding: 10px;
    }

    .sub-campeonato-header {
        background-color: #e9ecef;
        border-bottom: 1px solid #ced4da;
        font-weight: 600;
        color: #495057;
        text-align: center;
        font-size: 0.9em;
        padding: 8px;
    }

    .status {
        font-style: italic;
        color: #c82333;
        min-width: 70px;
        font-weight: 500;
    }

    .score {
        font-weight: bold;
    }

    .match-info {
        white-space: nowrap;
    }

    tr:nth-child(even):not(:has(.campeonato-header)):not(:has(.sub-campeonato-header)) {
        background-color: #f8f9fa;
    }

    tr:not(:has(.campeonato-header)):not(:has(.sub-campeonato-header)):hover {
        background-color: #e9f5e9;
    }

    .error-message {
        color: #721c24;
        text-align: center;
        font-weight: bold;
        padding: 15px;
        border: 1px solid #f5c6cb;
        background-color: #f8d7da;
        border-radius: 4px;
        margin: 15px 0;
    }

    .no-games-message {
        text-align: center;
        padding: 20px;
        color: #666;
        font-style: italic;
    }

    @media screen and (max-width: 600px) {
        table, thead, tbody, th, td, tr {
            display: block;
        }

        thead {
            display: none;
        }

        tr {
            margin-bottom: 15px;
        }

        td {
            position: relative;
            padding-left: 50%;
            text-align: left;
            border: none;
            border-bottom: 1px solid #ddd;
        }

        td::before {
            position: absolute;
            top: 6px;
            left: 10px;
            width: 40%;
            padding-right: 10px;
            white-space: nowrap;
            font-weight: bold;
        }

        td:nth-of-type(1)::before { content: 'Status'; }
        td:nth-of-type(2)::before { content: 'Jogo'; }
        td:nth-of-type(3)::before { content: 'Placar'; }
    }
</style>";

echo "</head>";
echo "<body>";
echo "<div class='container'>";
echo "<h1>Placar de Jogos de Hoje</h1>";

if ($curlError) {
    echo "<p class='error-message'>Erro no cURL: " . htmlspecialchars($curlError) . "</p>";
    echo "</div></body></html>";
    exit;
}

if ($httpCode !== 200) {
    echo "<p class='error-message'>Erro ao buscar página. HTTP: " . htmlspecialchars($httpCode) . "</p>";
    echo "</div></body></html>";
    exit;
}

if (empty($html)) {
    echo "<p class='error-message'>HTML vazio.</p>";
    echo "</div></body></html>";
    exit;
}

$doc = new DOMDocument();
libxml_use_internal_errors(true);
if (!$doc->loadHTML('<?xml encoding="UTF-8">' . $html)) {
    echo "<p class='error-message'>Erro ao carregar HTML.</p>";
    libxml_clear_errors();
    echo "</div></body></html>";
    exit;
}
libxml_clear_errors();

$xpath = new DOMXPath($doc);
$livescoreNode = $xpath->query("//*[@id='livescore']")->item(0);
if (!$livescoreNode) {
    echo "<p class='error-message'>#livescore não encontrado.</p>";
    echo "</div></body></html>";
    exit;
}

echo "<table>";
echo "<thead>";
echo "<tr>";
echo "<th>Status</th>";
echo "<th>Jogo</th>";
echo "<th>Placar</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

$jogosEncontradosGlobal = false;
$ultimoCampeonatoPrincipalExibido = null;
$ultimoSubCampeonatoExibidoNaTrendingBox = null;

$todosJogos = array();
$gameBlocks = $xpath->query(".//div[contains(@class, 'container') and contains(@class, 'content') and .//a[.//h5[contains(@class, 'team_link')]]]", $livescoreNode);

if ($gameBlocks && $gameBlocks->length > 0) {
    foreach ($gameBlocks as $currentBlockOfGames) {
        $isTrendingBoxBlock = strpos($currentBlockOfGames->getAttribute('class'), 'trending-box') !== false;
        $nomeCampeonatoParaEsteBloco = null;

        if ($isTrendingBoxBlock) {
            $nomeCampeonatoParaEsteBloco = "INTERNAL_TRENDING_BOX";
        } else {
            $headerNode = $xpath->query("preceding-sibling::div[contains(@class, 'row-fix') and .//h3[contains(@class, 'match-list_league-name')]][1]", $currentBlockOfGames)->item(0);
            if ($headerNode) {
                $leagueNameNode = $xpath->query(".//h3[contains(@class, 'match-list_league-name')]", $headerNode)->item(0);
                if ($leagueNameNode) {
                    $nomeCampeonatoParaEsteBloco = trim($leagueNameNode->nodeValue);
                }
            }
            if ($nomeCampeonatoParaEsteBloco === null) {
                $nomeCampeonatoParaEsteBloco = "Outros Jogos";
            }
        }

        $matchLinks = $xpath->query(".//a[.//h5[contains(@class, 'team_link')]]", $currentBlockOfGames);
        if ($matchLinks && $matchLinks->length > 0) {
            if (!$jogosEncontradosGlobal) $jogosEncontradosGlobal = true;
            foreach ($matchLinks as $matchLink) {
                $specificLeagueName = null;
                if ($isTrendingBoxBlock) {
                    $specificLeagueNameNode = $xpath->query(".//div[contains(@class, 'match-card-league-name')]", $matchLink)->item(0);
                    if ($specificLeagueNameNode) {
                        $specificLeagueName = trim($specificLeagueNameNode->nodeValue);
                    }
                }

                // Extração dos dados da partida
                $statusNode = $xpath->query(".//span[contains(@class, 'status-name')]", $matchLink)->item(0);
                $status = $statusNode ? trim($statusNode->nodeValue) : "N/A";

                $homeTeamNode = $xpath->query(".//h5[contains(@class, 'text-right') and contains(@class, 'team_link')]", $matchLink)->item(0);
                $homeTeam = $homeTeamNode ? trim($homeTeamNode->nodeValue) : "N/A";

                $homeScoreNode = $xpath->query("(.//div[contains(@class, 'match-score')]//span[contains(@class, 'badge-default')])[1]", $matchLink)->item(0);
                $homeScore = ($homeScoreNode && $homeScoreNode->nodeValue !== null && $homeScoreNode->nodeValue !== "") ? $homeScoreNode->nodeValue : "-";

                $awayTeamNode = $xpath->query(".//h5[contains(@class, 'text-left') and contains(@class, 'team_link')]", $matchLink)->item(0);
                $awayTeam = $awayTeamNode ? trim($awayTeamNode->nodeValue) : "N/A";

                $awayScoreNode = $xpath->query("(.//div[contains(@class, 'match-score')]//span[contains(@class, 'badge-default')])[2]", $matchLink)->item(0);
                $awayScore = ($awayScoreNode && $awayScoreNode->nodeValue !== null && $awayScoreNode->nodeValue !== "") ? $awayScoreNode->nodeValue : "-";

                $todosJogos[] = array(
                    'campeonato' => $isTrendingBoxBlock ? $specificLeagueName : $nomeCampeonatoParaEsteBloco,
                    'status' => $status,
                    'homeTeam' => $homeTeam,
                    'homeScore' => $homeScore,
                    'awayTeam' => $awayTeam,
                    'awayScore' => $awayScore,
                    'isTrendingBox' => $isTrendingBoxBlock,
                    'specificLeagueName' => $specificLeagueName
                );
            }
        }
    }
}

function ordenarPorStatus($a, $b) {
    return strcmp($a['status'], $b['status']);
}
usort($todosJogos, 'ordenarPorStatus');

$ultimoCampeonatoExibido = null;
$ultimoSubCampeonatoExibido = null;

foreach ($todosJogos as $jogo) {
    if (!$jogo['isTrendingBox'] && $jogo['campeonato'] !== $ultimoCampeonatoExibido) {
        echo "<tr><td colspan='3' class='campeonato-header'>" . htmlspecialchars($jogo['campeonato']) . "</td></tr>";
        $ultimoCampeonatoExibido = $jogo['campeonato'];
        $ultimoSubCampeonatoExibido = null;
    }

    if ($jogo['isTrendingBox'] && $jogo['specificLeagueName'] !== $ultimoSubCampeonatoExibido) {
        echo "<tr><td colspan='3' class='sub-campeonato-header'>" . htmlspecialchars($jogo['specificLeagueName']) . "</td></tr>";
        $ultimoSubCampeonatoExibido = $jogo['specificLeagueName'];
    }

    echo "<tr>";
    echo "<td class='status'>" . htmlspecialchars($jogo['status']) . "</td>";
    echo "<td class='match-info'>" . htmlspecialchars($jogo['homeTeam']) . " vs " . htmlspecialchars($jogo['awayTeam']) . "</td>";
    echo "<td class='score'>" . htmlspecialchars(trim($jogo['homeScore'])) . " - " . htmlspecialchars(trim($jogo['awayScore'])) . "</td>";
    echo "</tr>";
}

if (!$jogosEncontradosGlobal) {
    echo "<tr><td colspan='3' class='no-games-message'>Nenhum jogo encontrado.</td></tr>";
}

echo "</tbody></table></div></body></html>";
?>