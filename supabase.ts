
import { createClient } from '@supabase/supabase-js';

const SUPABASE_URL = 'https://bjtbpbbejwmhtvddugjn.supabase.co';
const SUPABASE_KEY = 'sb_publishable_LD3fXQXNZgkDSNeF7ezwGA_XQSJd8Cx'; 

export const supabase = createClient(SUPABASE_URL, SUPABASE_KEY);
