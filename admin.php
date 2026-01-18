<?php
// ATENÇÃO: As linhas abaixo ativam a exibição de erros PHP.
// REMOVA ou desabilite-as (mude 1 para 0) quando o site estiver em produção (online).
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclui config.php para ter acesso a constantes como FIREBASE_URL, CACHE_FILE
// e funções auxiliares como getTeamAbbreviation.
require_once 'configs/config.php'; // FIREBASE_URL é definido aqui

session_start();

// Credenciais de acesso admin
$email_admin = 'marcos2026@gmail.com';
$senha_correta_admin = 'Frenesi04'; // Senha para acessar a área administrativa
$pagina_login = true; 
$arquivo_senha_rodada = __DIR__ . '/senha_rodada.txt';

// Funções de Firebase específicas para este script (para evitar conflitos com config.php se houver)
if (!function_exists('admin_obterDadosFirebase')) {
    function admin_obterDadosFirebase($path) {
        $url = FIREBASE_URL . rtrim($path, '/') . '.json'; // Usa a constante FIREBASE_URL definida em config.php
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("ADMIN Firebase cURL Error for path '$path': " . $curlError);
            return null;
        }
        if ($httpCode != 200) {
            error_log("ADMIN Firebase HTTP Error $httpCode for path '$path'. Response: " . substr($response,0,200) );
            return null;
        }
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("ADMIN Firebase JSON Decode Error for path '$path': " . json_last_error_msg());
            return null;
        }
        return $data;
    }
}

if (!function_exists('admin_salvarDadosFirebase')) {
    function admin_salvarDadosFirebase($path, $data) {
        $url = FIREBASE_URL . rtrim($path, '/') . '.json'; // Usa a constante FIREBASE_URL definida em config.php
        $jsonData = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch); 
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch); 
        curl_close($ch);

        if ($curlError) {
            error_log("ADMIN Firebase cURL Error on SAVE for path '$path': " . $curlError);
            return false;
        }
        if (!($httpcode == 200 || $httpcode == 201)) {
             error_log("ADMIN Firebase HTTP Error $httpcode on SAVE for path '$path'. Response: ". substr($response,0,200));
             return false;
        }
        return true;
    }
}


// Processar logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    unset($_SESSION['admin_logado']);
    header('Location: admin.php');
    exit;
}

// Processar tentativa de login do admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['senha_admin_login'])) { 
    if ($_POST['senha_admin_login'] === $senha_correta_admin) {
        $_SESSION['admin_logado'] = true;
        $pagina_login = false;
        header('Location: 545admin.php'); 
        exit;
    } else {
        $erro_login = 'Senha incorreta!';
    }
}

// Se já estiver logado, não mostra a página de login
if (isset($_SESSION['admin_logado']) && $_SESSION['admin_logado'] === true) {
    $pagina_login = false;
}

$mensagem_senha_rodada = '';
// Processar atualização da senha da rodada
if (!$pagina_login && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nova_senha_rodada'])) {
    // (código de atualização da senha da rodada - mantido como antes)
    $nova_senha = trim($_POST['nova_senha_rodada']);
    if (!empty($nova_senha)) {
        if (file_put_contents($arquivo_senha_rodada, $nova_senha) !== false) {
            $mensagem_senha_rodada = "<p class='message-box' style='color:green; background-color:#e6ffe6;'>Senha da rodada atualizada com sucesso!</p>";
        } else {
            $mensagem_senha_rodada = "<p class='message-box error-message'>Erro ao salvar a senha da rodada. Verifique as permissões do arquivo/pasta.</p>";
        }
    } else {
        if (file_exists($arquivo_senha_rodada)) {
            unlink($arquivo_senha_rodada);
        }
        $mensagem_senha_rodada = "<p class='message-box' style='color:orange; background-color:#fff0e6;'>Senha da rodada removida (verificação desabilitada).</p>";
    }
}

