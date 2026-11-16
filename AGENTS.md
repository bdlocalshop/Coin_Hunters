# AGENTS.md — Coin Hunters Autonomous Engineering Directives

## 1. Repository & Monorepo Topology
This repository is a strict **Monorepo** managed inside Google Antigravity on a Windows 11 development environment. Agents must respect workspace boundaries and never mix backend and frontend dependencies.

```text
coin-hunters/
├── PRD.md                  # Master Product Requirements Document
├── AGENTS.md               # Agent roles, architecture & execution protocols
├── RULES.md                # Non-negotiable coding, security & financial constraints
├── PROMPTS.md              # Phased step-by-step build execution plan
├── backend/                # Laravel 11+ REST API + Filament PHP v3 Admin Panel
│   ├── app/
│   │   ├── Domain/
│   │   │   ├── Ledger/     # Immutable double-entry accounting engine
│   │   │   ├── Offerwalls/ # Direct API ingestion & S2S postback adapters
│   │   │   ├── Fraud/      # MaxMind, ProxyCheck, fingerprint & velocity guards
│   │   │   ├── Payouts/    # Manual crypto withdrawal escrow & admin approval
│   │   │   └── Referrals/  # Flat one-time qualification & bonus engine
│   │   ├── Filament/       # Admin resources, widgets, payout & fraud queues
│   │   └── Http/Controllers/Api/V1/
│   ├── database/
│   └── routes/api.php
└── frontend/               # Next.js (App Router, TypeScript, Tailwind CSS)
    ├── src/
    │   ├── app/            # Route groups: (public), (auth), (dashboard)
    │   ├── components/     # Coinhub-inspired dark UI shell, sidebar, ticker, modals
    │   ├── lib/            # Typed API client, Sanctum auth, device fingerprint collector
    │   └── types/          # Shared API response interfaces
```

---

## 2. Specialized Agent Personas & Responsibilities

When executing tasks in Antigravity, adopt the specialized domain constraints corresponding to the active directory:

### 2.1 Backend & Ledger Architect (`backend/app/Domain/Ledger`, `backend/database`)
* **Primary Focus:** Database schema design, PostgreSQL/MySQL transactional integrity, and the immutable double-entry ledger.
* **Mandates:**
  * All monetary values are stored in whole **Coins** (`BIGINT UNSIGNED`, `1,000 Coins = $1.00 USD`).
  * Never modify user coin balances via direct `UPDATE users SET available_coins = ...` outside the `LedgerService`.
  * Every balance change must execute inside `DB::transaction()` with row-level locking (`lockForUpdate()`), creating a `ledger_transactions` record and balanced `DEBIT`/`CREDIT` rows in `ledger_entries`.
  * Enforce database-level immutability: never write `update()` or `delete()` methods on `LedgerTransaction` or `LedgerEntry` Eloquent models.
  * Chargebacks and postback reversals must always generate a new compensating transaction referencing `parent_transaction_id`.

### 2.2 Offerwall & Postback Integration Engineer (`backend/app/Domain/Offerwalls`)
* **Primary Focus:** Direct Campaign API syncers and S2S postback webhook controllers for **Offerwall.gg**, **Offerwall.com**, **Lootably**, **BitLabs**, and **CPX Research**.
* **Mandates:**
  * Implement a clean `OfferwallAdapterInterface` with dedicated provider classes (`OfferwallGgAdapter`, `OfferwallComAdapter`, `LootablyAdapter`, `BitLabsAdapter`, `CpxResearchAdapter`).
  * Normalize external campaigns into a unified `offers` table (`external_offer_id`, `provider`, `title`, `description`, `icon_url`, `payout_coins`, `device_targets`, `countries`, `categories`, `click_url_template`, `epc_score`) and cache geo/device-filtered feeds in Redis.
  * Every S2S postback endpoint (`/api/v1/postbacks/{provider}`) must pass three sequential security gates:
    1. Provider IP/CIDR whitelist middleware.
    2. Cryptographic HMAC/MD5/secret token signature verification.
    3. Database-level idempotency lock (`UNIQUE(provider, external_transaction_id)`).
  * Route high-payout conversions (`>= threshold` configured in admin) to `pending_coins` hold status rather than immediate `available_coins`.

