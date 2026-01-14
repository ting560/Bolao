
import React, { useState } from 'react';

interface Props {
  onLogin: (email: string, password?: string) => void;
  onGoToRegister: () => void;
  onBack: () => void;
}

const Login: React.FC<Props> = ({ onLogin, onGoToRegister, onBack }) => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onLogin(email, password);
  };

  return (
    <div className="bg-white p-8 rounded-[32px] shadow-2xl max-w-md mx-auto border border-gray-100">
      <div className="flex justify-between items-center mb-6">
        <button onClick={onBack} className="text-gray-400 hover:text-indigo-600 text-sm font-bold transition-colors">← VOLTAR</button>
        <span className="text-[10px] bg-indigo-50 px-3 py-1 rounded-full font-black text-indigo-400 uppercase tracking-widest border border-indigo-100">Acesso Seguro</span>
      </div>
      
      <div className="text-center mb-8">
        <h2 className="text-3xl font-black text-indigo-900 mb-2 italic">Bolão dos Amigos</h2>
        <p className="text-gray-500 font-medium">Entre para dar seus palpites</p>
        <div className="mt-4 p-3 bg-yellow-50 rounded-xl border border-yellow-100 text-[10px] text-yellow-700 font-bold uppercase leading-tight">
          Adm? Use: marcos@gmail.com / Serrw3@8*Lo
        </div>
      </div>

      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label className="block text-xs font-black text-gray-400 uppercase tracking-wider mb-2 ml-1">E-mail</label>
          <input 
            type="email" 
            required 
            className="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl text-slate-900 font-bold placeholder:text-gray-300 focus:bg-white focus:border-indigo-500 focus:outline-none transition-all"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            placeholder="seu@email.com"
          />
        </div>

        <div>
          <label className="block text-xs font-black text-gray-400 uppercase tracking-wider mb-2 ml-1">Senha</label>
          <input 
            type="password" 
            required
            className="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl text-slate-900 font-bold placeholder:text-gray-300 focus:bg-white focus:border-indigo-500 focus:outline-none transition-all"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            placeholder="••••••••"
          />
        </div>
        
        <button 
          type="submit"
          className="w-full py-4 px-6 rounded-2xl shadow-lg shadow-indigo-200 text-sm font-black text-white bg-indigo-600 hover:bg-indigo-700 active:scale-[0.98] transition-all mt-4"
        >
          ENTRAR NO BOLÃO
        </button>
      </form>

      <div className="mt-8 text-center">
        <button onClick={onGoToRegister} className="text-sm text-indigo-600 hover:text-indigo-800 font-black transition-colors">
          Ainda não participa? <span className="underline">Cadastre-se agora</span>
        </button>
      </div>
    </div>
  );
};

export default Login;
