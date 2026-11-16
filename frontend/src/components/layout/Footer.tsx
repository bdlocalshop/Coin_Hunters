'use client';

import React from 'react';
import Link from 'next/link';
import { Coins, ShieldCheck, Zap, Lock } from 'lucide-react';

export const Footer: React.FC = () => {
  return (
    <footer className="bg-[#0E0A0A] border-t border-[#261D22] text-gray-400 text-xs">
      {/* Upper Grid Section */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
          {/* Brand Info */}
          <div className="lg:col-span-2 space-y-4">
            <Link href="/" className="flex items-center space-x-2.5">
              <div className="w-8 h-8 rounded-xl bg-gradient-to-tr from-[#FF5A36] to-[#F97316] flex items-center justify-center text-white shadow-md shadow-[#FF5A36]/20">
                <Coins className="w-4 h-4 text-[#FACC15]" />
              </div>
              <span className="font-extrabold text-base tracking-wide text-white">
                COIN <span className="text-[#FF5A36]">HUNTERS</span>
              </span>
            </Link>
            <p className="text-gray-400 text-xs leading-relaxed max-w-sm">
              The premier rewards platform connecting global gamers and survey takers with direct brand sponsorships. Complete verified tasks and cash out directly into real cryptocurrency.
            </p>
            <div className="flex items-center space-x-4 pt-2 text-[11px] text-gray-400">
              <span className="flex items-center space-x-1.5">
                <Zap className="w-3.5 h-3.5 text-[#FACC15]" />
                <span className="text-gray-300 font-medium">1,000 Coins = $1.00 USD</span>
              </span>
              <span className="flex items-center space-x-1.5">
                <ShieldCheck className="w-3.5 h-3.5 text-emerald-400" />
                <span className="text-gray-300 font-medium">Manual Fraud Verification</span>
              </span>
            </div>
          </div>

          {/* Column: Earn */}
          <div className="space-y-3">
            <h4 className="text-white font-bold uppercase tracking-wider text-xs">Earn Rewards</h4>
            <ul className="space-y-2">
              <li>
                <Link href="/earn" className="hover:text-white transition-colors">
                  All Earning Methods
                </Link>
              </li>
              <li>
                <Link href="/offer-walls" className="hover:text-white transition-colors">
                  Offer Walls
                </Link>
              </li>
              <li>
                <Link href="/offers" className="hover:text-white transition-colors">
                  Mobile & Game Offers
                </Link>
              </li>
              <li>
                <Link href="/surveys" className="hover:text-white transition-colors">
                  Paid Online Surveys
                </Link>
              </li>
              <li>
                <Link href="/leaderboard" className="hover:text-white transition-colors">
                  Earning Leaderboard
                </Link>
              </li>
            </ul>
          </div>

          {/* Column: Cashout Methods */}
          <div className="space-y-3">
            <h4 className="text-white font-bold uppercase tracking-wider text-xs">Crypto Cashout</h4>
            <ul className="space-y-2">
              <li>
                <Link href="/shop" className="hover:text-white transition-colors flex items-center space-x-1.5">
                  <span className="w-1.5 h-1.5 rounded-full bg-cyan-400" />
                  <span>Litecoin (LTC)</span>
                </Link>
              </li>
              <li>
                <Link href="/shop" className="hover:text-white transition-colors flex items-center space-x-1.5">
                  <span className="w-1.5 h-1.5 rounded-full bg-emerald-400" />
                  <span>Tether (USDT TRC20)</span>
                </Link>
              </li>
              <li>
                <Link href="/shop" className="hover:text-white transition-colors flex items-center space-x-1.5">
                  <span className="w-1.5 h-1.5 rounded-full bg-yellow-400" />
                  <span>Bitcoin (BTC)</span>
                </Link>
              </li>
              <li>
                <Link href="/shop" className="hover:text-white transition-colors flex items-center space-x-1.5">
                  <span className="w-1.5 h-1.5 rounded-full bg-purple-400" />
                  <span>Solana (SOL)</span>
                </Link>
              </li>
            </ul>
          </div>

          {/* Column: Compliance & Info */}
          <div className="space-y-3">
            <h4 className="text-white font-bold uppercase tracking-wider text-xs">Information</h4>
            <ul className="space-y-2">
              <li>
                <Link href="/how-it-works" className="hover:text-white transition-colors">
                  How It Works
                </Link>
              </li>
              <li>
                <Link href="/referrals" className="hover:text-white transition-colors">
                  Referral Program (+500)
                </Link>
              </li>
              <li>
                <Link href="/terms-of-service" className="hover:text-white transition-colors">
                  Terms of Service
                </Link>
              </li>
              <li>
                <Link href="/privacy-policy" className="hover:text-white transition-colors">
                  Privacy Policy
                </Link>
              </li>
              <li>
                <a href="mailto:support@coinhunters.local" className="hover:text-white transition-colors">
                  Contact Support
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>

      {/* Bottom Bar */}
      <div className="border-t border-[#1C1515] bg-[#0A0707] py-4">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between text-[11px] text-gray-500 space-y-2 sm:space-y-0">
          <div>
            &copy; 2026 Coin Hunters Monorepo. All rights reserved.
          </div>
          <div className="flex items-center space-x-4">
            <span className="flex items-center space-x-1">
              <Lock className="w-3 h-3 text-emerald-500" />
              <span>SSL 256-Bit Encrypted</span>
            </span>
            <span>•</span>
            <span>Zero-Gas Crypto Escrow</span>
          </div>
        </div>
      </div>
    </footer>
  );
};
