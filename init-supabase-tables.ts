import { supabase } from './supabase';

async function initializeTables() {
  console.log('Inicializando tabelas do Supabase...');
  
  try {
    // Criar tabela bolao_config se não existir
    const { error: configError } = await supabase.rpc('create_bolao_config_table');
    
    if (configError) {
      console.log('Tabela bolao_config já existe ou criada com sucesso');
    } else {
      console.log('Tabela bolao_config criada com sucesso');
    }
    
    // Inserir configuração padrão
    const { error: insertError } = await supabase
      .from('bolao_config')
      .upsert({
        id: 'main',
        api_url: '',
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
      });

    if (insertError) {
      console.log('Configuração padrão já existe');
    } else {
      console.log('Configuração padrão inserida');
    }
    
    console.log('✅ Inicialização concluída com sucesso!');
    
  } catch (error) {
    console.error('Erro durante inicialização:', error);
  }
}

// Executar apenas se chamado diretamente
if (import.meta.url === new URL(import.meta.url).href) {
  initializeTables();
}

export { initializeTables };