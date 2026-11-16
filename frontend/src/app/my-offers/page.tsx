'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { useAuth } from '@/context/AuthContext';
import { api } from '@/lib/api';
import { OfferHold } from '@/types/api';
import {
  Clock,
  ChevronRight,
  Gamepad2,
  Lock,
} from 'lucide-react';

interface ClickItem {
  id: number;
  click_uuid: string;
  offer_title: string;
  provider_slug: string;
  status: 'clicked' | 'pending' | 'completed' | 'reversed';
  created_at: string;
}

export default function MyOffersPage() {
  const { user, isAuthenticated, openAuthModal } = useAuth();
  const [activeTab, setActiveTab] = useState<'all' | 'clicked' | 'pending' | 'completed'>('all');
  const [holds, setHolds] = useState<OfferHold[]>([]);
  const [clicks, setClicks] = useState<ClickItem[]>([]);
  const [totalPendingCoins, setTotalPendingCoins] = useState(0);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let mounted = true;
    if (isAuthenticated) {
      api.offers
        .myOffers()
        .then((res) => {
          if (mounted) {
            setHolds(res.holds || []);
            setClicks(res.active_clicks || []);
            setTotalPendingCoins(res.total_pending_coins || 0);
          }
        })
        .catch(() => {})
        .finally(() => {
          if (mounted) setIsLoading(false);
        });
    } else {
      setIsLoading(false);
    }

    return () => {
      mounted = false;
    };
  }, [isAuthenticated]);

  if (!isAuthenticated) {
    return (
      <div className="max-w-4xl mx-auto px-4 py-20 text-center space-y-6">
        <div className="w-16 h-16 rounded-3xl bg-[#181313] border border-[#261D22] flex items-center justify-center mx-auto text-[#FF5A36] shadow-xl">
          <Lock className="w-8 h-8" />
        </div>
        <div className="space-y-2">
          <h1 className="text-2xl font-black text-white">Sign In To View Your Offers</h1>
          <p className="text-xs sm:text-sm text-gray-400 max-w-md mx-auto">
            Log in to monitor your active campaigns, track advertiser hold release dates, and verify your earnings.
          </p>
        </div>
        <button
          onClick={() => openAuthModal('login')}
          className="px-6 py-3 rounded-xl bg-gradient-to-r from-[#FF5A36] to-[#F97316] text-white text-xs font-bold shadow-lg shadow-[#FF5A36]/25 transition-all"
        >
          Sign In to Your Account
        </button>
      </div>
    );
  }

  // Aggregate items into unified list
  const combinedItems = [
    ...holds.map((h) => ({
      id: `hold-${h.id}`,
      title: h.offer_title,
      provider: h.provider_slug,
      type: 'hold' as const,
      status: h.status === 'held' ? 'pending' : h.status,
      coins: h.coins_amount,
      date: h.created_at,
      releaseAt: h.release_at,
    })),
    ...clicks.map((c) => ({
      id: `click-${c.id}`,
      title: c.offer_title || 'Direct Campaign Offer',
      provider: c.provider_slug,
      type: 'click' as const,
      status: c.status,
      coins: 0,
      date: c.created_at,
      releaseAt: null,
    })),
  ];

  const filteredItems = combinedItems.filter((item) => {
    if (activeTab === 'all') return true;
    if (activeTab === 'clicked') return item.status === 'clicked';
    if (activeTab === 'pending') return item.status === 'pending' || item.status === 'held';
    if (activeTab === 'completed') return item.status === 'completed' || item.status === 'released';
    return true;
  });

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
      {/* Page Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-[#261D22]">
        <div>
          <div className="flex items-center space-x-2 text-xs font-bold text-[#FF5A36] uppercase tracking-wider mb-1">
            <Clock className="w-4 h-4" />
            <span>Activity & Hold Ledger</span>
          </div>
          <h1 className="text-2xl sm:text-3xl font-black text-white">
            My Offers & Conversions
          </h1>
          <p className="text-xs sm:text-sm text-gray-400 mt-1">
            Real-time status of clicks initiated, advertiser postbacks, and held pending coins.
          </p>
        </div>

        <Link
          href="/shop"
          className="px-4 py-2 rounded-xl bg-gradient-to-r from-[#FF5A36] to-[#F97316] text-white text-xs font-bold shadow-md shadow-[#FF5A36]/20 transition-all flex items-center space-x-1.5 self-start md:self-auto"
        >
          <span>Redeem Balance</span>
          <ChevronRight className="w-3.5 h-3.5" />
        </Link>
      </div>

      {/* Balances Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="p-5 rounded-2xl bg-[#181313] border border-[#261D22] shadow-lg">
          <div className="text-xs font-semibold text-gray-400 uppercase tracking-wider">
            Available Coins
          </div>
          <div className="text-2xl font-black text-white mt-1">
            {(user?.available_coins || 0).toLocaleString()} <span className="text-xs font-normal text-[#FACC15]">Coins</span>
          </div>
          <div className="text-xs text-gray-400 font-mono mt-0.5">
            ${((user?.available_coins || 0) / 1000).toFixed(2)} USD ready for cashout
          </div>
        </div>

        <div className="p-5 rounded-2xl bg-[#181313] border border-[#261D22] shadow-lg">
          <div className="text-xs font-semibold text-gray-400 uppercase tracking-wider">
            Pending Coins (Holds)
          </div>
          <div className="text-2xl font-black text-amber-400 mt-1">
            {(user?.pending_coins || totalPendingCoins).toLocaleString()} <span className="text-xs font-normal text-amber-300">Coins</span>
          </div>
          <div className="text-xs text-gray-400 font-mono mt-0.5">
            ${(((user?.pending_coins || totalPendingCoins)) / 1000).toFixed(2)} USD releasing soon
          </div>
        </div>

        <div className="p-5 rounded-2xl bg-[#181313] border border-[#261D22] shadow-lg">
          <div className="text-xs font-semibold text-gray-400 uppercase tracking-wider">
            Lifetime Earned
          </div>
          <div className="text-2xl font-black text-emerald-400 mt-1">
            {(user?.lifetime_earned_coins || 0).toLocaleString()} <span className="text-xs font-normal text-emerald-300">Coins</span>
          </div>
          <div className="text-xs text-gray-400 font-mono mt-0.5">
            ${((user?.lifetime_earned_coins || 0) / 1000).toFixed(2)} USD total converted
          </div>
        </div>
      </div>

      {/* Tabs Filter Bar */}
      <div className="flex items-center space-x-2 bg-[#181313] p-1.5 rounded-2xl border border-[#261D22] w-fit">
        <button
          onClick={() => setActiveTab('all')}
          className={`px-4 py-2 rounded-xl text-xs font-semibold transition-all ${
            activeTab === 'all'
              ? 'bg-[#211919] text-white shadow-sm border border-[#382A30]'
              : 'text-gray-400 hover:text-white'
          }`}
        >
          All Activity ({combinedItems.length})
        </button>
        <button
          onClick={() => setActiveTab('clicked')}
          className={`px-4 py-2 rounded-xl text-xs font-semibold transition-all ${
            activeTab === 'clicked'
              ? 'bg-[#211919] text-[#FF5A36] border border-[#382A30]'
              : 'text-gray-400 hover:text-white'
          }`}
        >
          Clicked ({clicks.filter((c) => c.status === 'clicked').length})
        </button>
        <button
          onClick={() => setActiveTab('pending')}
          className={`px-4 py-2 rounded-xl text-xs font-semibold transition-all ${
            activeTab === 'pending'
              ? 'bg-[#211919] text-amber-400 border border-[#382A30]'
              : 'text-gray-400 hover:text-white'
          }`}
        >
          Pending Holds ({holds.filter((h) => h.status === 'held').length})
        </button>
        <button
          onClick={() => setActiveTab('completed')}
          className={`px-4 py-2 rounded-xl text-xs font-semibold transition-all ${
            activeTab === 'completed'
              ? 'bg-[#211919] text-emerald-400 border border-[#382A30]'
              : 'text-gray-400 hover:text-white'
          }`}
        >
          Completed ({holds.filter((h) => h.status === 'released').length})
        </button>
      </div>

      {/* Conversion Table / List */}
      <div className="bg-[#181313] rounded-3xl border border-[#261D22] overflow-hidden shadow-xl">
        {isLoading ? (
          <div className="p-8 space-y-4">
            {Array.from({ length: 4 }).map((_, i) => (
              <div key={i} className="h-14 bg-[#211919] rounded-xl animate-pulse" />
            ))}
          </div>
        ) : filteredItems.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-[#140F0F] text-gray-500 uppercase tracking-wider text-[10px] border-b border-[#261D22]">
                <tr>
                  <th className="py-3.5 px-6 font-bold">Offer / Activity</th>
                  <th className="py-3.5 px-6 font-bold">Network</th>
                  <th className="py-3.5 px-6 font-bold">Coins</th>
                  <th className="py-3.5 px-6 font-bold">Status</th>
                  <th className="py-3.5 px-6 font-bold">Timestamp</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#261D22] text-gray-300">
                {filteredItems.map((item) => (
                  <tr key={item.id} className="hover:bg-[#1E1717] transition-colors">
                    <td className="py-4 px-6 font-bold text-white flex items-center space-x-3">
                      <div className="w-8 h-8 rounded-lg bg-[#211919] border border-[#382A30] flex items-center justify-center shrink-0">
                        <Gamepad2 className="w-4 h-4 text-[#FF5A36]" />
                      </div>
                      <span className="truncate max-w-xs">{item.title}</span>
                    </td>
                    <td className="py-4 px-6 uppercase text-[11px] font-semibold text-gray-400">
                      {item.provider?.replace('_', '.')}
                    </td>
                    <td className="py-4 px-6 font-black font-mono">
                      {item.coins > 0 ? (
                        <span className="text-[#FACC15]">+{item.coins.toLocaleString()}</span>
                      ) : (
                        <span className="text-gray-500">Tracking...</span>
                      )}
                    </td>
                    <td className="py-4 px-6">
                      {item.status === 'clicked' && (
                        <span className="px-2.5 py-1 rounded-full text-[10px] font-bold bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                          In Progress
                        </span>
                      )}
                      {(item.status === 'pending' || item.status === 'held') && (
                        <span className="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-300 border border-amber-500/20">
                          Hold (Releasing Soon)
                        </span>
                      )}
                      {(item.status === 'completed' || item.status === 'released') && (
                        <span className="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                          Credited
                        </span>
                      )}
                      {item.status === 'reversed' && (
                        <span className="px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-500/10 text-red-400 border border-red-500/20">
                          Reversed
                        </span>
                      )}
                    </td>
                    <td className="py-4 px-6 text-gray-500 text-[11px] font-mono">
                      {new Date(item.date).toLocaleDateString()}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="p-12 text-center space-y-3">
            <Clock className="w-10 h-10 text-gray-600 mx-auto" />
            <h3 className="text-base font-bold text-white">No Offers Found</h3>
            <p className="text-xs text-gray-400 max-w-sm mx-auto">
              You haven&apos;t started any offers under this category yet. Explore the catalog to start earning!
            </p>
            <Link
              href="/offers"
              className="inline-block mt-2 px-4 py-2 rounded-xl bg-gradient-to-r from-[#FF5A36] to-[#F97316] text-white text-xs font-bold shadow-md shadow-[#FF5A36]/20"
            >
              Browse Offers
            </Link>
          </div>
        )}
      </div>
    </div>
  );
}
