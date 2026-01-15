
import React, { useState, useEffect, useRef } from 'react';
import { Page, User, Match, Guess, BolaoConfig } from './types';
import { INITIAL_MATCHES, INITIAL_USERS } from './constants';
import Login from './components/Login';
import Register from './components/Register';
import Dashboard from './components/Dashboard';
import Guesses from './components/Guesses';
import Admin from './components/Admin';
import { calculatePoints } from './utils/scoring';
import { persistenceService } from './services/PersistenceService';

const DEFAULT_CONFIG: BolaoConfig = {
  pointsNaMosca: 12,
  pointsWinner: 5,
  feePercent: 10,
  prize1stPercent: 70,
  prize2ndPercent: 20,
  entryFee: 20
};

const App: React.FC = () => {
  const [currentPage, setCurrentPage] = useState<Page>(Page.DASHBOARD);
  const [currentUser, setCurrentUser] = useState<User | null>(null);
  const [matches, setMatches] = useState<Match[]>([]);
  const [users, setUsers] = useState<User[]>([]);
  const [guesses, setGuesses] = useState<Guess[]>([]);
  const [config, setConfig] = useState<BolaoConfig>(DEFAULT_CONFIG);
  const [activeApiUrl, setActiveApiUrl] = useState<string>('');
  const [isLoading, setIsLoading] = useState(true);

  // Carregar dados do Supabase ao iniciar
  useEffect(() => {
    const loadData = async () => {
      setIsLoading(true);
      try {
        // Inicializar banco de dados
        await persistenceService.initializeDatabase();
        
        // Carregar todos os dados
        const data = await persistenceService.loadAllData();
        
        if (data) {
          setUsers(data.users.length > 0 ? data.users : INITIAL_USERS);
          setMatches(data.matches.length > 0 ? data.matches : INITIAL_MATCHES);
          setGuesses(data.guesses || []);
          setConfig(data.config || DEFAULT_CONFIG);
          setActiveApiUrl(data.api_url || '');
        } else {
          // Se falhar, usar dados padrão
          setUsers(INITIAL_USERS);
          setMatches(INITIAL_MATCHES);
          setGuesses([]);
          setConfig(DEFAULT_CONFIG);
        }
      } catch (error) {
        console.error('Erro ao carregar dados:', error);
        // Usar dados padrão em caso de erro
        setUsers(INITIAL_USERS);
        setMatches(INITIAL_MATCHES);
        setGuesses([]);
        setConfig(DEFAULT_CONFIG);
      } finally {
        setIsLoading(false);
      }
    };

    loadData();
  }, []);

  // Salvar dados sempre que houver alterações
  useEffect(() => {
    if (!isLoading) {
      const saveTimer = setTimeout(async () => {
        try {
          await persistenceService.saveAllData(users, matches, guesses, {
            ...config,
            api_url: activeApiUrl
          });
        } catch (error) {
          console.error('Erro ao salvar dados:', error);
        }
      }, 1000); // Debounce de 1 segundo

      return () => clearTimeout(saveTimer);
    }
  }, [users, matches, guesses, config, activeApiUrl, isLoading]);

  // Sincronização de Resultados Live (API Externa ou PHP)
  useEffect(() => {
    const syncResults = async () => {
      try {
        let liveJogos: any[] = [];

        // Prioridade 1: API Externa (SofaScore/URL configurada no Admin)
        if (activeApiUrl) {
          const response = await fetch(activeApiUrl);
          const data = await response.json();
          if (data.events) {
            liveJogos = data.events.map((ev: any) => ({
              homeName: ev.homeTeam.name.toUpperCase(),
              awayName: ev.awayTeam.name.toUpperCase(),
              homeScore: ev.homeScore?.current,
              awayScore: ev.awayScore?.current,
              status: ev.status.type,
              minutes: ev.status.description
            }));
          }
        } 
        // Prioridade 2: Fallback para index.php
        else {
          const response = await fetch('index.php?ajax=1');
          const data = await response.json();
          if (data.sucesso && data.jogos) {
            liveJogos = data.jogos.map((lj: any) => ({
              homeName: lj.nmMand.toUpperCase(),
              awayName: lj.nmAdv.toUpperCase(),
              homeScore: parseInt(lj.qtdGolsMand),
              awayScore: parseInt(lj.qtdGolsAdv),
              status: (lj.minuto_real?.includes('FIM') || lj.situacao?.includes('ENCERRADO')) ? 'finished' : 'pending',
              minutes: lj.minuto_real || lj.situacao
            }));
          }
        }

        if (liveJogos.length > 0) {
          updateMatchesAndScores(liveJogos);
        }
      } catch (error) {
        console.error("Erro na sincronização live:", error);
      }
    };

    const updateMatchesAndScores = (liveData: any[]) => {
      setMatches(prevMatches => {
        const updatedMatches: Match[] = prevMatches.map(m => {
          const live = liveData.find(l => 
            l.homeName.includes(m.homeTeam.toUpperCase()) || 
            m.homeTeam.toUpperCase().includes(l.homeName)
          );

          if (live) {
            return {
              ...m,
              homeScore: live.homeScore ?? m.homeScore,
              awayScore: live.awayScore ?? m.awayScore,
              status: live.status === 'finished' ? 'finished' : 'pending',
              liveMinutes: live.minutes
            };
          }
          return m;
        });

        recalculateAllUserPoints(updatedMatches);
        return updatedMatches;
      });
    };

    const recalculateAllUserPoints = (currentMatches: Match[]) => {
      setUsers(prevUsers => prevUsers.map(u => {
        let totalPoints = 0;
        const userGuesses = guesses.filter(g => g.userId === u.id);
        
        userGuesses.forEach(g => {
          const m = currentMatches.find(match => match.id === g.matchId);
          if (m && m.homeScore !== undefined) {
            totalPoints += calculatePoints(m, g);
          }
        });

        return { ...u, points: totalPoints };
      }));
    };

    syncResults();
    const interval = setInterval(syncResults, 60000); // Atualiza a cada 60 segundos
    return () => clearInterval(interval);
  }, [guesses, activeApiUrl]);

  const handleLogin = (email: string, password?: string) => {
    const user = users.find(u => u.email === email);
    if (email === 'marcos@gmail.com') {
      if (password === 'Serrw3@8*Lo') {
        setCurrentUser(user || null);
        setCurrentPage(Page.DASHBOARD);
        return;
      } else {
        alert("Senha administrativa incorreta!");
        return;
      }
    }
    if (user) {
      setCurrentUser(user);
      setCurrentPage(Page.DASHBOARD);
    } else {
      alert("Usuário não encontrado!");
    }
  };

  const handleRegister = (name: string, email: string, pass: string) => {
    const newUser: User = {
      id: Math.random().toString(36).substr(2, 9),
      name,
      email,
      password: pass,
      role: 'user',
      points: 0
    };
    setUsers([...users, newUser]);
    setCurrentUser(newUser);
    setCurrentPage(Page.DASHBOARD);
  };

  const saveGuess = (newGuesses: Guess[]) => {
    if (!currentUser) return;
    const filteredOld = guesses.filter(g => g.userId !== currentUser.id);
    setGuesses([...filteredOld, ...newGuesses]);
    alert("Palpites salvos com sucesso!");
    setCurrentPage(Page.DASHBOARD);
  };

  const renderPage = () => {
    switch (currentPage) {
      case Page.DASHBOARD:
        return (
          <Dashboard 
            user={currentUser}
            matches={matches} 
            users={users}
            guesses={guesses}
            config={config}
            onGoToGuesses={() => currentUser ? setCurrentPage(Page.GUESSES) : setCurrentPage(Page.LOGIN)}
            onLogout={() => { setCurrentUser(null); setCurrentPage(Page.DASHBOARD); }}
            onGoToAdmin={() => setCurrentPage(Page.ADMIN)}
            onGoToLogin={() => setCurrentPage(Page.LOGIN)}
          />
        );
      case Page.LOGIN:
        return <Login onLogin={handleLogin} onGoToRegister={() => setCurrentPage(Page.REGISTER)} onBack={() => setCurrentPage(Page.DASHBOARD)} />;
      case Page.REGISTER:
        return <Register onRegister={handleRegister} onGoToLogin={() => setCurrentPage(Page.LOGIN)} onBack={() => setCurrentPage(Page.DASHBOARD)} />;
      case Page.GUESSES:
        return <Guesses matches={matches} onSave={saveGuess} onCancel={() => setCurrentPage(Page.DASHBOARD)} currentUser={currentUser!} currentGuesses={guesses} />;
      case Page.ADMIN:
        return (
          <Admin 
            matches={matches} 
            users={users} 
            guesses={guesses}
            config={config}
            setMatches={setMatches} 
            setUsers={setUsers}
            setGuesses={setGuesses}
            setConfig={setConfig}
            onBack={() => setCurrentPage(Page.DASHBOARD)}
            activeApiUrl={activeApiUrl}
            setActiveApiUrl={setActiveApiUrl}
          />
        );
      default:
        return <div>Página não encontrada</div>;
    }
  };

  return (
    <div className="min-h-screen bg-indigo-600">
      <div className="max-w-4xl mx-auto py-6 px-4">
        {renderPage()}
      </div>
    </div>
  );
};

export default App;
