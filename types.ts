
export interface User {
  id: string;
  name: string;
  email: string;
  password?: string;
  role: 'admin' | 'user';
  points: number;
}

export interface Match {
  id: string;
  homeTeam: string;
  awayTeam: string;
  homeLogo: string;
  awayLogo: string;
  date: string;
  status: 'pending' | 'finished';
  homeScore?: number;
  awayScore?: number;
  liveMinutes?: string; // Novo: Tempo real (ex: "45'")
}

export interface Guess {
  id: string;
  userId: string;
  matchId: string;
  homeScore: number;
  awayScore: number;
  userName?: string; 
}

export interface BolaoConfig {
  pointsNaMosca: number;
  pointsWinner: number;
  feePercent: number;
  prize1stPercent: number;
  prize2ndPercent: number;
  entryFee: number;
}

export enum Page {
  LOGIN = 'LOGIN',
  REGISTER = 'REGISTER',
  DASHBOARD = 'DASHBOARD',
  GUESSES = 'GUESSES',
  ADMIN = 'ADMIN'
}
