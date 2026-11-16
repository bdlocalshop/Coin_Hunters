import {
  AuthResponse,
  LoginPayload,
  RegisterPayload,
  User,
  Offer,
  OfferwallProvider,
  Survey,
  OfferClickResponse,
  OfferHold,
  CryptoPayoutMethod,
  WithdrawalRequest,
  PlatformStats,
  LeaderboardItem,
  PaginatedResponse,
  ApiErrorResponse,
} from '@/types/api';
import { getDeviceFingerprint } from './fingerprint';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';
const TOKEN_KEY = 'coin_hunters_token';
const USER_KEY = 'coin_hunters_user';

export class ApiError extends Error {
  status: number;
  errors?: Record<string, string[]>;

  constructor(status: number, message: string, errors?: Record<string, string[]>) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.errors = errors;
  }
}

// Token & Auth storage utilities
export function getStoredToken(): string | null {
  if (typeof window === 'undefined') return null;
  try {
    return localStorage.getItem(TOKEN_KEY);
  } catch {
    return null;
  }
}

export function setStoredToken(token: string): void {
  if (typeof window === 'undefined') return;
  try {
    localStorage.setItem(TOKEN_KEY, token);
  } catch {
    // ignore
  }
}

export function removeStoredToken(): void {
  if (typeof window === 'undefined') return;
  try {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
  } catch {
    // ignore
  }
}

export function getStoredUser(): User | null {
  if (typeof window === 'undefined') return null;
  try {
    const raw = localStorage.getItem(USER_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

export function setStoredUser(user: User): void {
  if (typeof window === 'undefined') return;
  try {
    localStorage.setItem(USER_KEY, JSON.stringify(user));
  } catch {
    // ignore
  }
}

// Low-level fetch client
async function request<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
  const url = `${API_BASE_URL}${endpoint.startsWith('/') ? endpoint : `/${endpoint}`}`;

  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    ...(options.headers as Record<string, string>),
  };

  const token = getStoredToken();
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  // Attach deterministic device fingerprint
  try {
    const deviceHash = await getDeviceFingerprint();
    if (deviceHash) {
      headers['X-Device-Hash'] = deviceHash;
    }
  } catch {
    // proceed without fingerprint if fails
  }

  const response = await fetch(url, {
    ...options,
    headers,
  });

  if (response.status === 204) {
    return {} as T;
  }

  let data: any;
  try {
    data = await response.json();
  } catch {
    data = { message: response.statusText };
  }

  if (!response.ok) {
    if (response.status === 401) {
      removeStoredToken();
    }
    const errorData = data as ApiErrorResponse;
    throw new ApiError(
      response.status,
      errorData.message || `Request failed with status ${response.status}`,
      errorData.errors
    );
  }

  return data as T;
}

// Domain API client namespaces
export const api = {
  auth: {
    async register(payload: RegisterPayload): Promise<AuthResponse> {
      const res = await request<AuthResponse>('/auth/register', {
        method: 'POST',
        body: JSON.stringify(payload),
      });
      if (res.token) {
        setStoredToken(res.token);
        setStoredUser(res.user);
      }
      return res;
    },

    async login(payload: LoginPayload): Promise<AuthResponse> {
      const res = await request<AuthResponse>('/auth/login', {
        method: 'POST',
        body: JSON.stringify(payload),
      });
      if (res.token) {
        setStoredToken(res.token);
        setStoredUser(res.user);
      }
      return res;
    },

    async logout(): Promise<void> {
      try {
        await request('/auth/logout', { method: 'POST' });
      } finally {
        removeStoredToken();
      }
    },

    async me(): Promise<{ user: User }> {
      const res = await request<{ user: User }>('/auth/me');
      if (res.user) {
        setStoredUser(res.user);
      }
      return res;
    },

    async referrals(): Promise<import('@/types/api').ReferralsResponse> {
      return request('/referrals');
    },
  },

  offers: {
    async list(params?: {
      category?: string;
      device?: string;
      search?: string;
      page?: number;
      per_page?: number;
    }): Promise<PaginatedResponse<Offer>> {
      const query = new URLSearchParams();
      if (params?.category) query.append('category', params.category);
      if (params?.device) query.append('device', params.device);
      if (params?.search) query.append('search', params.search);
      if (params?.page) query.append('page', String(params.page));
      if (params?.per_page) query.append('per_page', String(params.per_page));

      const queryString = query.toString() ? `?${query.toString()}` : '';
      return request<PaginatedResponse<Offer>>(`/offers${queryString}`);
    },

    async get(id: number): Promise<{ offer: Offer }> {
      return request<{ offer: Offer }>(`/offers/${id}`);
    },

    async click(id: number): Promise<OfferClickResponse> {
      return request<OfferClickResponse>(`/offers/${id}/click`, {
        method: 'POST',
      });
    },

    async providers(): Promise<{ providers: OfferwallProvider[] }> {
      return request<{ providers: OfferwallProvider[] }>('/offer-walls');
    },

    async surveys(): Promise<{ surveys: Survey[] }> {
      return request<{ surveys: Survey[] }>('/surveys');
    },

    async myOffers(): Promise<{
      active_clicks: any[];
      holds: OfferHold[];
      total_pending_coins: number;
    }> {
      return request('/my-offers');
    },
  },

  shop: {
    async methods(): Promise<{ methods: CryptoPayoutMethod[] }> {
      return request<{ methods: CryptoPayoutMethod[] }>('/shop/methods');
    },

    async withdraw(payload: {
      crypto_payout_method_id: number;
      coins_amount: number;
      wallet_address: string;
    }): Promise<{ message: string; withdrawal: WithdrawalRequest; user: User }> {
      return request('/shop/withdraw', {
        method: 'POST',
        body: JSON.stringify(payload),
      });
    },

    async withdrawals(): Promise<{ withdrawals: WithdrawalRequest[] }> {
      return request<{ withdrawals: WithdrawalRequest[] }>('/shop/withdrawals');
    },
  },

  public: {
    async stats(): Promise<PlatformStats> {
      return request<PlatformStats>('/public/stats');
    },

    async leaderboard(): Promise<{ leaderboard: LeaderboardItem[] }> {
      return request<{ leaderboard: LeaderboardItem[] }>('/leaderboard');
    },
  },
};
