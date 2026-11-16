'use client';

import React, { useState, useEffect, useCallback } from 'react';
import { api } from '@/lib/api';
import { Offer } from '@/types/api';
import { OfferModal } from '@/components/offers/OfferModal';
import {
  Gamepad2,
  Smartphone,
  Monitor,
  Search,
  ArrowUpDown,
  ChevronLeft,
  ChevronRight,
} from 'lucide-react';

const CATEGORIES = [
  { id: 'all', label: 'All Offers' },
  { id: 'games', label: 'Games' },
  { id: 'apps', label: 'Apps & Tools' },
  { id: 'surveys', label: 'Surveys' },
  { id: 'finance', label: 'Fintech & Crypto' },
];

const DEVICES = [
  { id: 'all', label: 'All Devices' },
  { id: 'android', label: 'Android' },
  { id: 'ios', label: 'iOS' },
  { id: 'desktop', label: 'Desktop' },
];

export default function OffersCatalogPage() {
  const [offers, setOffers] = useState<Offer[]>([]);
  const [totalOffers, setTotalOffers] = useState(0);
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [isLoading, setIsLoading] = useState(true);

  // Filters
  const [selectedDevice, setSelectedDevice] = useState('all');
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [sortOrder, setSortOrder] = useState('featured');

  // Selected Offer for Modal
  const [activeOffer, setActiveOffer] = useState<Offer | null>(null);

  const fetchOffers = useCallback(async () => {
    setIsLoading(true);
    try {
      const res = await api.offers.list({
        device: selectedDevice !== 'all' ? selectedDevice : undefined,
        category: selectedCategory !== 'all' ? selectedCategory : undefined,
        search: searchQuery.trim() || undefined,
        page: currentPage,
        per_page: 24,
      });

      setOffers(res.data);
      setCurrentPage(res.current_page);
      setLastPage(res.last_page);
      setTotalOffers(res.total);
    } catch {
      // Fallback
    } finally {
      setIsLoading(false);
    }
  }, [selectedDevice, selectedCategory, searchQuery, currentPage]);

  useEffect(() => {
    fetchOffers();
  }, [fetchOffers]);

  // Sort offers client side if needed
  const sortedOffers = [...offers].sort((a, b) => {
    if (sortOrder === 'payout_desc') {
      return b.user_payout_coins - a.user_payout_coins;
    }
    if (sortOrder === 'payout_asc') {
      return a.user_payout_coins - b.user_payout_coins;
    }
    return (b.epc_score || 0) - (a.epc_score || 0);
  });

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
      {/* Page Title & Search Bar */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-[#261D22]">
        <div>
          <div className="flex items-center space-x-2 text-xs font-bold text-[#FF5A36] uppercase tracking-wider mb-1">
            <Gamepad2 className="w-4 h-4" />
            <span>Direct Campaign Catalog</span>
          </div>
          <h1 className="text-2xl sm:text-3xl font-black text-white">
            Available Verified Offers
          </h1>
          <p className="text-xs sm:text-sm text-gray-400 mt-1">
            Browse {totalOffers > 0 ? `${totalOffers}+` : 'high-margin'} verified campaigns synced from top global advertiser networks.
          </p>
        </div>

        {/* Search Input */}
        <div className="relative w-full md:w-72">
          <Search className="w-4 h-4 text-gray-500 absolute left-3.5 top-1/2 transform -translate-y-1/2" />
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => {
              setSearchQuery(e.target.value);
              setCurrentPage(1);
            }}
            placeholder="Search by game or brand..."
            className="w-full pl-10 pr-4 py-2.5 bg-[#181313] border border-[#261D22] rounded-xl text-xs text-white placeholder-gray-500 focus:outline-none focus:border-[#FF5A36] transition-colors"
          />
        </div>
      </div>

      {/* Filter Bar */}
      <div className="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4 bg-[#181313] p-4 rounded-2xl border border-[#261D22]">
        {/* Device Pills */}
        <div className="flex items-center space-x-1.5 overflow-x-auto pb-2 lg:pb-0 scrollbar-none">
          {DEVICES.map((d) => (
            <button
              key={d.id}
              onClick={() => {
                setSelectedDevice(d.id);
                setCurrentPage(1);
              }}
              className={`px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all ${
                selectedDevice === d.id
                  ? 'bg-gradient-to-r from-[#FF5A36] to-[#F97316] text-white shadow-md shadow-[#FF5A36]/20'
                  : 'bg-[#120E0E] text-gray-400 hover:text-white border border-[#261D22]'
              }`}
            >
              {d.label}
            </button>
          ))}
        </div>

        {/* Category Pills & Sort Dropdown */}
        <div className="flex items-center space-x-3 overflow-x-auto">
          <div className="flex items-center space-x-1.5">
            {CATEGORIES.map((cat) => (
              <button
                key={cat.id}
                onClick={() => {
                  setSelectedCategory(cat.id);
                  setCurrentPage(1);
                }}
                className={`px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all ${
                  selectedCategory === cat.id
                    ? 'bg-[#211919] text-[#FF5A36] border border-[#382A30]'
                    : 'text-gray-400 hover:text-white'
                }`}
              >
                {cat.label}
              </button>
            ))}
          </div>

          <div className="h-5 w-px bg-[#261D22] hidden sm:block" />

          {/* Sort Selector */}
          <div className="flex items-center space-x-2 shrink-0">
            <ArrowUpDown className="w-3.5 h-3.5 text-gray-500" />
            <select
              value={sortOrder}
              onChange={(e) => setSortOrder(e.target.value)}
              className="bg-[#120E0E] border border-[#261D22] rounded-xl text-xs text-gray-300 py-1.5 px-3 focus:outline-none focus:border-[#FF5A36]"
            >
              <option value="featured">Best EPC / Featured</option>
              <option value="payout_desc">Highest Payout</option>
              <option value="payout_asc">Lowest Payout</option>
            </select>
          </div>
        </div>
      </div>

      {/* Offers Grid */}
      {isLoading ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {Array.from({ length: 8 }).map((_, i) => (
            <div
              key={i}
              className="h-64 rounded-2xl bg-[#181313] border border-[#261D22] animate-pulse p-4 flex flex-col justify-between"
            >
              <div className="space-y-3">
                <div className="h-32 rounded-xl bg-[#211919]" />
                <div className="h-4 bg-[#211919] rounded w-3/4" />
                <div className="h-3 bg-[#211919] rounded w-1/2" />
              </div>
              <div className="h-6 bg-[#211919] rounded" />
            </div>
          ))}
        </div>
      ) : sortedOffers.length > 0 ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {sortedOffers.map((offer) => (
            <div
              key={offer.id}
              onClick={() => setActiveOffer(offer)}
              className="p-4 rounded-2xl bg-[#181313] hover:bg-[#211919] border border-[#261D22] hover:border-[#382A30] transition-all cursor-pointer group flex flex-col justify-between shadow-md"
            >
              <div className="space-y-3">
                {/* Image Banner */}
                <div className="w-full h-32 rounded-xl bg-[#261D22] overflow-hidden relative border border-[#382A30]">
                  {offer.icon_url ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img
                      src={offer.icon_url}
                      alt={offer.title}
                      className="w-full h-full object-cover group-hover:scale-105 transition-transform"
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center">
                      <Gamepad2 className="w-10 h-10 text-gray-500" />
                    </div>
                  )}

                  {/* USD Value Pill */}
                  <div className="absolute top-2 right-2 px-2 py-0.5 rounded-lg bg-black/70 backdrop-blur-sm text-[10px] font-bold text-[#FACC15] border border-white/10 font-mono">
                    ${(offer.user_payout_coins / 1000).toFixed(2)}
                  </div>

                  {offer.is_featured && (
                    <div className="absolute top-2 left-2 px-2 py-0.5 rounded-lg bg-[#FF5A36] text-[9px] font-black uppercase text-white shadow-md">
                      Featured
                    </div>
                  )}
                </div>

                {/* Offer Details */}
                <div>
                  <div className="flex items-center justify-between text-[10px] text-gray-500 uppercase font-semibold mb-1">
                    <span>{offer.provider_slug.replace('_', '.')}</span>
                    <div className="flex items-center space-x-1">
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
                  <h3 className="font-bold text-white text-sm line-clamp-1 group-hover:text-[#FF5A36] transition-colors">
                    {offer.title}
                  </h3>
                  <p className="text-[11px] text-gray-400 line-clamp-2 mt-1 leading-relaxed">
                    {offer.description}
                  </p>
                </div>
              </div>

              {/* Bottom Action Strip */}
              <div className="mt-3 pt-2.5 border-t border-[#261D22] flex items-center justify-between">
                <span className="text-xs font-black text-[#FACC15]">
                  +{offer.user_payout_coins.toLocaleString()} Coins
                </span>
                <span className="text-[11px] font-bold text-white bg-[#261D22] px-3 py-1 rounded-lg group-hover:bg-[#FF5A36] transition-colors">
                  View
                </span>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="p-12 text-center bg-[#181313] border border-[#261D22] rounded-2xl space-y-3">
          <Gamepad2 className="w-12 h-12 text-gray-600 mx-auto" />
          <h3 className="text-base font-bold text-white">No Offers Found</h3>
          <p className="text-xs text-gray-400">
            Try adjusting your search criteria or device filter.
          </p>
        </div>
      )}

      {/* Pagination Controls */}
      {lastPage > 1 && (
        <div className="flex items-center justify-center space-x-3 pt-4">
          <button
            onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
            disabled={currentPage === 1}
            className="p-2 rounded-xl bg-[#181313] border border-[#261D22] text-gray-400 hover:text-white disabled:opacity-40 transition-colors"
          >
            <ChevronLeft className="w-4 h-4" />
          </button>
          <span className="text-xs text-gray-400">
            Page <strong className="text-white">{currentPage}</strong> of{' '}
            <strong className="text-white">{lastPage}</strong>
          </span>
          <button
            onClick={() => setCurrentPage((p) => Math.min(lastPage, p + 1))}
            disabled={currentPage === lastPage}
            className="p-2 rounded-xl bg-[#181313] border border-[#261D22] text-gray-400 hover:text-white disabled:opacity-40 transition-colors"
          >
            <ChevronRight className="w-4 h-4" />
          </button>
        </div>
      )}

      {/* Offer Detail Modal */}
      <OfferModal offer={activeOffer} onClose={() => setActiveOffer(null)} />
    </div>
  );
}
