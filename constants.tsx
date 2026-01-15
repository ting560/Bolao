
import { Match, User } from './types';
import { getTeamLogo } from './utils/scoring';

export const INITIAL_MATCHES: Match[] = [
  {
    id: '1',
    homeTeam: 'FLUMINENSE',
    awayTeam: 'GREMIO',
    homeLogo: getTeamLogo('fluminense'),
    awayLogo: getTeamLogo('gremio'),
    date: '2026-01-28T21:00:00',
    status: 'pending'
  },
  {
    id: '2',
    homeTeam: 'BOTAFOGO',
    awayTeam: 'CRUZEIRO',
    homeLogo: getTeamLogo('botafogo'),
    awayLogo: getTeamLogo('cruzeiro'),
    date: '2026-01-29T19:00:00',
    status: 'pending'
  },
  {
    id: '3',
    homeTeam: 'SAO PAULO',
    awayTeam: 'FLAMENGO',
    homeLogo: getTeamLogo('sao paulo'),
    awayLogo: getTeamLogo('flamengo'),
    date: '2026-01-30T16:00:00',
    status: 'pending'
  }
];

export const INITIAL_USERS: User[] = [
  { 
    id: 'admin-1', 
    name: 'Marcos Admin', 
    email: 'marcos@gmail.com', 
    password: 'Serrw3@8*Lo', 
    role: 'admin', 
    points: 0 
  },
  { id: '2', name: 'João Silva', email: 'joao@email.com', role: 'user', points: 45 },
  { id: '3', name: 'Maria Souza', email: 'maria@email.com', role: 'user', points: 38 },
];
