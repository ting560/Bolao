
import { Match, Guess } from '../types';

export const calculatePoints = (match: Match, guess: Guess): number => {
  if (match.status !== 'finished' || match.homeScore === undefined || match.awayScore === undefined) {
    return 0;
  }

  const { homeScore: mH, awayScore: mA } = match;
  const { homeScore: gH, awayScore: gA } = guess;

  // Na Mosca (12 pontos)
  if (mH === gH && mA === gA) return 12;

  // Acertar Vencedor ou Empate (5 pontos)
  const matchResult = mH > mA ? 'home' : mH < mA ? 'away' : 'draw';
  const guessResult = gH > gA ? 'home' : gH < gA ? 'away' : 'draw';

  if (matchResult === guessResult) return 5;

  return 0;
};

export const isMatchExpired = (matchDate: string): boolean => {
  return new Date() >= new Date(matchDate);
};

export const getTeamLogo = (teamName: string): string => {
  const name = teamName.toLowerCase().trim();
  // Mapeamento de alguns IDs comuns baseados na estrutura da superplacar
  const logos: Record<string, string> = {
    'flamengo': '1728417620_flamengo.png',
    'fluminense': '1728417620_fluminense.png',
    'vasco': '1728417620_vasco.png',
    'botafogo': '1728417620_botafogo.png',
    'palmeiras': '1728417620_palmeiras.png',
    'corinthians': '1728417620_corinthians.png',
    'sao paulo': '1728417620_sao_paulo.png',
    'gremio': '1728417620_gremio.png',
    'cruzeiro': '1728417620_cruzeiro.png'
  };
  const fileName = logos[name] || `1728417620_${name}.png`;
  return `https://superplacar.com.br/imagem/times/${fileName}`;
};
