<?php
require_once 'configs/config.php';

$arquivo_senha_rodada = __DIR__ . '/senha_rodada.txt'; // Caminho para o arquivo de senha

$dadosAposta = obterDadosPlacar();
$jogosAbertosParaAposta = [];
if (isset($dadosAposta['jogos']) && is_array($dadosAposta['jogos'])) {
    foreach ($dadosAposta['jogos'] as $jogo) {
        if (isGameFuture($jogo['datetime'])) {
            $jogosAbertosParaAposta[] = $jogo;
        }
    }
    $dadosAposta['jogos'] = $jogosAbertosParaAposta;
}

$mensagem_form = '';
$senha_rodada_obrigatoria = file_exists($arquivo_senha_rodada) && !empty(trim(file_get_contents($arquivo_senha_rodada)));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_apostador = trim($_POST['nome_apostador'] ?? '');
    $senha_submetida = trim($_POST['senha_rodada_aposta'] ?? ''); // Senha submetida pelo usuário
    $palpites_submetidos = $_POST['palpites'] ?? [];
    $rodada_atual_nome = $dadosAposta['rodada'] ?? 'rodada_desconhecida';

    $nome_apostador_seguro = htmlspecialchars(preg_replace('/[\.\$\#\[\]\/]/', '', $nome_apostador));
    
    // Validação da senha da rodada, se obrigatória
    $senha_valida = true; // Assume que é válida se não for obrigatória
    if ($senha_rodada_obrigatoria) {
        $senha_correta_rodada = trim(file_get_contents($arquivo_senha_rodada));
        if (empty($senha_submetida)) { // Verifica se a senha foi submetida
            $senha_valida = false;
            $mensagem_form = "Erro: A senha da rodada é obrigatória!";
        } elseif ($senha_submetida !== $senha_correta_rodada) {
            $senha_valida = false;
            $mensagem_form = "Erro: Senha da rodada incorreta!";
        }
    }

    if (empty($nome_apostador_seguro)) {
        $mensagem_form = "Nome do apostador inválido ou vazio.";
    } elseif (!$senha_valida) {
        // Mensagem de senha inválida já foi definida acima
    } elseif (!empty($palpites_submetidos)) {
        $aposta_para_salvar = ['timestamp_aposta' => time(), 'palpites' => []];
        $valid_jogo_ids = array_column($dadosAposta['jogos'], 'id');

        foreach ($palpites_submetidos as $jogo_id => $palpite_placar) {
            if (!in_array($jogo_id, $valid_jogo_ids)) {
                continue;
            }
            $placar_time1 = isset($palpite_placar['time1']) && is_numeric($palpite_placar['time1']) && $palpite_placar['time1'] >= 0 ? intval($palpite_placar['time1']) : null;
            $placar_time2 = isset($palpite_placar['time2']) && is_numeric($palpite_placar['time2']) && $palpite_placar['time2'] >= 0 ? intval($palpite_placar['time2']) : null;

            if ($placar_time1 !== null && $placar_time2 !== null) {
                $aposta_para_salvar['palpites'][$jogo_id] = ['time1' => $placar_time1, 'time2' => $placar_time2];
            }
        }

        if (!empty($aposta_para_salvar['palpites'])) {
            if ($rodada_atual_nome === 'Rodada Indisponível' || $rodada_atual_nome === 'Rodada Desconhecida') {
                $mensagem_form = "Erro: Nome da rodada inválido.";
            } else {
                $resultado_save = salvarApostaFirebase($rodada_atual_nome, $nome_apostador_seguro, $aposta_para_salvar);
                if ($resultado_save['success']) {
                    header('Location: index.php?palpite_salvo=1&apostador=' . rawurlencode($nome_apostador_seguro) . '&rodada=' . rawurlencode($rodada_atual_nome));
                    exit;
                } else {
                    $mensagem_form = "Erro: " . $resultado_save['message'];
                }
            }
        } else {
            $mensagem_form = "Nenhum palpite válido submetido.";
        }
    } else {
        $mensagem_form = "Preencha seu nome e os palpites" . ($senha_rodada_obrigatoria ? " e a senha da rodada" : "") . ".";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fazer Aposta - Bolão</title>
    <link rel="stylesheet" href="estilo.css?v=<?php echo time(); ?>">
    <style>
        .aposta-jogo-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; flex-wrap: wrap; }
        .aposta-jogo-item:last-child { border-bottom: none; }
        .aposta-time-info { display: flex; align-items: center; flex-basis: 35%; box-sizing: border-box; }
        .aposta-time-info.time1 { justify-content: flex-end; text-align: right; padding-right: 5px;}
        .aposta-time-info.time2 { justify-content: flex-start; text-align: left; padding-left: 5px;}
        .aposta-time-logo img { width: 25px; height: 25px; object-fit: contain; }
        .aposta-time-name { margin: 0 8px; font-weight: 500; word-break: break-word; }
        .aposta-placar-inputs { display: flex; align-items: center; justify-content: center; flex-basis: 20%; box-sizing: border-box; }
        .aposta-placar-inputs input[type="number"] { width: 45px; padding: 8px 5px; text-align: center; border: 1px solid #ccc; border-radius: 4px; margin: 0 3px; -moz-appearance: textfield; flex-grow: 0; flex-shrink: 0; }
        .aposta-placar-inputs input::-webkit-outer-spin-button,
        .aposta-placar-inputs input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .vs-separator { margin: 0 5px; font-weight: bold; flex-shrink: 0; }
        .aposta-jogo-status { flex-basis: 100%; text-align: center; margin-top: 8px; font-size: 0.9em; color: #555; }
        .aposta-jogo-status small { display: inline-block; margin: 0 5px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"], .form-group input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .submit-button { display: inline-block; padding: 12px 25px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; font-size: 1.1em; border: none; cursor: pointer; transition: background-color 0.2s; }
        .submit-button:hover { background-color: #218838; }
        .message-box { padding: 10px; margin-bottom: 15px; border: 1px solid transparent; border-radius: 4px; text-align: center; }
        .message-box.error-message { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
        .regras-premiacao-section { margin-top: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; }
        .regras-premiacao-section h3, .regras-premiacao-section h4 { color: #1877f2; margin-top: 0; margin-bottom: 10px; }
        .regras-premiacao-section ul { list-style-type: disc; padding-left: 20px; margin-bottom: 15px; }
        .regras-premiacao-section ul ul { list-style-type: circle; margin-top: 5px; margin-bottom: 5px; }
        .regras-premiacao-section li { margin-bottom: 5px; }
        @media (max-width: 600px) {
            .aposta-jogo-item { flex-direction: column; align-items: center; }
            .aposta-time-info { flex-basis: auto; width: 100%; justify-content: center; margin-bottom: 5px; padding: 0; }
            .aposta-placar-inputs { flex-basis: auto; width: 100%; justify-content: center; margin-top: 5px; }
            .aposta-jogo-status { margin-top: 5px; padding-bottom: 5px; }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Fazer sua Aposta</h1>
    <p style="text-align:center; margin-bottom:10px;">Rodada: <strong><?= htmlspecialchars($dadosAposta['rodada'] ?? 'N/A') ?></strong></p>

    <?php if (!empty($mensagem_form)): ?>
        <div class="message-box <?= strpos(strtolower($mensagem_form), 'erro') !== false ? 'error-message' : '' ?>"><?= $mensagem_form ?></div>
    <?php endif; ?>

    <?php if (!empty($dadosAposta['error'])): ?>
        <div class="message-box error-message"><?= htmlspecialchars($dadosAposta['error']) ?></div>
    <?php elseif (empty($dadosAposta['jogos'])): ?>
        <div class="message-box warning-message">Nenhum jogo disponível para aposta.</div>
    <?php else: ?>
        <form action="aposta.php" method="POST">
            <div class="form-group">
                <label for="nome_apostador">Seu Nome:</label>
                <input type="text" id="nome_apostador" name="nome_apostador" required value="<?= htmlspecialchars($_POST['nome_apostador'] ?? '') ?>">
            </div>

            <?php if ($senha_rodada_obrigatoria): ?>
            <div class="form-group">
                <label for="senha_rodada_aposta">Senha da Rodada:</label>
                <input type="password" id="senha_rodada_aposta" name="senha_rodada_aposta" required>
            </div>
            <?php endif; ?>

            <h2>Jogos abertos para Aposta:</h2>
            <?php foreach ($dadosAposta['jogos'] as $jogo): ?>
                <div class="aposta-jogo-item">
                    <div class="aposta-time-info time1">
                        <span class="aposta-time-name"><?= htmlspecialchars($jogo['team1']['name']) ?></span>
                        <div class="aposta-time-logo">
                            <img src="<?= htmlspecialchars($jogo['team1']['logo']) ?>" alt="Logo <?= htmlspecialchars($jogo['team1']['name']) ?>" onerror="this.onerror=null; this.src='<?= PLACEHOLDER_LOGO_URL ?>';">
                        </div>
                    </div>
                    <div class="aposta-placar-inputs">
                        <input type="number" name="palpites[<?= htmlspecialchars($jogo['id']) ?>][time1]" min="0" max="99" placeholder="0" required>
                        <span class="vs-separator">X</span>
                        <input type="number" name="palpites[<?= htmlspecialchars($jogo['id']) ?>][time2]" min="0" max="99" placeholder="0" required>
                    </div>
                    <div class="aposta-time-info time2">
                        <div class="aposta-time-logo">
                            <img src="<?= htmlspecialchars($jogo['team2']['logo']) ?>" alt="Logo <?= htmlspecialchars($jogo['team2']['name']) ?>" onerror="this.onerror=null; this.src='<?= PLACEHOLDER_LOGO_URL ?>';">
                        </div>
                        <span class="aposta-time-name"><?= htmlspecialchars($jogo['team2']['name']) ?></span>
                    </div>
                    <div class="aposta-jogo-status">
                        <small>Data/Hora: <?= htmlspecialchars($jogo['datetime']) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>

            <div style="text-align: center; margin-top: 25px;">
                <button type="submit" class="submit-button">Confirmar Palpites</button>
            </div>
        </form>
    <?php endif; ?>

    <div class="regras-premiacao-section">
        <h3 style="text-align: center;">Regras do Bolão e Premiação</h3>
        <h4>Pontuação por Jogo:</h4>
        <ul>
            <li><strong>Na Mosca (12 pontos):</strong> Acertar o placar completo.</li>
            <li><strong>Acertar Vencedor (5 pontos):</strong> Acertar o time vencedor (não cumulativo com "Na Mosca").</li>
            <li><strong>Acertar Empate (5 pontos):</strong> Acertar o empate (não cumulativo com "Na Mosca").</li>
            <li>Palpites devem ser enviados até o horário de início de cada jogo.</li>
        </ul>
        <h4>Premiação e Distribuição:</h4>
        <ul>
            <li><strong>Primeiro colocado:</strong> 70% do total arrecadado.</li>
            <li><strong>Segundo colocado:</strong> 20% do total arrecadado.</li>
            <li><strong>Taxa de administração:</strong> 10% do total arrecadado.</li>
            <li>Em caso de empate, o prêmio será dividido.</li>
            <li>Acompanhe a classificação detalhada na página <a href="palpites.php">Classificação</a>.</li>
        </ul>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <a href="index.php" style="color: #007bff; text-decoration: none;">« Voltar</a>
    </div>
</div>
</body>
</html>