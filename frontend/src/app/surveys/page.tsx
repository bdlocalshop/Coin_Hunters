'use client';

import React, { useState, useEffect } from 'react';
import { useAuth } from '@/context/AuthContext';
import { api } from '@/lib/api';
import { Survey } from '@/types/api';
import {
  ClipboardList,
  Clock,
  ExternalLink,
  Sparkles,
  HelpCircle,
  Zap,
} from 'lucide-react';

const FALLBACK_SURVEYS: Survey[] = [
  {
    id: 'bl-101',
    provider: 'BitLabs',
    title: 'Technology & Smartphone Usage Trends 2026',
    payout_coins: 1850,
    estimated_minutes: 12,
    conversion_rate: 98,
    click_url: '#',
  },
  {
    id: 'cpx-201',
    provider: 'CPX Research',
    title: 'Entertainment, Streaming & Gaming Survey',
    payout_coins: 3200,
    estimated_minutes: 16,
    conversion_rate: 95,
    click_url: '#',
  },
  {
    id: 'bl-102',
    provider: 'BitLabs',
    title: 'Grocery, Beverage & Household Goods Study',
    payout_coins: 950,
    estimated_minutes: 7,
    conversion_rate: 99,
    click_url: '#',
  },
  {
    id: 'cpx-202',
    provider: 'CPX Research',
    title: 'Automotive & Electric Vehicle Preferences',
    payout_coins: 4500,
    estimated_minutes: 20,
    conversion_rate: 91,
    click_url: '#',
  },
  {
    id: 'bl-103',
    provider: 'BitLabs',
    title: 'Travel & Vacation Booking Habits',
    payout_coins: 2100,
    estimated_minutes: 14,
    conversion_rate: 96,
    click_url: '#',
  },
  {
    id: 'cpx-203',
    provider: 'CPX Research',
    title: 'Personal Finance, Crypto & Banking Insights',
    payout_coins: 5200,
    estimated_minutes: 22,
    conversion_rate: 92,
    click_url: '#',
  },
];

