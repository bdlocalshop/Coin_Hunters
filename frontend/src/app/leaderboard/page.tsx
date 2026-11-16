'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { api } from '@/lib/api';
import { LeaderboardItem } from '@/types/api';
import {
  Trophy,
  Crown,
} from 'lucide-react';

const FALLBACK_LEADERBOARD: LeaderboardItem[] = [
  { rank: 1, username: 'crypto_valkyrie', avatar_url: '', coins_earned: 894500, usd_earned: 894.5 },
  { rank: 2, username: 'apex_hunter_99', avatar_url: '', coins_earned: 742100, usd_earned: 742.1 },
  { rank: 3, username: 'shadow_strike', avatar_url: '', coins_earned: 618400, usd_earned: 618.4 },
  { rank: 4, username: 'cyber_phoenix', avatar_url: '', coins_earned: 512000, usd_earned: 512.0 },
  { rank: 5, username: 'night_owl_88', avatar_url: '', coins_earned: 435200, usd_earned: 435.2 },
  { rank: 6, username: 'solana_surfer', avatar_url: '', coins_earned: 389000, usd_earned: 389.0 },
  { rank: 7, username: 'pixel_monk', avatar_url: '', coins_earned: 341500, usd_earned: 341.5 },
  { rank: 8, username: 'iron_clag', avatar_url: '', coins_earned: 298000, usd_earned: 298.0 },
  { rank: 9, username: 'luna_quest', avatar_url: '', coins_earned: 275000, usd_earned: 275.0 },
  { rank: 10, username: 'storm_rider', avatar_url: '', coins_earned: 242000, usd_earned: 242.0 },
];

