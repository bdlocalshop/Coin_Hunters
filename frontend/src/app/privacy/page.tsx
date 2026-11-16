'use client';

import React from 'react';
import { ShieldCheck } from 'lucide-react';

export default function PrivacyPage() {
  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8 text-gray-300">
      <div className="pb-6 border-b border-[#261D22] space-y-2">
        <div className="inline-flex items-center space-x-2 text-xs font-bold text-[#FF5A36] uppercase tracking-wider">
          <ShieldCheck className="w-4 h-4" />
          <span>Privacy & Data Protection</span>
        </div>
        <h1 className="text-3xl font-black text-white">Privacy Policy</h1>
        <p className="text-xs text-gray-400">Last updated: January 2026</p>
      </div>

      <div className="space-y-6 text-xs sm:text-sm leading-relaxed">
        <section className="space-y-2">
          <h2 className="text-base font-bold text-white">1. Information We Collect</h2>
          <p className="text-gray-400">
            We collect personal information necessary to deliver services, authenticate accounts, verify affiliate referrals, and combat automated click abuse:
          </p>
          <ul className="list-disc list-inside space-y-1 text-gray-400 ml-2">
            <li><strong>Account Data:</strong> Username, email address, password hash, and generated referral codes.</li>
            <li><strong>Device Telemetry:</strong> IP address, autonomous system number (ASN), browser user-agent, Canvas/WebGL rendering hashes, screen metrics, and bot flags.</li>
            <li><strong>Transaction Records:</strong> Offer click identifiers, completed milestones, postback logs, and destination crypto payout addresses.</li>
          </ul>
        </section>

        <section className="space-y-2">
          <h2 className="text-base font-bold text-white">2. Anti-Fraud & Risk Assessment Integration</h2>
          <p className="text-gray-400">
            To safeguard our advertiser partners against proxy hijacking, we transmit client IP addresses and device hashes to MaxMind GeoIP2/minFraud and ProxyCheck.io during registration, authentication, and outbound offer clicks. We never sell your personal data to third parties.
          </p>
        </section>

        <section className="space-y-2">
          <h2 className="text-base font-bold text-white">3. Data Security & Storage</h2>
          <p className="text-gray-400">
            All user sessions and API payloads are transmitted over 256-bit TLS encryption. Financial ledger records are stored immutably with zero-sum transactional guarantees.
          </p>
        </section>
      </div>
    </div>
  );
}
