'use client';

import React from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';
import {
  Home,
  Flame,
  Layers,
  Gamepad2,
  ClipboardList,
  Trophy,
  Wallet,
  Clock,
  Users,
  Gift,
  Coins,
} from 'lucide-react';

interface SidebarProps {
  onClose?: () => void;
}

export const Sidebar: React.FC<SidebarProps> = ({ onClose }) => {
  const pathname = usePathname();
  const { user, isAuthenticated, openAuthModal } = useAuth();

  const primaryNavItems = [
    { label: 'Home', href: '/', icon: Home },
    { label: 'Earn', href: '/earn', icon: Flame },
    { label: 'Offer Walls', href: '/offer-walls', icon: Layers },
    { label: 'Offers', href: '/offers', icon: Gamepad2 },
    { label: 'Surveys', href: '/surveys', icon: ClipboardList, badge: 'Live' },
    { label: 'Leaderboard', href: '/leaderboard', icon: Trophy },
    { label: 'Shop', href: '/shop', icon: Wallet },
  ];

  const accountNavItems = [
    { label: 'My Offers', href: '/my-offers', icon: Clock },
    { label: 'Referrals', href: '/referrals', icon: Users, badge: '+500' },
  ];

  const handleLinkClick = () => {
    if (onClose) onClose();
  };

  return (
    <aside className="w-64 flex-shrink-0 bg-[#140F0F] border-r border-[#261D22] flex flex-col h-full min-h-screen text-gray-300">
      {/* Brand Header */}
      <div className="h-16 flex items-center px-6 border-b border-[#261D22]">
        <Link href="/" onClick={handleLinkClick} className="flex items-center space-x-2.5 group">
          <div className="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#FF5A36] to-[#F97316] flex items-center justify-center text-white shadow-lg shadow-[#FF5A36]/25 group-hover:scale-105 transition-transform">
            <Coins className="w-5 h-5 text-[#FACC15]" />
          </div>
          <div>
            <div className="font-extrabold text-base tracking-wide text-white leading-none">
              COIN <span className="text-[#FF5A36]">HUNTERS</span>
            </div>
            <span className="text-[10px] text-gray-500 font-medium tracking-widest uppercase">
              EARN & CASHOUT
            </span>
          </div>
        </Link>
      </div>

      {/* Navigation Scroll Area */}
      <div className="flex-1 overflow-y-auto px-4 py-5 space-y-6">
        {/* Main Section */}
        <div>
          <div className="text-[11px] font-bold uppercase tracking-wider text-gray-500 px-3 mb-2">
            Explore
          </div>
          <nav className="space-y-1">
            {primaryNavItems.map((item) => {
              const Icon = item.icon;
              const isActive = pathname === item.href;
              return (
                <Link
                  key={item.href}
                  href={item.href}
                  onClick={handleLinkClick}
                  className={`flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all ${
                    isActive
                      ? 'bg-[#211919] text-[#FF5A36] border border-[#382A30] shadow-sm shadow-[#FF5A36]/10'
                      : 'text-gray-400 hover:text-white hover:bg-[#1A1414]'
                  }`}
                >
                  <div className="flex items-center space-x-3">
                    <Icon className={`w-4 h-4 ${isActive ? 'text-[#FF5A36]' : 'text-gray-400'}`} />
                    <span>{item.label}</span>
                  </div>
                  {item.badge && (
                    <span className="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                      {item.badge}
                    </span>
                  )}
                </Link>
              );
            })}
          </nav>
        </div>

        {/* Authenticated Account Section */}
        <div>
          <div className="text-[11px] font-bold uppercase tracking-wider text-gray-500 px-3 mb-2">
            Account
          </div>
          <nav className="space-y-1">
            {accountNavItems.map((item) => {
              const Icon = item.icon;
              const isActive = pathname === item.href;
              return (
                <Link
                  key={item.href}
                  href={item.href}
                  onClick={handleLinkClick}
                  className={`flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all ${
                    isActive
                      ? 'bg-[#211919] text-[#FF5A36] border border-[#382A30]'
                      : 'text-gray-400 hover:text-white hover:bg-[#1A1414]'
                  }`}
                >
                  <div className="flex items-center space-x-3">
                    <Icon className={`w-4 h-4 ${isActive ? 'text-[#FF5A36]' : 'text-gray-400'}`} />
                    <span>{item.label}</span>
                  </div>
                  {item.badge && (
                    <span className="px-1.5 py-0.5 text-[10px] font-extrabold rounded-md bg-[#FACC15]/10 text-[#FACC15] border border-[#FACC15]/20">
                      {item.badge}
                    </span>
                  )}
                </Link>
              );
            })}
          </nav>
        </div>

        {/* Sidebar Promo Card: Welcome Bonus */}
        {!isAuthenticated ? (
          <div className="p-4 rounded-2xl bg-gradient-to-b from-[#211919] to-[#181313] border border-[#382A30] relative overflow-hidden shadow-lg">
            <div className="absolute top-0 right-0 transform translate-x-2 -translate-y-2 w-16 h-16 bg-[#FF5A36]/10 rounded-full blur-xl pointer-events-none" />
            <div className="flex items-center space-x-2 text-[#FACC15] mb-1.5">
              <Gift className="w-4 h-4" />
              <span className="text-xs font-bold uppercase tracking-wider">Welcome Gift</span>
            </div>
            <p className="text-xs text-gray-300 mb-3 leading-relaxed">
              Register now & receive <strong className="text-white">250 Coins ($0.25)</strong> on your first qualified activity!
            </p>
            <button
              onClick={() => {
                if (onClose) onClose();
                openAuthModal('register');
              }}
              className="w-full py-2 px-3 rounded-xl bg-gradient-to-r from-[#FF5A36] to-[#F97316] hover:from-[#ea4823] hover:to-[#e0650f] text-white text-xs font-bold shadow-md shadow-[#FF5A36]/20 transition-all text-center"
            >
              Claim 250 Coins
            </button>
          </div>
        ) : (
          <div className="p-3.5 rounded-xl bg-[#181313] border border-[#261D22] text-xs">
            <div className="text-gray-400 text-[11px] mb-1">Your Referral Code:</div>
            <div className="flex items-center justify-between font-mono font-bold text-white bg-[#120E0E] px-2.5 py-1.5 rounded-lg border border-[#261D22]">
              <span className="text-[#FACC15]">{user?.referral_code || '---'}</span>
              <span className="text-[10px] text-gray-400 font-sans font-normal">500 Bonus</span>
            </div>
          </div>
        )}
      </div>

      {/* Sidebar Footer info */}
      <div className="p-4 border-t border-[#261D22] text-[11px] text-gray-500 flex items-center justify-between">
        <span>Coin Hunters v1.0</span>
        <span className="flex items-center space-x-1 text-emerald-500 font-medium">
          <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
          <span>Live Systems</span>
        </span>
      </div>
    </aside>
  );
};