// Lógica para processar a atualização de um palpite
if (!$pagina_login && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'atualizar_palpite') {
    // (código de atualização de palpite - mantido como antes, usando admin_salvarDadosFirebase)
    $rodadaNome = $_POST['rodada_nome'] ?? null;
    $apostadorNome = $_POST['apostador_nome'] ?? null;
    $jogoId = $_POST['jogo_id'] ?? null;
    $novoPalpiteTime1 = isset($_POST['novo_palpite_time1']) && trim($_POST['novo_palpite_time1']) !== '' ? trim($_POST['novo_palpite_time1']) : null;
    $novoPalpiteTime2 = isset($_POST['novo_palpite_time2']) && trim($_POST['novo_palpite_time2']) !== '' ? trim($_POST['novo_palpite_time2']) : null;

    if ($rodadaNome && $apostadorNome && $jogoId && $novoPalpiteTime1 !== null && $novoPalpiteTime2 !== null && is_numeric($novoPalpiteTime1) && is_numeric($novoPalpiteTime2)) {
        $novoPalpiteObj = ['time1' => intval($novoPalpiteTime1), 'time2' => intval($novoPalpiteTime2)];
        $firebasePath = 'apostas/' . rawurlencode($rodadaNome) . '/' . rawurlencode($apostadorNome) . '/palpites/' . $jogoId;
        $resultadoUpdate = admin_salvarDadosFirebase($firebasePath, $novoPalpiteObj); 
        if ($resultadoUpdate) {
            header('Location: 545admin.php?ver_rodada=' . rawurlencode($rodadaNome) . '&ver_apostador=' . rawurlencode($apostadorNome) . '&update=success#palpites_apostador');
            exit;
        } else {
            $erro_atualizacao = 'Erro ao atualizar o palpite no Firebase.';
        }
    } else {
        $erro_atualizacao = 'Palpites inválidos ou dados insuficientes para atualizar.';
    }
}

// Lógica para processar a remoção de um palpite (GET request)
if (!$pagina_login && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'remover_palpite') {
    // (código de remoção de palpite - mantido como antes, usando admin_salvarDadosFirebase)
    $rodadaNome = $_GET['rodada_nome'] ?? null;
    $apostadorNome = $_GET['apostador_nome'] ?? null;
    $jogoIdParaRemover = $_GET['jogo_id'] ?? null;
    if ($rodadaNome && $apostadorNome && $jogoIdParaRemover) {
        $firebasePath = 'apostas/' . rawurlencode($rodadaNome) . '/' . rawurlencode($apostadorNome) . '/palpites/' . $jogoIdParaRemover;
        $resultadoDelete = admin_salvarDadosFirebase($firebasePath, null); 
        $redirectParams = '&ver_rodada=' . rawurlencode($rodadaNome) . '&ver_apostador=' . rawurlencode($apostadorNome) . '#palpites_apostador';
        if ($resultadoDelete) {
            header('Location: 545admin.php?delete_status=success_palpite' . $redirectParams);
        } else {
            header('Location: 545admin.php?delete_status=error_palpite' . $redirectParams);
        }
    } else {
        header('Location: 545admin.php?delete_status=missing_data_palpite' . ($rodadaNome ? '&ver_rodada=' . rawurlencode($rodadaNome) : '') . ($apostadorNome ? '&ver_apostador=' . rawurlencode($apostadorNome) : '') . '#palpites_apostador');
    }
    exit;
}

