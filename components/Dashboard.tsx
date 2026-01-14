
import React, { useState, useEffect } from 'react';
import { Match, User, Guess, BolaoConfig } from '../types';

interface Props {
  user: User | null;
  matches: Match[];
  users: User[];
  guesses: Guess[];
  config: BolaoConfig;
  onGoToGuesses: () => void;
  onLogout: () => void;
  onGoToAdmin: () => void;
  onGoToLogin: () => void;
}

const Dashboard: React.FC<Props> = ({ user, matches, users, guesses, config, onGoToGuesses, onLogout, onGoToAdmin, onGoToLogin }) => {
  const [selectedMatch, setSelectedMatch] = useState<Match | null>(null);
  const [isDarkMode, setIsDarkMode] = useState(() => localStorage.getItem('theme') === 'dark');

  useEffect(() => {
    if (isDarkMode) {
      document.documentElement.classList.add('dark');
      localStorage.setItem('theme', 'dark');
    } else {
      document.documentElement.classList.remove('dark');
      localStorage.setItem('theme', 'light');
    }
  }, [isDarkMode]);

  const sortedUsers = [...users].sort((a, b) => b.points - a.points);
  
  const getMatchGuesses = (matchId: string) => {
    return guesses.filter(g => g.matchId === matchId);
  };

  return (
    <div className={`min-h-screen transition-colors duration-300 ${isDarkMode ? 'bg-slate-900 text-slate-100' : 'bg-gray-50 text-slate-900'} pb-20`}>
      <nav className={`p-4 border-b flex justify-between items-center ${isDarkMode ? 'bg-slate-800/50 border-slate-700' : 'bg-white border-gray-200'} sticky top-0 z-40 backdrop-blur-md`}>
        <div className="flex items-center gap-2 overflow-hidden">
          <div className="bg-indigo-600 p-2 rounded-lg text-white font-black text-sm md:text-xl italic shadow-lg shrink-0">B</div>
          <span className="font-black text-xs md:text-lg tracking-tighter uppercase truncate">BOLÃO AO VIVO</span>
        </div>
        
        <div className="flex items-center gap-2 md:gap-3">
          <button onClick={() => setIsDarkMode(!isDarkMode)} className={`p-2 rounded-full transition-all text-sm ${isDarkMode ? 'bg-yellow-400 text-slate-900' : 'bg-slate-100 text-slate-600'}`}>
            {isDarkMode ? '☀️' : '🌙'}
          </button>
          {!user ? (
            <button onClick={onGoToLogin} className="bg-indigo-600 text-white px-3 py-1.5 md:px-5 md:py-2 rounded-xl font-bold text-[10px] md:text-sm shadow-lg hover:bg-indigo-700 transition">LOGIN</button>
          ) : (
            <div className="flex items-center gap-2 md:gap-4">
              <span className="hidden sm:inline font-bold text-xs md:text-sm">Olá, {user.name.split(' ')[0]}</span>
              <button onClick={onLogout} className="text-red-500 font-bold text-[10px] md:text-sm uppercase">Sair</button>
            </div>
          )}
        </div>
      </nav>

      <div className="max-w-6xl mx-auto px-4 mt-6 md:mt-8 space-y-8 md:space-y-10">
        <div className={`p-6 md:p-10 rounded-[24px] md:rounded-[40px] relative overflow-hidden shadow-2xl ${isDarkMode ? 'bg-indigo-600' : 'bg-indigo-700'} text-white`}>
          <div className="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6 text-center md:text-left">
            <div className="space-y-2">
              <h1 className="text-2xl md:text-4xl font-black italic tracking-tighter uppercase leading-tight text-yellow-400">PONTUAÇÃO AO VIVO</h1>
              <p className="opacity-80 text-xs md:text-sm max-w-md">O ranking atualiza em tempo real com os gols! {config.pointsNaMosca} pts no placar exato.</p>
            </div>
            {user ? (
              <button onClick={onGoToGuesses} className="w-full md:w-auto bg-yellow-400 text-indigo-900 px-6 md:px-8 py-3.5 md:py-4 rounded-2xl md:rounded-3xl font-black shadow-xl hover:scale-105 transition-transform uppercase text-[10px] md:text-sm">MEUS PALPITES</button>
            ) : (
              <button onClick={onGoToLogin} className="w-full md:w-auto bg-white text-indigo-700 px-6 md:px-8 py-3.5 md:py-4 rounded-2xl md:rounded-3xl font-black shadow-xl hover:scale-105 transition-transform uppercase text-[10px] md:text-sm">CRIAR CONTA</button>
            )}
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 md:gap-10">
          <div className="lg:col-span-2 space-y-6">
            <h2 className="text-xl md:text-2xl font-black flex items-center gap-3 uppercase italic">
              <span className="animate-pulse text-red-500">🔴</span> PLACAR LIVE
            </h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6">
              {matches.map(match => {
                const isLiveNow = match.liveMinutes && !match.liveMinutes.includes('FIM') && !match.liveMinutes.includes('ENCERRADO');
                return (
                  <div key={match.id} onClick={() => setSelectedMatch(match)} className={`p-5 md:p-6 rounded-[24px] md:rounded-[32px] border-2 transition-all cursor-pointer group hover:-translate-y-1 shadow-sm hover:shadow-xl ${isDarkMode ? 'bg-slate-800 border-slate-700' : 'bg-white border-gray-100'} ${isLiveNow ? 'border-red-500/50' : ''}`}>
                    <div className="text-center mb-4 md:mb-6">
                      <span className={`px-3 py-1 rounded-full text-[8px] md:text-[10px] font-black uppercase tracking-widest ${isLiveNow ? 'bg-red-500 text-white animate-pulse' : isDarkMode ? 'bg-slate-700 text-slate-400' : 'bg-gray-100 text-gray-500'}`}>
                        {isLiveNow ? `AO VIVO • ${match.liveMinutes}` : match.liveMinutes || new Date(match.date).toLocaleDateString('pt-BR')}
                      </span>
                    </div>
                    <div className="flex items-center justify-between gap-4">
                      <div className="flex flex-col items-center flex-1 text-center min-w-0">
                        <img src={match.homeLogo} className="w-10 h-10 md:w-14 md:h-14 object-contain mb-2 drop-shadow-md" alt="" />
                        <span className="font-black text-[9px] md:text-xs uppercase truncate w-full">{match.homeTeam}</span>
                      </div>
                      <div className={`text-xl md:text-3xl font-black px-2 md:px-4 ${isDarkMode ? 'text-indigo-400' : 'text-indigo-900'}`}>
                        {match.homeScore !== undefined ? `${match.homeScore} x ${match.awayScore}` : 'VS'}
                      </div>
                      <div className="flex flex-col items-center flex-1 text-center min-w-0">
                        <img src={match.awayLogo} className="w-10 h-10 md:w-14 md:h-14 object-contain mb-2 drop-shadow-md" alt="" />
                        <span className="font-black text-[9px] md:text-xs uppercase truncate w-full">{match.awayTeam}</span>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>

          <div className="space-y-6">
            <h2 className="text-xl md:text-2xl font-black flex items-center gap-3 uppercase italic">
              <span className="text-yellow-500">🏆</span> RANKING LIVE
            </h2>
            <div className={`rounded-[24px] md:rounded-[32px] overflow-hidden shadow-2xl border ${isDarkMode ? 'bg-slate-800 border-slate-700' : 'bg-white border-gray-100'}`}>
              {sortedUsers.map((u, idx) => (
                <div key={idx} className={`flex items-center justify-between p-4 md:p-5 border-b last:border-0 ${user?.id === u.id ? 'bg-indigo-500/10' : ''}`}>
                  <div className="flex items-center gap-3 md:gap-4">
                    <span className={`w-7 h-7 md:w-8 md:h-8 rounded-xl flex items-center justify-center font-black text-[10px] md:text-xs ${idx === 0 ? 'bg-yellow-400 text-yellow-900' : isDarkMode ? 'bg-slate-700 text-slate-400' : 'bg-slate-100 text-slate-400'}`}>
                      {idx + 1}
                    </span>
                    <span className="font-bold text-xs md:text-sm truncate max-w-[100px] md:max-w-[140px]">{u.name}</span>
                  </div>
                  <div className="text-right shrink-0">
                    <p className="font-black text-indigo-500 text-base md:text-lg leading-none">{u.points}</p>
                    <span className="text-[8px] font-black uppercase opacity-50">Pts</span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {user?.role === 'admin' && (
        <button onClick={onGoToAdmin} className="fixed bottom-6 right-6 bg-red-600 text-white w-14 h-14 md:w-16 md:h-16 rounded-full shadow-2xl font-black flex items-center justify-center hover:scale-110 active:scale-95 transition-all z-40 border-4 border-white/20">⚙️</button>
      )}
    </div>
  );
};

export default Dashboard;
