'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';
import { api } from '@/lib/api';
import { Offer, Survey } from '@/types/api';
import { OfferModal } from '@/components/offers/OfferModal';
import {
  Flame,
  Gamepad2,
  Layers,
  ClipboardList,
  Smartphone,
  Monitor,
  ChevronRight,
  Clock,
  Sparkles,
} from 'lucide-react';

const FALLBACK_GAMES: Offer[] = [
  {
    id: 101,
    provider_slug: 'offerwall_gg',
    external_offer_id: 'raid-shadow-legends',
    title: 'Raid: Shadow Legends',
    description: 'Level up to Level 25 and summon 2 Sacred Shards.',
    icon_url: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=120&auto=format&fit=crop&q=80',
    network_payout_usd: 45.0,
    user_payout_coins: 31500,
    device_targets: ['android', 'ios'],
    countries: ['ALL'],
    categories: ['games'],
    epc_score: 9.8,
    is_featured: true,
    is_active: true,
  },
  {
    id: 102,
    provider_slug: 'lootably',
    external_offer_id: 'mafia-city',
    title: 'Mafia City: War of Underworld',
    description: 'Upgrade Mansion to Level 15 within 21 days.',
    icon_url: 'https://images.unsplash.com/photo-1612287233282-55038c9c0d75?w=120&auto=format&fit=crop&q=80',
    network_payout_usd: 35.0,
    user_payout_coins: 24500,
    device_targets: ['android'],
    countries: ['ALL'],
    categories: ['games'],
    epc_score: 9.2,
    is_featured: true,
    is_active: true,
  },
  {
    id: 103,
    provider_slug: 'offerwall_com',
    external_offer_id: 'rise-of-kingdoms',
    title: 'Rise of Kingdoms: Lost Crusade',
    description: 'Reach City Hall Level 17 within 25 days.',
    icon_url: 'https://images.unsplash.com/photo-1538481199705-c710c4e965fc?w=120&auto=format&fit=crop&q=80',
    network_payout_usd: 55.0,
    user_payout_coins: 38500,
    device_targets: ['ios', 'android'],
    countries: ['ALL'],
    categories: ['games'],
    epc_score: 9.9,
    is_featured: true,
    is_active: true,
  },
  {
    id: 104,
    provider_slug: 'lootably',
    external_offer_id: 'board-kings',
    title: 'Board Kings: Casual Roll',
    description: 'Reach Board Level 11 in 14 days.',
    icon_url: 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=120&auto=format&fit=crop&q=80',
    network_payout_usd: 22.0,
    user_payout_coins: 15400,
    device_targets: ['android'],
    countries: ['ALL'],
    categories: ['games'],
    epc_score: 8.7,
    is_featured: false,
    is_active: true,
  },
];

const FALLBACK_SURVEYS: Survey[] = [
  {
    id: 's-1',
    provider: 'BitLabs',
    title: 'Technology & Smartphone Usage Habits',
    payout_coins: 1450,
    estimated_minutes: 10,
    click_url: '#',
  },
  {
    id: 's-2',
    provider: 'CPX Research',
    title: 'Global Video Streaming & Media Study',
    payout_coins: 2800,
    estimated_minutes: 15,
    click_url: '#',
  },
  {
    id: 's-3',
    provider: 'BitLabs',
    title: 'Daily Grocery & Snack Purchasing Choices',
    payout_coins: 850,
    estimated_minutes: 6,
    click_url: '#',
  },
];

const PROVIDERS_LIST = [
  { slug: 'offerwall_gg', name: 'Offerwall.gg', badge: '+20% Bonus', rating: 4.9, desc: 'Direct S2S postback sync with top mobile games' },
  { slug: 'offerwall_com', name: 'Offerwall.com', badge: 'High Payouts', rating: 4.8, desc: 'Global game publisher campaigns with quick hold release' },
  { slug: 'lootably', name: 'Lootably', badge: 'Instant Credit', rating: 4.9, desc: 'Desktop, iOS and Android multi-device high converting tasks' },
  { slug: 'bitlabs', name: 'BitLabs', badge: 'Top Surveys', rating: 4.7, desc: 'Targeted survey router with 98% qualification matching' },
  { slug: 'cpx_research', name: 'CPX Research', badge: 'Global Fill', rating: 4.8, desc: 'Worldwide high-EPC consumer research opportunities' },
];

