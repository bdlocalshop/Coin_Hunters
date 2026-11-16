'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { useAuth } from '@/context/AuthContext';
import { api } from '@/lib/api';
import { EarningsTickerItem } from '@/types/api';
import {
  Menu,
  Coins,
  Clock,
  LogOut,
  ChevronDown,
  Gift,
  Wallet,
} from 'lucide-react';

interface HeaderProps {
  onToggleMobileMenu: () => void;
}

const DEFAULT_TICKER_ITEMS: EarningsTickerItem[] = [
  { id: '1', username: 'krypton_99', activity: 'Raid: Shadow Legends', coins: 14500, time_ago: '1m ago' },
  { id: '2', username: 'sarah_m', activity: 'BitLabs Survey', coins: 850, time_ago: '2m ago' },
  { id: '3', username: 'shadow_gamer', activity: 'Offerwall.gg Mobile App', coins: 3200, time_ago: '3m ago' },
  { id: '4', username: 'btc_hunter', activity: 'CPX Research Survey', coins: 1200, time_ago: '4m ago' },
  { id: '5', username: 'vortex_pro', activity: 'Lootably Game Offer', coins: 8400, time_ago: '5m ago' },
  { id: '6', username: 'elena_q', activity: 'Offerwall.com Task', coins: 2100, time_ago: '7m ago' },
];

