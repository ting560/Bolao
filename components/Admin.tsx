
import React, { useState, useEffect } from 'react';
import { Match, User, Guess, BolaoConfig } from '../types';
import { getTeamLogo } from '../utils/scoring';
import { supabase } from '../supabase';

interface Props {
  matches: Match[];
  users: User[];
  guesses: Guess[];
  config: BolaoConfig;
  setMatches: React.Dispatch<React.SetStateAction<Match[]>>;
  setUsers: React.Dispatch<React.SetStateAction<User[]>>;
  setGuesses?: React.Dispatch<React.SetStateAction<Guess[]>>;
  setConfig: React.Dispatch<React.SetStateAction<BolaoConfig>>;
  onBack: () => void;
  activeApiUrl: string;
  setActiveApiUrl: (url: string) => void;
}

const Admin: React.FC<Props> = ({ matches, users, guesses, config, setMatches, setUsers, setConfig, onBack, setGuesses, activeApiUrl, setActiveApiUrl }) => {
  const [tab, setTab] = useState<'matches' | 'users' | 'finance' | 'settings' | 'guesses'>('matches');
  const [matchSubTab, setMatchSubTab] = useState<'list' | 'api' | 'paste'>('list');
  const [isAdding, setIsAdding] = useState(false);
  const [formData, setFormData] = useState({ home: '', away: '', date: '', homeScore: '', awayScore: '' });
  
  const [apiUrl, setApiUrl] = useState(activeApiUrl);
  const [loadingApi, setLoadingApi] = useState(false);
  const [savingToDB, setSavingToDB] = useState(false);
  const [rawText, setRawText] = useState('');
  const [parsedMatches, setParsedMatches] = useState<any[]>([]);

  // Carregar URL da API do Supabase ao montar o componente
  useEffect(() => {
    loadApiUrlFromDatabase();
  }, []);

  const loadApiUrlFromDatabase = async () => {
    try {
      const { data, error } = await supabase
        .from('bolao_config')
        .select('api_url')
        .eq('id', 'main')
        .single();

      if (error && error.code !== 'PGRST116') { // PGRST116 = no rows returned
        console.error('Erro ao carregar URL da API:', error);
        return;
      }

      if (data?.api_url) {
        setApiUrl(data.api_url);
        setActiveApiUrl(data.api_url);
      }
    } catch (error) {
      console.error('Erro ao conectar com Supabase:', error);
    }
  };

  const saveApiUrlToDatabase = async (url: string) => {
    setSavingToDB(true);
    try {
      const { error } = await supabase
        .from('bolao_config')
        .upsert({ 
          id: 'main', 
          api_url: url,
          updated_at: new Date().toISOString()
        });

      if (error) {
        console.error('Erro ao salvar URL da API:', error);
        alert('Erro ao salvar configuração no banco de dados');
      } else {
        console.log('URL da API salva com sucesso no Supabase');
      }
    } catch (error) {
      console.error('Erro de conexão com Supabase:', error);
      alert('Erro de conexão com o banco de dados');
    } finally {
      setSavingToDB(false);
    }
  };

  const fetchFromApi = async () => {
    if (!apiUrl) return alert("Cole a URL da API primeiro!");
    setLoadingApi(true);
    try {
      const response = await fetch(apiUrl);
      const data = await response.json();
      
      const events = data.events || [];
      if (events.length === 0) throw new Error("Nenhum evento encontrado.");

      const newMatches: any[] = events.map((ev: any) => ({
        home: ev.homeTeam.name.toUpperCase(),
        away: ev.awayTeam.name.toUpperCase(),
        homeLogo: `https://api.sofascore.app/api/v1/team/${ev.homeTeam.id}/image`,
        awayLogo: `https://api.sofascore.app/api/v1/team/${ev.awayTeam.id}/image`,
        date: new Date(ev.startTimestamp * 1000).toISOString(),
        status: ev.status.type === 'finished' ? 'finished' : 'pending',
        homeScore: ev.homeScore?.current,
        awayScore: ev.awayScore?.current,
        liveMinutes: ev.status.description
      }));

      setParsedMatches(newMatches);
      setActiveApiUrl(apiUrl); // Define esta como a API de atualização automática
      
      // Salvar no Supabase para compartilhar com todos os usuários
      await saveApiUrlToDatabase(apiUrl);
      
      alert(`${newMatches.length} jogos encontrados e Monitoramento Ativo ligado! Configuração salva no banco de dados.`);
    } catch (error) {
      console.error(error);
      alert("Erro ao ler API. Verifique a URL ou CORS.");
    } finally {
      setLoadingApi(false);
    }
  };

  const importParsed = () => {
    const newMatches = [...matches];
    parsedMatches.forEach(p => {
      // Evita duplicatas simples
      if (!matches.find(m => m.homeTeam === p.home && m.awayTeam === p.away)) {
        newMatches.push({
          id: Math.random().toString(36).substr(2, 9),
          homeTeam: p.home,
          awayTeam: p.away,
          homeLogo: p.homeLogo || getTeamLogo(p.home),
          awayLogo: p.awayLogo || getTeamLogo(p.away),
          date: p.date,
          status: p.status || 'pending',
          homeScore: p.homeScore,
          awayScore: p.awayScore,
          liveMinutes: p.liveMinutes
        });
      }
    });
    setMatches(newMatches);
    setMatchSubTab('list');
    setParsedMatches([]);
    alert("Rodada importada e monitoramento de 60s iniciado!");
  };

  const handleParseText = () => {
    const lines = rawText.split('\n').filter(l => l.trim().length > 3);
    const found: any[] = [];
    for (let i = 0; i < lines.length; i++) {
      const line = lines[i].toUpperCase();
      if (line.includes(' X ') || line.includes(' VS ')) {
        const parts = line.split(/ X | VS /);
        if (parts.length >= 2) {
          found.push({
            home: parts[0].trim(),
            away: parts[1].trim(),
            date: new Date().toISOString()
          });
        }
      }
    }
    setParsedMatches(found);
  };

  return (
    <div className="bg-white rounded-[40px] shadow-2xl p-6 md:p-10 min-h-[85vh] text-slate-800 border border-gray-100 overflow-hidden">
      <div className="flex justify-between items-center mb-8">
        <div className="flex items-center gap-3">
          <div className="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-white text-xl font-black italic shadow-lg">P</div>
          <div>
            <h1 className="text-2xl font-black text-indigo-900 italic uppercase tracking-tighter leading-none">PAINEL ADMIN</h1>
            <div className="flex items-center gap-2 mt-1">
               <p className="text-[10px] text-gray-400 font-bold uppercase">Gestão Live</p>
               {activeApiUrl && <span className="text-[8px] bg-green-500 text-white px-2 py-0.5 rounded-full animate-pulse font-black">MONITORANDO 60S</span>}
            </div>
          </div>
        </div>
        <button onClick={onBack} className="bg-slate-100 px-6 py-2 rounded-2xl font-black text-xs hover:bg-slate-200 transition uppercase shadow-sm">VOLTAR</button>
      </div>

      <div className="flex flex-wrap gap-2 mb-8 bg-gray-100 p-1.5 rounded-2xl">
        {['matches', 'users', 'finance', 'settings'].map((id) => (
          <button 
            key={id}
            onClick={() => { setTab(id as any); setIsAdding(false); }} 
            className={`flex-1 py-3 px-2 rounded-xl font-black text-[10px] md:text-xs transition uppercase truncate ${tab === id ? 'bg-white shadow-md text-indigo-600' : 'text-gray-400 hover:text-gray-600'}`}
          >
            {id === 'matches' ? 'Jogos' : id === 'users' ? 'Usuários' : id === 'finance' ? 'Financeiro' : 'Regras'}
          </button>
        ))}
      </div>

      {/* Aba Jogos */}
      {tab === 'matches' && (
        <div className="space-y-6">
          <div className="flex flex-wrap gap-4 border-b border-gray-100 pb-3">
            <button onClick={() => setMatchSubTab('list')} className={`text-[11px] font-black uppercase transition-all ${matchSubTab === 'list' ? 'text-indigo-600 border-b-2 border-indigo-600 pb-3' : 'text-gray-400'}`}>📋 Lista Atual</button>
            <button onClick={() => setMatchSubTab('api')} className={`text-[11px] font-black uppercase transition-all ${matchSubTab === 'api' ? 'text-indigo-600 border-b-2 border-indigo-600 pb-3' : 'text-gray-400'}`}>🔗 Sincronizar API</button>
            <button onClick={() => setMatchSubTab('paste')} className={`text-[11px] font-black uppercase transition-all ${matchSubTab === 'paste' ? 'text-indigo-600 border-b-2 border-indigo-600 pb-3' : 'text-gray-400'}`}>✍️ Colar Texto</button>
          </div>

          {matchSubTab === 'api' ? (
            <div className="space-y-4 animate-in fade-in">
              <div className="bg-blue-50 p-6 rounded-[32px] border border-blue-100">
                <div className="flex justify-between items-center mb-4">
                  <h3 className="text-sm font-black text-blue-900 uppercase italic">🔗 URL de Monitoramento Real</h3>
                  {activeApiUrl && <button onClick={() => setActiveApiUrl('')} className="text-[9px] text-red-500 font-bold underline">Desativar Auto-Update</button>}
                </div>
                <input 
                  className="w-full p-4 rounded-2xl border-2 border-blue-200 focus:border-blue-500 outline-none font-medium text-xs mb-4"
                  placeholder="https://www.sofascore.com/api/v1/unique-tournament/..."
                  value={apiUrl}
                  onChange={e => setApiUrl(e.target.value)}
                />
                <button 
                  onClick={fetchFromApi} 
                  disabled={loadingApi || savingToDB}
                  className="w-full bg-blue-600 text-white py-4 rounded-2xl font-black uppercase shadow-lg hover:bg-blue-700 transition disabled:opacity-50"
                >
                  {loadingApi ? 'CONECTANDO...' : savingToDB ? 'SALVANDO CONFIGURAÇÃO...' : 'ATIVAR MONITORAMENTO LIVE'}
                </button>
                
                {activeApiUrl && (
                  <div className="mt-4 p-4 bg-green-50 rounded-2xl border border-green-200">
                    <div className="flex items-center gap-2">
                      <div className="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                      <span className="text-xs font-black text-green-700 uppercase">CONFIGURAÇÃO SALVA NO BANCO DE DADOS</span>
                    </div>
                    <p className="text-[10px] text-green-600 mt-1">Todos os usuários receberão esta configuração automaticamente</p>
                  </div>
                )}
              </div>

              {parsedMatches.length > 0 && (
                <div className="space-y-4">
                  <div className="bg-green-50 p-4 rounded-2xl border border-green-200 flex justify-between items-center">
                    <span className="text-xs font-black text-green-700 uppercase">{parsedMatches.length} JOGOS DISPONÍVEIS</span>
                    <button onClick={importParsed} className="bg-green-600 text-white px-6 py-2 rounded-xl font-black text-[10px] uppercase italic">IMPORTAR RODADA</button>
                  </div>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-2">
                    {parsedMatches.map((p, i) => (
                      <div key={i} className="bg-white border p-4 rounded-2xl flex items-center gap-3 text-[10px] font-bold">
                        <img src={p.homeLogo} className="w-6 h-6 object-contain" />
                        <span className="flex-1 truncate">{p.home} <span className="text-indigo-500">{p.homeScore ?? 0}x{p.awayScore ?? 0}</span> {p.away}</span>
                        <img src={p.awayLogo} className="w-6 h-6 object-contain" />
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          ) : matchSubTab === 'paste' ? (
            <div className="space-y-4 animate-in fade-in">
              <textarea 
                className="w-full h-40 p-4 rounded-3xl border-2 border-gray-100 focus:border-indigo-500 outline-none font-medium text-sm"
                placeholder="Time A X Time B"
                value={rawText}
                onChange={e => setRawText(e.target.value)}
              />
              <button onClick={handleParseText} className="w-full bg-indigo-600 text-white py-4 rounded-2xl font-black uppercase shadow-lg">IDENTIFICAR</button>
              {parsedMatches.length > 0 && <button onClick={importParsed} className="w-full bg-green-600 text-white py-4 rounded-2xl font-black uppercase">CADASTRAR</button>}
            </div>
          ) : (
            <div className="space-y-4">
              <button onClick={() => setIsAdding(true)} className="w-full bg-indigo-600 text-white py-4 rounded-2xl font-black uppercase shadow-xl">+ Adicionar Manual</button>
              <div className="grid grid-cols-1 gap-4">
                {matches.length === 0 ? (
                   <div className="p-10 text-center text-gray-400 font-bold italic">Nenhum jogo na rodada. Use a aba "Sincronizar API" para importar e ligar o ao vivo.</div>
                ) : (
                  matches.map(m => (
                    <div key={m.id} className="bg-white border p-5 rounded-[28px] flex items-center justify-between shadow-sm hover:border-indigo-300 transition">
                      <div className="flex items-center gap-4">
                        <img src={m.homeLogo} className="w-10 h-10 object-contain" alt="" />
                        <div>
                          <span className="font-black text-[10px] md:text-xs uppercase">{m.homeTeam} <span className="text-gray-300">VS</span> {m.awayTeam}</span>
                          {m.liveMinutes && <p className="text-[8px] text-red-500 font-black animate-pulse mt-0.5">{m.liveMinutes}</p>}
                        </div>
                        <img src={m.awayLogo} className="w-10 h-10 object-contain" alt="" />
                      </div>
                      <div className="flex items-center gap-2">
                        {m.homeScore !== undefined && (
                          <div className="flex items-center gap-1">
                            <span className="bg-indigo-600 text-white px-2 py-1 rounded-lg text-[11px] font-black shadow-md">{m.homeScore}</span>
                            <span className="text-gray-300 font-bold">x</span>
                            <span className="bg-indigo-600 text-white px-2 py-1 rounded-lg text-[11px] font-black shadow-md">{m.awayScore}</span>
                          </div>
                        )}
                        <button onClick={() => setMatches(matches.filter(x => x.id !== m.id))} className="w-8 h-8 bg-red-50 text-red-500 rounded-lg flex items-center justify-center font-black ml-2 hover:bg-red-500 hover:text-white transition-colors">✕</button>
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>
          )}
        </div>
      )}

      {/* Aba Usuários */}
      {tab === 'users' && (
        <div className="space-y-6">
          <div className="flex justify-between items-center mb-6">
            <h2 className="text-xl font-black text-indigo-900 uppercase">Gerenciar Usuários</h2>
            <button 
              onClick={() => {
                const name = prompt('Nome do usuário:');
                const email = prompt('Email:');
                if (name && email) {
                  const newUser: User = {
                    id: Math.random().toString(36).substr(2, 9),
                    name,
                    email,
                    role: 'user',
                    points: 0
                  };
                  setUsers([...users, newUser]);
                  alert('Usuário adicionado com sucesso!');
                }
              }}
              className="bg-indigo-600 text-white px-4 py-2 rounded-xl font-black text-xs uppercase"
            >
              + Novo Usuário
            </button>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {users.map(user => (
              <div key={user.id} className="bg-white border p-5 rounded-2xl shadow-sm hover:border-indigo-300 transition">
                <div className="flex justify-between items-start mb-3">
                  <div>
                    <h3 className="font-black text-sm uppercase text-indigo-900">{user.name}</h3>
                    <p className="text-xs text-gray-500">{user.email}</p>
                    <span className={`inline-block mt-2 px-2 py-1 rounded-lg text-[9px] font-black uppercase ${user.role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'}`}>
                      {user.role === 'admin' ? 'ADMIN' : 'USUÁRIO'}
                    </span>
                  </div>
                  <button 
                    onClick={() => {
                      if (confirm(`Remover usuário ${user.name}?`)) {
                        setUsers(users.filter(u => u.id !== user.id));
                      }
                    }}
                    className="w-7 h-7 bg-red-50 text-red-500 rounded-lg flex items-center justify-center font-black hover:bg-red-500 hover:text-white transition-colors"
                  >
                    ✕
                  </button>
                </div>
                <div className="pt-3 border-t border-gray-100">
                  <div className="flex justify-between items-center">
                    <span className="text-xs font-bold text-gray-500">Pontos:</span>
                    <span className="text-lg font-black text-indigo-600">{user.points}</span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Aba Financeiro */}
      {tab === 'finance' && (
        <div className="space-y-6">
          <h2 className="text-xl font-black text-indigo-900 uppercase mb-6">Financeiro do Bolão</h2>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div className="bg-green-50 p-6 rounded-2xl border border-green-100 text-center">
              <div className="text-3xl font-black text-green-600 mb-2">R$ {(users.length * config.entryFee).toFixed(2)}</div>
              <div className="text-xs font-black text-green-700 uppercase">Arrecadado Total</div>
              <div className="text-[10px] text-green-500 mt-1">{users.length} participantes × R$ {config.entryFee}</div>
            </div>
            
            <div className="bg-blue-50 p-6 rounded-2xl border border-blue-100 text-center">
              <div className="text-3xl font-black text-blue-600 mb-2">R$ {((users.length * config.entryFee) * (config.feePercent/100)).toFixed(2)}</div>
              <div className="text-xs font-black text-blue-700 uppercase">Taxa Administrativa</div>
              <div className="text-[10px] text-blue-500 mt-1">{config.feePercent}% do total</div>
            </div>
            
            <div className="bg-purple-50 p-6 rounded-2xl border border-purple-100 text-center">
              <div className="text-3xl font-black text-purple-600 mb-2">R$ {((users.length * config.entryFee) * (1 - config.feePercent/100)).toFixed(2)}</div>
              <div className="text-xs font-black text-purple-700 uppercase">Prêmio Disponível</div>
              <div className="text-[10px] text-purple-500 mt-1">Após taxa administrativa</div>
            </div>
          </div>

          <div className="bg-white border rounded-2xl p-6">
            <h3 className="font-black text-lg text-indigo-900 mb-4 uppercase">Distribuição de Prêmios</h3>
            <div className="space-y-4">
              <div className="flex justify-between items-center p-4 bg-yellow-50 rounded-xl">
                <div>
                  <div className="font-black text-yellow-700 uppercase">1º Lugar</div>
                  <div className="text-sm text-yellow-600">{config.prize1stPercent}% do prêmio disponível</div>
                </div>
                <div className="text-right">
                  <div className="text-2xl font-black text-yellow-600">R$ {(((users.length * config.entryFee) * (1 - config.feePercent/100)) * (config.prize1stPercent/100)).toFixed(2)}</div>
                </div>
              </div>
              
              <div className="flex justify-between items-center p-4 bg-gray-50 rounded-xl">
                <div>
                  <div className="font-black text-gray-700 uppercase">2º Lugar</div>
                  <div className="text-sm text-gray-600">{config.prize2ndPercent}% do prêmio disponível</div>
                </div>
                <div className="text-right">
                  <div className="text-2xl font-black text-gray-600">R$ {(((users.length * config.entryFee) * (1 - config.feePercent/100)) * (config.prize2ndPercent/100)).toFixed(2)}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Aba Regras/Configurações */}
      {tab === 'settings' && (
        <div className="space-y-6">
          <h2 className="text-xl font-black text-indigo-900 uppercase mb-6">Configurações & Regras</h2>
          
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="bg-white border rounded-2xl p-6">
              <h3 className="font-black text-lg text-indigo-900 mb-4 uppercase">Pontuação</h3>
              <div className="space-y-4">
                <div className="flex justify-between items-center p-3 bg-green-50 rounded-xl">
                  <span className="font-bold text-green-700">Na Mosca (Placar exato)</span>
                  <div className="flex items-center gap-2">
                    <input 
                      type="number" 
                      value={config.pointsNaMosca}
                      onChange={e => setConfig({...config, pointsNaMosca: parseInt(e.target.value) || 0})}
                      className="w-16 px-2 py-1 border rounded-lg text-center font-black"
                    />
                    <span className="font-black text-green-600">pontos</span>
                  </div>
                </div>
                
                <div className="flex justify-between items-center p-3 bg-blue-50 rounded-xl">
                  <span className="font-bold text-blue-700">Acertar Vencedor/Empate</span>
                  <div className="flex items-center gap-2">
                    <input 
                      type="number" 
                      value={config.pointsWinner}
                      onChange={e => setConfig({...config, pointsWinner: parseInt(e.target.value) || 0})}
                      className="w-16 px-2 py-1 border rounded-lg text-center font-black"
                    />
                    <span className="font-black text-blue-600">pontos</span>
                  </div>
                </div>
              </div>
            </div>
            
            <div className="bg-white border rounded-2xl p-6">
              <h3 className="font-black text-lg text-indigo-900 mb-4 uppercase">Financeiro</h3>
              <div className="space-y-4">
                <div className="flex justify-between items-center p-3 bg-purple-50 rounded-xl">
                  <span className="font-bold text-purple-700">Taxa Administrativa</span>
                  <div className="flex items-center gap-2">
                    <input 
                      type="number" 
                      value={config.feePercent}
                      onChange={e => setConfig({...config, feePercent: parseInt(e.target.value) || 0})}
                      className="w-16 px-2 py-1 border rounded-lg text-center font-black"
                    />
                    <span className="font-black text-purple-600">%</span>
                  </div>
                </div>
                
                <div className="flex justify-between items-center p-3 bg-orange-50 rounded-xl">
                  <span className="font-bold text-orange-700">Valor da Inscrição</span>
                  <div className="flex items-center gap-2">
                    <span className="font-black text-orange-600">R$</span>
                    <input 
                      type="number" 
                      value={config.entryFee}
                      onChange={e => setConfig({...config, entryFee: parseInt(e.target.value) || 0})}
                      className="w-20 px-2 py-1 border rounded-lg text-center font-black"
                    />
                  </div>
                </div>
              </div>
            </div>
            
            <div className="bg-white border rounded-2xl p-6 md:col-span-2">
              <h3 className="font-black text-lg text-indigo-900 mb-4 uppercase">Distribuição de Prêmios</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="flex justify-between items-center p-4 bg-yellow-50 rounded-xl">
                  <span className="font-bold text-yellow-700">1º Lugar</span>
                  <div className="flex items-center gap-2">
                    <input 
                      type="number" 
                      value={config.prize1stPercent}
                      onChange={e => setConfig({...config, prize1stPercent: parseInt(e.target.value) || 0})}
                      className="w-16 px-2 py-1 border rounded-lg text-center font-black"
                    />
                    <span className="font-black text-yellow-600">%</span>
                  </div>
                </div>
                
                <div className="flex justify-between items-center p-4 bg-gray-50 rounded-xl">
                  <span className="font-bold text-gray-700">2º Lugar</span>
                  <div className="flex items-center gap-2">
                    <input 
                      type="number" 
                      value={config.prize2ndPercent}
                      onChange={e => setConfig({...config, prize2ndPercent: parseInt(e.target.value) || 0})}
                      className="w-16 px-2 py-1 border rounded-lg text-center font-black"
                    />
                    <span className="font-black text-gray-600">%</span>
                  </div>
                </div>
              </div>
              <div className="mt-4 pt-4 border-t border-gray-100">
                <div className={`text-center font-black ${config.prize1stPercent + config.prize2ndPercent === 100 ? 'text-green-600' : 'text-red-600'}`}>
                  Total: {config.prize1stPercent + config.prize2ndPercent}% 
                  {config.prize1stPercent + config.prize2ndPercent === 100 ? '✓ CORRETO' : '✗ DEVE SER 100%'}
                </div>
              </div>
            </div>
          </div>
          
          <div className="bg-indigo-50 p-6 rounded-2xl border border-indigo-100">
            <h3 className="font-black text-indigo-900 mb-3 uppercase">Regras do Jogo</h3>
            <ul className="text-sm text-indigo-700 space-y-2 list-disc pl-5">
              <li>Palpites devem ser feitos antes do início dos jogos</li>
              <li>Pontuação é calculada automaticamente após término das partidas</li>
              <li>Empates contam como resultado correto se palpitado corretamente</li>
              <li>O valor da inscrição será distribuído conforme as porcentagens definidas</li>
              <li>Os organizadores ficam com a taxa administrativa definida</li>
            </ul>
          </div>
        </div>
      )}
    </div>
  );
};

export default Admin;