export default function LeaderboardPage() {
  const [leaderboard, setLeaderboard] = useState<LeaderboardItem[]>(FALLBACK_LEADERBOARD);
  const [timeframe, setTimeframe] = useState<'24h' | '7d' | '30d'>('7d');

  useEffect(() => {
    let mounted = true;
    api.public
      .leaderboard()
      .then((res) => {
        if (mounted && res.leaderboard && res.leaderboard.length > 0) {
          setLeaderboard(res.leaderboard);
        }
      })
      .catch(() => {});

    return () => {
      mounted = false;
    };
  }, [timeframe]);

  const top3 = leaderboard.slice(0, 3);
  const rest = leaderboard.slice(3);

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">
      {/* Page Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-[#261D22]">
        <div>
          <div className="flex items-center space-x-2 text-xs font-bold text-[#FF5A36] uppercase tracking-wider mb-1">
            <Trophy className="w-4 h-4 text-[#FACC15]" />
            <span>Top Coin Hunters</span>
          </div>
          <h1 className="text-2xl sm:text-3xl font-black text-white">
            Earnings Leaderboard
          </h1>
          <p className="text-xs sm:text-sm text-gray-400 mt-1">
            Celebrating the top-earning hunters converting offers and surveys into crypto rewards.
          </p>
        </div>

        {/* Timeframe Filter Buttons */}
        <div className="flex items-center space-x-1.5 bg-[#181313] p-1.5 rounded-2xl border border-[#261D22]">
          {(['24h', '7d', '30d'] as const).map((tf) => (
            <button
              key={tf}
              onClick={() => setTimeframe(tf)}
              className={`px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all uppercase ${
                timeframe === tf
                  ? 'bg-gradient-to-r from-[#FF5A36] to-[#F97316] text-white shadow-md shadow-[#FF5A36]/20'
                  : 'text-gray-400 hover:text-white'
              }`}
            >
              {tf === '24h' ? '24 Hours' : tf === '7d' ? '7 Days' : '30 Days'}
            </button>
          ))}
        </div>
      </div>

      {/* Top 3 Podium */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
        {/* Rank 2 (Silver) */}
        {top3[1] && (
          <div className="p-6 rounded-3xl bg-[#181313] border border-[#261D22] flex flex-col items-center text-center space-y-3 relative shadow-xl order-2 md:order-1">
            <div className="w-16 h-16 rounded-2xl bg-gradient-to-br from-slate-400 to-slate-600 p-0.5 text-white flex items-center justify-center font-bold text-xl shadow-lg shadow-slate-500/20">
              <span className="w-full h-full rounded-2xl bg-[#1A1414] flex items-center justify-center">
                🥈
              </span>
            </div>
            <div className="space-y-1">
              <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-500/15 text-slate-300 border border-slate-500/30">
                Rank #2
              </span>
              <h3 className="font-extrabold text-white text-base truncate max-w-[200px]">
                {top3[1].username}
              </h3>
            </div>
            <div className="pt-2 border-t border-[#261D22] w-full">
              <div className="text-xl font-black text-[#FACC15] font-mono">
                {top3[1].coins_earned.toLocaleString()}
              </div>
              <span className="text-xs text-gray-500 font-mono">
                ${(top3[1].coins_earned / 1000).toFixed(2)} USD
              </span>
            </div>
          </div>
        )}

        {/* Rank 1 (Gold / Champion) */}
        {top3[0] && (
          <div className="p-8 rounded-3xl bg-gradient-to-b from-[#211919] via-[#1A1414] to-[#181313] border-2 border-[#FACC15]/40 flex flex-col items-center text-center space-y-4 relative shadow-2xl order-1 md:order-2 md:-translate-y-4">
            <div className="absolute -top-4 w-8 h-8 rounded-full bg-[#FACC15] text-[#120E0E] flex items-center justify-center shadow-lg">
              <Crown className="w-5 h-5 fill-current" />
            </div>

            <div className="w-20 h-20 rounded-3xl bg-gradient-to-br from-[#FACC15] to-[#EAB308] p-0.5 text-white flex items-center justify-center font-bold text-2xl shadow-xl shadow-[#FACC15]/30">
              <span className="w-full h-full rounded-3xl bg-[#140F0F] flex items-center justify-center">
                👑
              </span>
            </div>

            <div className="space-y-1">
              <span className="px-3 py-0.5 rounded-full text-xs font-black bg-[#FACC15]/20 text-[#FACC15] border border-[#FACC15]/40 uppercase tracking-wider">
                Rank #1 Champion
              </span>
              <h3 className="font-black text-white text-lg truncate max-w-[240px]">
                {top3[0].username}
              </h3>
            </div>

            <div className="pt-3 border-t border-[#382A30] w-full">
              <div className="text-2xl font-black text-[#FACC15] font-mono">
                {top3[0].coins_earned.toLocaleString()} Coins
              </div>
              <span className="text-sm text-gray-300 font-mono">
                ${(top3[0].coins_earned / 1000).toFixed(2)} USD Earned
              </span>
            </div>
          </div>
        )}

        {/* Rank 3 (Bronze) */}
        {top3[2] && (
          <div className="p-6 rounded-3xl bg-[#181313] border border-[#261D22] flex flex-col items-center text-center space-y-3 relative shadow-xl order-3">
            <div className="w-16 h-16 rounded-2xl bg-gradient-to-br from-amber-700 to-amber-900 p-0.5 text-white flex items-center justify-center font-bold text-xl shadow-lg shadow-amber-800/20">
              <span className="w-full h-full rounded-2xl bg-[#1A1414] flex items-center justify-center">
                🥉
              </span>
            </div>
            <div className="space-y-1">
              <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-700/15 text-amber-300 border border-amber-700/30">
                Rank #3
              </span>
              <h3 className="font-extrabold text-white text-base truncate max-w-[200px]">
                {top3[2].username}
              </h3>
            </div>
            <div className="pt-2 border-t border-[#261D22] w-full">
              <div className="text-xl font-black text-[#FACC15] font-mono">
                {top3[2].coins_earned.toLocaleString()}
              </div>
              <span className="text-xs text-gray-500 font-mono">
                ${(top3[2].coins_earned / 1000).toFixed(2)} USD
              </span>
            </div>
          </div>
        )}
      </div>

      {/* Ranks 4-50 Table */}
      <div className="bg-[#181313] rounded-3xl border border-[#261D22] overflow-hidden shadow-xl">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-[#140F0F] text-gray-500 uppercase tracking-wider text-[10px] border-b border-[#261D22]">
              <tr>
                <th className="py-3.5 px-6 font-bold w-16">Rank</th>
                <th className="py-3.5 px-6 font-bold">Player</th>
                <th className="py-3.5 px-6 font-bold">Coins Earned</th>
                <th className="py-3.5 px-6 font-bold">USD Value</th>
                <th className="py-3.5 px-6 font-bold text-right">Action</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[#261D22] text-gray-300">
              {rest.map((item) => (
                <tr key={item.rank} className="hover:bg-[#1E1717] transition-colors">
                  <td className="py-4 px-6 font-black font-mono text-gray-400">
                    #{item.rank}
                  </td>
                  <td className="py-4 px-6 font-bold text-white flex items-center space-x-3">
                    <div className="w-7 h-7 rounded-lg bg-[#211919] border border-[#261D22] flex items-center justify-center text-[11px] uppercase font-bold text-[#FF5A36]">
                      {item.username.slice(0, 2)}
                    </div>
                    <span>{item.username}</span>
                  </td>
                  <td className="py-4 px-6 font-mono font-black text-[#FACC15]">
                    +{item.coins_earned.toLocaleString()}
                  </td>
                  <td className="py-4 px-6 font-mono text-gray-400">
                    ${(item.coins_earned / 1000).toFixed(2)}
                  </td>
                  <td className="py-4 px-6 text-right">
                    <Link
                      href="/offers"
                      className="px-3 py-1 rounded-lg bg-[#211919] hover:bg-[#FF5A36] text-white text-[11px] font-bold transition-colors"
                    >
                      Compete
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