// Lógica para processar a remoção de um PARTICIPANTE INTEIRO de uma rodada
if (!$pagina_login && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'remover_participante') {
    // (código de remoção de participante - mantido como antes, usando admin_salvarDadosFirebase)
    $rodadaNome = $_GET['rodada_nome'] ?? null;
    $apostadorNomeParaRemover = $_GET['apostador_nome'] ?? null;
    error_log("ADMIN Tentando remover participante: Rodada='$rodadaNome', Apostador='$apostadorNomeParaRemover'");
    if ($rodadaNome && $apostadorNomeParaRemover) {
        $firebasePathParticipante = 'apostas/' . rawurlencode($rodadaNome) . '/' . rawurlencode($apostadorNomeParaRemover);
        error_log("ADMIN Firebase path para remover participante: " . $firebasePathParticipante);
        $resultadoDeleteParticipante = admin_salvarDadosFirebase($firebasePathParticipante, null); 
        $redirectParams = 'ver_rodada=' . rawurlencode($rodadaNome) . '#participantes_rodada';
        if ($resultadoDeleteParticipante) {
            error_log("ADMIN Participante removido com sucesso.");
            header('Location: 545admin.php?' . $redirectParams . '&delete_status=success_participante');
        } else {
            error_log("ADMIN Erro ao remover participante do Firebase.");
            header('Location: 545admin.php?' . $redirectParams . '&delete_status=error_participante');
        }
    } else {
        error_log("ADMIN Dados insuficientes para remover participante.");
        $fallbackRedirect = '545admin.php' . ($rodadaNome ? '?ver_rodada=' . rawurlencode($rodadaNome) . '#participantes_rodada' : '');
        header('Location: ' . $fallbackRedirect . '&delete_status=missing_data_participante');
    }
    exit;
}

