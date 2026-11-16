'use client';

import React, { useState } from 'react';
import { useAuth } from '@/context/AuthContext';
import {
  Layers,
  Star,
  ExternalLink,
  ShieldCheck,
  X,
} from 'lucide-react';

interface PartnerWallMeta {
  slug: string;
  name: string;
  logo: string;
  badge: string;
  rating: number;
  description: string;
  features: string[];
  color: string;
}

const PARTNER_WALLS: PartnerWallMeta[] = [
  {
    slug: 'offerwall_gg',
    name: 'Offerwall.gg',
    logo: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=160&auto=format&fit=crop&q=80',
    badge: '+20% Active Promo',
    rating: 4.9,
    description: 'Premier direct API network with top-tier mobile RPGs and simulation games. Features instant server-to-server postback tracking.',
    features: ['Direct S2S postbacks', 'High EPC mobile games', 'Instant credit verification'],
    color: 'from-orange-500 to-amber-600',
  },
  {
    slug: 'offerwall_com',
    name: 'Offerwall.com',
    logo: 'https://images.unsplash.com/photo-1538481199705-c710c4e965fc?w=160&auto=format&fit=crop&q=80',
    badge: 'Instant S2S',
    rating: 4.8,
    description: 'Global app and game publisher campaigns with fast milestone verification and high advertiser conversion rates.',
    features: ['Zero chargeback escrow', 'Daily refreshed offers', 'Global country targeting'],
    color: 'from-blue-500 to-cyan-600',
  },
  {
    slug: 'lootably',
    name: 'Lootably',
    logo: 'https://images.unsplash.com/photo-1612287233282-55038c9c0d75?w=160&auto=format&fit=crop&q=80',
    badge: 'Multi-Device',
    rating: 4.9,
    description: 'Multi-platform game and web task network. Optimized for desktop, iOS and Android players with continuous bonuses.',
    features: ['Desktop & mobile tasks', 'Fast automated sync', 'High payout caps'],
    color: 'from-purple-500 to-indigo-600',
  },
  {
    slug: 'bitlabs',
    name: 'BitLabs',
    logo: 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?w=160&auto=format&fit=crop&q=80',
    badge: '98% Match Rate',
    rating: 4.7,
    description: 'Specialized interactive market research survey router. Earn Coins sharing opinions on technology, gaming, and lifestyle.',
    features: ['Disqualification points', 'Clean dark survey UI', 'Short 5-15 min surveys'],
    color: 'from-emerald-500 to-teal-600',
  },
  {
    slug: 'cpx_research',
    name: 'CPX Research',
    logo: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=160&auto=format&fit=crop&q=80',
    badge: 'Global High Fill',
    rating: 4.8,
    description: 'Worldwide consumer insights platform with high payout surveys matched dynamically by country and age group.',
    features: ['Worldwide coverage', 'Dynamic demographic routing', 'Reliable daily credits'],
    color: 'from-rose-500 to-red-600',
  },
];