export default function SurveysPage() {
  const { user, isAuthenticated, openAuthModal } = useAuth();
  const [surveys, setSurveys] = useState<Survey[]>(FALLBACK_SURVEYS);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let mounted = true;
    api.offers
      .surveys()
      .then((res) => {
        if (mounted && res.surveys && res.surveys.length > 0) {
          setSurveys(res.surveys);
        }
      })
      .catch(() => {})
      .finally(() => {
        if (mounted) setIsLoading(false);
      });

    return () => {
      mounted = false;
    };
  }, []);

  const handleStartSurvey = (survey: Survey) => {
    if (!isAuthenticated) {
      openAuthModal('register');
      return;
    }

    const userId = user?.id || 1;
    let url = survey.click_url;
    if (!url || url === '#') {
      if (survey.provider.toLowerCase().includes('bitlabs')) {
        url = `https://web.bitlabs.ai/?token=coinhunters-token&uid=${userId}`;
      } else {
        url = `https://offers.cpx-research.com/index.php?app_id=coinhunters&ext_user_id=${userId}`;
      }
    }
    window.open(url, '_blank', 'noopener,noreferrer');
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-[#261D22]">
        <div>
          <div className="flex items-center space-x-2 text-xs font-bold text-[#FF5A36] uppercase tracking-wider mb-1">
            <ClipboardList className="w-4 h-4" />
            <span>Market Research & Insights</span>
          </div>
          <h1 className="text-2xl sm:text-3xl font-black text-white">
            High-Paying Paid Surveys
          </h1>
          <p className="text-xs sm:text-sm text-gray-400 mt-1">
            Share your honest opinions with global research brands. Disqualification compensation supported.
          </p>
        </div>

        <div className="flex items-center space-x-2 px-3.5 py-1.5 rounded-xl bg-[#181313] border border-[#261D22] text-xs">
          <Sparkles className="w-4 h-4 text-[#FACC15]" />
          <span className="text-gray-300">Live Matched by Country & Demographics</span>
        </div>
      </div>

      {/* Featured Survey Routers */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* BitLabs Router Card */}
        <div className="p-6 rounded-3xl bg-gradient-to-br from-[#181313] to-[#1E1717] border border-[#261D22] hover:border-[#382A30] transition-all flex flex-col justify-between shadow-xl space-y-4">
          <div className="space-y-3">
            <div className="flex items-center justify-between">
              <span className="font-extrabold text-white text-lg">BitLabs Survey Router</span>
              <span className="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                98% Match Rate
              </span>
            </div>
            <p className="text-xs text-gray-400 leading-relaxed">
              Dynamically presents the highest-paying surveys tailored to your demographic profile. Even if you disqualify, you receive compensation bonus Coins.
            </p>
            <div className="flex items-center space-x-4 text-xs text-gray-300 pt-1">
              <span className="flex items-center space-x-1">
                <Clock className="w-3.5 h-3.5 text-gray-500" />
                <span>5 - 20 mins</span>
              </span>
              <span className="flex items-center space-x-1">
                <Zap className="w-3.5 h-3.5 text-[#FACC15]" />
                <span>Up to 15,000 Coins ($15.00)</span>
              </span>
            </div>
          </div>

          <button
            onClick={() => handleStartSurvey({ id: 'bl-wall', provider: 'BitLabs', title: 'BitLabs Router', payout_coins: 0, estimated_minutes: 10, click_url: '#' })}
            className="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-[#FF5A36] to-[#F97316] hover:from-[#ea4823] hover:to-[#e0650f] text-white text-xs font-bold shadow-lg shadow-[#FF5A36]/20 transition-all flex items-center justify-center space-x-2"
          >
            <span>Launch BitLabs Router</span>
            <ExternalLink className="w-3.5 h-3.5" />
          </button>
        </div>

        {/* CPX Research Router Card */}
        <div className="p-6 rounded-3xl bg-gradient-to-br from-[#181313] to-[#1E1717] border border-[#261D22] hover:border-[#382A30] transition-all flex flex-col justify-between shadow-xl space-y-4">
          <div className="space-y-3">
            <div className="flex items-center justify-between">
              <span className="font-extrabold text-white text-lg">CPX Research Global Wall</span>
              <span className="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-[#FF5A36]/10 text-[#FF5A36] border border-[#FF5A36]/20">
                Top Payouts
              </span>
            </div>
            <p className="text-xs text-gray-400 leading-relaxed">
              Global consumer insights network matching users in 100+ countries. Infinite survey stream refreshed every 15 minutes.
            </p>
            <div className="flex items-center space-x-4 text-xs text-gray-300 pt-1">
              <span className="flex items-center space-x-1">
                <Clock className="w-3.5 h-3.5 text-gray-500" />
                <span>7 - 25 mins</span>
              </span>
              <span className="flex items-center space-x-1">
                <Zap className="w-3.5 h-3.5 text-[#FACC15]" />
                <span>Up to 25,000 Coins ($25.00)</span>
              </span>
            </div>
          </div>

          <button
            onClick={() => handleStartSurvey({ id: 'cpx-wall', provider: 'CPX Research', title: 'CPX Wall', payout_coins: 0, estimated_minutes: 15, click_url: '#' })}
            className="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-[#FF5A36] to-[#F97316] hover:from-[#ea4823] hover:to-[#e0650f] text-white text-xs font-bold shadow-lg shadow-[#FF5A36]/20 transition-all flex items-center justify-center space-x-2"
          >
            <span>Launch CPX Research</span>
            <ExternalLink className="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      {/* Available Direct Matched Surveys */}
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h2 className="text-base font-extrabold text-white uppercase tracking-wider">
            Live Matched Surveys
          </h2>
          <span className="text-xs text-gray-400">{surveys.length} surveys ready</span>
        </div>

        {isLoading ? (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {Array.from({ length: 6 }).map((_, i) => (
              <div key={i} className="h-28 rounded-2xl bg-[#181313] border border-[#261D22] animate-pulse" />
            ))}
          </div>
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {surveys.map((survey) => (
              <div
                key={survey.id}
                onClick={() => handleStartSurvey(survey)}
                className="p-5 rounded-2xl bg-[#181313] hover:bg-[#211919] border border-[#261D22] hover:border-[#382A30] transition-all cursor-pointer group flex flex-col justify-between shadow-md"
              >
                <div className="space-y-3">
                  <div className="flex items-center justify-between text-xs">
                    <span className="font-bold text-gray-400 uppercase text-[10px] tracking-wider">
                      {survey.provider}
                    </span>
                    <div className="flex items-center space-x-1 text-gray-400 text-xs">
                      <Clock className="w-3 h-3 text-[#FF5A36]" />
                      <span>~{survey.estimated_minutes} min</span>
                    </div>
                  </div>

                  <h3 className="font-extrabold text-white text-sm group-hover:text-[#FF5A36] transition-colors line-clamp-2 leading-snug">
                    {survey.title}
                  </h3>
                </div>

                <div className="mt-4 pt-3 border-t border-[#261D22] flex items-center justify-between">
                  <div>
                    <div className="text-[#FACC15] font-black text-sm">
                      +{survey.payout_coins.toLocaleString()} Coins
                    </div>
                    <div className="text-[10px] text-gray-500 font-mono">
                      ${(survey.payout_coins / 1000).toFixed(2)} USD
                    </div>
                  </div>

                  <span className="px-3.5 py-1.5 rounded-xl bg-[#211919] group-hover:bg-[#FF5A36] text-white text-xs font-bold transition-colors">
                    Take Survey
                  </span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Survey Guidelines Box */}
      <div className="p-6 rounded-3xl bg-[#140F0F] border border-[#261D22] space-y-4">
        <h4 className="text-xs font-bold text-white uppercase tracking-wider flex items-center space-x-2">
          <HelpCircle className="w-4 h-4 text-[#FF5A36]" />
          <span>Rules For Survey Success & Maximum Approvals</span>
        </h4>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-gray-400">
          <div className="p-3.5 rounded-xl bg-[#181313] border border-[#261D22] space-y-1">
            <span className="font-bold text-gray-200 block">1. Take Your Time</span>
            <p className="text-[11px] leading-relaxed">
              Survey algorithms monitor reading speed. Rushing or answering randomly triggers automated fraud rejections.
            </p>
          </div>
          <div className="p-3.5 rounded-xl bg-[#181313] border border-[#261D22] space-y-1">
            <span className="font-bold text-gray-200 block">2. Be Consistent</span>
            <p className="text-[11px] leading-relaxed">
              Always enter consistent age, country, and occupation information. Contradictory answers lead to blacklisting.
            </p>
          </div>
          <div className="p-3.5 rounded-xl bg-[#181313] border border-[#261D22] space-y-1">
            <span className="font-bold text-gray-200 block">3. Never Use VPNs</span>
            <p className="text-[11px] leading-relaxed">
              Datacenter IPs and VPN proxies are instantly rejected by BitLabs and CPX security filters.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
