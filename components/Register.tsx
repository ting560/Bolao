
import React, { useState } from 'react';

interface Props {
  onRegister: (name: string, email: string, pass: string) => void;
  onGoToLogin: () => void;
  onBack: () => void;
}

const Register: React.FC<Props> = ({ onRegister, onGoToLogin, onBack }) => {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onRegister(name, email, password);
  };

  return (
    <div className="bg-white p-8 rounded-[32px] shadow-2xl max-w-md mx-auto border border-gray-100">
      <div className="flex justify-between items-center mb-6">
        <button onClick={onBack} className="text-gray-400 hover:text-indigo-600 text-sm font-bold transition-colors">← VOLTAR</button>
      </div>

      <div className="text-center mb-8">
        <h2 className="text-3xl font-black text-indigo-900 mb-2 italic">Criar Conta</h2>
        <p className="text-gray-500 font-medium">Cadastre-se para começar a pontuar</p>
      </div>

      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label className="block text-xs font-black text-gray-400 uppercase tracking-wider mb-2 ml-1">Nome Completo</label>
          <input 
            type="text" 
            required 
            placeholder="Como quer ser chamado?"
            className="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl text-slate-900 font-bold placeholder:text-gray-300 focus:bg-white focus:border-indigo-500 focus:outline-none transition-all"
            value={name}
            onChange={(e) => setName(e.target.value)}
          />
        </div>

        <div>
          <label className="block text-xs font-black text-gray-400 uppercase tracking-wider mb-2 ml-1">Seu Melhor E-mail</label>
          <input 
            type="email" 
            required 
            placeholder="contato@email.com"
            className="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl text-slate-900 font-bold placeholder:text-gray-300 focus:bg-white focus:border-indigo-500 focus:outline-none transition-all"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
          />
        </div>

        <div>
          <label className="block text-xs font-black text-gray-400 uppercase tracking-wider mb-2 ml-1">Escolha uma Senha</label>
          <input 
            type="password" 
            required 
            placeholder="••••••••"
            className="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl text-slate-900 font-bold placeholder:text-gray-300 focus:bg-white focus:border-indigo-500 focus:outline-none transition-all"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
          />
        </div>

        <button 
          type="submit"
          className="w-full mt-4 py-4 px-6 rounded-2xl shadow-lg shadow-indigo-200 text-sm font-black text-white bg-indigo-600 hover:bg-indigo-700 active:scale-[0.98] transition-all"
        >
          FINALIZAR MEU CADASTRO
        </button>
      </form>

      <div className="mt-8 text-center">
        <button onClick={onGoToLogin} className="text-sm text-indigo-600 hover:text-indigo-800 font-black transition-colors">
          Já tem uma conta? <span className="underline">Faça login aqui</span>
        </button>
      </div>
    </div>
  );
};

export default Register;
