'use client';

import React from 'react';
import Link from 'next/link';
import {
  HelpCircle,
  ArrowRight,
  CheckCircle2,
} from 'lucide-react';

export default function HowItWorksPage() {
  return (
    <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-12">
      {/* Header */}
      <div className="text-center space-y-3">
        <div className="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-[#211919] border border-[#382A30] text-xs font-bold text-[#FF5A36] uppercase tracking-wider">
          <HelpCircle className="w-4 h-4" />
          <span>Platform Overview</span>
        </div>
        <h1 className="text-3xl sm:text-4xl font-black text-white">
          How Coin Hunters Works
        </h1>
        <p className="text-sm text-gray-400 max-w-xl mx-auto leading-relaxed">
          Learn how global game studios and consumer research firms sponsor you to test apps, play mobile games, and share your opinion.
        </p>
      </div>

      {/* 3 Step Walkthrough */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="p-6 rounded-3xl bg-[#181313] border border-[#261D22] space-y-4 shadow-xl">
          <div className="w-12 h-12 rounded-2xl bg-gradient-to-tr from-[#FF5A36] to-[#F97316] text-white flex items-center justify-center font-black text-base shadow-lg shadow-[#FF5A36]/20">
            01
          </div>
          <h3 className="text-lg font-bold text-white">1. Choose Your Task</h3>
          <p className="text-xs text-gray-400 leading-relaxed">
            Browse our curated catalog of mobile games, web applications, and live survey routers. Filter seamlessly by Android, iOS, or PC desktop.
          </p>
          <div className="pt-2 text-xs text-gray-300 flex items-center space-x-1.5">
            <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" />
            <span>Over 100+ active campaigns</span>
          </div>
        </div>

        <div className="p-6 rounded-3xl bg-[#181313] border border-[#261D22] space-y-4 shadow-xl">
          <div className="w-12 h-12 rounded-2xl bg-gradient-to-tr from-[#FACC15] to-[#EAB308] text-[#120E0E] flex items-center justify-center font-black text-base shadow-lg shadow-[#FACC15]/20">
            02
          </div>
          <h3 className="text-lg font-bold text-white">2. Reach Milestones</h3>
          <p className="text-xs text-gray-400 leading-relaxed">
            Follow the simple requirements: reach level 15 in an RPG, test a fintech app, or answer survey questionnaires. Our S2S postback server tracks completion automatically.
          </p>
          <div className="pt-2 text-xs text-gray-300 flex items-center space-x-1.5">
            <CheckCircle2 className="w-4 h-4 text-[#FACC15] shrink-0" />
            <span>Cryptographic server tracking</span>
          </div>
        </div>

        <div className="p-6 rounded-3xl bg-[#181313] border border-[#261D22] space-y-4 shadow-xl">
          <div className="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-500 text-white flex items-center justify-center font-black text-base shadow-lg shadow-emerald-500/20">
            03
          </div>
          <h3 className="text-lg font-bold text-white">3. Cashout Crypto</h3>
          <p className="text-xs text-gray-400 leading-relaxed">
            Convert your Coins directly to Litecoin, Tether (USDT), Bitcoin, or Solana with standard 1,000 Coins = $1.00 USD valuation and fast manual admin review.
          </p>
          <div className="pt-2 text-xs text-gray-300 flex items-center space-x-1.5">
            <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" />
            <span>Zero hidden payout fees</span>
          </div>
        </div>
      </div>

      {/* Financial Valuation Guarantee */}
      <div className="p-8 rounded-3xl bg-[#140F0F] border border-[#261D22] flex flex-col md:flex-row items-center justify-between gap-6">
        <div className="space-y-2 max-w-xl">
          <h3 className="text-lg font-black text-white">
            Simple & Transparent Coin Economics
          </h3>
          <p className="text-xs text-gray-400 leading-relaxed">
            Unlike other platforms that inflate points with complex conversions, <strong>Coin Hunters</strong> uses an immutable double-entry ledger. Every 1,000 Coins earned is always worth exactly $1.00 USD in real crypto.
          </p>
        </div>

        <Link
          href="/shop"
          className="px-6 py-3 rounded-xl bg-gradient-to-r from-[#FF5A36] to-[#F97316] text-white text-xs font-bold shadow-lg shadow-[#FF5A36]/20 transition-all shrink-0 flex items-center space-x-2"
        >
          <span>View Cashout Shop</span>
          <ArrowRight className="w-4 h-4" />
        </Link>
      </div>
    </div>
  );
}
