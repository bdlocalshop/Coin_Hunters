'use client';

import React, { useState } from 'react';
import { HelpCircle, ChevronDown, ChevronUp } from 'lucide-react';

interface FaqItem {
  q: string;
  a: string;
}

const FAQS: FaqItem[] = [
  {
    q: 'What is the conversion rate of Coins to USD?',
    a: 'Coin Hunters operates on a strict whole-coin valuation where 1,000 Coins = $1.00 USD. All offer rewards, survey payouts, and crypto cashouts follow this exact standard with zero hidden fees.',
  },
  {
    q: 'How fast are crypto withdrawals processed?',
    a: 'Withdrawals are processed manually by our compliance administrators within 1 to 24 hours. Once approved, the transaction is broadcast directly to the blockchain and a clickable tx_hash is posted to your Cashout History.',
  },
  {
    q: 'Why are some coins marked as "Pending" (Held)?',
    a: 'High-payout offers (5,000 Coins or more) are placed into a security hold window (typically 14 days) to protect our liquidity against chargebacks and affiliate clawbacks. Once the hold period concludes, pending coins are automatically released into your available balance.',
  },
  {
    q: 'Can I use a VPN, Tor, or Proxy when completing tasks?',
    a: 'No. The use of VPNs, datacenter proxies, or Tor exit nodes is strictly forbidden. MaxMind and ProxyCheck screen all registrations, logins, and offer clicks. Detected proxies will prevent offer launch and may lead to account suspension.',
  },
  {
    q: 'How does the referral program work?',
    a: 'Coin Hunters features a flat one-time affiliate reward. When you invite a friend, they receive a 250 Coins welcome bonus. When your friend qualifies by earning 1,000 Coins in lifetime activity, you immediately receive a flat 500 Coins ($0.50 USD) bonus directly in your balance. There are no pyramid trees or misleading revshare clauses.',
  },
  {
    q: 'What should I do if an offer milestone did not credit?',
    a: 'First, ensure you gave the partner network enough time (some game publishers take up to 24 hours to verify deep in-game purchases or high levels). If after 24 hours your offer has not credited, check your "My Offers" tab or contact our support team with screenshots of your in-game profile and user ID.',
  },
];

export default function FaqPage() {
  const [openIdx, setOpenIdx] = useState<number | null>(0);

  const toggle = (idx: number) => {
    setOpenIdx(openIdx === idx ? null : idx);
  };

  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8">
      <div className="text-center space-y-2">
        <div className="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-[#211919] border border-[#382A30] text-xs font-bold text-[#FF5A36] uppercase tracking-wider">
          <HelpCircle className="w-4 h-4" />
          <span>Help & Answers</span>
        </div>
        <h1 className="text-3xl font-black text-white">Frequently Asked Questions</h1>
        <p className="text-xs sm:text-sm text-gray-400">
          Everything you need to know about earning coins, anti-fraud rules, and crypto cashouts.
        </p>
      </div>

      <div className="space-y-3">
        {FAQS.map((faq, idx) => (
          <div
            key={idx}
            className="p-5 rounded-2xl bg-[#181313] border border-[#261D22] transition-colors"
          >
            <button
              onClick={() => toggle(idx)}
              className="w-full flex items-center justify-between text-left text-sm font-bold text-white hover:text-[#FF5A36] transition-colors"
            >
              <span>{faq.q}</span>
              {openIdx === idx ? (
                <ChevronUp className="w-4 h-4 text-[#FF5A36] shrink-0 ml-2" />
              ) : (
                <ChevronDown className="w-4 h-4 text-gray-500 shrink-0 ml-2" />
              )}
            </button>
            {openIdx === idx && (
              <p className="mt-3 text-xs text-gray-400 leading-relaxed border-t border-[#261D22] pt-3">
                {faq.a}
              </p>
            )}
          </div>
        ))}
      </div>
    </div>
  );
}
