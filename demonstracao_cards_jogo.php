<?php
/**
 * Demonstração do Novo Estilo dos Cards de Jogo
 */

echo "<h1>⚽ Demonstração do Novo Estilo dos Cards de Jogo</h1>";

echo "<div style='background:#f8f9fa;padding:20px;margin:20px 0;border-radius:10px;'>";

echo "<h2>✨ Elementos Atualizados:</h2>";

echo "<h3>1. Card do Jogo (.jogo-item)</h3>";
echo "<ul>";
echo "<li>Sombra mais pronunciada: 0 4px 15px rgba(0,0,0,0.1)</li>";
echo "<li>Efeito hover com elevação: translateY(-3px)</li>";
echo "<li>Sombra hover aumentada: 0 8px 25px rgba(0,0,0,0.15)</li>";
echo "<li>Borda removida para design mais limpo</li>";
echo "</ul>";

echo "<h3>2. Cabeçalho do Jogo (.jogo-header)</h3>";
echo "<ul>";
echo "<li>Gradiente de fundo: linear-gradient(135deg, #f8f9fa, #e9ecef)</li>";
echo "<li>Borda sutil: 2px solid #e9ecef</li>";
echo "<li>Altura mínima aumentada para 140px</li>";
echo "<li>Efeito hover na borda: muda para #3498db</li>";
echo "<li>Hover no fundo: gradiente mais claro</li>";
echo "</ul>";

echo "<h3>3. Informações do Topo (.jogo-top-info)</h3>";
echo "<ul>";
echo "<li>Fonte maior: 1.1em</li>";
echo "<li>Margem inferior aumentada: 15px</li>";
echo "<li>Data/horário em negrito: font-weight 700</li>";
echo "<li>Cor específica: #2c3e50</li>";
echo "</ul>";

echo "<h3>4. Linha do Placar (.jogo-score-line)</h3>";
echo "<ul>";
echo "<li>Espaçamento aumentado: gap 10px</li>";
echo "<li>Margem inferior: 15px</li>";
echo "<li>Alinhamento centralizado aprimorado</li>";
echo "</ul>";

echo "<h3>5. Abreviações dos Times (.time-abbrev)</h3>";
echo "<ul>";
echo "<li>Fonte maior: 1.3em</li>";
echo "<li>Negrito: font-weight 700</li>";
echo "<li>Transformação maiúscula</li>";
echo "<li>Espaçamento de letras: 0.5px</li>";
echo "<li>Cor: #2c3e50</li>";
echo "</ul>";

echo "<h3>6. Logos dos Times (.time-logo)</h3>";
echo "<ul>";
echo "<li>Tamanho aumentado: 40px x 40px</li>";
echo "<li>Borda arredondada: 50%</li>";
echo "<li>Fundo cinza claro: #f8f9fa</li>";
echo "<li>Padding interno: 5px</li>";
echo "</ul>";

echo "<h3>7. Placar (.placar-gol e .placar-x)</h3>";
echo "<ul>";
echo "<li>Fonte muito maior: 2.0em</li>";
echo "<li>Negrito acentuado</li>";
echo "<li>Cor: #2c3e50</li>";
echo "<li>Espaçamento do X: margin 0 8px</li>";
echo "<li>Fonte X: 1.2em, weight 700</li>";
echo "</ul>";

echo "<h3>8. Status do Jogo (.status)</h3>";
echo "<ul>";
echo "<li>Borda arredondada grande: 20px</li>";
echo "<li>Padding: 6px 12px</li>";
echo "<li>Sombra sutil: 0 2px 4px rgba(0,0,0,0.2)</li>";
echo "<li>Gradientes específicos por status:</li>";
echo "<ul>";
echo "<li>Ao Vivo: vermelho (#e74c3c → #c0392b)</li>";
echo "<li>Em Breve: laranja (#f39c12 → #d35400)</li>";
echo "<li>Encerrado: verde (#27ae60 → #229954)</li>";
echo "</ul>";
echo "</ul>";

echo "</div>";

echo "<div style='background:#e8f5e8;padding:20px;margin:20px 0;border-radius:10px;border-left:5px solid #28a745;'>";
echo "<h2>🎯 Benefícios Visuais:</h2>";
echo "<ul>";
echo "<li>Design mais moderno e profissional</li>";
echo "<li>Melhor hierarquia visual dos elementos</li>";
echo "<li>Espaçamentos mais equilibrados</li>";
echo "<li>Efeitos de interação mais fluidos</li>";
echo "<li>Cores mais vibrantes e contrastantes</li>";
echo "<li>Consistência com design systems modernos</li>";
echo "</ul>";
echo "</div>";

// Exemplo visual do card
echo "<div style='text-align:center;margin:30px 0;'>";
echo "<h2>👁️ Exemplo Visual:</h2>";
echo "<div style='max-width:500px;margin:0 auto;background:#fff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.1);overflow:hidden;'>";
echo "<div style='padding:20px;background:linear-gradient(135deg,#f8f9fa,#e9ecef);min-height:140px;display:flex;flex-direction:column;align-items:center;justify-content:center;border:2px solid #e9ecef;border-radius:12px;'>";
echo "<div style='margin-bottom:15px;font-size:1.1em;color:#606770;font-weight:600;text-align:center;'>";
echo "<span style='font-weight:700;color:#2c3e50;'>28/01/2026 - 19:00</span>";
echo "</div>";
echo "<div style='display:flex;align-items:center;justify-content:center;margin-bottom:15px;gap:10px;'>";
echo "<span style='font-weight:700;font-size:1.3em;color:#2c3e50;text-transform:uppercase;letter-spacing:0.5px;'>CAM</span>";
echo "<div style='width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:#f8f9fa;padding:5px;'>";
echo "<div style='width:30px;height:30px;background:#ccc;border-radius:50%;'></div>";
echo "</div>";
echo "<span style='font-weight:bold;font-size:2.0em;color:#2c3e50;min-width:40px;text-align:center;'>-</span>";
echo "<span style='margin:0 8px;color:#606770;font-size:1.2em;font-weight:700;'>X</span>";
echo "<span style='font-weight:bold;font-size:2.0em;color:#2c3e50;min-width:40px;text-align:center;'>-</span>";
echo "<div style='width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:#f8f9fa;padding:5px;'>";
echo "<div style='width:30px;height:30px;background:#ccc;border-radius:50%;'></div>";
echo "</div>";
echo "<span style='font-weight:700;font-size:1.3em;color:#2c3e50;text-transform:uppercase;letter-spacing:0.5px;'>PAL</span>";
echo "</div>";
echo "<div style='display:flex;flex-direction:column;align-items:center;margin-top:10px;'>";
echo "<div style='text-transform:uppercase;font-size:0.85em;font-weight:700;letter-spacing:0.5px;padding:6px 12px;border-radius:20px;color:white;margin-bottom:8px;white-space:nowrap;box-shadow:0 2px 4px rgba(0,0,0,0.2);background:linear-gradient(45deg,#f39c12,#d35400);'>Em breve</div>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div style='text-align:center;margin:30px 0;'>";
echo "<h2>🚀 Veja o Resultado Real:</h2>";
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

echo "<hr><p style='text-align:center;color:#666;'><small>⚽ Demonstração criada em " . date('d/m/Y H:i:s') . "</small></p>";
?>