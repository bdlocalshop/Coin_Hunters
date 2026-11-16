# Product Requirements Document (PRD): Coin Hunters

## 1. Executive Summary
**Coin Hunters** is a full-stack Get-Paid-To (GPT) web platform where users earn virtual currency (**Coins**) by completing mobile/web offers, app installs, and market research surveys, and redeem those Coins for cryptocurrency payouts. The platform combines a modern, dark-mode gamer/fintech aesthetic inspired by Coinhub[cite: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10] with a strict, auditable financial backend and enterprise-grade anti-fraud protections[cite: 11].

---

## 2. System Architecture & Tech Stack
* **Client Frontend:** Next.js (App Router, TypeScript, Tailwind CSS)[cite: 11]
* **Backend & REST API:** Laravel 11+ (PHP 8.3+, Laravel Sanctum for SPA/API token auth, Laravel Horizon/Queues for background jobs)[cite: 11]
* **Admin Panel:** Filament PHP v3 (embedded within the Laravel backend for offer management, user moderation, payout approvals, and ledger auditing)
* **Primary Database:** PostgreSQL 16 (or MySQL 8.0+ with InnoDB strict transactional isolation)[cite: 11]
* **Caching & Rate Limiting:** Redis (caching direct offer feeds, leaderboard/ticker states, and webhook idempotency locks)
* **Deployment Target:** Decoupled deployment (Vercel/Node for Next.js frontend; VPS/Hostinger/Laravel Forge for Laravel API + Queue Workers).

---

## 3. Virtual Economy & Immutable Double-Entry Ledger

### 3.1 Currency Standard
* **Platform Currency:** **Coins**[cite: 1, 9]
* **Conversion Rate:** **1,000 Coins = $1.00 USD** (standard GPT granularity allowing micro-tasks down to $0.01 = 10 Coins)[cite: 3, 9].
* **Precision:** Stored internally as unsigned `BIGINT` (representing whole Coins) to eliminate floating-point rounding errors.

### 3.2 Immutable Double-Entry Ledger
Every financial state change MUST be recorded as balanced, append-only journal entries rather than mutable `balance` column updates[cite: 11]:
* **System Accounts (Chart of Accounts):**
  * `asset:network_receivables` (Revenue earned from offerwall/survey partners)
  * `liability:user_available_balance` (Spendable user Coins)
  * `liability:user_pending_balance` (Coins under temporary fraud/chargeback hold)[cite: 1]
  * `expense:marketing_bonuses` (Signup bonuses & flat referral payouts)[cite: 11]
  * `asset:crypto_treasury` (Outflow for approved crypto withdrawals)[cite: 11]
* **Ledger Rules:**
  * Every transaction creates a `ledger_transactions` header record (`uuid`, `type`, `reference_id`, `idempotency_key`, `metadata`) and at least two `ledger_entries` rows (`account_id`, `user_id`, `direction: DEBIT|CREDIT`, `amount`) where $\sum \text{Debits} = \sum \text{Credits}$.
  * Rows in `ledger_transactions` and `ledger_entries` are strictly **append-only** (no `UPDATE` or `DELETE` permitted at the application or DB role level).
  * User balances (`available_coins`, `pending_coins`, `lifetime_earned_coins`) are materialized views/projections updated atomically within the same DB transaction as the ledger entries.
  * Chargebacks/reversals generate a compensating reversal transaction referencing the original `ledger_transaction_id`.

---

## 4. Core Functional Modules

### 4.1 Landing Page & Navigation Shell (Coinhub Layout)
* **Persistent Left Sidebar:**
  * Brand Logo (**Coin Hunters**)[cite: 10]
  * Primary Nav: `Home`, `Earn`, `Offer Walls`, `Offers`, `Surveys` (with live count badge), `Leaderboard`, `Shop`[cite: 2, 10]
  * Authenticated Account Nav: `My Offers`, `Referrals`[cite: 5, 8]
  * Bottom Promo/Action Card: `Welcome Bonus` status card & `Download / App Info` trigger[cite: 10]
* **Top Header Bar:**
  * Live **Recent Earnings Ticker** (horizontal scrolling pills showing real-time user payouts/completions: Avatar, Username, Activity/Offer Name, `+Coins`)[cite: 8, 10]
  * Language selector, Notification bell, Live Coin Balance pill (`1,000 Coins = $1.00`), User Profile dropdown (or `Sign Up` / `Sign In` CTAs for guests)[cite: 3, 8, 10].
