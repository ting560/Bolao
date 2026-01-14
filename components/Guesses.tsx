
import React, { useState } from 'react';
import { Match, Guess, User } from '../types';
import { isMatchExpired } from '../utils/scoring';

interface Props {
  matches: Match[];
  onSave: (guesses: Guess[]) => void;
  onCancel: () => void;
  currentUser: User;
  currentGuesses: Guess[];
}

const Guesses: React.FC<Props> = ({ matches, onSave, onCancel, currentUser, currentGuesses }) => {
  const [localGuesses, setLocalGuesses] = useState<Record<string, { home: number; away: number }>>(() => {
    const initial: Record<string, { home: number; away: number }> = {};
    matches.forEach(m => {
      const existing = currentGuesses.find(g => g.matchId === m.id && g.userId === currentUser.id);
      initial[m.id] = { home: existing?.homeScore ?? 0, away: existing?.awayScore ?? 0 };
    });
    return initial;
  });

  const handleScoreChange = (matchId: string, team: 'home' | 'away', val: string) => {
    const num = val === '' ? 0 : parseInt(val);
    setLocalGuesses(prev => ({
      ...prev,
      [matchId]: {
        ...prev[matchId],
        [team]: isNaN(num) ? 0 : num
      }
    }));
  };

  const handleSubmit = () => {
    const formattedGuesses: Guess[] = (Object.entries(localGuesses) as [string, { home: number; away: number }][])
      .filter(([matchId]) => {
        const match = matches.find(m => m.id === matchId);
        return match && !isMatchExpired(match.date);
      })
      .map(([matchId, scores]) => ({
        id: Math.random().toString(36).substr(2, 9),
        userId: currentUser.id,
        matchId,
        homeScore: scores.home,
        awayScore: scores.away
      }));

    if (formattedGuesses.length === 0) {
      alert("Nenhum palpite válido enviado.");
      return;
    }

    onSave(formattedGuesses);
  };

  return (
    <div className="bg-white rounded-[24px] md:rounded-[40px] shadow-2xl p-4 md:p-10 mb-10 border border-gray-100">
      <div className="flex justify-between items-center mb-6 md:mb-10">
        <div>
          <h1 className="text-xl md:text-3xl font-black text-indigo-900 italic uppercase">MEUS PALPITES</h1>
          <p className="text-[10px] text-gray-400 font-bold uppercase mt-1">Preencha e confirme</p>
        </div>
        <button onClick={onCancel} className="bg-slate-100 px-4 py-2 rounded-xl font-black text-[10px] md:text-xs hover:bg-slate-200 transition uppercase">Voltar</button>
      </div>

      <div className="space-y-4 md:divide-y md:divide-gray-50 md:space-y-0">
        {matches.map(match => {
          const expired = isMatchExpired(match.date);
          return (
            <div key={match.id} className={`p-4 md:py-8 rounded-3xl md:rounded-none border-2 md:border-0 ${expired ? 'bg-gray-50 opacity-60 border-gray-100' : 'bg-white border-transparent shadow-sm md:shadow-none'}`}>
              <div className="flex flex-col items-center gap-4">
                <div className="flex items-center justify-between w-full max-w-xl gap-2">
                  <div className="flex flex-col md:flex-row items-center gap-2 flex-1 text-center md:text-right min-w-0">
                    <span className="hidden md:block font-black text-sm text-slate-700 truncate order-1 md:order-2">{match.homeTeam}</span>
                    <img src={match.homeLogo} alt="" className="w-10 h-10 md:w-12 md:h-12 object-contain drop-shadow-sm order-2 md:order-1" />
                    <span className="md:hidden font-black text-[10px] uppercase text-slate-700 truncate w-full order-3">{match.homeTeam}</span>
                  </div>

                  <div className="flex items-center gap-2 md:gap-4 shrink-0">
                    <input 
                      type="number" 
                      disabled={expired}
                      className="w-12 h-12 md:w-16 md:h-16 text-center border-2 border-slate-200 rounded-2xl text-xl md:text-2xl font-black text-indigo-600 focus:border-indigo-500 focus:ring-0 outline-none disabled:bg-gray-100 shadow-sm transition-all"
                      value={localGuesses[match.id].home}
                      onChange={(e) => handleScoreChange(match.id, 'home', e.target.value)}
                    />
                    <span className="text-xl font-black text-slate-300">X</span>
                    <input 
                      type="number" 
                      disabled={expired}
                      className="w-12 h-12 md:w-16 md:h-16 text-center border-2 border-slate-200 rounded-2xl text-xl md:text-2xl font-black text-indigo-600 focus:border-indigo-500 focus:ring-0 outline-none disabled:bg-gray-100 shadow-sm transition-all"
                      value={localGuesses[match.id].away}
                      onChange={(e) => handleScoreChange(match.id, 'away', e.target.value)}
                    />
                  </div>

                  <div className="flex flex-col md:flex-row items-center gap-2 flex-1 text-center md:text-left min-w-0">
                    <img src={match.awayLogo} alt="" className="w-10 h-10 md:w-12 md:h-12 object-contain drop-shadow-sm" />
                    <span className="font-black text-[10px] md:text-sm text-slate-700 uppercase md:normal-case truncate w-full">{match.awayTeam}</span>
                  </div>
                </div>
                {expired && <span className="text-[10px] font-black text-red-500 bg-red-50 px-3 py-1 rounded-full uppercase tracking-tighter">Jogo iniciado - Encerrado</span>}
              </div>
            </div>
          );
        })}
      </div>

      <div className="mt-10 md:mt-12 flex flex-col items-center gap-4">
        <button 
          onClick={handleSubmit}
          className="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 px-12 rounded-2xl text-base md:text-lg shadow-xl shadow-indigo-500/20 active:scale-95 transition-all uppercase tracking-tight"
        >
          CONFIRMAR PALPITES
        </button>
        <p className="text-[10px] text-gray-400 font-bold italic uppercase">Você pode alterar até o horário do jogo</p>
      </div>
    </div>
  );
};

export default Guesses;
