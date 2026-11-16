export interface User {
  id: number;
  username: string;
  email: string;
  avatar_url: string | null;
  referral_code: string;
  referred_by_id: number | null;
  available_coins: number;
  pending_coins: number;
  locked_withdrawal_coins: number;
  lifetime_earned_coins: number;
  status: 'active' | 'suspended' | 'banned';
  country_code: string | null;
  fraud_score: number;
  created_at: string;
}

export interface AuthResponse {
  message: string;
  token: string;
  user: User;
  bonus_credited?: boolean;
  bonus_amount?: number;
}

export interface RegisterPayload {
  username: string;
  email: string;
  password: string;
  password_confirmation: string;
  referral_code?: string;
}

export interface LoginPayload {
  login: string;
  password: string;
}

export interface Offer {
  id: number;
  provider_slug: string;
  provider_name?: string;
  provider_logo?: string;
  external_offer_id: string;
  title: string;
  description: string;
  requirements?: string[] | Record<string, unknown>;
  icon_url: string;
  network_payout_usd: number;
  user_payout_coins: number;
  device_targets: string[];
  countries: string[];
  categories: string[];
  epc_score: number;
  is_featured: boolean;
  is_active: boolean;
}

export interface OfferwallProvider {
  id: number;
  slug: string;
  name: string;
  logo_url: string;
  payout_margin_percent: number;
  bonus_multiplier: number;
  is_active: boolean;
  badge_text?: string;
  rating?: number;
}

export interface Survey {
  id: string;
  provider: string;
  title: string;
  payout_coins: number;
  estimated_minutes: number;
  conversion_rate?: number;
  click_url: string;
}

export interface OfferClickResponse {
  click_uuid: string;
  redirect_url: string;
}

export interface OfferHold {
  id: number;
  offer_title: string;
  provider_slug: string;
  coins_amount: number;
  release_at: string;
  status: 'held' | 'released' | 'reversed';
  created_at: string;
}

export interface CryptoPayoutMethod {
  id: number;
  symbol: string;
  name: string;
  network: string;
  icon_url: string;
  min_coins: number;
  fee_coins: number;
  address_regex?: string;
  is_active: boolean;
}

export interface WithdrawalRequest {
  id: number;
  uuid: string;
  coins_amount: number;
  usd_amount: number;
  wallet_address: string;
  status: 'pending' | 'approved' | 'rejected';
  tx_hash: string | null;
  rejection_reason: string | null;
  method?: CryptoPayoutMethod;
  created_at: string;
}

export interface EarningsTickerItem {
  id: string;
  username: string;
  avatar_url?: string;
  activity: string;
  coins: number;
  time_ago: string;
}

export interface PlatformStats {
  total_users: number;
  total_coins_paid: number;
  total_usd_paid: number;
  avg_cashout_time_minutes: number;
  avg_daily_earnings_usd: number;
  ticker_feed?: EarningsTickerItem[];
}

export interface LeaderboardItem {
  rank: number;
  username: string;
  avatar_url?: string;
  coins_earned: number;
  usd_earned: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface ReferralStats {
  total_referrals: number;
  qualified_referrals: number;
  pending_referrals: number;
  lifetime_referral_coins: number;
  qualification_coins: number;
  bonus_per_qualified: number;
}

export interface ReferredUser {
  id: number;
  username: string;
  joined_at: string;
  lifetime_earned_coins: number;
  is_qualified: boolean;
  bonus_paid: boolean;
}

export interface ReferralsResponse {
  referral_code: string;
  stats: ReferralStats;
  referred_users: ReferredUser[];
}

export interface ApiErrorResponse {
  message: string;
  errors?: Record<string, string[]>;
}
