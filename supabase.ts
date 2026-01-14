
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2.39.7';

const SUPABASE_URL = 'https://bjtbpbbejwmhtvddugjn.supabase.co';
// Nota: A chave abaixo deve ser a sua anon/public key completa do projeto
const SUPABASE_KEY = 'YOUR_ACTUAL_PUBLISHABLE_KEY'; 

export const supabase = createClient(SUPABASE_URL, SUPABASE_KEY);