export const Header: React.FC<HeaderProps> = ({ onToggleMobileMenu }) => {
  const { user, isAuthenticated, openAuthModal, logout } = useAuth();
  const [tickerItems, setTickerItems] = useState<EarningsTickerItem[]>(DEFAULT_TICKER_ITEMS);
  const [isUserMenuOpen, setIsUserMenuOpen] = useState(false);

  useEffect(() => {
    let mounted = true;
    api.public
      .stats()
      .then((stats) => {
        if (mounted && stats.ticker_feed && stats.ticker_feed.length > 0) {
          setTickerItems(stats.ticker_feed);
        }
      })
      .catch(() => {
        // use fallback ticker
      });

    return () => {
      mounted = false;
    };
  }, []);

  return (
    <header className="h-16 bg-[#140F0F] border-b border-[#261D22] sticky top-0 z-40 flex items-center justify-between px-4 sm:px-6">
      {/* Left: Mobile hamburger */}
      <div className="flex items-center space-x-3 lg:hidden">
        <button
          onClick={onToggleMobileMenu}
          className="p-2 rounded-xl text-gray-400 hover:text-white hover:bg-[#1F1818] transition-colors"
          aria-label="Open navigation menu"
        >
          <Menu className="w-5 h-5" />
        </button>
        <Link href="/" className="font-extrabold text-sm text-white tracking-wide">
          COIN <span className="text-[#FF5A36]">HUNTERS</span>
        </Link>
      </div>

      {/* Center: Live Horizontally Scrolling Recent Earnings Ticker */}
      <div className="hidden md:flex flex-1 max-w-2xl lg:max-w-3xl mx-4 overflow-hidden relative select-none">
        {/* Subtle Fade Masks on sides */}
        <div className="absolute left-0 top-0 bottom-0 w-8 bg-gradient-to-r from-[#140F0F] to-transparent z-10 pointer-events-none" />
        <div className="absolute right-0 top-0 bottom-0 w-8 bg-gradient-to-l from-[#140F0F] to-transparent z-10 pointer-events-none" />

        <div className="animate-ticker flex items-center space-x-4 py-1">
          {/* Double items to enable continuous infinite seamless loop */}
          {[...tickerItems, ...tickerItems].map((item, idx) => (
            <div
              key={`${item.id}-${idx}`}
              className="flex items-center space-x-2 px-3 py-1 bg-[#1A1414] border border-[#261D22] rounded-full text-xs shrink-0 hover:border-[#382A30] transition-colors"
            >
              <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shrink-0" />
              <span className="font-semibold text-gray-300">{item.username}</span>
              <span className="text-gray-500 text-[11px] truncate max-w-[130px]">{item.activity}</span>
              <span className="text-[#FACC15] font-bold">
                +{item.coins.toLocaleString()}
              </span>
              <span className="text-[10px] text-gray-500 font-mono">
                (${ (item.coins / 1000).toFixed(2) })
              </span>
            </div>
          ))}
        </div>
      </div>

      {/* Right: Auth Controls & Balances */}
      <div className="flex items-center space-x-3">
        {isAuthenticated && user ? (
          <div className="flex items-center space-x-3">
            {/* Pending Coins Badge (if any) */}
            {user.pending_coins > 0 && (
              <div
                title="Coins held under security verification"
                className="hidden xl:flex items-center space-x-1.5 px-2.5 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs font-medium"
              >
                <Clock className="w-3.5 h-3.5 text-amber-400" />
                <span>Pending:</span>
                <span className="font-bold">{user.pending_coins.toLocaleString()}</span>
              </div>
            )}

            {/* Live Available Balance Badge */}
            <Link
              href="/shop"
              className="flex items-center space-x-2 px-3.5 py-1.5 rounded-xl bg-[#1A1414] border border-[#382A30] hover:border-[#FACC15]/40 transition-colors group shadow-inner"
            >
              <div className="w-5 h-5 rounded-full bg-gradient-to-tr from-[#FACC15] to-[#EAB308] flex items-center justify-center text-[#120E0E] shadow-sm">
                <Coins className="w-3 h-3 text-[#120E0E] stroke-[2.5]" />
              </div>
              <div className="flex items-baseline space-x-1.5">
                <span className="font-extrabold text-sm text-white group-hover:text-[#FACC15] transition-colors">
                  {user.available_coins.toLocaleString()}
                </span>
                <span className="text-[11px] text-gray-400 font-mono">
                  ${(user.available_coins / 1000).toFixed(2)}
                </span>
              </div>
            </Link>

            {/* User Dropdown */}
            <div className="relative">
              <button
                onClick={() => setIsUserMenuOpen(!isUserMenuOpen)}
                className="flex items-center space-x-2 p-1.5 rounded-xl bg-[#1A1414] border border-[#261D22] hover:border-[#382A30] transition-colors"
              >
                <div className="w-7 h-7 rounded-lg bg-gradient-to-br from-[#FF5A36] to-[#F97316] text-white flex items-center justify-center font-bold text-xs uppercase">
                  {user.username.slice(0, 2)}
                </div>
                <ChevronDown className="w-3.5 h-3.5 text-gray-400" />
              </button>

              {/* Dropdown Menu */}
              {isUserMenuOpen && (
                <>
                  <div
                    className="fixed inset-0 z-30"
                    onClick={() => setIsUserMenuOpen(false)}
                  />
                  <div className="absolute right-0 mt-2 w-56 bg-[#181313] border border-[#261D22] rounded-2xl shadow-2xl py-2 z-40 text-xs animate-in fade-in duration-150">
                    <div className="px-4 py-2 border-b border-[#261D22]">
                      <div className="font-bold text-white text-sm truncate">{user.username}</div>
                      <div className="text-gray-500 text-[11px] truncate">{user.email}</div>
                    </div>

                    <div className="py-1">
                      <Link
                        href="/my-offers"
                        onClick={() => setIsUserMenuOpen(false)}
                        className="flex items-center space-x-2.5 px-4 py-2 text-gray-300 hover:text-white hover:bg-[#211919] transition-colors"
                      >
                        <Clock className="w-4 h-4 text-gray-400" />
                        <span>My Completed Offers</span>
                      </Link>
                      <Link
                        href="/referrals"
                        onClick={() => setIsUserMenuOpen(false)}
                        className="flex items-center space-x-2.5 px-4 py-2 text-gray-300 hover:text-white hover:bg-[#211919] transition-colors"
                      >
                        <Gift className="w-4 h-4 text-[#FACC15]" />
                        <span>Referral Program (+500)</span>
                      </Link>
                      <Link
                        href="/shop"
                        onClick={() => setIsUserMenuOpen(false)}
                        className="flex items-center space-x-2.5 px-4 py-2 text-gray-300 hover:text-white hover:bg-[#211919] transition-colors"
                      >
                        <Wallet className="w-4 h-4 text-emerald-400" />
                        <span>Cashout Crypto</span>
                      </Link>
                    </div>

                    <div className="pt-1 border-t border-[#261D22]">
                      <button
                        onClick={() => {
                          setIsUserMenuOpen(false);
                          logout();
                        }}
                        className="w-full flex items-center space-x-2.5 px-4 py-2 text-red-400 hover:bg-red-950/30 transition-colors text-left"
                      >
                        <LogOut className="w-4 h-4" />
                        <span>Sign Out</span>
                      </button>
                    </div>
                  </div>
                </>
              )}
            </div>
          </div>
        ) : (
          <div className="flex items-center space-x-2 sm:space-x-3">
            <button
              onClick={() => openAuthModal('login')}
              className="px-3.5 py-1.5 rounded-xl text-xs font-semibold text-gray-300 hover:text-white hover:bg-[#1A1414] border border-transparent hover:border-[#261D22] transition-all"
            >
              Sign In
            </button>
            <button
              onClick={() => openAuthModal('register')}
              className="relative px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-xl bg-gradient-to-r from-[#FF5A36] to-[#F97316] hover:from-[#ea4823] hover:to-[#e0650f] text-white text-xs font-bold shadow-md shadow-[#FF5A36]/25 transition-all transform active:scale-[0.98] flex items-center space-x-1.5"
            >
              <span>Sign Up</span>
              <span className="hidden sm:inline-block px-1.5 py-0.2 text-[9px] bg-black/30 rounded-md font-mono text-[#FACC15]">
                +250
              </span>
            </button>
          </div>
        )}
      </div>
    </header>
  );
};
