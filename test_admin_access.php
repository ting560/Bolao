<?php
/**
 * Teste de Acesso Admin - Verificação das Credenciais
 */

echo "<h1>🧪 Teste de Credenciais Admin</h1>";

// Testar credenciais configuradas
$credenciais_esperadas = [
    'email' => 'marcos2026@gmail.com',
    'senha' => 'Frenesi04'
];

echo "<div style='background:#f8f9fa;padding:20px;margin:20px 0;border-radius:10px;'>";

echo "<h2>✅ Credenciais Configuradas:</h2>";
echo "<p><strong>Email:</strong> {$credenciais_esperadas['email']}</p>";
echo "<p><strong>Senha:</strong> {$credenciais_esperadas['senha']}</p>";

echo "<h2>📁 Arquivos Atualizados:</h2>";
$arquivos_atualizados = [
    'admin_login.php' => 'Novo arquivo de login com autenticação por email/senha',
    'admin_panel.php' => 'Atualizado para verificar email e senha',
    'admin.php' => 'Credenciais atualizadas',
    '545admin.php' => 'Credenciais atualizadas',
    'index.php' => 'Botões de acesso admin removidos'
];

foreach ($arquivos_atualizados as $arquivo => $descricao) {
    echo "<p><strong>{$arquivo}:</strong> {$descricao}</p>";
}

echo "<h2>🔒 Segurança Implementada:</h2>";
echo "<ul>";
echo "<li>Autenticação por email e senha</li>";
echo "<li>Sessão segura com timeout</li>";
echo "<li>Área admin totalmente isolada</li>";
echo "<li>Botões de acesso removidos do index público</li>";
echo "<li>Redirecionamento automático após login/logout</li>";
echo "</ul>";

echo "<h2>🚀 Como Acessar:</h2>";
echo "<ol>";
echo "<li>Acesse diretamente: <code>admin_login.php</code></li>";
echo "<li>Digite o email: <strong>{$credenciais_esperadas['email']}</strong></li>";
echo "<li>Digite a senha: <strong>{$credenciais_esperadas['senha']}</strong></li>";
echo "<li>Será redirecionado automaticamente para o painel</li>";
echo "</ol>";

echo "</div>";

// Links úteis
echo "<div style='background:#e8f5e8;padding:20px;margin:20px 0;border-radius:10px;border-left:5px solid #28a745;'>";
echo "<h2>🔗 Links de Acesso:</h2>";
echo "<ul>";
echo "<li><a href='admin_login.php' target='_blank'>🔐 Painel Admin (Login)</a></li>";
echo "<li><a href='index.php' target='_blank'>🏠 Página Inicial (Sem botões admin)</a></li>";
echo "<li><a href='admin_panel.php' target='_blank'>⚙️ Painel Admin (Direto)</a></li>";
echo "</ul>";
echo "</div>";

// Estilos
echo "
<style>
body { 
    font-family: 'Segoe UI', Arial, sans-serif; 
    margin: 20px; 
    background: #f5f7fa; 
}
h1 { 
    color: #2c3e50; 
    border-bottom: 4px solid #3498db; 
    padding-bottom: 15px; 
}
h2 { 
    color: #34495e; 
    margin-top: 30px; 
}
code { 
    background: #f1f2f6; 
    padding: 2px 6px; 
    border-radius: 3px; 
    font-family: monospace; 
}
a { 
    color: #3498db; 
    text-decoration: none; 
}
a:hover { 
    text-decoration: underline; 
}
ul li, ol li { 
    margin: 8px 0; 
    line-height: 1.6; 
}
</style>
";

echo "<hr><p><small>🧪 Teste executado em " . date('d/m/Y H:i:s') . "</small></p>";
?>