* **Public Home Page (`/`)[cite: 10]:**
  * **Hero Section:** Headline ("Get paid for testing apps, games & surveys"), Top 3 Featured High-Payout Offers widget, and inline quick-registration card (Email + Google OAuth + Signup Bonus callout)[cite: 10].
  * **Platform Live Metrics Bar:** Avg. time to first cashout, Avg. earned per active user/day, Total USD paid out[cite: 10].
  * **3-Step Onboarding Strip:** Create Account $\rightarrow$ Complete Offers $\rightarrow$ Earn Coins[cite: 10].
  * **Trusted Providers Showcase:** Logos of integrated networks (`Offerwall.gg`, `Offerwall.com`, `BitLabs`, `CPX Research`, `Lootably`)[cite: 10, 11].
  * **Footer:** Links to `About Us`, `How It Works`, `FAQ`, `Terms of Service`, `Privacy Policy`, `Contact / Report a Problem`[cite: 1, 6, 9, 10].

### 4.2 Offerwall & Survey Integrations (Direct Campaign API)
* **Integrated Networks:**
  1. **Offerwall.gg** (Direct Offer/Task API + S2S Postback)[cite: 11]
  2. **Offerwall.com** (Direct Campaign API + S2S Postback)[cite: 11]
  3. **Lootably (`lootably.com`)** (Direct Offer Feed API + S2S Postback)[cite: 11]
  4. **BitLabs** (Direct Survey & Offer API + S2S Postback)[cite: 11]
  5. **CPX Research** (Direct Survey Feed API + S2S Postback)[cite: 11]
* **Direct API Ingestion & Caching:**
  * Laravel scheduled jobs sync and normalize direct campaigns from each provider into a unified `offers` schema (`external_offer_id`, `provider`, `title`, `description`, `icon_url`, `payout_coins`, `device_targets`, `countries`, `categories`, `click_url_template`, `epc_score`).
  * Real-time user-targeted survey/offer queries are filtered by user `country_code`, `device_os` (`iOS`, `Android`, `Desktop`), and cached in Redis per user/geo segment.
* **UI Views:**
  * **`/earn` (Unified Hub):** Categorized horizontal carousels for Featured Offers, Mobile Games, Surveys, and Partner Wall cards.
  * **`/offers` (Direct Offer Catalog):** Filterable grid (Device: All/Android/iOS/Desktop, Category: Games/Apps/Signups, Sort: Highest Payout/Most Popular). Clicking an offer opens a detail modal showing step-by-step requirements, reward breakdown, and a tracked `Start Offer` CTA.
  * **`/surveys` (Survey Router & Wall):** Displays live matched surveys from **BitLabs** and **CPX Research** with estimated completion time (minutes) and Coin reward, plus partner wall launch cards.
  * **`/offer-walls` (Partner Hub):** Grid of the 5 partner cards with bonus badges (e.g., `+15% Weekend Boost` configurable by admin)[cite: 7].
  * **`/my-offers` (User Tracking):** Tabs for `All`, `Clicked`, `Pending`, `In Progress`, and `Completed` offers synced from outbound click logs and S2S postbacks[cite: 8].

### 4.3 Engagement & Retention (Streamlined Scope)
* **Signup Bonus:** Configurable fixed Coin reward credited upon email verification + passing initial anti-fraud check (recorded via double-entry ledger from `expense:marketing_bonuses`)[cite: 11].
* **Flat One-Time Referral System (`/referrals`):**
  * Each user receives a unique referral link (`https://coinhunters.tld/r/{code}`)[cite: 5, 11].
  * **Trigger Condition:** When a referred user registers, passes fraud screening, and earns their first threshold of offer Coins (e.g., 1,000 Coins / $1.00 earned to prevent referral farming), the referrer automatically receives a **flat one-time Coin bonus**[cite: 11].
  * Referral dashboard displays `Total Referrals`, `Qualified Referrals`, `Lifetime Referral Coins Earned`, invite link copy box, and referral status table (`Pending Qualification` vs. `Paid`)[cite: 5].
* **Excluded Scope:** No daily spin wheels, 7-day login streaks, daily quest checklists, or multi-tier affiliate commissions (per `thing-may-have.txt` Gamification: No / Flat referral spec)[cite: 11].