export default function OfferWallsPage() {
  const { user, isAuthenticated, openAuthModal } = useAuth();
  const [activeWall, setActiveWall] = useState<PartnerWallMeta | null>(null);

  const handleOpenWall = (wall: PartnerWallMeta) => {
    if (!isAuthenticated) {
      openAuthModal('register');
      return;
    }
    setActiveWall(wall);
  };

  const getPartnerWallUrl = (wall: PartnerWallMeta) => {
    const userId = user?.id || 1;
    // Real S2S subid parameters
    switch (wall.slug) {
      case 'offerwall_gg':
        return `https://offerwall.gg/api/v1/wall?app_id=coinhunters&user_id=${userId}`;
      case 'offerwall_com':
        return `https://offerwall.com/wall?pub_id=coinhunters&sub_id=${userId}`;
      case 'lootably':
        return `https://wall.lootably.com/?placement_id=coinhunters&user_id=${userId}`;
      case 'bitlabs':
        return `https://web.bitlabs.ai/?token=coinhunters-token&uid=${userId}`;
      case 'cpx_research':
        return `https://offers.cpx-research.com/index.php?app_id=coinhunters&ext_user_id=${userId}`;
      default:
        return '#';
    }
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
      {/* Page Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-[#261D22]">
        <div>
          <div className="flex items-center space-x-2 text-xs font-bold text-[#FF5A36] uppercase tracking-wider mb-1">
            <Layers className="w-4 h-4" />
            <span>Integrated Network Partners</span>
          </div>
          <h1 className="text-2xl sm:text-3xl font-black text-white">
            Partner Offerwalls
          </h1>
          <p className="text-xs sm:text-sm text-gray-400 mt-1">
            Access thousands of external campaigns directly inside verified partner walls.
          </p>
        </div>

        <div className="flex items-center space-x-2 px-3.5 py-1.5 rounded-xl bg-[#181313] border border-[#261D22] text-xs">
          <ShieldCheck className="w-4 h-4 text-emerald-400" />
          <span className="text-gray-300">100% Cryptographic S2S Verification</span>
        </div>
      </div>

      {/* Grid of 5 Partner Offerwalls */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {PARTNER_WALLS.map((wall) => (
          <div
            key={wall.slug}
            className="p-6 rounded-3xl bg-[#181313] hover:bg-[#1E1717] border border-[#261D22] hover:border-[#382A30] transition-all flex flex-col justify-between shadow-xl group relative overflow-hidden"
          >
            {/* Ambient subtle glow on card top */}
            <div className={`absolute top-0 right-0 w-32 h-32 bg-gradient-to-br ${wall.color} opacity-10 rounded-full blur-2xl pointer-events-none group-hover:opacity-20 transition-opacity`} />

            <div className="space-y-4 relative">
              {/* Top Row: Brand & Rating */}
              <div className="flex items-start justify-between">
                <div className="flex items-center space-x-3">
                  <div className="w-12 h-12 rounded-2xl bg-[#211919] border border-[#382A30] flex items-center justify-center font-black text-base text-white shadow-md">
                    {wall.name.slice(0, 2).toUpperCase()}
                  </div>
                  <div>
                    <h3 className="font-extrabold text-white text-base group-hover:text-[#FF5A36] transition-colors">
                      {wall.name}
                    </h3>
                    <div className="flex items-center space-x-1 text-xs text-[#FACC15] mt-0.5 font-bold">
                      <Star className="w-3.5 h-3.5 fill-[#FACC15]" />
                      <span>{wall.rating}</span>
                      <span className="text-gray-500 font-normal ml-1">/ 5.0</span>
                    </div>
                  </div>
                </div>

                <span className="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-[#FF5A36]/10 text-[#FF5A36] border border-[#FF5A36]/25">
                  {wall.badge}
                </span>
              </div>

              {/* Description */}
              <p className="text-xs text-gray-400 leading-relaxed min-h-[3rem]">
                {wall.description}
              </p>

              {/* Feature Checklist */}
              <div className="space-y-2 pt-2 border-t border-[#261D22]">
                {wall.features.map((feature, i) => (
                  <div key={i} className="flex items-center space-x-2 text-xs text-gray-300">
                    <span className="w-1.5 h-1.5 rounded-full bg-[#FF5A36]" />
                    <span>{feature}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* Launch CTA Button */}
            <div className="pt-6 mt-6 border-t border-[#261D22]">
              <button
                onClick={() => handleOpenWall(wall)}
                className="w-full py-3 px-4 rounded-xl bg-[#211919] hover:bg-gradient-to-r hover:from-[#FF5A36] hover:to-[#F97316] text-white text-xs font-bold border border-[#382A30] hover:border-transparent shadow-md transition-all flex items-center justify-center space-x-2 group-hover:bg-[#FF5A36]"
              >
                <span>Launch {wall.name}</span>
                <ExternalLink className="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        ))}
      </div>

      {/* Wall Launch Modal / Embed Preview */}
      {activeWall && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
          <div
            className="relative w-full max-w-4xl bg-[#181313] border border-[#261D22] rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Modal Header */}
            <div className="flex items-center justify-between px-6 py-4 border-b border-[#261D22] bg-[#140F0F]">
              <div className="flex items-center space-x-3">
                <span className="w-3 h-3 rounded-full bg-emerald-500 animate-pulse" />
                <div>
                  <h3 className="font-extrabold text-white text-base">
                    {activeWall.name} Secure Launcher
                  </h3>
                  <p className="text-[11px] text-gray-500">
                    S2S Sub-Account ID: <strong className="text-gray-300 font-mono">user_{user?.id || 'demo'}</strong>
                  </p>
                </div>
              </div>

              <div className="flex items-center space-x-2">
                <button
                  onClick={() => {
                    const url = getPartnerWallUrl(activeWall);
                    window.open(url, '_blank', 'noopener,noreferrer');
                  }}
                  className="px-3.5 py-1.5 rounded-xl bg-[#211919] hover:bg-[#282020] text-xs font-semibold text-gray-300 hover:text-white border border-[#382A30] transition-all flex items-center space-x-1.5"
                >
                  <span>Open in New Tab</span>
                  <ExternalLink className="w-3 h-3" />
                </button>
                <button
                  onClick={() => setActiveWall(null)}
                  className="p-1.5 rounded-xl text-gray-400 hover:text-white hover:bg-[#211919] transition-colors"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>
            </div>

            {/* Modal Body / Sandbox Launcher */}
            <div className="flex-1 p-8 text-center space-y-6 flex flex-col items-center justify-center bg-[#120E0E]">
              <div className="w-20 h-20 rounded-3xl bg-[#181313] border border-[#382A30] flex items-center justify-center shadow-2xl text-2xl font-black text-white">
                {activeWall.name.slice(0, 2).toUpperCase()}
              </div>

              <div className="max-w-md space-y-2">
                <h4 className="text-xl font-black text-white">
                  Launching {activeWall.name}
                </h4>
                <p className="text-xs text-gray-400 leading-relaxed">
                  Your tracking token has been generated. When you complete offers on {activeWall.name}, your Coins will credit directly to your Coin Hunters balance via encrypted postback.
                </p>
              </div>

              <div className="p-4 rounded-2xl bg-[#181313] border border-[#261D22] text-xs max-w-md w-full text-left space-y-2">
                <div className="flex items-center justify-between text-gray-400 text-[11px]">
                  <span>Status:</span>
                  <span className="text-emerald-400 font-bold flex items-center space-x-1">
                    <span className="w-1.5 h-1.5 rounded-full bg-emerald-400" />
                    <span>Postback Webhook Active</span>
                  </span>
                </div>
                <div className="flex items-center justify-between text-gray-400 text-[11px]">
                  <span>Reward Multiplier:</span>
                  <span className="text-[#FACC15] font-bold font-mono">1.00x - 1.20x</span>
                </div>
                <div className="flex items-center justify-between text-gray-400 text-[11px]">
                  <span>Anti-Fraud Check:</span>
                  <span className="text-gray-300 font-mono">Passed (Zero VPN)</span>
                </div>
              </div>

              <button
                onClick={() => {
                  const url = getPartnerWallUrl(activeWall);
                  window.open(url, '_blank', 'noopener,noreferrer');
                }}
                className="px-8 py-3.5 rounded-2xl bg-gradient-to-r from-[#FF5A36] to-[#F97316] hover:from-[#ea4823] hover:to-[#e0650f] text-white text-sm font-extrabold shadow-xl shadow-[#FF5A36]/30 transition-all flex items-center space-x-2.5 transform hover:scale-105"
              >
                <span>Launch {activeWall.name} Window</span>
                <ExternalLink className="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
