import { supabase } from '../supabase';
import { User, Match, Guess, BolaoConfig } from '../types';

class PersistenceService {
  // Carregar todos os dados do Supabase
  async loadAllData() {
    try {
      const [users, matches, guesses, configData] = await Promise.all([
        this.loadUsers(),
        this.loadMatches(),
        this.loadGuesses(),
        this.loadConfigWithApiUrl()
      ]);

      return { 
        users, 
        matches, 
        guesses, 
        config: configData?.config || null,
        api_url: configData?.api_url || ''
      };
    } catch (error) {
      console.error('Erro ao carregar dados:', error);
      return null;
    }
  }

  // Salvar todos os dados no Supabase
  async saveAllData(users: User[], matches: Match[], guesses: Guess[], config: BolaoConfig) {
    try {
      await Promise.all([
        this.saveUsers(users),
        this.saveMatches(matches),
        this.saveGuesses(guesses),
        this.saveConfig(config)
      ]);
      console.log('Todos os dados salvos com sucesso!');
      return true;
    } catch (error) {
      console.error('Erro ao salvar dados:', error);
      return false;
    }
  }

  // USUÁRIOS
  async loadUsers(): Promise<User[]> {
    try {
      const { data, error } = await supabase
        .from('users')
        .select('*')
        .order('points', { ascending: false });

      if (error) {
        console.log('Tabela users não existe ainda, usando dados padrão');
        return [];
      }

      return data || [];
    } catch (error) {
      console.error('Erro ao carregar usuários:', error);
      return [];
    }
  }

  async saveUsers(users: User[]): Promise<boolean> {
    try {
      // Upsert para cada usuário (atualiza ou cria)
      const promises = users.map(user => 
        supabase.from('users').upsert(user, { onConflict: 'id' })
      );
      
      const results = await Promise.all(promises);
      const hasErrors = results.some(result => result.error);
      
      if (hasErrors) {
        console.error('Alguns usuários não foram salvos');
        return false;
      }
      
      return true;
    } catch (error) {
      console.error('Erro ao salvar usuários:', error);
      return false;
    }
  }

  // JOGOS/MATCHES
  async loadMatches(): Promise<Match[]> {
    try {
      const { data, error } = await supabase
        .from('matches')
        .select('*')
        .order('date', { ascending: true });

      if (error) {
        console.log('Tabela matches não existe ainda, usando dados padrão');
        return [];
      }

      return data || [];
    } catch (error) {
      console.error('Erro ao carregar jogos:', error);
      return [];
    }
  }

  async saveMatches(matches: Match[]): Promise<boolean> {
    try {
      const promises = matches.map(match => 
        supabase.from('matches').upsert(match, { onConflict: 'id' })
      );
      
      const results = await Promise.all(promises);
      const hasErrors = results.some(result => result.error);
      
      return !hasErrors;
    } catch (error) {
      console.error('Erro ao salvar jogos:', error);
      return false;
    }
  }

  // PALPITES/GUESSES
  async loadGuesses(): Promise<Guess[]> {
    try {
      const { data, error } = await supabase
        .from('guesses')
        .select('*');

      if (error) {
        console.log('Tabela guesses não existe ainda, usando dados padrão');
        return [];
      }

      return data || [];
    } catch (error) {
      console.error('Erro ao carregar palpites:', error);
      return [];
    }
  }

  async saveGuesses(guesses: Guess[]): Promise<boolean> {
    try {
      const promises = guesses.map(guess => 
        supabase.from('guesses').upsert(guess, { onConflict: 'id' })
      );
      
      const results = await Promise.all(promises);
      const hasErrors = results.some(result => result.error);
      
      return !hasErrors;
    } catch (error) {
      console.error('Erro ao salvar palpites:', error);
      return false;
    }
  }

  // CONFIGURAÇÕES
  async loadConfig(): Promise<BolaoConfig | null> {
    try {
      const { data, error } = await supabase
        .from('bolao_config')
        .select('config')
        .eq('id', 'main')
        .single();

      if (error && error.code !== 'PGRST116') {
        console.error('Erro ao carregar configuração:', error);
        return null;
      }

      return data?.config || null;
    } catch (error) {
      console.error('Erro ao carregar configuração:', error);
      return null;
    }
  }

  async loadConfigWithApiUrl(): Promise<{config: BolaoConfig | null, api_url: string} | null> {
    try {
      const { data, error } = await supabase
        .from('bolao_config')
        .select('config, api_url')
        .eq('id', 'main')
        .single();

      if (error && error.code !== 'PGRST116') {
        console.error('Erro ao carregar configuração com API URL:', error);
        return null;
      }

      return {
        config: data?.config || null,
        api_url: data?.api_url || ''
      };
    } catch (error) {
      console.error('Erro ao carregar configuração com API URL:', error);
      return null;
    }
  }

  async saveConfig(config: BolaoConfig): Promise<boolean> {
    try {
      const { error } = await supabase
        .from('bolao_config')
        .upsert({ 
          id: 'main', 
          config: config,
          updated_at: new Date().toISOString()
        });

      return !error;
    } catch (error) {
      console.error('Erro ao salvar configuração:', error);
      return false;
    }
  }

  // Inicializar tabelas do banco de dados
  async initializeDatabase() {
    console.log('Inicializando banco de dados...');
    
    try {
      // Criar tabelas se não existirem
      const tables = [
        `
        CREATE TABLE IF NOT EXISTS users (
          id TEXT PRIMARY KEY,
          name TEXT NOT NULL,
          email TEXT UNIQUE NOT NULL,
          password TEXT,
          role TEXT NOT NULL DEFAULT 'user',
          points INTEGER DEFAULT 0,
          created_at TIMESTAMP DEFAULT NOW()
        );
        `,
        `
        CREATE TABLE IF NOT EXISTS matches (
          id TEXT PRIMARY KEY,
          home_team TEXT NOT NULL,
          away_team TEXT NOT NULL,
          home_logo TEXT,
          away_logo TEXT,
          date TIMESTAMP NOT NULL,
          status TEXT DEFAULT 'pending',
          home_score INTEGER,
          away_score INTEGER,
          live_minutes TEXT,
          created_at TIMESTAMP DEFAULT NOW()
        );
        `,
        `
        CREATE TABLE IF NOT EXISTS guesses (
          id TEXT PRIMARY KEY,
          user_id TEXT NOT NULL,
          match_id TEXT NOT NULL,
          home_score INTEGER NOT NULL,
          away_score INTEGER NOT NULL,
          created_at TIMESTAMP DEFAULT NOW(),
          FOREIGN KEY (user_id) REFERENCES users(id),
          FOREIGN KEY (match_id) REFERENCES matches(id)
        );
        `,
        `
        CREATE TABLE IF NOT EXISTS bolao_config (
          id TEXT PRIMARY KEY,
          config JSONB,
          api_url TEXT,
          created_at TIMESTAMP DEFAULT NOW(),
          updated_at TIMESTAMP DEFAULT NOW()
        );
        `
      ];

      // Executar criação das tabelas
      for (const tableQuery of tables) {
        try {
          await supabase.rpc('execute_sql', { sql: tableQuery });
        } catch (error) {
          // As tabelas podem já existir, isso é normal
          console.log('Tabela já existe ou criada:', error.message);
        }
      }

      console.log('✅ Banco de dados inicializado com sucesso!');
      return true;
    } catch (error) {
      console.error('Erro ao inicializar banco de dados:', error);
      return false;
    }
  }
}

export const persistenceService = new PersistenceService();