### 4.4 Crypto Payout Shop (`/shop`)
* **Supported Payout Assets:** Cryptocurrency withdrawals only (e.g., **Litecoin (LTC)**, **USDT (TRC20/BEP20/Polygon)**, **Bitcoin (BTC)**, **Solana (SOL)** — configurable in Filament Admin)[cite: 3, 11].
* **Redemption Flow:**
  * User selects crypto asset, enters wallet address (with address regex checksum validation) and Coin amount (minimum threshold e.g., `1,000 Coins = $1.00` for LTC/USDT, higher for BTC)[cite: 3].
  * Coins are immediately debited from `liability:user_available_balance` into a locked withdrawal escrow state via the ledger.
  * Admin reviews/approves in Filament (or auto-approves trusted low-risk users) and records the on-chain `tx_hash`. Rejected withdrawals issue a full ledger reversal back to user balance.

---

## 5. Anti-Fraud & Security Architecture

### 5.1 Network & Device Intelligence
* **Pre-Auth & Pre-Click Screening:**
  * Integrate **MaxMind minFraud / GeoIP2** and **ProxyCheck.io** APIs on registration, login, and outbound offer click generation[cite: 11].
  * **Automated Blocking Rules:** Reject or flag requests originating from VPNs, residential/datacenter proxies, Tor exit nodes, hosting ASNs, or mismatched country/timezone headers.
* **Emulator & Multi-Account Protection:**
  * Collect browser/device fingerprints on the Next.js client (Canvas/WebGL hash, screen metrics, audio context, `navigator.webdriver` / headless flags, User-Agent client hints)[cite: 11].
  * Block known Android emulator signatures (BlueStacks, Nox, LDPlayer) on mobile-targeted web flows and enforce **1 account per device fingerprint / household IP** policy[cite: 1, 11].
  * Velocity rate-limiting via Redis (`max X offer clicks/minute`, `max Y completions/hour`)[cite: 1].

### 5.2 S2S Postback Security & Idempotency
Each network (`Offerwall.gg`, `Offerwall.com`, `BitLabs`, `CPX Research`, `Lootably`) receives a dedicated webhook endpoint (`/api/v1/postbacks/{provider}`):
1. **IP Whitelisting:** Middleware validates `REMOTE_ADDR` (or trusted Cloudflare `CF-Connecting-IP`) against provider-specific CIDR/IP allowlists stored in database/config[cite: 11].
2. **Cryptographic Signature / Token Verification:**
   * Verify provider-specific HMAC-SHA256 / MD5 hash signatures or secret tokens before parsing payload (`Offerwall.gg` secret/signature, `BitLabs` HMAC hash, `CPX Research` hash, `Lootably` hash, `Offerwall.com` secret)[cite: 11].
3. **Strict Idempotency:**
   * Enforce a database `UNIQUE` constraint on `(provider, external_transaction_id)`. Duplicate postbacks return HTTP `200 OK` immediately without double-crediting.
4. **High-Value Hold Policy:**
   * Offers exceeding an admin-defined Coin threshold (e.g., `>= 5,000 Coins` / `$5.00`) are automatically routed to `liability:user_pending_balance` for a configurable hold period (e.g., 7–30 days) before auto-releasing via scheduled job[cite: 1].

---

## 6. Filament v3 Admin Panel Requirements
* **Dashboard Widgets:** Daily Gross Revenue (USD), Daily Coin Liability Issued, Net Margin, Pending Withdrawals Queue, Fraud-Flagged Postbacks.
* **Offer & Network Manager:** Toggle providers on/off, configure global payout margin percentage (e.g., pass 70% of network payout to users), set temporary promo multipliers, and pin featured offers.
* **User & Fraud Inspector:** View user IP history, MaxMind/ProxyCheck risk scores, device fingerprint collisions, ban/suspend users, and inspect full double-entry ledger history per user.
* **Payout Queue:** Approve/reject crypto cashout requests, export batch payouts, and attach blockchain `tx_hash`.
* **Postback Logs:** Raw inbound webhook inspector showing HTTP status, signature check result, IP check result, and payload JSON for debugging.

---

## 7. Non-Functional & Compliance Requirements
* **Legal Pages:** Dedicated static/CMS-managed pages for `Terms of Service` (18+ / 16+ age gate, 1-account rule, VPN ban, up to 90-day offer hold disclosure), `Privacy Policy`, `FAQ`, and `How It Works`[cite: 1, 6, 9].
* **Performance:** Offer catalog API response time `< 150ms` via Redis caching; postback webhook processing `< 250ms` with async queue dispatch for notifications/emails.