export default function EarnPage() {
  const router = useRouter();
  const { isAuthenticated, openAuthModal } = useAuth();
  const [featuredOffers, setFeaturedOffers] = useState<Offer[]>(FALLBACK_GAMES);
  const [surveys, setSurveys] = useState<Survey[]>(FALLBACK_SURVEYS);
  const [selectedOffer, setSelectedOffer] = useState<Offer | null>(null);

  useEffect(() => {
    let mounted = true;

    api.offers
      .list({ per_page: 8 })
      .then((res) => {
        if (mounted && res.data && res.data.length > 0) {
          setFeaturedOffers(res.data);
        }
      })
      .catch(() => {});

    api.offers
      .surveys()
      .then((res) => {
        if (mounted && res.surveys && res.surveys.length > 0) {
          setSurveys(res.surveys.slice(0, 4));
        }
      })
      .catch(() => {});

    return () => {
      mounted = false;
    };
  }, []);

  const handleSurveyClick = (survey: Survey) => {
    if (!isAuthenticated) {
      openAuthModal('register');
      return;
    }
    if (survey.click_url && survey.click_url !== '#') {
      window.open(survey.click_url, '_blank', 'noopener,noreferrer');
    } else {
      router.push('/surveys');
    }
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">
      {/* Page Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-[#261D22]">
        <div>
          <div className="flex items-center space-x-2 text-xs font-bold text-[#FF5A36] uppercase tracking-wider mb-1">
            <Flame className="w-4 h-4" />
            <span>Unified Earn Hub</span>
          </div>
          <h1 className="text-2xl sm:text-3xl font-black text-white">
            Choose Your Way To Earn Real Crypto
          </h1>
          <p className="text-xs sm:text-sm text-gray-400 mt-1">
            Every completion pays in whole Coins. 1,000 Coins = $1.00 USD cashout.
          </p>
        </div>

        <div className="flex items-center space-x-3">
          <Link
            href="/offers"
            className="px-4 py-2 rounded-xl bg-[#181313] hover:bg-[#211919] border border-[#261D22] text-xs font-semibold text-gray-300 hover:text-white transition-all flex items-center space-x-1.5"
          >
            <span>All Offers</span>
            <ChevronRight className="w-3.5 h-3.5" />
          </Link>
          <Link
            href="/surveys"
            className="px-4 py-2 rounded-xl bg-[#181313] hover:bg-[#211919] border border-[#261D22] text-xs font-semibold text-gray-300 hover:text-white transition-all flex items-center space-x-1.5"
          >
            <span>Surveys</span>
            <ChevronRight className="w-3.5 h-3.5" />
          </Link>
        </div>
      </div>

      {/* 1. FEATURED OFFERS CAROUSEL / SPOTLIGHT */}
      <section className="space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <Sparkles className="w-4 h-4 text-[#FACC15]" />
            <h2 className="text-base font-extrabold text-white uppercase tracking-wider">
              Featured High-Payout Highlights
            </h2>
          </div>
          <Link href="/offers" className="text-xs text-[#FF5A36] hover:underline flex items-center space-x-1">
            <span>View All</span>
            <ChevronRight className="w-3 h-3" />
          </Link>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {featuredOffers.slice(0, 3).map((offer) => (
            <div
              key={offer.id}
              onClick={() => setSelectedOffer(offer)}
              className="p-5 rounded-2xl bg-[#181313] hover:bg-[#211919] border border-[#261D22] hover:border-[#382A30] transition-all cursor-pointer group flex flex-col justify-between shadow-lg relative overflow-hidden"
            >
              <div className="space-y-3">
                <div className="flex items-start justify-between">
                  <div className="w-14 h-14 rounded-2xl bg-[#261D22] overflow-hidden shrink-0 border border-[#382A30] flex items-center justify-center">
                    {offer.icon_url ? (
                      // eslint-disable-next-line @next/next/no-img-element
                      <img src={offer.icon_url} alt={offer.title} className="w-full h-full object-cover group-hover:scale-105 transition-transform" />
                    ) : (
                      <Gamepad2 className="w-7 h-7 text-gray-400" />
                    )}
                  </div>
                  <div className="text-right">
                    <span className="text-[#FACC15] font-black text-base block">
                      +{offer.user_payout_coins.toLocaleString()}
                    </span>
                    <span className="text-[11px] text-gray-400 font-mono">
                      ${(offer.user_payout_coins / 1000).toFixed(2)}
                    </span>
                  </div>
                </div>

                <div>
                  <div className="flex items-center space-x-2 text-[10px] uppercase font-bold text-gray-400 mb-1">
                    <span>{offer.provider_slug.replace('_', '.')}</span>
                    <span>•</span>
                    <div className="flex items-center space-x-1">
                      {offer.device_targets?.includes('android') && <Smartphone className="w-3 h-3 text-emerald-400" />}
                      {offer.device_targets?.includes('ios') && <Smartphone className="w-3 h-3 text-cyan-400" />}
                      {offer.device_targets?.includes('desktop') && <Monitor className="w-3 h-3 text-gray-400" />}
                    </div>
                  </div>
                  <h3 className="font-extrabold text-white text-base group-hover:text-[#FF5A36] transition-colors line-clamp-1">
                    {offer.title}
                  </h3>
                  <p className="text-xs text-gray-400 line-clamp-2 mt-1 leading-relaxed">
                    {offer.description}
                  </p>
                </div>
              </div>

              <div className="mt-4 pt-3 border-t border-[#261D22] flex items-center justify-between text-xs">
                <span className="text-gray-400 text-[11px]">EPC Score: {offer.epc_score || '9.5'}</span>
                <span className="font-bold text-[#FF5A36] group-hover:translate-x-0.5 transition-transform flex items-center space-x-1">
                  <span>Start Offer</span>
                  <ChevronRight className="w-3.5 h-3.5" />
                </span>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* 2. PARTNER OFFERWALLS STRIP */}
      <section className="space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <Layers className="w-4 h-4 text-[#FF5A36]" />
            <h2 className="text-base font-extrabold text-white uppercase tracking-wider">
              Integrated Partner Offerwalls
            </h2>
          </div>
          <Link href="/offer-walls" className="text-xs text-[#FF5A36] hover:underline flex items-center space-x-1">
            <span>Explore All Walls</span>
            <ChevronRight className="w-3 h-3" />
          </Link>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
          {PROVIDERS_LIST.map((provider) => (
            <Link
              key={provider.slug}
              href={`/offer-walls#${provider.slug}`}
              className="p-4 rounded-2xl bg-[#181313] hover:bg-[#211919] border border-[#261D22] hover:border-[#382A30] transition-all group flex flex-col justify-between shadow-md"
            >
              <div className="space-y-2">
                <div className="flex items-center justify-between">
                  <span className="font-extrabold text-white text-sm group-hover:text-[#FF5A36] transition-colors">
                    {provider.name}
                  </span>
                  <span className="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#FF5A36]/10 text-[#FF5A36] border border-[#FF5A36]/20">
                    {provider.badge}
                  </span>
                </div>
                <p className="text-[11px] text-gray-400 leading-snug line-clamp-2">
                  {provider.desc}
                </p>
              </div>

              <div className="mt-3 pt-2 border-t border-[#261D22] flex items-center justify-between text-[11px] text-gray-500">
                <span>Rating: ★ {provider.rating}</span>
                <span className="text-gray-300 group-hover:text-white font-medium flex items-center">
                  <span>Open Wall</span>
                  <ChevronRight className="w-3 h-3 ml-0.5" />
                </span>
              </div>
            </Link>
          ))}
        </div>
      </section>

      {/* 3. TOP MOBILE GAMES GRID */}
      <section className="space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <Gamepad2 className="w-4 h-4 text-emerald-400" />
            <h2 className="text-base font-extrabold text-white uppercase tracking-wider">
              Top Mobile & Desktop Games
            </h2>
          </div>
          <Link href="/offers?category=games" className="text-xs text-[#FF5A36] hover:underline flex items-center space-x-1">
            <span>Browse Games</span>
            <ChevronRight className="w-3 h-3" />
          </Link>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {featuredOffers.map((game) => (
            <div
              key={game.id}
              onClick={() => setSelectedOffer(game)}
              className="p-4 rounded-2xl bg-[#181313] hover:bg-[#211919] border border-[#261D22] hover:border-[#382A30] transition-all cursor-pointer group flex flex-col justify-between shadow-md"
            >
              <div className="space-y-3">
                <div className="w-full h-32 rounded-xl bg-[#261D22] overflow-hidden relative border border-[#382A30]">
                  {game.icon_url ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img src={game.icon_url} alt={game.title} className="w-full h-full object-cover group-hover:scale-105 transition-transform" />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center">
                      <Gamepad2 className="w-10 h-10 text-gray-500" />
                    </div>
                  )}
                  <div className="absolute top-2 right-2 px-2 py-0.5 rounded-lg bg-black/70 backdrop-blur-sm text-[10px] font-bold text-[#FACC15] border border-white/10 font-mono">
                    ${(game.user_payout_coins / 1000).toFixed(2)}
                  </div>
                </div>

                <div>
                  <div className="flex items-center justify-between text-[10px] text-gray-500 uppercase font-semibold mb-1">
                    <span>{game.provider_slug.replace('_', '.')}</span>
                    <div className="flex items-center space-x-1">
                      {game.device_targets?.includes('android') && <Smartphone className="w-3 h-3 text-emerald-400" />}
                      {game.device_targets?.includes('ios') && <Smartphone className="w-3 h-3 text-cyan-400" />}
                      {game.device_targets?.includes('desktop') && <Monitor className="w-3 h-3 text-gray-400" />}
                    </div>
                  </div>
                  <h3 className="font-bold text-white text-sm line-clamp-1 group-hover:text-[#FF5A36] transition-colors">
                    {game.title}
                  </h3>
                  <p className="text-[11px] text-gray-400 line-clamp-2 mt-1">
                    {game.description}
                  </p>
                </div>
              </div>

              <div className="mt-3 pt-2.5 border-t border-[#261D22] flex items-center justify-between">
                <span className="text-xs font-black text-[#FACC15]">
                  +{game.user_payout_coins.toLocaleString()} Coins
                </span>
                <span className="text-[11px] font-bold text-white bg-[#261D22] px-2.5 py-1 rounded-lg group-hover:bg-[#FF5A36] transition-colors">
                  Play
                </span>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* 4. QUICK SURVEYS PREVIEW */}
      <section className="space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <ClipboardList className="w-4 h-4 text-[#FACC15]" />
            <h2 className="text-base font-extrabold text-white uppercase tracking-wider">
              Quick Matched Surveys
            </h2>
          </div>
          <Link href="/surveys" className="text-xs text-[#FF5A36] hover:underline flex items-center space-x-1">
            <span>All Surveys</span>
            <ChevronRight className="w-3 h-3" />
          </Link>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          {surveys.map((survey) => (
            <div
              key={survey.id}
              onClick={() => handleSurveyClick(survey)}
              className="p-4 rounded-2xl bg-[#181313] hover:bg-[#211919] border border-[#261D22] hover:border-[#382A30] transition-all cursor-pointer group flex items-center justify-between shadow-md"
            >
              <div className="space-y-1 pr-3">
                <div className="flex items-center space-x-2 text-[10px] text-gray-400 font-bold uppercase">
                  <span>{survey.provider}</span>
                  <span>•</span>
                  <span className="flex items-center space-x-1 text-gray-400">
                    <Clock className="w-3 h-3" />
                    <span>~{survey.estimated_minutes} min</span>
                  </span>
                </div>
                <h4 className="font-bold text-white text-xs group-hover:text-[#FF5A36] transition-colors line-clamp-1">
                  {survey.title}
                </h4>
                <div className="text-[#FACC15] font-black text-sm pt-0.5">
                  +{survey.payout_coins.toLocaleString()} Coins
                  <span className="text-[10px] text-gray-500 font-mono font-normal ml-1">
                    (${ (survey.payout_coins / 1000).toFixed(2) })
                  </span>
                </div>
              </div>

              <button
                type="button"
                className="px-3 py-1.5 rounded-xl bg-gradient-to-r from-[#FF5A36] to-[#F97316] text-white text-xs font-bold shrink-0 shadow-md shadow-[#FF5A36]/20 group-hover:scale-105 transition-transform"
              >
                Start
              </button>
            </div>
          ))}
        </div>
      </section>

      {/* Reusable Offer Detail Modal */}
      <OfferModal offer={selectedOffer} onClose={() => setSelectedOffer(null)} />
    </div>
  );
}
