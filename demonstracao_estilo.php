<?php
/**
 * Demonstração do Novo Estilo do Sistema
 */

echo "<h1>🎨 Demonstração do Novo Estilo</h1>";

echo "<div style='background:#f8f9fa;padding:20px;margin:20px 0;border-radius:10px;'>";

echo "<h2>✨ Estilos Atualizados Aplicados:</h2>";

echo "<h3>1. Corpo da Página (body)</h3>";
echo "<ul>";
echo "<li>Gradiente moderno: linear-gradient(135deg, #667eea 0%, #764ba2 100%)</li>";
echo "<li>Altura mínima de 100vh para ocupar toda tela</li>";
echo "<li>Fonte moderna do sistema (-apple-system, BlinkMacSystemFont, etc)</li>";
echo "</ul>";

echo "<h3>2. Container Principal (.container)</h3>";
echo "<ul>";
echo "<li>Largura aumentada para 900px</li>";
echo "<li>Bordas arredondadas maiores (16px)</li>";
echo "<li>Sombra mais pronunciada: 0 10px 30px rgba(0,0,0,0.15)</li>";
echo "<li>Efeito de vidro (backdrop-filter: blur)</li>";
echo "<li>Borda sutil com transparência</li>";
echo "</ul>";

echo "<h3>3. Título Principal (h1)</h3>";
echo "<ul>";
echo "<li>Tamanho aumentado para 2.2em</li>";
echo "<li>Texto em gradiente azul (#3498db → #2c3e50)</li>";
echo "<li>Sombra de texto para destaque</li>";
echo "<li>Efeito de texto cortado com transparência</li>";
echo "</ul>";

echo "<h3>4. Informações da Rodada (.rodada-info)</h3>";
echo "<ul>";
echo "<li>Gradiente de fundo sutil</li>";
echo "<li>Borda lateral colorida (5px solid #3498db)</li>";
echo "<li>Padding aumentado para melhor espaçamento</li>";
echo "<li>Cor do texto mais escura (#2c3e50)</li>";
echo "</ul>";

echo "<h3>5. Links de Ação (.atualizar-link)</h3>";
echo "<ul>";
echo "<li>Design de botão moderno com bordas arredondadas</li>";
echo "<li>Efeito hover com elevação e mudança de cor</li>";
echo "<li>Animação suave de transformação</li>";
echo "<li>Sombra dinâmica no hover</li>";
echo "</ul>";

echo "</div>";

echo "<div style='background:#e8f5e8;padding:20px;margin:20px 0;border-radius:10px;border-left:5px solid #28a745;'>";
echo "<h2>🚀 Benefícios do Novo Design:</h2>";
echo "<ul>";
echo "<li>Visual mais moderno e profissional</li>";
echo "<li>Melhor hierarquia visual dos elementos</li>";
echo "<li>Espaçamentos mais equilibrados</li>";
echo "<li>Efeitos de interação mais fluidos</li>";
echo "<li>Consistência com design systems modernos</li>";
echo "</ul>";
echo "</div>";

echo "<div style='text-align:center;margin:30px 0;'>";
echo "<h2>🎯 Veja o Resultado:</h2>";
echo "<p><a href='index.php' style='display:inline-block;padding:15px 30px;background:#3498db;color:white;text-decoration:none;border-radius:25px;font-weight:bold;box-shadow:0 5px 15px rgba(52,152,219,0.4);transition:all 0.3s;'>🏠 Ver Página Inicial</a></p>";
echo "</div>";

// Estilos
echo "
<style>
body { 
    font-family: 'Segoe UI', Arial, sans-serif; 
    margin: 20px; 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #333;
    min-height: 100vh;
}
h1 { 
    color: #2c3e50; 
    border-bottom: 4px solid #3498db; 
    padding-bottom: 15px; 
    text-align: center;
    background: linear-gradient(45deg, #3498db, #2c3e50);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
h2 { 
    color: #34495e; 
    margin-top: 30px; 
}
h3 { 
    color: #2c3e50; 
    margin: 20px 0 10px 0; 
}
ul li { 
    margin: 8px 0; 
    line-height: 1.6; 
}
a { 
    color: #3498db; 
    text-decoration: none; 
}
a:hover { 
    text-decoration: underline; 
}
</style>
";

echo "<hr><p style='text-align:center;color:#666;'><small>🎨 Demonstração criada em " . date('d/m/Y H:i:s') . "</small></p>";
?>