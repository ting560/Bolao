<?php
// Este arquivo inclui a lógica para calcular as pontuações e exibir a classificação detalhada
require_once 'configs/config.php';

// Garante que a página não seja armazenada em cache pelo navegador
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Obtém os dados dos jogos. 'obterDadosPlacar' vem de config.php
// O parâmetro forceUpdate=true pode vir da URL (ex: palpites.php?update=1)
$dadosPlacar = obterDadosPlacar(isset($_GET['update']) && $_GET['update'] == '1');

// Função para calcular pontuações com base nos palpites e resultados reais
function calcularPontuacoes($jogos, $rodadaNome) {
    // Retorna array vazio se rodadaNome for inválido
    if (empty($rodadaNome) || $rodadaNome === 'Rodada Indisponível' || $rodadaNome === 'Rodada Desconhecida') {
        error_log("ALERTA PHP (palpites): Tentativa de calcular pontuação com nome de rodada inválido: '$rodadaNome'.");
        return [];
    }

    // Constrói a URL para buscar todas as apostas da rodada no Firebase
    $url = FIREBASE_URL . '/apostas/' . rawurlencode($rodadaNome) . '.json';

    // Inicializa cURL para buscar dados do Firebase
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Retorna a resposta como string
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Define um timeout de 10 segundos
    $response = curl_exec($ch); // Executa a requisição
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Obtém o código HTTP da resposta
    $curlError = curl_error($ch); // Obtém qualquer erro do cURL
    curl_close($ch); // Fecha a sessão cURL

    $apostadores = []; // Array para armazenar as pontuações dos apostadores

    // Verifica se a requisição foi bem sucedida (código HTTP 200)
    if ($httpCode === 200) {
        $data = json_decode($response, true); // Decodifica a resposta JSON em um array PHP

        // Verifica se os dados decodificados não são nulos e são um array
        if ($data && is_array($data)) {
            // Itera sobre cada apostador nos dados recebidos
            foreach ($data as $apostador => $info) {
                $pontuacaoTotal = 0;
                $acertos_na_mosca = 0;
                $acertos_vencedor_ou_empate = 0;

                // Itera sobre cada jogo para calcular a pontuação
                foreach ($jogos as $jogo) {
                    // Pular se o jogo não está encerrado OU se o placar não é válido (não está no formato NxN)
                    if ($jogo["status"] !== "Encerrado" || !is_string($jogo["score"]) || !preg_match("/^\d+x\d+$/", $jogo["score"])) {
                         if ($jogo["status"] === "Encerrado") {
                              error_log("DEBUG PHP (palpites): Jogo Encerrado (ID: {$jogo["id"]}) sem placar válido 'NxN'. Status: '{$jogo["status"]}', Score: '{$jogo["score"]}'. Pulando cálculo para este jogo.");
                         }
                        continue;
                    }
                    
                    // Verifica se o apostador tem um palpite para este jogo
                    if (!isset($info["palpites"][$jogo["id"]])) {
                        continue; 
                    }

                    $palpite = $info["palpites"][$jogo["id"]]; 
                    $palpiteCasa = null;
                    $palpiteFora = null;

                    // Lida com os dois formatos possíveis do palpite (array ou string "XxY")
                    if (is_array($palpite) || is_object($palpite)) {
                        if (isset($palpite['time1']) && isset($palpite['time2']) && is_numeric($palpite['time1']) && is_numeric($palpite['time2'])) {
                             $palpiteCasa = (int)$palpite['time1'];
                             $palpiteFora = (int)$palpite['time2'];
                        }
                    } elseif (is_string($palpite)) {
                        if (preg_match("/^(\d+)\s*x\s*(\d+)$/i", trim($palpite), $palpiteMatches)) {
                           $palpiteCasa = (int)$palpiteMatches[1];
                           $palpiteFora = (int)$palpiteMatches[2];
                        }
                    }

                    // Se não conseguiu extrair os gols do palpite, pula este jogo para este apostador
                    if ($palpiteCasa === null || $palpiteFora === null) {
                        error_log("DEBUG PHP (palpites): Palpite inválido ou incompleto para apostador '$apostador', jogo ID '{$jogo['id']}'. Palpite: " . json_encode($palpite));
                        continue;
                    }

                    // Obtém os gols do resultado real do jogo (já validado como NxN acima)
                    $resultado = explode("x", $jogo["score"]);
                    $golsCasaReal = (int)$resultado[0];
                    $golsForaReal = (int)$resultado[1];

                    // Calcula a pontuação conforme as novas regras
                    // 1. Na mosca: 12 pontos
                    if ($golsCasaReal === $palpiteCasa && $golsForaReal === $palpiteFora) {
                        $pontuacaoTotal += 12;
                        $acertos_na_mosca++;
                    } else {
                        // 2. Acertar o vencedor: 5 pontos
                        $vencedorReal = ($golsCasaReal > $golsForaReal) ? 'casa' : (($golsForaReal > $golsCasaReal) ? 'fora' : 'empate');
                        $vencedorPalpite = ($palpiteCasa > $palpiteFora) ? 'casa' : (($palpiteFora > $palpiteCasa) ? 'fora' : 'empate');

                        if ($vencedorReal === $vencedorPalpite) {
                            $pontuacaoTotal += 5;
                            $acertos_vencedor_ou_empate++;
                        }
                        // Nota: Empate está coberto pela lógica acima.
                        // Se acertou o empate, e não foi na mosca, ganha 5 pontos.
                    }
                }

                // Adiciona o apostador ao array de resultados
                $apostadores[] = [
                    'nome' => $apostador,
                    'pontuacao' => $pontuacaoTotal,
                    'na_mosca' => $acertos_na_mosca,
                    'vencedor_empate' => $acertos_vencedor_ou_empate
                ];
            }
        } else {
             error_log("DEBUG PHP (palpites): Dados do Firebase para rodada '$rodadaNome' não são um array ou estão vazios após decode. Response: " . substr($response, 0, 200));
        }
    } else {
        // Loga erro se a requisição Firebase falhou
        error_log("ERRO PHP (palpites): Falha ao buscar dados da rodada '$rodadaNome' no Firebase. HTTP Code: $httpCode, cURL Error: $curlError. Response: " . substr($response, 0, 200));
    }

    // Ordena os apostadores pela pontuação em ordem decrescente
    usort($apostadores, function($a, $b) {
        if ($b['pontuacao'] === $a['pontuacao']) {
            // Critério de desempate 1: mais acertos "Na Mosca"
            if ($b['na_mosca'] === $a['na_mosca']) {
                // Critério de desempate 2: mais acertos de "Vencedor/Empate"
                if ($b['vencedor_empate'] === $a['vencedor_empate']) {
                    // Critério de desempate 3: ordem alfabética do nome
                    return strcmp($a['nome'], $b['nome']);
                }
                return $b['vencedor_empate'] - $a['vencedor_empate'];
            }
            return $b['na_mosca'] - $a['na_mosca'];
        }
        return $b['pontuacao'] - $a['pontuacao'];
    });

    return $apostadores; // Retorna o array de apostadores com suas pontuações
}


