'use client';

import React, { useState, useEffect } from 'react';
import { useAuth } from '@/context/AuthContext';
import { api } from '@/lib/api';
import { ReferralsResponse } from '@/types/api';
import {
  Users,
  Copy,
  Check,
  Gift,
  ShieldCheck,
} from 'lucide-react';

export default function ReferralsPage() {
  const { user, isAuthenticated, openAuthModal } = useAuth();
  const [referralData, setReferralData] = useState<ReferralsResponse | null>(null);
  const [copied, setCopied] = useState(false);

  useEffect(() => {
    let mounted = true;

    if (isAuthenticated) {
      api.auth
        .referrals()
        .then((res) => {
          if (mounted) {
            setReferralData(res);
          }
        })
        .catch(() => {});
    }

    return () => {
      mounted = false;
    };
  }, [isAuthenticated]);

  const referralCode = user?.referral_code || referralData?.referral_code || 'CH2026';
  const referralUrl = typeof window !== 'undefined'
    ? `${window.location.origin}/?ref=${referralCode}`
    : `https://coinhunters.com/?ref=${referralCode}`;

  const copyToClipboard = () => {
    if (typeof navigator !== 'undefined') {
      navigator.clipboard.writeText(referralUrl);
      setCopied(true);
      setTimeout(() => setCopied(false), 2500);
    }
  };

  if (!isAuthenticated) {
    return (
      <div className="max-w-4xl mx-auto px-4 py-20 text-center space-y-6">
        <div className="w-16 h-16 rounded-3xl bg-[#181313] border border-[#261D22] flex items-center justify-center mx-auto text-[#FF5A36] shadow-xl">
          <Gift className="w-8 h-8 text-[#FACC15]" />
        </div>
        <div className="space-y-2">
          <h1 className="text-2xl font-black text-white">Join the Coin Hunters Affiliate Program</h1>
          <p className="text-xs sm:text-sm text-gray-400 max-w-md mx-auto">
            Log in or create your free account to grab your custom referral link and earn 500 Coins for every qualified player you bring.
          </p>
        </div>
        <button
          onClick={() => openAuthModal('register')}
          className="px-6 py-3 rounded-xl bg-gradient-to-r from-[#FF5A36] to-[#F97316] text-white text-xs font-bold shadow-lg shadow-[#FF5A36]/25 transition-all"
        >
          Create Account & Get Referral Link
        </button>
      </div>
    );
  }

  const stats = referralData?.stats || {
    total_referrals: 0,
    qualified_referrals: 0,
    pending_referrals: 0,
    lifetime_referral_coins: 0,
    qualification_coins: 1000,
    bonus_per_qualified: 500,
  };

  const referredUsers = referralData?.referred_users || [];

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">
      {/* Page Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-[#261D22]">
        <div>
          <div className="flex items-center space-x-2 text-xs font-bold text-[#FF5A36] uppercase tracking-wider mb-1">
            <Users className="w-4 h-4" />
            <span>Flat One-Time Referral Rewards</span>
          </div>
          <h1 className="text-2xl sm:text-3xl font-black text-white">
            Refer Friends & Earn 500 Coins
          </h1>
          <p className="text-xs sm:text-sm text-gray-400 mt-1">
            Flat, transparent rewards with zero tier pyramid gimmicks. Earn a one-time 500 Coins bonus when your friend qualifies.
          </p>
        </div>

        <div className="flex items-center space-x-2 px-3.5 py-1.5 rounded-xl bg-[#181313] border border-[#261D22] text-xs">
          <ShieldCheck className="w-4 h-4 text-emerald-400" />
          <span className="text-gray-300">Instant Double-Entry Ledger Crediting</span>
        </div>
      </div>

      {/* Referral Link Card */}
      <div className="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-[#181313] via-[#211919] to-[#181313] border border-[#382A30] shadow-xl space-y-4">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <div>
            <h3 className="font-extrabold text-white text-base">Your Personal Invite Link</h3>
            <p className="text-xs text-gray-400 mt-0.5">
              Share this link anywhere. New signups through your link receive an instant 250 Coins welcome gift!
            </p>
          </div>
          <div className="flex items-center space-x-2">
            <span className="text-xs text-gray-400">Referral Code:</span>
            <span className="font-mono font-bold text-[#FACC15] bg-[#120E0E] px-2.5 py-1 rounded-lg border border-[#261D22] text-xs uppercase tracking-wider">
              {referralCode}
            </span>
          </div>
        </div>

        <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-2">
          <div className="flex-1 bg-[#120E0E] border border-[#261D22] rounded-xl px-4 py-3 text-xs font-mono text-gray-300 truncate select-all">
            {referralUrl}
          </div>
          <button
            onClick={copyToClipboard}
            className="px-6 py-3 rounded-xl bg-gradient-to-r from-[#FF5A36] to-[#F97316] hover:from-[#ea4823] hover:to-[#e0650f] text-white text-xs font-extrabold shadow-md shadow-[#FF5A36]/20 transition-all flex items-center justify-center space-x-2 shrink-0"
          >
            {copied ? (
              <>
                <Check className="w-4 h-4 text-emerald-300" />
                <span>Copied to Clipboard!</span>
              </>
            ) : (
              <>
                <Copy className="w-4 h-4" />
                <span>Copy Invite Link</span>
              </>
            )}
          </button>
        </div>
      </div>

      {/* 3 Metric Stat Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div className="p-5 rounded-2xl bg-[#181313] border border-[#261D22] shadow-lg">
          <div className="text-xs font-semibold text-gray-400 uppercase tracking-wider">
            Total Friends Joined
          </div>
          <div className="text-3xl font-black text-white mt-1">
            {stats.total_referrals}
          </div>
          <div className="text-xs text-gray-500 mt-1">
            Users registered with your code
          </div>
        </div>

        <div className="p-5 rounded-2xl bg-[#181313] border border-[#261D22] shadow-lg">
          <div className="text-xs font-semibold text-gray-400 uppercase tracking-wider">
            Qualified Referrals
          </div>
          <div className="text-3xl font-black text-emerald-400 mt-1">
            {stats.qualified_referrals}
          </div>
          <div className="text-xs text-gray-500 mt-1">
            Reached 1,000 Coins threshold
          </div>
        </div>

        <div className="p-5 rounded-2xl bg-[#181313] border border-[#261D22] shadow-lg">
          <div className="text-xs font-semibold text-gray-400 uppercase tracking-wider">
            Lifetime Referral Earnings
          </div>
          <div className="text-3xl font-black text-[#FACC15] mt-1 font-mono">
            +{stats.lifetime_referral_coins.toLocaleString()} <span className="text-xs font-normal">Coins</span>
          </div>
          <div className="text-xs text-gray-500 mt-1">
            ≈ ${(stats.lifetime_referral_coins / 1000).toFixed(2)} USD paid out
          </div>
        </div>
      </div>

      {/* How the Flat One-Time Program Works */}
      <div className="p-6 rounded-3xl bg-[#140F0F] border border-[#261D22] space-y-6">
        <div className="text-center max-w-xl mx-auto space-y-1">
          <h3 className="text-base font-extrabold text-white">
            How The Flat Referral Bonus Works
          </h3>
          <p className="text-xs text-gray-400">
            Transparent, zero-manipulation rewards. No multi-level pyramid structures.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="p-4 rounded-2xl bg-[#181313] border border-[#261D22] space-y-2">
            <div className="w-8 h-8 rounded-lg bg-[#FF5A36]/15 text-[#FF5A36] flex items-center justify-center font-bold text-xs">
              01
            </div>
            <h4 className="font-bold text-white text-xs">Share Your Link</h4>
            <p className="text-[11px] text-gray-400 leading-relaxed">
              Send your personal link to gamers, friends, or followers looking to earn crypto.
            </p>
          </div>

          <div className="p-4 rounded-2xl bg-[#181313] border border-[#261D22] space-y-2">
            <div className="w-8 h-8 rounded-lg bg-[#FACC15]/15 text-[#FACC15] flex items-center justify-center font-bold text-xs">
              02
            </div>
            <h4 className="font-bold text-white text-xs">Friend Earns 1,000 Coins</h4>
            <p className="text-[11px] text-gray-400 leading-relaxed">
              Your friend starts testing games and completing surveys. When their lifetime earnings hit 1,000 Coins ($1.00), they qualify.
            </p>
          </div>

          <div className="p-4 rounded-2xl bg-[#181313] border border-[#261D22] space-y-2">
            <div className="w-8 h-8 rounded-lg bg-emerald-500/15 text-emerald-400 flex items-center justify-center font-bold text-xs">
              03
            </div>
            <h4 className="font-bold text-white text-xs">Receive 500 Coins Instantly</h4>
            <p className="text-[11px] text-gray-400 leading-relaxed">
              The ledger atomically credits 500 Coins ($0.50 USD) directly to your available balance from marketing reserves.
            </p>
          </div>
        </div>
      </div>

      {/* Referred Users Status Table */}
      <div className="space-y-4">
        <h2 className="text-base font-extrabold text-white uppercase tracking-wider">
          Referred Users Activity
        </h2>

        <div className="bg-[#181313] rounded-3xl border border-[#261D22] overflow-hidden shadow-xl">
          {referredUsers.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-[#140F0F] text-gray-500 uppercase tracking-wider text-[10px] border-b border-[#261D22]">
                  <tr>
                    <th className="py-3.5 px-6 font-bold">User</th>
                    <th className="py-3.5 px-6 font-bold">Joined Date</th>
                    <th className="py-3.5 px-6 font-bold">Progress (Goal: 1,000 Coins)</th>
                    <th className="py-3.5 px-6 font-bold">Status</th>
                    <th className="py-3.5 px-6 font-bold">Bonus Paid</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-[#261D22] text-gray-300">
                  {referredUsers.map((ref) => {
                    const pct = Math.min(100, Math.floor((ref.lifetime_earned_coins / 1000) * 100));
                    return (
                      <tr key={ref.id} className="hover:bg-[#1E1717] transition-colors">
                        <td className="py-4 px-6 font-bold text-white font-mono">
                          {ref.username}
                        </td>
                        <td className="py-4 px-6 text-gray-500 text-[11px] font-mono">
                          {new Date(ref.joined_at).toLocaleDateString()}
                        </td>
                        <td className="py-4 px-6 max-w-xs">
                          <div className="space-y-1">
                            <div className="flex items-center justify-between text-[10px] text-gray-400 font-mono">
                              <span>{ref.lifetime_earned_coins.toLocaleString()} / 1,000 Coins</span>
                              <span>{pct}%</span>
                            </div>
                            <div className="h-1.5 w-full bg-[#120E0E] rounded-full overflow-hidden">
                              <div
                                className={`h-full rounded-full transition-all ${
                                  pct >= 100 ? 'bg-emerald-500' : 'bg-[#FF5A36]'
                                }`}
                                style={{ width: `${pct}%` }}
                              />
                            </div>
                          </div>
                        </td>
                        <td className="py-4 px-6">
                          {ref.is_qualified ? (
                            <span className="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                              Qualified
                            </span>
                          ) : (
                            <span className="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-300 border border-amber-500/20">
                              In Progress
                            </span>
                          )}
                        </td>
                        <td className="py-4 px-6 font-bold font-mono">
                          {ref.bonus_paid ? (
                            <span className="text-[#FACC15]">+500 Coins</span>
                          ) : (
                            <span className="text-gray-600">—</span>
                          )}
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="p-10 text-center space-y-2">
              <Users className="w-8 h-8 text-gray-600 mx-auto" />
              <h4 className="text-sm font-bold text-white">No Referrals Yet</h4>
              <p className="text-xs text-gray-500 max-w-sm mx-auto">
                Share your invite link above with fellow gamers and community members to start earning flat 500 Coin qualification rewards.
              </p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
