'use client';

import React, { useState, useEffect } from 'react';
import { useAuth } from '@/context/AuthContext';
import { api } from '@/lib/api';
import { CryptoPayoutMethod, WithdrawalRequest } from '@/types/api';
import {
  Wallet,
  Coins,
  ShieldCheck,
  ExternalLink,
  Clock,
  CheckCircle,
  AlertCircle,
  X,
  ArrowRight,
} from 'lucide-react';

const FALLBACK_METHODS: CryptoPayoutMethod[] = [
  {
    id: 1,
    symbol: 'LTC',
    name: 'Litecoin',
    network: 'Litecoin Mainnet',
    icon_url: 'https://cryptologos.cc/logos/litecoin-ltc-logo.png',
    min_coins: 5000,
    fee_coins: 0,
    address_regex: '^[LM3][a-km-zA-HJ-NP-Z1-9]{26,33}$',
    is_active: true,
  },
  {
    id: 2,
    symbol: 'USDT_TRC20',
    name: 'Tether (USDT)',
    network: 'Tron TRC-20',
    icon_url: 'https://cryptologos.cc/logos/tether-usdt-logo.png',
    min_coins: 10000,
    fee_coins: 0,
    address_regex: '^T[A-Za-z1-9]{33}$',
    is_active: true,
  },
  {
    id: 3,
    symbol: 'BTC',
    name: 'Bitcoin',
    network: 'Bitcoin Native',
    icon_url: 'https://cryptologos.cc/logos/bitcoin-btc-logo.png',
    min_coins: 25000,
    fee_coins: 500,
    address_regex: '^(bc1|[13])[a-zA-HJ-NP-Z0-9]{25,39}$',
    is_active: true,
  },
  {
    id: 4,
    symbol: 'SOL',
    name: 'Solana',
    network: 'Solana Mainnet',
    icon_url: 'https://cryptologos.cc/logos/solana-sol-logo.png',
    min_coins: 5000,
    fee_coins: 0,
    address_regex: '^[1-9A-HJ-NP-Za-km-z]{32,44}$',
    is_active: true,
  },
];

