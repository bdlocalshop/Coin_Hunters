'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { useAuth } from '@/context/AuthContext';
import { api } from '@/lib/api';
import { Offer, PlatformStats } from '@/types/api';
import {
  Gamepad2,
  Gift,
  Coins,
  ShieldCheck,
  Zap,
  ArrowRight,
  TrendingUp,
  Clock,
  CheckCircle2,
  Smartphone,
  Monitor,
  Flame,
  Star,
  ChevronRight,
  Wallet,
} from 'lucide-react';

const FALLBACK_FEATURED_OFFERS: Offer[] = [
  {
    id: 1,
    provider_slug: 'offerwall_gg',
    provider_name: 'Offerwall.gg',
    external_offer_id: 'raid-01',
    title: 'Raid: Shadow Legends',
    description: 'Install and reach level 25 within 14 days. Open 2 Sacred Shards to complete.',
    icon_url: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=120&auto=format&fit=crop&q=80',
    network_payout_usd: 50.0,
    user_payout_coins: 35000,
    device_targets: ['android', 'ios'],
    countries: ['US', 'GB', 'CA', 'DE', 'ALL'],
    categories: ['games', 'rpg'],
    epc_score: 9.8,
    is_featured: true,
    is_active: true,
  },
  {
    id: 2,
    provider_slug: 'lootably',
    provider_name: 'Lootably',
    external_offer_id: 'board-kings-02',
    title: 'Board Kings: Board Games',
    description: 'Download, build and unlock board 12. Great payouts for casual puzzle players!',
    icon_url: 'https://images.unsplash.com/photo-1612287233282-55038c9c0d75?w=120&auto=format&fit=crop&q=80',
    network_payout_usd: 28.0,
    user_payout_coins: 19600,
    device_targets: ['android'],
    countries: ['US', 'CA', 'AU', 'ALL'],
    categories: ['games', 'casual'],
    epc_score: 8.9,
    is_featured: true,
    is_active: true,
  },
  {
    id: 3,
    provider_slug: 'bitlabs',
    provider_name: 'BitLabs',
    external_offer_id: 'survey-premium-03',
    title: 'Consumer Technology Survey',
    description: 'Share your habits regarding gaming gadgets and smart home tech. 12 mins.',
    icon_url: 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?w=120&auto=format&fit=crop&q=80',
    network_payout_usd: 3.5,
    user_payout_coins: 2450,
    device_targets: ['desktop', 'android', 'ios'],
    countries: ['ALL'],
    categories: ['surveys'],
    epc_score: 9.4,
    is_featured: true,
    is_active: true,
  },
];

const TRUSTED_PROVIDERS: Array<{ slug: string; name: string; tag: string; multiplier: string }> = [
  { slug: 'offerwall_gg', name: 'Offerwall.gg', tag: 'Direct S2S API', multiplier: '+20% Coins' },
  { slug: 'offerwall_com', name: 'Offerwall.com', tag: 'Top Mobile Games', multiplier: 'Instant Track' },
  { slug: 'bitlabs', name: 'BitLabs', tag: 'Live Surveys & Polls', multiplier: '99% Fill Rate' },
  { slug: 'cpx_research', name: 'CPX Research', tag: 'Global High EPC', multiplier: 'High Payout' },
  { slug: 'lootably', name: 'Lootably', tag: 'Desktop & Mobile', multiplier: 'Verified Fast' },
];