// Verifica se a requisição é AJAX. Se for, retorna os dados em JSON.
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || (isset($_GET['ajax']) && $_GET['ajax'] == '1')) {
    header('Content-Type: application/json');
    $apostadores = calcularPontuacoes($dadosPlacar['jogos'], $dadosPlacar['rodada']);
    $dadosRetornoAjax = array_merge($dadosPlacar, ['apostadores' => $apostadores]); // Inclui as novas chaves
    echo json_encode($dadosRetornoAjax);
    exit;
}

// Se não for AJAX, renderiza a página HTML completa
$apostadores = calcularPontuacoes($dadosPlacar['jogos'], $dadosPlacar['rodada']);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bolão entre Amigos - Classificação e Regras</title>
    <link rel="stylesheet" href="estilo.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="container">
    <h1>Bolão entre Amigos</h1>
    <a href="index.php" class="atualizar-link" style="margin-bottom: 5px;">Ver Jogos</a>
    <a href="palpites.php?update=1" class="atualizar-link">Atualizar Classificação</a>
     <div id="rodada-info" class="rodada-info"><?= htmlspecialchars($dadosPlacar['rodada'] ?? 'Carregando...') ?></div>
    <div id="loading-indicator" class="loading-indicator">Carregando dados...</div>

    <div id="main-error-box" class="message-box error-message" style="display: none;"></div>
    <div id="warning-box" class="message-box warning-message" style="display: none;"></div>
    <div id="cache-notice" class="cache-notice"></div>


    <div class="apostadores-section classificacao-apostadores">
        <h2>Classificação dos Apostadores</h2>
        <table id="apostadores-tabela" class="classificacao-table">
            <thead>
                <tr>
                    <th>Pos</th>
                    <th>Apostador</th>
                    <th>Pontos</th>
                    <th>Na Mosca</th>
                    <th>Vencedor/Empate</th>
                </tr>
            </thead>
            <tbody id="tabela-classificacao-body">
                <?php // Preenche a tabela de classificação diretamente com PHP ?>
                <?php if (empty($apostadores)): ?>
                    <tr><td colspan="5">Nenhum palpite registrado ou erro ao calcular.</td></tr>
                <?php else: ?>
                    <?php foreach ($apostadores as $index => $apostador): ?>
                        <tr>
                            <td class="posicao"><?= $index + 1 ?>º</td>
                            <td><?= htmlspecialchars($apostador['nome']) ?></td>
                            <td><strong><?= $apostador['pontuacao'] ?></strong></td>
                            <td><?= $apostador['na_mosca'] ?></td>
                            <td><?= $apostador['vencedor_empate'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="regras-premiacao-section" style="margin-top: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9;">
        <h3 style="text-align: center; color: #1877f2; margin-top: 0;">Regras de Pontuação e Premiação</h3>
        
        <h4>Pontuação por Jogo:</h4>
        <ul>
            <li><strong>Na Mosca (12 pontos):</strong> Acertar o placar completo com os gols do vencedor e do perdedor.
                <ul><li><em>Exemplo: Resultado 2x1, Palpite 2x1.</em></li></ul>
            </li>
            <li><strong>Acertar Vencedor (5 pontos):</strong> Acertar o time vencedor, mesmo que o placar de gols seja diferente (não cumulativo com "Na Mosca").
                <ul><li><em>Exemplo: Resultado 2x1, Palpite 3x1 ou 1x0.</em></li></ul>
            </li>
            <li><strong>Acertar Empate (5 pontos):</strong> Acertar o empate, mesmo que o placar de gols seja diferente (não cumulativo com "Na Mosca").
                <ul><li><em>Exemplo: Resultado 2x2, Palpite 1x1 ou 0x0.</em></li></ul>
            </li>
        </ul>

        <h4>Premiação e Distribuição:</h4>
        <ul>
            <li><strong>Primeiro colocado:</strong> receberá 70% do total arrecadado.</li>
            <li><strong>Segundo colocado:</strong> receberá 20% do total arrecadado.</li>
            <li><strong>Taxa de administração:</strong> 10% do total arrecadado.</li>
            <li>Em caso de empate na pontuação para o 1º ou 2º lugar, o prêmio correspondente à(s) posição(ões) empatada(s) será dividido igualmente entre os empatados.</li>
        </ul>
    </div>


    <div style="text-align: center; margin-top: 20px;">
        <a href="index.php" style="color: #007bff; text-decoration: none;">« Voltar para Jogos</a>
    </div>
</div>

<script>
// JavaScript inline para atualizar a classificação via AJAX
const rodadaInfoElement = document.getElementById('rodada-info');
const mainErrorBox = document.getElementById('main-error-box');
const warningBox = document.getElementById('warning-box');
const loadingIndicatorElement = document.getElementById('loading-indicator');
const cacheNoticeElement = document.getElementById('cache-notice');
const apostadoresTabelaBody = document.querySelector('#apostadores-tabela tbody');

let isFetching = false;

function sanitizeHTML(str) {
    if (typeof str !== 'string') return str;
    const temp = document.createElement('div');
    temp.textContent = str;
    return temp.innerHTML;
}

// Esta função renderiza TODA a lista de apostadores na tabela
function renderApostadores(apostadores) {
    if (!apostadoresTabelaBody) return;

    if (!apostadores || !Array.isArray(apostadores) || apostadores.length === 0) {
        apostadoresTabelaBody.innerHTML = '<tr><td colspan="5">Nenhum palpite registrado ou erro ao calcular.</td></tr>';
        return;
    }

    let html = '';
    apostadores.forEach((apostador, index) => {
        html += `
            <tr>
                <td class="posicao">${index + 1}º</td>
                <td>${sanitizeHTML(apostador.nome)}</td>
                <td><strong>${sanitizeHTML(apostador.pontuacao)}</strong></td>
                <td>${sanitizeHTML(apostador.na_mosca)}</td>
                <td>${sanitizeHTML(apostador.vencedor_empate)}</td>
            </tr>
        `;
    });
    apostadoresTabelaBody.innerHTML = html;
}

// Função principal para exibir os dados (apenas a classificação e rodada aqui)
function displayData(data) {
    if (rodadaInfoElement) rodadaInfoElement.textContent = sanitizeHTML(data.rodada || 'Rodada Indisponível');
    if (mainErrorBox) { mainErrorBox.style.display = 'none'; mainErrorBox.textContent = ''; }
    if (warningBox) { warningBox.style.display = 'none'; warningBox.textContent = ''; }
    if (cacheNoticeElement) cacheNoticeElement.textContent = '';

    // Exibe erros ou avisos
    if (data.error) {
        if (warningBox) {
            warningBox.textContent = sanitizeHTML(data.error);
            warningBox.style.display = 'block';
        }
    }

    // Renderiza a classificação dos apostadores
    if (data.apostadores) {
        renderApostadores(data.apostadores);
    } else {
         if (apostadoresTabelaBody) {
              apostadoresTabelaBody.innerHTML = '<tr><td colspan="5">Erro ao carregar classificação ou dados ausentes.</td></tr>';
         }
    }

    if (data.cache_source && cacheNoticeElement) {
        cacheNoticeElement.textContent = 'Classificação carregada do cache. Clique em "Atualizar Classificação" para buscar as últimas informações.';
    }
}

// Função para buscar dados atualizados via AJAX
function atualizarDados() {
    if (isFetching) return;
    isFetching = true;
    if(loadingIndicatorElement) loadingIndicatorElement.style.display = 'block';

    fetch('?ajax=1&t=' + new Date().getTime(), { cache: 'no-store' })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                     throw new Error(`Erro na requisição (HTTP ${response.status}): ${text.substring(0, 200)}...`);
                });
            }
            return response.json();
        })
        .then(data => {
            displayData(data);
        })
        .catch(error => {
            console.error('Erro ao atualizar classificação:', error);
            const fallbackErrorMsg = `Falha ao atualizar classificação: ${sanitizeHTML(error.message)}. Verifique sua conexão e o log do servidor.`;
            if (warningBox) {
                warningBox.textContent = fallbackErrorMsg;
                warningBox.style.display = 'block';
            }
            if (apostadoresTabelaBody) {
                 apostadoresTabelaBody.innerHTML = '<tr><td colspan="5">Erro ao carregar classificação.</td></tr>';
            }
        })
        .finally(() => {
            isFetching = false;
            if(loadingIndicatorElement) loadingIndicatorElement.style.display = 'none';
            setTimeout(atualizarDados, 120000); // Atualiza a cada 2 minutos
        });
}

// Ponto de partida: Exibe os dados iniciais renderizados pelo PHP
const initialData = <?php echo json_encode(array_merge($dadosPlacar, ['apostadores' => $apostadores])); ?>;
displayData(initialData);

// Agenda a primeira atualização automática após um delay inicial
setTimeout(atualizarDados, 120000);

// Adiciona um listener para o link de "Atualizar Classificação"
document.querySelector('.atualizar-link[href^="palpites.php?update=1"]').addEventListener('click', function(event) {
    event.preventDefault();
    atualizarDados();
});
</script>
</body>
</html>