export default function ShopPage() {
  const { user, isAuthenticated, openAuthModal, refreshUser } = useAuth();
  const [methods, setMethods] = useState<CryptoPayoutMethod[]>(FALLBACK_METHODS);
  const [withdrawals, setWithdrawals] = useState<WithdrawalRequest[]>([]);
  const [selectedMethod, setSelectedMethod] = useState<CryptoPayoutMethod | null>(null);

  // Modal withdrawal form state
  const [coinsAmount, setCoinsAmount] = useState<number>(5000);
  const [walletAddress, setWalletAddress] = useState<string>('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  useEffect(() => {
    let mounted = true;

    api.shop
      .methods()
      .then((res) => {
        if (mounted && res.methods && res.methods.length > 0) {
          setMethods(res.methods);
        }
      })
      .catch(() => {});

    if (isAuthenticated) {
      api.shop
        .withdrawals()
        .then((res) => {
          if (mounted && res.withdrawals) {
            setWithdrawals(res.withdrawals);
          }
        })
        .catch(() => {});
    }

    return () => {
      mounted = false;
    };
  }, [isAuthenticated]);

  const handleOpenWithdraw = (method: CryptoPayoutMethod) => {
    if (!isAuthenticated) {
      openAuthModal('register');
      return;
    }
    setSelectedMethod(method);
    setCoinsAmount(method.min_coins);
    setWalletAddress('');
    setSubmitError(null);
    setSuccessMessage(null);
  };

  const handleWithdrawSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedMethod) return;

    if (!user || user.available_coins < coinsAmount) {
      setSubmitError('Insufficient available coins balance.');
      return;
    }

    if (coinsAmount < selectedMethod.min_coins) {
      setSubmitError(`Minimum cashout is ${selectedMethod.min_coins.toLocaleString()} Coins.`);
      return;
    }

    if (!walletAddress.trim()) {
      setSubmitError('Please enter a destination wallet address.');
      return;
    }

    setIsSubmitting(true);
    setSubmitError(null);

    try {
      await api.shop.withdraw({
        crypto_payout_method_id: selectedMethod.id,
        coins_amount: coinsAmount,
        wallet_address: walletAddress.trim(),
      });

      setSuccessMessage('Withdrawal request submitted successfully! Your coins are safely escrowed.');
      await refreshUser();

      // Refresh history
      const hist = await api.shop.withdrawals();
      setWithdrawals(hist.withdrawals);

      setTimeout(() => {
        setSelectedMethod(null);
      }, 2000);
    } catch (err: any) {
      setSubmitError(err.message || 'Withdrawal failed. Check address or rate limits.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const setPercentCoins = (pct: number) => {
    if (!user) return;
    const target = Math.floor((user.available_coins * pct) / 100);
    setCoinsAmount(Math.max(selectedMethod?.min_coins || 0, target));
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">
      {/* Page Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-[#261D22]">
        <div>
          <div className="flex items-center space-x-2 text-xs font-bold text-[#FF5A36] uppercase tracking-wider mb-1">
            <Wallet className="w-4 h-4" />
            <span>Crypto Payout Store</span>
          </div>
          <h1 className="text-2xl sm:text-3xl font-black text-white">
            Redeem Coins For Real Crypto
          </h1>
          <p className="text-xs sm:text-sm text-gray-400 mt-1">
            Fixed standard rate: 1,000 Coins = $1.00 USD. Zero hidden fees on crypto redemptions.
          </p>
        </div>

        <div className="flex items-center space-x-2 px-3.5 py-1.5 rounded-xl bg-[#181313] border border-[#261D22] text-xs">
          <ShieldCheck className="w-4 h-4 text-emerald-400" />
          <span className="text-gray-300">Escrow Protected • Fast Manual Reviews</span>
        </div>
      </div>

      {/* User Balances & Valuation Banner */}
      <div className="p-6 rounded-3xl bg-gradient-to-r from-[#181313] via-[#211919] to-[#181313] border border-[#382A30] flex flex-col md:flex-row items-stretch md:items-center justify-between gap-6 shadow-xl">
        <div className="space-y-1">
          <span className="text-xs font-bold text-gray-400 uppercase tracking-wider">
            Your Available Cashout Balance
          </span>
          <div className="flex items-baseline space-x-3">
            <span className="text-3xl sm:text-4xl font-black text-white">
              {(user?.available_coins || 0).toLocaleString()}
            </span>
            <span className="text-[#FACC15] font-black text-lg">Coins</span>
            <span className="text-sm font-mono text-gray-400">
              ≈ ${((user?.available_coins || 0) / 1000).toFixed(2)} USD
            </span>
          </div>
          <p className="text-xs text-gray-400">
            Coins in hold status ({(user?.pending_coins || 0).toLocaleString()} Coins) unlock automatically after verification.
          </p>
        </div>

        <div className="flex items-center space-x-4 p-3.5 rounded-2xl bg-[#120E0E] border border-[#261D22]">
          <div className="w-10 h-10 rounded-xl bg-[#FACC15]/10 flex items-center justify-center text-[#FACC15]">
            <Coins className="w-5 h-5" />
          </div>
          <div className="text-xs">
            <span className="text-gray-500 uppercase font-semibold block text-[10px]">Standard Value</span>
            <span className="font-extrabold text-white text-sm">1,000 Coins = $1.00 USD</span>
          </div>
        </div>
      </div>

      {/* Crypto Cashout Methods Grid */}
      <div className="space-y-4">
        <h2 className="text-base font-extrabold text-white uppercase tracking-wider">
          Available Cryptocurrencies
        </h2>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
          {methods.map((method) => {
            const minUsd = (method.min_coins / 1000).toFixed(2);
            return (
              <div
                key={method.id}
                onClick={() => handleOpenWithdraw(method)}
                className="p-6 rounded-3xl bg-[#181313] hover:bg-[#211919] border border-[#261D22] hover:border-[#382A30] transition-all cursor-pointer group flex flex-col justify-between shadow-xl"
              >
                <div className="space-y-4">
                  <div className="flex items-start justify-between">
                    <div className="w-12 h-12 rounded-2xl bg-[#261D22] border border-[#382A30] flex items-center justify-center font-black text-sm text-white group-hover:scale-105 transition-transform shadow-md">
                      {method.symbol.slice(0, 3)}
                    </div>
                    <span className="px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#120E0E] text-gray-300 border border-[#261D22]">
                      Min ${minUsd}
                    </span>
                  </div>

                  <div>
                    <h3 className="font-extrabold text-white text-lg group-hover:text-[#FF5A36] transition-colors">
                      {method.name}
                    </h3>
                    <p className="text-xs text-gray-400 mt-0.5">
                      {method.network}
                    </p>
                  </div>

                  <div className="space-y-1.5 pt-2 border-t border-[#261D22] text-xs">
                    <div className="flex items-center justify-between text-gray-400">
                      <span>Minimum Coins:</span>
                      <span className="font-bold text-white font-mono">{method.min_coins.toLocaleString()}</span>
                    </div>
                    <div className="flex items-center justify-between text-gray-400">
                      <span>Network Fee:</span>
                      <span className="font-bold text-emerald-400 font-mono">
                        {method.fee_coins === 0 ? 'FREE' : `${method.fee_coins} Coins`}
                      </span>
                    </div>
                  </div>
                </div>

                <div className="pt-6 mt-6 border-t border-[#261D22]">
                  <button
                    type="button"
                    className="w-full py-2.5 px-4 rounded-xl bg-[#261D22] group-hover:bg-gradient-to-r group-hover:from-[#FF5A36] group-hover:to-[#F97316] text-white text-xs font-bold transition-all flex items-center justify-center space-x-1.5"
                  >
                    <span>Withdraw {method.symbol.split('_')[0]}</span>
                    <ArrowRight className="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Interactive Withdraw Modal */}
      {selectedMethod && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
          <div
            className="relative w-full max-w-lg bg-[#181313] border border-[#261D22] rounded-3xl shadow-2xl overflow-hidden"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Modal Header */}
            <div className="flex items-center justify-between p-5 border-b border-[#261D22] bg-[#140F0F]">
              <div className="flex items-center space-x-3">
                <div className="w-9 h-9 rounded-xl bg-[#211919] border border-[#382A30] flex items-center justify-center font-bold text-white text-xs">
                  {selectedMethod.symbol.slice(0, 3)}
                </div>
                <div>
                  <h3 className="font-extrabold text-white text-base">
                    Withdraw {selectedMethod.name}
                  </h3>
                  <span className="text-[11px] text-gray-400">{selectedMethod.network}</span>
                </div>
              </div>
              <button
                onClick={() => setSelectedMethod(null)}
                className="p-1 rounded-lg text-gray-400 hover:text-white hover:bg-[#211919] transition-colors"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Modal Form */}
            <form onSubmit={handleWithdrawSubmit} className="p-6 space-y-5">
              {/* Balances available check */}
              <div className="p-3.5 rounded-2xl bg-[#120E0E] border border-[#261D22] flex items-center justify-between text-xs">
                <span className="text-gray-400">Your Available Balance:</span>
                <span className="font-extrabold text-white font-mono">
                  {(user?.available_coins || 0).toLocaleString()} Coins (${(((user?.available_coins || 0) / 1000)).toFixed(2)})
                </span>
              </div>

              {/* Amount Input & Percentage Buttons */}
              <div>
                <div className="flex items-center justify-between mb-1.5">
                  <label className="text-xs font-medium text-gray-300">
                    Coins to Redeem
                  </label>
                  <span className="text-[11px] text-gray-400">
                    Min: {selectedMethod.min_coins.toLocaleString()} Coins
                  </span>
                </div>

                <div className="relative">
                  <input
                    type="number"
                    min={selectedMethod.min_coins}
                    max={user?.available_coins || 1000000}
                    step={100}
                    value={coinsAmount}
                    onChange={(e) => setCoinsAmount(parseInt(e.target.value) || 0)}
                    className="w-full px-4 py-3 bg-[#120E0E] border border-[#261D22] rounded-xl text-base font-mono font-bold text-white focus:outline-none focus:border-[#FF5A36] transition-colors"
                  />
                  <div className="absolute inset-y-0 right-3 flex items-center text-xs font-mono font-semibold text-[#FACC15]">
                    ≈ ${(coinsAmount / 1000).toFixed(2)} USD
                  </div>
                </div>

                {/* Percentage Quick Picks */}
                <div className="grid grid-cols-4 gap-2 mt-2">
                  {[25, 50, 75, 100].map((pct) => (
                    <button
                      key={pct}
                      type="button"
                      onClick={() => setPercentCoins(pct)}
                      className="py-1 px-2 rounded-lg bg-[#211919] hover:bg-[#282020] text-gray-300 hover:text-white text-[11px] font-bold border border-[#261D22] transition-colors"
                    >
                      {pct === 100 ? 'Max (100%)' : `${pct}%`}
                    </button>
                  ))}
                </div>
              </div>

              {/* Wallet Address Input */}
              <div>
                <label className="block text-xs font-medium text-gray-300 mb-1.5">
                  Destination {selectedMethod.name} Wallet Address
                </label>
                <input
                  type="text"
                  required
                  value={walletAddress}
                  onChange={(e) => setWalletAddress(e.target.value)}
                  placeholder={`Enter your ${selectedMethod.symbol} address...`}
                  className="w-full px-4 py-2.5 bg-[#120E0E] border border-[#261D22] rounded-xl text-xs font-mono text-white placeholder-gray-500 focus:outline-none focus:border-[#FF5A36] transition-colors"
                />
                <span className="text-[10px] text-gray-500 mt-1 block">
                  Verify your address matches {selectedMethod.network}. Transactions cannot be reversed once processed.
                </span>
              </div>

              {/* Status alerts */}
              {submitError && (
                <div className="p-3 rounded-xl bg-red-950/40 border border-red-800/60 text-red-300 text-xs flex items-center space-x-2">
                  <AlertCircle className="w-4 h-4 text-red-400 shrink-0" />
                  <span>{submitError}</span>
                </div>
              )}

              {successMessage && (
                <div className="p-3 rounded-xl bg-emerald-950/40 border border-emerald-800/60 text-emerald-300 text-xs flex items-center space-x-2">
                  <CheckCircle className="w-4 h-4 text-emerald-400 shrink-0" />
                  <span>{successMessage}</span>
                </div>
              )}

              {/* Submit CTA */}
              <button
                type="submit"
                disabled={isSubmitting}
                className="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-[#FF5A36] to-[#F97316] hover:from-[#ea4823] hover:to-[#e0650f] text-white text-xs font-extrabold shadow-lg shadow-[#FF5A36]/25 transition-all flex items-center justify-center space-x-2 disabled:opacity-50"
              >
                {isSubmitting ? (
                  <span className="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                ) : (
                  <>
                    <span>Confirm & Request Payout (${(coinsAmount / 1000).toFixed(2)})</span>
                    <ArrowRight className="w-4 h-4" />
                  </>
                )}
              </button>
            </form>
          </div>
        </div>
      )}

      {/* Withdrawal History Table */}
      <div className="space-y-4">
        <h2 className="text-base font-extrabold text-white uppercase tracking-wider">
          Recent Cashout History
        </h2>

        <div className="bg-[#181313] rounded-3xl border border-[#261D22] overflow-hidden shadow-xl">
          {withdrawals.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-[#140F0F] text-gray-500 uppercase tracking-wider text-[10px] border-b border-[#261D22]">
                  <tr>
                    <th className="py-3.5 px-6 font-bold">Method</th>
                    <th className="py-3.5 px-6 font-bold">Amount</th>
                    <th className="py-3.5 px-6 font-bold">Destination Address</th>
                    <th className="py-3.5 px-6 font-bold">Status</th>
                    <th className="py-3.5 px-6 font-bold">Tx Hash / Explorer</th>
                    <th className="py-3.5 px-6 font-bold">Date</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-[#261D22] text-gray-300">
                  {withdrawals.map((w) => (
                    <tr key={w.id} className="hover:bg-[#1E1717] transition-colors">
                      <td className="py-4 px-6 font-bold text-white">
                        {w.method?.symbol || 'Crypto'}
                      </td>
                      <td className="py-4 px-6 font-mono">
                        <span className="text-[#FACC15] font-black">{w.coins_amount.toLocaleString()}</span>
                        <span className="text-gray-500 text-[11px] ml-1.5">(${w.usd_amount.toFixed(2)})</span>
                      </td>
                      <td className="py-4 px-6 font-mono text-[11px] text-gray-400">
                        {w.wallet_address.slice(0, 8)}...{w.wallet_address.slice(-6)}
                      </td>
                      <td className="py-4 px-6">
                        {w.status === 'pending' && (
                          <span className="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-300 border border-amber-500/20">
                            Pending Review
                          </span>
                        )}
                        {w.status === 'approved' && (
                          <span className="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            Paid & Sent
                          </span>
                        )}
                        {w.status === 'rejected' && (
                          <span
                            title={w.rejection_reason || 'Rejected & coins refunded'}
                            className="px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-500/10 text-red-400 border border-red-500/20 cursor-help"
                          >
                            Rejected (Refunded)
                          </span>
                        )}
                      </td>
                      <td className="py-4 px-6 font-mono text-[11px]">
                        {w.tx_hash ? (
                          <a
                            href={`https://blockchair.com/search?q=${w.tx_hash}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-[#FF5A36] hover:underline flex items-center space-x-1"
                          >
                            <span>{w.tx_hash.slice(0, 10)}...</span>
                            <ExternalLink className="w-3 h-3" />
                          </a>
                        ) : (
                          <span className="text-gray-600">—</span>
                        )}
                      </td>
                      <td className="py-4 px-6 text-gray-500 text-[11px] font-mono">
                        {new Date(w.created_at).toLocaleDateString()}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="p-10 text-center space-y-2">
              <Clock className="w-8 h-8 text-gray-600 mx-auto" />
              <h4 className="text-sm font-bold text-white">No Withdrawal History</h4>
              <p className="text-xs text-gray-500">
                You haven&apos;t requested any payouts yet. Choose a crypto method above to cash out!
              </p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