function obterJogosDaCachePrincipal() {
    // (código da função obterJogosDaCachePrincipal - mantido)
    if (file_exists(CACHE_FILE)) { 
        $cachedData = json_decode(file_get_contents(CACHE_FILE), true);
        if ($cachedData && !empty($cachedData['jogos'])) {
            $jogosReindexados = [];
            foreach ($cachedData['jogos'] as $jogo) {
                if (isset($jogo['id'])) { $jogosReindexados[$jogo['id']] = $jogo; }
            }
            return ['rodada' => ($cachedData['rodada'] ?? 'N/A'), 'jogos' => $jogosReindexados];
        }
    }
    return ['rodada' => 'Rodada Indisponível (Cache vazio)', 'jogos' => [], 'error' => 'Cache principal vazio.'];
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração do Bolão</title>
    <link rel="stylesheet" href="estilo.css?v=<?php echo time(); ?>"> 
</head>
<body>

<header class="admin-header clearfix">
    <?php if (isset($_SESSION['admin_logado']) && $_SESSION['admin_logado'] === true && !$pagina_login): ?>
        <a href="545admin.php?action=logout" class="logout-link">Logout</a>
    <?php endif; ?>
    <h1>Administração do Bolão</h1>
</header>

<div class="container-admin"> 

    <?php if ($pagina_login): ?>
        <div class="login-form">
            <h2>Login Administrativo</h2>
            <?php if (isset($erro_login)): ?>
                <p class="message-box error-message"><?= htmlspecialchars($erro_login) ?></p>
            <?php endif; ?>
            <form method="POST" action="545admin.php">
                <label for="senha-admin-login">Senha:</label> 
                <input type="password" id="senha-admin-login" name="senha_admin_login" required>
                <input type="submit" value="Entrar" class="btn btn-success"> 
            </form>
        </div>
    <?php else: // Se estiver logado ?>
        <h2>Painel Administrativo</h2>
        <p>Bem-vindo à área de administração!</p>

        <!-- Seção para Gerenciar Senha da Rodada -->
        <div class="admin-section">
            <h4>Gerenciar Senha da Rodada para Apostas</h4>
            <?php 
            if (!empty($mensagem_senha_rodada)) {
                echo $mensagem_senha_rodada;
            }
            $senha_rodada_atual = '';
            if (file_exists($arquivo_senha_rodada)) {
                $senha_rodada_atual = trim(file_get_contents($arquivo_senha_rodada));
            }
            ?>
            <form method="POST" action="545admin.php" class="login-form" style="width: auto; margin: 20px 0; padding: 15px; background-color: #f0f2f5; border: 1px solid #ddd;">
                <label for="nova_senha_rodada">Senha Atual/Nova para Apostas:</label>
                <input type="text" id="nova_senha_rodada" name="nova_senha_rodada" value="<?= htmlspecialchars($senha_rodada_atual) ?>" placeholder="Deixe em branco para desabilitar" style="margin-bottom: 10px;">
                <input type="submit" value="Atualizar Senha da Rodada" class="btn btn-primary">
            </form>
            <p><small>Se a senha estiver em branco, a verificação de senha na página de aposta será desabilitada.</small></p>
        </div>
        
        <?php
        // Listagem de Rodadas e Palpites (CÓDIGO EXISTENTE MANTIDO)
        echo "<h3>Rodadas com Palpites:</h3>";
        $rodadasData = admin_obterDadosFirebase('apostas'); // Usar a função com prefixo 'admin_'

        $rodadaSelecionada = null;
        if (isset($_GET['ver_rodada'])) {
            $rodadaSelecionada = trim($_GET['ver_rodada']);
        }

        if ($rodadasData && is_array($rodadasData)) {
            if (empty($rodadasData)) {
                echo "<p>Nenhuma rodada com palpites encontrada.</p>";
            } else {
                echo "<ul>";
                foreach (array_keys($rodadasData) as $nomeRodada) {
                    echo "<li><a href='545admin.php?ver_rodada=" . rawurlencode($nomeRodada) . "'>" . htmlspecialchars($nomeRodada) . "</a></li>";
                }
                echo "</ul>";

                if ($rodadaSelecionada && isset($rodadasData[$rodadaSelecionada])) {
                    echo "<div class='admin-section' id='participantes_rodada'>";
                    echo "<h4>Participantes da Rodada: " . htmlspecialchars($rodadaSelecionada) . "</h4>";
                    $participantesDaRodada = $rodadasData[$rodadaSelecionada];
                    $apostadorSelecionado = isset($_GET['ver_apostador']) ? trim($_GET['ver_apostador']) : null;

                    if (isset($_GET['delete_status'])) {
                        if ($_GET['delete_status'] === 'success_participante') echo "<p class='message-box' style='color:green; background-color:#e6ffe6;'>Participante removido!</p>";
                        elseif ($_GET['delete_status'] === 'error_participante') echo "<p class='message-box error-message'>Erro ao remover participante.</p>";
                        elseif ($_GET['delete_status'] === 'missing_data_participante') echo "<p class='message-box warning-message'>Dados insuficientes para remover participante.</p>";
                    }

                    if (empty($participantesDaRodada)) {
                        echo "<p>Nenhum participante.</p>";
                    } else {
                        echo "<ul>";
                        foreach (array_keys($participantesDaRodada) as $nomeParticipante) {
                            echo "<li>";
                            echo "<a href='545admin.php?ver_rodada=" . rawurlencode($rodadaSelecionada) . "&ver_apostador=" . rawurlencode($nomeParticipante) . "#palpites_apostador'>" . htmlspecialchars($nomeParticipante) . "</a>";
                            echo " <a href='gerar_bilhete.php?rodada=" . rawurlencode($rodadaSelecionada) . "&apostador=" . rawurlencode($nomeParticipante) . "' target='_blank' class='btn btn-info btn-sm' style='margin-left:10px; font-size:0.8em; padding: 2px 6px;'>Gerar Bilhete</a>";
                            echo " <a href='545admin.php?action=remover_participante&rodada_nome=" . rawurlencode($rodadaSelecionada) . "&apostador_nome=" . rawurlencode($nomeParticipante) . "&ver_rodada=" . rawurlencode($rodadaSelecionada) . "' class='btn btn-danger btn-sm' style='font-size:0.8em; padding: 2px 6px; margin-left:5px;' onclick='return confirm(\"Tem certeza que deseja remover " . htmlspecialchars(addslashes($nomeParticipante)) . " e todos os seus palpites desta rodada?\");'>Remover</a>";
                            echo "</li>";
                        }
                        echo "</ul>";

                        if ($apostadorSelecionado && isset($participantesDaRodada[$apostadorSelecionado]['palpites'])) {
                            echo "<div class='admin-section' id='palpites_apostador'>";
                            echo "<h5>Palpites de " . htmlspecialchars($apostadorSelecionado) . ":</h5>";
                            $palpitesDoApostador = $participantesDaRodada[$apostadorSelecionado]['palpites'];
                            $dadosJogosAdmin = obterJogosDaCachePrincipal();
                            $jogosInfo = $dadosJogosAdmin['jogos'] ?? [];
                            $jogoParaEditar = $_GET['editar_palpite'] ?? null;

                            if (isset($erro_atualizacao)) echo "<p class='message-box error-message'>" . htmlspecialchars($erro_atualizacao) . "</p>";
                            if (isset($_GET['update']) && $_GET['update'] === 'success') echo "<p class='message-box' style='color:green; background-color:#e6ffe6;'>Palpite atualizado!</p>";
                            if (isset($_GET['delete_status'])) {
                                if ($_GET['delete_status'] === 'success_palpite') echo "<p class='message-box' style='color:green; background-color:#e6ffe6;'>Palpite removido!</p>";
                                elseif ($_GET['delete_status'] === 'error_palpite') echo "<p class='message-box error-message'>Erro ao remover palpite.</p>";
                                elseif ($_GET['delete_status'] === 'missing_data_palpite') echo "<p class='message-box warning-message'>Dados insuficientes para remover palpite.</p>";
                            }

                            if ($jogoParaEditar && isset($palpitesDoApostador[$jogoParaEditar])) {
                                // Formulário de edição (código existente mantido)
                                $palpiteAtual = $palpitesDoApostador[$jogoParaEditar];
                                $time1NomeEdit = isset($jogosInfo[$jogoParaEditar]['team1']['name']) ? htmlspecialchars($jogosInfo[$jogoParaEditar]['team1']['name']) : 'Time 1';
                                $time2NomeEdit = isset($jogosInfo[$jogoParaEditar]['team2']['name']) ? htmlspecialchars($jogosInfo[$jogoParaEditar]['team2']['name']) : 'Time 2';
                                echo "<h4>Editando: " . $time1NomeEdit . " vs " . $time2NomeEdit . "</h4>";
                                echo "<form method='POST' action='545admin.php?ver_rodada=" . rawurlencode($rodadaSelecionada) . "&ver_apostador=" . rawurlencode($apostadorSelecionado) . "#palpites_apostador' class='edit-palpite-form'>";
                                echo "<input type='hidden' name='action' value='atualizar_palpite'>";
                                echo "<input type='hidden' name='rodada_nome' value='" . htmlspecialchars($rodadaSelecionada) . "'>";
                                echo "<input type='hidden' name='apostador_nome' value='" . htmlspecialchars($apostadorSelecionado) . "'>";
                                echo "<input type='hidden' name='jogo_id' value='" . htmlspecialchars($jogoParaEditar) . "'>";
                                $palpiteTime1_val = ''; $palpiteTime2_val = '';
                                if (is_array($palpiteAtual) && isset($palpiteAtual['time1']) && isset($palpiteAtual['time2'])) {
                                    $palpiteTime1_val = $palpiteAtual['time1']; $palpiteTime2_val = $palpiteAtual['time2'];
                                } elseif (is_string($palpiteAtual) && preg_match("/^(\d+)\s*x\s*(\d+)$/i", trim($palpiteAtual), $pMatches)) {
                                    $palpiteTime1_val = $pMatches[1]; $palpiteTime2_val = $pMatches[2];
                                }
                                echo "<div class='palpite-edit-inputs-admin'>";
                                echo "<input type='number' name='novo_palpite_time1' value='" . htmlspecialchars($palpiteTime1_val) . "' min='0' max='99' required>";
                                echo "<span>x</span>";
                                echo "<input type='number' name='novo_palpite_time2' value='" . htmlspecialchars($palpiteTime2_val) . "' min='0' max='99' required>";
                                echo "</div>";
                                echo "<input type='submit' value='Atualizar Palpite' class='btn btn-success'>"; 
                                echo " <a href='545admin.php?ver_rodada=" . rawurlencode($rodadaSelecionada) . "&ver_apostador=" . rawurlencode($apostadorSelecionado) . "#palpites_apostador' class='btn btn-cancel'>Cancelar</a>"; 
                                echo "</form><br>";
                            }

                            echo "<table class='palpites-table'><thead><tr><th>Time 1</th><th>Palpite</th><th>Time 2</th><th>Ações</th></tr></thead><tbody>";
                            foreach ($palpitesDoApostador as $jogoId => $palpite) {
                                // Tabela de palpites (código existente mantido)
                                if ($jogoParaEditar === $jogoId) continue;
                                $time1Nome = isset($jogosInfo[$jogoId]['team1']['name']) ? htmlspecialchars($jogosInfo[$jogoId]['team1']['name']) : "Jogo ID: $jogoId - T1";
                                $time2Nome = isset($jogosInfo[$jogoId]['team2']['name']) ? htmlspecialchars($jogosInfo[$jogoId]['team2']['name']) : "T2";
                                $palpiteExibicao = "Inválido";
                                if (is_array($palpite) && isset($palpite['time1']) && isset($palpite['time2'])) {
                                    $palpiteExibicao = htmlspecialchars($palpite['time1'] . 'x' . $palpite['time2']);
                                } elseif (is_string($palpite)) {
                                    $palpiteExibicao = htmlspecialchars($palpite);
                                }
                                echo "<tr>";
                                echo "<td>" . $time1Nome . "</td>";
                                echo "<td>" . $palpiteExibicao . "</td>";
                                echo "<td>" . $time2Nome . "</td>";
                                $linkRemoverPalpite = "545admin.php?action=remover_palpite&rodada_nome=" . rawurlencode($rodadaSelecionada) . "&apostador_nome=" . rawurlencode($apostadorSelecionado) . "&jogo_id=" . rawurlencode($jogoId);
                                echo "<td>";
                                echo "<a href='545admin.php?ver_rodada=" . rawurlencode($rodadaSelecionada) . "&ver_apostador=" . rawurlencode($apostadorSelecionado) . "&editar_palpite=" . rawurlencode($jogoId) . "#palpites_apostador' class='btn btn-warning btn-sm' style='margin-right:5px; padding: 2px 6px;'>Editar</a>";
                                echo "<a href='" . $linkRemoverPalpite . "' class='btn btn-danger btn-sm' style='padding: 2px 6px;' onclick='return confirm(\"Remover este palpite?\");'>Remover</a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                            echo "</tbody></table>";
                            echo "</div>"; 
                        }
                    }
                    echo "</div>"; 
                } elseif ($rodadaSelecionada) {
                    echo "<p>Rodada '" . htmlspecialchars($rodadaSelecionada) . "' não encontrada.</p>";
                }
            }
        } else {
            echo "<p>Erro ao buscar dados das rodadas ou nenhuma rodada encontrada.</p>";
            if (empty($rodadasData) && $rodadasData !== null) {
                echo "<p>(Nenhuma aposta registrada ainda no Firebase em 'apostas'.)</p>";
            }
        }
        ?>
    <?php endif; // Fim do if ($pagina_login) ?>
</div> 

<footer class="admin-footer">
    <p>© <?php echo date("Y"); ?> Sistema de Bolão. Todos os direitos reservados.</p>
</footer>

</body>
</html>