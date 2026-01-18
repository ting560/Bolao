#!/bin/bash
# Script de atualização automática do sistema de bolão
# Executar a cada minuto para atualizar resultados e calcular pontos

# Diretório do projeto
PROJECT_DIR="/c/xampp/htdocs/1000"
LOG_FILE="$PROJECT_DIR/logs/auto_update.log"

# Função para log
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> "$LOG_FILE"
}

# Verificar se o diretório existe
if [ ! -d "$PROJECT_DIR" ]; then
    echo "Diretório do projeto não encontrado: $PROJECT_DIR"
    exit 1
fi

# Criar diretório de logs se não existir
mkdir -p "$PROJECT_DIR/logs"

# Atualizar resultados dos jogos e calcular pontos
log_message "Iniciando atualização automática..."

# Chamar o script de atualização
php "$PROJECT_DIR/auto_update_games.php" >> "$LOG_FILE" 2>&1

# Verificar se houve jogos atualizados
if [ $? -eq 0 ]; then
    log_message "Atualização automática concluída com sucesso"
else
    log_message "Erro na atualização automática"
fi

# Atualizar jogos do SofaScore a cada 5 minutos
MINUTE=$(date +%M)
if [ $((MINUTE % 5)) -eq 0 ]; then
    log_message "Atualizando jogos do SofaScore..."
    php "$PROJECT_DIR/sofascore_integration.php?update=1" >> "$LOG_FILE" 2>&1
    
    if [ $? -eq 0 ]; then
        log_message "Jogos do SofaScore atualizados"
    else
        log_message "Erro ao atualizar jogos do SofaScore"
    fi
fi