### 2.3 Anti-Fraud & Security Specialist (`backend/app/Domain/Fraud`, `frontend/src/lib/fingerprint.ts`)
* **Primary Focus:** Blocking VPNs/proxies, emulators, multi-accounting, and click/postback abuse.
* **Mandates:**
  * Integrate **MaxMind** (GeoIP2 / minFraud) and **ProxyCheck.io** checks on registration, login, and outbound offer click generation (`/api/v1/offers/{id}/click`).
  * Collect deterministic client device fingerprints in Next.js (Canvas/WebGL hash, screen metrics, audio context, `navigator.webdriver` flags, User-Agent client hints) and reject duplicate accounts sharing device hashes or high-risk proxy/hosting ASNs.
  * Block known Android emulator signatures on mobile-targeted web offer clicks.
  * Enforce Redis sliding-window rate limiters on auth, offer clicks, and withdrawal requests.

### 2.4 Filament v3 Admin & Manual Payout Engineer (`backend/app/Filament`, `backend/app/Domain/Payouts`)
* **Primary Focus:** Operational backoffice for manual crypto payout processing, offer margin tuning, and fraud review.
* **Mandates:**
  * Build Filament v3 resources for `Users`, `Offers`, `OfferwallProviders`, `LedgerTransactions`, `PostbackLogs`, and `WithdrawalRequests`.
  * **Manual Crypto Payout Workflow:**
    * When a user requests a payout (LTC, USDT, BTC, SOL), immediately debit `available_coins` into `locked_withdrawal_coins` via the ledger.
    * In Filament's `WithdrawalRequests` resource, provide **Approve & Mark Paid** (requires admin to input blockchain `tx_hash`, finalizes ledger debit to `asset:crypto_treasury`) and **Reject & Refund** (requires rejection reason, posts a compensating ledger reversal crediting `available_coins`).
  * Provide admin actions to ban users, inspect device/IP collisions, release held pending coins early, and adjust network profit margins.

### 2.5 Frontend UI/UX Engineer (`frontend/src`)
* **Primary Focus:** High-contrast dark-mode Next.js App Router frontend modeled after the Coinhub aesthetic.
* **Mandates:**
  * Implement the persistent layout shell: Left navigation sidebar (`Home`, `Earn`, `Offer Walls`, `Offers`, `Surveys`, `Leaderboard`, `Shop`, `My Offers`, `Referrals`), top live earnings ticker, live Coin balance badge (`1,000 Coins = $1.00`), and mobile drawer fallback.
  * Strictly exclude gamification clutter (no spin wheels, daily login streaks, daily quest checklists, or multi-tier referral trees).
  * Build clean typed API hooks consuming Laravel Sanctum endpoints with graceful loading skeletons and error toasts.

---

## 3. Antigravity Execution Workflow

1. **Sequential Execution:** Follow `PROMPTS.md` strictly phase by phase. Do not scaffold future phases prematurely.
2. **Verify Before Advancing:** After generating migrations, services, or API endpoints, run verification commands (`php artisan migrate:fresh --seed`, `php artisan test`, `npm run build` / `tsc --noEmit`) before marking a task complete.
3. **Environment Hygiene:** Keep all API secrets, MaxMind/ProxyCheck keys, and offerwall postback secrets in `backend/.env` (with `.env.example` kept in sync) and public API base URLs in `frontend/.env.local`.
4. **Windows 11 Compatibility:** Ensure all CLI scripts, artisan commands, and Next.js build scripts run cleanly in Windows PowerShell without Unix-only shell dependencies.