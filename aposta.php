<?php
session_start();
require_once 'configs/config.php';
require_once 'auth_functions.php';

// Requer login para acessar esta página
requireLogin();

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

// Obter dados do usuário logado
$user = getCurrentUser();
$nome_apostador = $user['name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $palpites_submetidos = $_POST['palpites'] ?? [];
    $rodada_atual_nome = $dadosAposta['rodada'] ?? 'rodada_desconhecida';

    if (!empty($palpites_submetidos)) {
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
                $resultado_save = salvarApostaFirebase($rodada_atual_nome, $nome_apostador, $aposta_para_salvar);
                if ($resultado_save['success']) {
                    header('Location: index.php?palpite_salvo=1&apostador=' . rawurlencode($nome_apostador) . '&rodada=' . rawurlencode($rodada_atual_nome));
                    exit;
                } else {
                    $mensagem_form = "Erro: " . $resultado_save['message'];
                }
            }
        } else {
            $mensagem_form = "Nenhum palpite válido submetido.";
        }
    } else {
        $mensagem_form = "Preencha os palpites.";
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
        .user-info-box {
            background: #e8f4f8;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .user-info-box h3 {
            margin: 0 0 10px 0;
            color: #0c5460;
        }
        .user-info-box p {
            margin: 5px 0;
            color: #155724;
        }
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
    
    <!-- Informações do usuário logado -->
    <div class="user-info-box">
        <h3>👤 Apostando como: <?php echo htmlspecialchars($user['name']); ?></h3>
        <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
        <p>Rodada: <strong><?php echo htmlspecialchars($dadosAposta['rodada'] ?? 'N/A'); ?></strong></p>
    </div>

    <?php if (!empty($mensagem_form)): ?>
        <div class="message-box <?= strpos(strtolower($mensagem_form), 'erro') !== false ? 'error-message' : '' ?>">
            <?= $mensagem_form ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($dadosAposta['error'])): ?>
        <div class="message-box error-message"><?= htmlspecialchars($dadosAposta['error']) ?></div>
    <?php elseif (empty($dadosAposta['jogos'])): ?>
        <div class="message-box warning-message">Nenhum jogo disponível para aposta.</div>
    <?php else: ?>
        <form action="aposta.php" method="POST">
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
                <a href="index.php" style="display: inline-block; margin-left: 15px; padding: 12px 25px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 5px;">Cancelar</a>
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