export default function HomePage() {
  const { isAuthenticated, openAuthModal } = useAuth();
  const [featuredOffers, setFeaturedOffers] = useState<Offer[]>(FALLBACK_FEATURED_OFFERS);
  const [stats, setStats] = useState<PlatformStats>({
    total_users: 142850,
    total_coins_paid: 1254300000,
    total_usd_paid: 1254300,
    avg_cashout_time_minutes: 18,
    avg_daily_earnings_usd: 16.5,
  });

  // Inline Quick Register Form state for unauthenticated users in hero

  useEffect(() => {
    let mounted = true;

    // Fetch platform stats
    api.public
      .stats()
      .then((res) => {
        if (mounted && res) {
          setStats((prev) => ({
            ...prev,
            ...res,
          }));
        }
      })
      .catch(() => {
        // keep fallback stats
      });

    // Fetch live offers
    api.offers
      .list({ per_page: 6 })
      .then((res) => {
        if (mounted && res.data && res.data.length > 0) {
          setFeaturedOffers(res.data.slice(0, 3));
        }
      })
      .catch(() => {
        // keep fallback offers
      });

    return () => {
      mounted = false;
    };
  }, []);

  const handleOfferClick = async (offer: Offer) => {
    if (!isAuthenticated) {
      openAuthModal('register');
      return;
    }

    try {
      const res = await api.offers.click(offer.id);
      if (res.redirect_url) {
        window.open(res.redirect_url, '_blank', 'noopener,noreferrer');
      }
    } catch (err: any) {
      alert(err.message || 'Unable to track offer click.');
    }
  };

  return (
    <div className="space-y-12 pb-16">
      {/* 1. HERO SECTION */}
      <section className="relative overflow-hidden pt-8 pb-12 px-4 sm:px-6 lg:px-8 border-b border-[#261D22] bg-gradient-to-b from-[#181313]/50 to-[#120E0E]">
        {/* Glow ambient background element */}
        <div className="absolute top-0 left-1/3 w-96 h-96 bg-[#FF5A36]/10 rounded-full blur-3xl pointer-events-none" />
        <div className="absolute top-20 right-10 w-72 h-72 bg-[#FACC15]/5 rounded-full blur-3xl pointer-events-none" />

        <div className="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
          {/* Left Column: Headline & Action */}
          <div className="lg:col-span-7 space-y-6">
            <div className="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-[#211919] border border-[#382A30] text-xs">
              <Flame className="w-4 h-4 text-[#FF5A36] animate-pulse" />
              <span className="text-gray-300 font-medium">Top Rated Crypto Rewards Platform</span>
              <span className="text-[#FACC15] font-bold">1,000 Coins = $1.00 USD</span>
            </div>

            <h1 className="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight text-white leading-[1.15]">
              Get Paid For Testing{' '}
              <span className="bg-gradient-to-r from-[#FF5A36] via-[#FF7D59] to-[#FACC15] bg-clip-text text-transparent">
                Apps, Games & Surveys
              </span>
            </h1>

            <p className="text-sm sm:text-base text-gray-400 max-w-xl leading-relaxed">
              Earn real Coins on the web&apos;s cleanest gaming rewards hub. Complete direct advertiser offers with zero gamification clutter, and cash out directly into real crypto with ultra-fast approval.
            </p>

            {/* Key feature pills */}
            <div className="grid grid-cols-3 gap-3 pt-1 max-w-lg">
              <div className="flex items-center space-x-2 text-xs text-gray-300">
                <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" />
                <span>Instant S2S Tracking</span>
              </div>
              <div className="flex items-center space-x-2 text-xs text-gray-300">
                <ShieldCheck className="w-4 h-4 text-[#FACC15] shrink-0" />
                <span>Zero Chargebacks</span>
              </div>
              <div className="flex items-center space-x-2 text-xs text-gray-300">
                <Zap className="w-4 h-4 text-[#FF5A36] shrink-0" />
                <span>Fast Manual Payouts</span>
              </div>
            </div>

            {/* Primary Action Buttons */}
            <div className="pt-2 flex flex-col sm:flex-row items-stretch sm:items-center space-y-3 sm:space-y-0 sm:space-x-4">
              {!isAuthenticated ? (
                <>
                  <button
                    onClick={() => openAuthModal('register')}
                    className="px-6 py-3.5 rounded-xl bg-gradient-to-r from-[#FF5A36] to-[#F97316] hover:from-[#ea4823] hover:to-[#e0650f] text-white text-sm font-extrabold shadow-xl shadow-[#FF5A36]/25 transition-all transform hover:-translate-y-0.5 flex items-center justify-center space-x-2.5"
                  >
                    <span>Start Earning Free</span>
                    <Gift className="w-4 h-4 text-[#FACC15]" />
                  </button>
                  <Link
                    href="/offers"
                    className="px-6 py-3.5 rounded-xl bg-[#1A1414] hover:bg-[#211919] border border-[#261D22] hover:border-[#382A30] text-gray-300 hover:text-white text-sm font-semibold transition-all flex items-center justify-center space-x-2"
                  >
                    <span>Explore 100+ Offers</span>
                    <ArrowRight className="w-4 h-4 text-gray-400" />
                  </Link>
                </>
              ) : (
                <>
                  <Link
                    href="/earn"
                    className="px-6 py-3.5 rounded-xl bg-gradient-to-r from-[#FF5A36] to-[#F97316] hover:from-[#ea4823] hover:to-[#e0650f] text-white text-sm font-extrabold shadow-xl shadow-[#FF5A36]/25 transition-all flex items-center justify-center space-x-2"
                  >
                    <span>Go To Earn Hub</span>
                    <Flame className="w-4 h-4" />
                  </Link>
                  <Link
                    href="/shop"
                    className="px-6 py-3.5 rounded-xl bg-[#1A1414] hover:bg-[#211919] border border-[#261D22] text-gray-300 hover:text-white text-sm font-semibold transition-all flex items-center justify-center space-x-2"
                  >
                    <span>Cashout Crypto</span>
                    <Wallet className="w-4 h-4 text-[#FACC15]" />
                  </Link>
                </>
              )}
            </div>
          </div>

          {/* Right Column: Featured Offers Widget / Quick Signup Box */}
          <div className="lg:col-span-5 space-y-4">
            <div className="flex items-center justify-between px-1">
              <span className="text-xs font-bold uppercase tracking-wider text-gray-400 flex items-center space-x-1.5">
                <Star className="w-3.5 h-3.5 text-[#FACC15] fill-[#FACC15]" />
                <span>Featured High-Paying Offers</span>
              </span>
              <Link
                href="/offers"
                className="text-xs text-[#FF5A36] hover:underline flex items-center space-x-1"
              >
                <span>View all</span>
                <ChevronRight className="w-3.5 h-3.5" />
              </Link>
            </div>

            {/* Featured Offers Stacked List */}
            <div className="space-y-3">
              {featuredOffers.map((offer) => (
                <div
                  key={offer.id}
                  onClick={() => handleOfferClick(offer)}
                  className="p-3.5 rounded-2xl bg-[#181313] hover:bg-[#211919] border border-[#261D22] hover:border-[#382A30] transition-all cursor-pointer group shadow-md"
                >
                  <div className="flex items-center space-x-3.5">
                    {/* Offer Icon */}
                    <div className="w-12 h-12 rounded-xl bg-[#261D22] overflow-hidden shrink-0 border border-[#382A30] relative flex items-center justify-center">
                      {offer.icon_url ? (
                        // eslint-disable-next-line @next/next/no-img-element
                        <img
                          src={offer.icon_url}
                          alt={offer.title}
                          className="w-full h-full object-cover group-hover:scale-105 transition-transform"
                        />
                      ) : (
                        <Gamepad2 className="w-6 h-6 text-gray-500" />
                      )}
                    </div>

                    {/* Details */}
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center space-x-2">
                        <span className="text-[10px] uppercase font-bold text-gray-400 tracking-wider">
                          {offer.provider_slug.replace('_', '.')}
                        </span>
                        <div className="flex items-center space-x-1 text-gray-500">
                          {offer.device_targets?.includes('android') && (
                            <Smartphone className="w-3 h-3 text-emerald-400" />
                          )}
                          {offer.device_targets?.includes('ios') && (
                            <Smartphone className="w-3 h-3 text-cyan-400" />
                          )}
                          {offer.device_targets?.includes('desktop') && (
                            <Monitor className="w-3 h-3 text-gray-400" />
                          )}
                        </div>
                      </div>
                      <h3 className="font-bold text-white text-sm truncate group-hover:text-[#FF5A36] transition-colors">
                        {offer.title}
                      </h3>
                      <p className="text-[11px] text-gray-400 truncate">
                        {offer.description}
                      </p>
                    </div>

                    {/* Payout Badge */}
                    <div className="text-right shrink-0">
                      <div className="flex items-center justify-end space-x-1 text-[#FACC15] font-extrabold text-sm">
                        <span>+{offer.user_payout_coins.toLocaleString()}</span>
                      </div>
                      <span className="text-[10px] text-gray-400 font-mono">
                        ${(offer.user_payout_coins / 1000).toFixed(2)}
                      </span>
                    </div>
                  </div>
                </div>
              ))}
            </div>

            {/* Quick Registration prompt box for guests */}
            {!isAuthenticated && (
              <div className="p-4 rounded-2xl bg-gradient-to-r from-[#211919] to-[#181313] border border-[#382A30] flex items-center justify-between text-xs">
                <div className="flex items-center space-x-2.5">
                  <div className="w-8 h-8 rounded-lg bg-[#FF5A36]/15 flex items-center justify-center text-[#FF5A36]">
                    <Gift className="w-4 h-4" />
                  </div>
                  <div>
                    <div className="font-bold text-white">Join 140,000+ Hunters</div>
                    <div className="text-gray-400 text-[11px]">Instant 250 Coins Welcome Bonus</div>
                  </div>
                </div>
                <button
                  onClick={() => openAuthModal('register')}
                  className="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-[#FF5A36] to-[#F97316] text-white font-bold hover:shadow-md hover:shadow-[#FF5A36]/20 transition-all"
                >
                  Join Free
                </button>
              </div>
            )}
          </div>
        </div>
      </section>

      {/* 2. 3-COLUMN LIVE PLATFORM METRICS BAR */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="p-5 rounded-2xl bg-[#181313] border border-[#261D22] flex items-center space-x-4 shadow-lg">
            <div className="w-12 h-12 rounded-xl bg-[#FF5A36]/10 border border-[#FF5A36]/20 flex items-center justify-center text-[#FF5A36] shrink-0">
              <Clock className="w-6 h-6" />
            </div>
            <div>
              <div className="text-xs font-semibold text-gray-400 uppercase tracking-wider">
                Avg. First Cashout
              </div>
              <div className="text-2xl font-black text-white mt-0.5">
                ~{stats.avg_cashout_time_minutes || 18} Minutes
              </div>
              <div className="text-[11px] text-emerald-400 flex items-center space-x-1 mt-0.5">
                <span>Fastest in rewards industry</span>
              </div>
            </div>
          </div>

          <div className="p-5 rounded-2xl bg-[#181313] border border-[#261D22] flex items-center space-x-4 shadow-lg">
            <div className="w-12 h-12 rounded-xl bg-[#FACC15]/10 border border-[#FACC15]/20 flex items-center justify-center text-[#FACC15] shrink-0">
              <TrendingUp className="w-6 h-6" />
            </div>
            <div>
              <div className="text-xs font-semibold text-gray-400 uppercase tracking-wider">
                Avg. Daily Earnings
              </div>
              <div className="text-2xl font-black text-white mt-0.5">
                ${stats.avg_daily_earnings_usd?.toFixed(2) || '16.50'} <span className="text-xs font-normal text-gray-400">/ user</span>
              </div>
              <div className="text-[11px] text-[#FACC15] flex items-center space-x-1 mt-0.5">
                <span>16,500 Coins earned daily</span>
              </div>
            </div>
          </div>

          <div className="p-5 rounded-2xl bg-[#181313] border border-[#261D22] flex items-center space-x-4 shadow-lg">
            <div className="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0">
              <Coins className="w-6 h-6" />
            </div>
            <div>
              <div className="text-xs font-semibold text-gray-400 uppercase tracking-wider">
                Total USD Paid Out
              </div>
              <div className="text-2xl font-black text-white mt-0.5">
                ${(stats.total_usd_paid || 1254300).toLocaleString()}
              </div>
              <div className="text-[11px] text-gray-400 flex items-center space-x-1 mt-0.5">
                <span>To {stats.total_users?.toLocaleString() || '140,000+'} users</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 3. TRUSTED OFFERWALL & SURVEY PROVIDERS LOGO STRIP */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center space-y-1 mb-6">
          <h2 className="text-lg font-extrabold text-white tracking-wide">
            Verified Offerwall & Survey Networks
          </h2>
          <p className="text-xs text-gray-400">
            Direct high-margin API agreements guaranteeing top payouts per completion.
          </p>
        </div>

        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
          {TRUSTED_PROVIDERS.map((provider) => (
            <Link
              key={provider.slug}
              href="/offer-walls"
              className="p-4 rounded-2xl bg-[#181313] hover:bg-[#211919] border border-[#261D22] hover:border-[#382A30] transition-all group flex flex-col justify-between"
            >
              <div className="flex items-center justify-between mb-3">
                <span className="font-extrabold text-white text-sm group-hover:text-[#FF5A36] transition-colors">
                  {provider.name}
                </span>
                <span className="px-2 py-0.5 rounded-md text-[10px] font-bold bg-[#FF5A36]/10 text-[#FF5A36] border border-[#FF5A36]/20">
                  {provider.multiplier}
                </span>
              </div>
              <div className="text-[11px] text-gray-500 font-medium">
                {provider.tag}
              </div>
            </Link>
          ))}
        </div>
      </section>

      {/* 4. 3-STEP "READY TO EARN? HERE'S HOW" ONBOARDING STRIP */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="p-8 rounded-3xl bg-[#181313] border border-[#261D22] relative overflow-hidden">
          <div className="text-center max-w-xl mx-auto space-y-2 mb-10">
            <span className="text-xs font-bold text-[#FF5A36] uppercase tracking-wider">
              Simple 3-Step Process
            </span>
            <h2 className="text-2xl font-black text-white">
              How You Hunt Down Real Crypto Rewards
            </h2>
            <p className="text-xs text-gray-400">
              No complex point calculations. 1,000 Coins are always worth \$1.00 USD.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 relative">
            {/* Step 1 */}
            <div className="p-6 rounded-2xl bg-[#120E0E] border border-[#261D22] space-y-3 relative">
              <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#FF5A36] to-[#F97316] text-white flex items-center justify-center font-black text-sm shadow-md shadow-[#FF5A36]/25">
                01
              </div>
              <h3 className="font-bold text-white text-base">Select Your Offer</h3>
              <p className="text-xs text-gray-400 leading-relaxed">
                Choose from hundreds of mobile apps, games, and verified surveys. Filter easily by iOS, Android, or PC desktop.
              </p>
            </div>

            {/* Step 2 */}
            <div className="p-6 rounded-2xl bg-[#120E0E] border border-[#261D22] space-y-3 relative">
              <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#FACC15] to-[#EAB308] text-[#120E0E] flex items-center justify-center font-black text-sm shadow-md shadow-[#FACC15]/25">
                02
              </div>
              <h3 className="font-bold text-white text-base">Complete Milestones</h3>
              <p className="text-xs text-gray-400 leading-relaxed">
                Reach in-game levels, test apps, or complete surveys. Advertisers post live S2S webhooks directly crediting your balance.
              </p>
            </div>

            {/* Step 3 */}
            <div className="p-6 rounded-2xl bg-[#120E0E] border border-[#261D22] space-y-3 relative">
              <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-500 text-white flex items-center justify-center font-black text-sm shadow-md shadow-emerald-500/25">
                03
              </div>
              <h3 className="font-bold text-white text-base">Cashout Crypto</h3>
              <p className="text-xs text-gray-400 leading-relaxed">
                Redeem your Coins for Litecoin, USDT, Bitcoin, or Solana with zero hidden withdrawal fees and manual review protection.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* 5. BOTTOM CONVERSION BANNER */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="p-8 sm:p-12 rounded-3xl bg-gradient-to-r from-[#211919] via-[#1A1414] to-[#211919] border border-[#382A30] text-center space-y-6 relative overflow-hidden shadow-2xl">
          <div className="max-w-2xl mx-auto space-y-3">
            <h2 className="text-2xl sm:text-3xl font-black text-white">
              Ready To Hunt Down Your First Crypto Cashout?
            </h2>
            <p className="text-xs sm:text-sm text-gray-300">
              Join thousands of daily players earning real rewards today. Sign up in under 30 seconds and claim your 250 Coins welcome gift.
            </p>
          </div>

          <div className="flex flex-col sm:flex-row items-center justify-center space-y-3 sm:space-y-0 sm:space-x-4">
            <button
              onClick={() => openAuthModal('register')}
              className="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-gradient-to-r from-[#FF5A36] to-[#F97316] hover:from-[#ea4823] hover:to-[#e0650f] text-white text-sm font-extrabold shadow-xl shadow-[#FF5A36]/30 transition-all transform hover:scale-105"
            >
              Claim 250 Coins Welcome Gift
            </button>
            <Link
              href="/how-it-works"
              className="w-full sm:w-auto px-6 py-3.5 rounded-xl bg-[#120E0E] hover:bg-[#181313] border border-[#261D22] text-gray-300 text-sm font-semibold transition-all"
            >
              Learn More
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
}
