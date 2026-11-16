'use client';

import React from 'react';
import { FileText } from 'lucide-react';

export default function TermsPage() {
  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8 text-gray-300">
      <div className="pb-6 border-b border-[#261D22] space-y-2">
        <div className="inline-flex items-center space-x-2 text-xs font-bold text-[#FF5A36] uppercase tracking-wider">
          <FileText className="w-4 h-4" />
          <span>Legal Agreement</span>
        </div>
        <h1 className="text-3xl font-black text-white">Terms of Service</h1>
        <p className="text-xs text-gray-400">Last updated: January 2026</p>
      </div>

      <div className="space-y-6 text-xs sm:text-sm leading-relaxed">
        <section className="space-y-2">
          <h2 className="text-base font-bold text-white">1. Acceptance of Terms</h2>
          <p className="text-gray-400">
            By creating an account or accessing Coin Hunters, you agree to comply with and be legally bound by these Terms of Service. If you do not agree with any portion of these terms, you must not use our website or services.
          </p>
        </section>

        <section className="space-y-2">
          <h2 className="text-base font-bold text-white">2. Prohibited Conduct & Anti-Fraud Policy</h2>
          <p className="text-gray-400">
            Coin Hunters maintains zero tolerance toward fraud. The following activities will result in immediate and permanent account termination, forfeiture of pending and available coin balances, and blacklisting of associated device fingerprints:
          </p>
          <ul className="list-disc list-inside space-y-1 text-gray-400 ml-2">
            <li>Using virtual private networks (VPNs), proxies, Tor, or datacenter ASNs to spoof IP addresses or country origins.</li>
            <li>Operating Android emulators (BlueStacks, Nox, LDPlayer), virtual machines, or automated headless browsers (Puppeteer, Selenium).</li>
            <li>Creating multiple accounts per person, household, or hardware device hash to exploit registration or referral bonuses.</li>
            <li>Simulating clicks, injecting fraudulent S2S postback webhooks, or tampering with tracking parameters.</li>
          </ul>
        </section>

        <section className="space-y-2">
          <h2 className="text-base font-bold text-white">3. Coin Valuations & Reward Holds</h2>
          <p className="text-gray-400">
            Coins are virtual units of account managed within our immutable double-entry ledger at a fixed standard rate of 1,000 Coins = $1.00 USD. Coins hold no fiat cash value outside of authorized redemptions in our Crypto Shop. Offers carrying high payout amounts are subject to a security hold period to ensure advertiser milestone verification.
          </p>
        </section>

        <section className="space-y-2">
          <h2 className="text-base font-bold text-white">4. Manual Crypto Withdrawals</h2>
          <p className="text-gray-400">
            All crypto withdrawals (LTC, USDT, BTC, SOL) are subject to manual compliance review by platform administrators. You are solely responsible for providing a valid wallet address matching the chosen network. Coin Hunters cannot reverse blockchain transactions once broadcast.
          </p>
        </section>
      </div>
    </div>
  );
}
