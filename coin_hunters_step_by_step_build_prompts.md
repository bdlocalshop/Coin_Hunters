# PROMPTS.md — Coin Hunters Step-by-Step Build Execution Plan (Antigravity IDE)

Execute these prompts **one phase at a time** inside your Google Antigravity IDE Agent Manager. Wait for each phase to finish and pass its verification checks before running the next prompt.

## Phase 1: Monorepo Scaffolding, Laravel 11 + Filament v3 & Next.js Setup

**Prompt to copy into Antigravity:**

> Read `PRD.md`, `AGENTS.md`, and `RULES.md` thoroughly before making any changes.
> Initialize the **Coin Hunters** monorepo on Windows 11 PowerShell:
>
> 1. Create `/backend` with a fresh Laravel 11+ project (`composer create-project laravel/laravel backend`), install `laravel/sanctum` and `filament/filament:"^3.2"`, and run `php artisan filament:install --panels` to configure the `/admin` panel.
>
> 2. Create `/frontend` with a fresh Next.js App Router project (`npx create-next-app@latest frontend --typescript --tailwind --eslint --app --src-dir --import-alias "@/*"`).
>
> 3. Configure `backend/.env` and `backend/.env.example` for PostgreSQL/MySQL, Redis cache/queue drivers, Sanctum stateful domains (`localhost:3000`), and placeholder keys for `MAXMIND_ACCOUNT_ID`, `MAXMIND_LICENSE_KEY`, `PROXYCHECK_API_KEY`, `OFFERWALL_GG_SECRET`, `OFFERWALL_COM_SECRET`, `LOOTABLY_SECRET`, `BITLABS_SECRET`, and `CPX_RESEARCH_SECRET`.
>
> 4. Create the Domain directory skeleton inside `backend/app/Domain/`: `Ledger`, `Offerwalls`, `Fraud`, `Payouts`, and `Referrals`.
>
> 5. Create a `/docs/ui-reference/` directory in the repository root and add `docs/ui-reference/` to the root `.gitignore` file so UI reference screenshots stay local.
>
> 6. Verify both apps compile cleanly (`php artisan about` in `/backend` and `npm run build` in `/frontend`).

## Phase 2: Database Schema & Immutable Double-Entry Ledger Engine

**Prompt to copy into Antigravity:**

> Read `RULES.md` Section 1 (Financial & Double-Entry Ledger Rules) and `PRD.md` Section 3.
> Implement the core database migrations and the immutable double-entry ledger inside `backend/app/Domain/Ledger`:
>
> 1. Update `users` migration to add: `username` (unique), `avatar_url`, `referral_code` (unique), `referred_by_id` (nullable FK to `users`), `referral_bonus_paid` (boolean, default false), `signup_bonus_paid` (boolean, default false), `available_coins` (`BIGINT UNSIGNED`, default 0), `pending_coins` (`BIGINT UNSIGNED`, default 0), `locked_withdrawal_coins` (`BIGINT UNSIGNED`, default 0), `lifetime_earned_coins` (`BIGINT UNSIGNED`, default 0), `status` (`active|suspended|banned`), `country_code` (char 2, nullable), and `fraud_score` (unsigned int, default 0).
>
> 2. Create `ledger_accounts` (`id`, `code` unique e.g., `asset:network_receivables`, `liability:user_available_balance`, `liability:user_pending_balance`, `liability:user_locked_withdrawal`, `expense:marketing_bonuses`, `asset:crypto_treasury`, `name`, `type`).
>
> 3. Create `ledger_transactions` (`id`, `uuid` unique, `parent_transaction_id` nullable FK, `type`, `reference_type`, `reference_id`, `idempotency_key` unique, `description`, `metadata` JSON, `created_at` — no `updated_at` or `deleted_at`).
>
> 4. Create `ledger_entries` (`id`, `ledger_transaction_id` FK, `ledger_account_id` FK, `user_id` nullable FK, `direction` enum `DEBIT|CREDIT`, `amount` `BIGINT UNSIGNED`, `created_at`).
>
> 5. Implement `LedgerService::post(...)` using `DB::transaction()` and `lockForUpdate()`, validating that $\sum \text{DEBIT} == \sum \text{CREDIT}$ and amounts $> 0$, atomically updating user projection balances, and throwing `ImmutableRecordException` on any model `updating` or `deleting` event.
>
> 6. Seed the system Chart of Accounts (`LedgerAccountSeeder`) and write PHPUnit feature tests verifying balanced entries, immutability exceptions, and idempotency protection. Run `php artisan migrate:fresh --seed` and `php artisan test`.

## Phase 3: Anti-Fraud Intelligence & Sanctum Auth + Signup/Referral Bonuses

**Prompt to copy into Antigravity:**

> Read `RULES.md` Section 3 & Section 5, and `PRD.md` Section 4.3 & 5.1.
> Build the Anti-Fraud engine (`backend/app/Domain/Fraud`), Referral engine (`backend/app/Domain/Referrals`), and Auth endpoints:
>
> 1. Create `user_devices` (`id`, `user_id`, `device_hash`, `ip_address`, `asn`, `country_code`, `is_proxy`, `is_emulator`, `risk_score`, `user_agent`, `timestamps`) and `platform_settings` (`key` unique, `value`, `description`) seeded with `signup_bonus_coins = 250`, `referral_bonus_coins = 500`, `referral_qualification_coins = 1000`, `hold_threshold_coins = 5000`, `hold_duration_days = 14`.
>
> 2. Implement `FraudGuardService` integrating **MaxMind** GeoIP2/minFraud and **ProxyCheck.io** APIs (with graceful fallback/mock mode when keys are empty in local dev), blocking VPNs, Tor, datacenter ASNs, emulators, headless browsers (`webdriver`), and duplicate banned `device_hash` collisions.
>
> 3. Implement `ReferralService::checkAndRewardQualification(User $referredUser)` which checks if `$referredUser->lifetime_earned_coins >= referral_qualification_coins` and `!$referredUser->referral_bonus_paid`, then credits the referrer via `LedgerService` from `expense:marketing_bonuses` in a single flat one-time payout.
>
> 4. Build `/api/v1/auth/register`, `/api/v1/auth/login`, `/api/v1/auth/logout`, and `/api/v1/auth/me` with FormRequest validation, Redis rate limiting (`5/min`), `FraudGuardService` screening, and one-time `signup_bonus_coins` ledger credit upon verification.
>
> 5. Write Pest/PHPUnit tests covering proxy blocking, device collision blocking, signup bonus crediting, and flat referral qualification.

## Phase 4: Direct Offerwall API Ingestion & Three-Gate S2S Postbacks

**Prompt to copy into Antigravity:**

> Read `RULES.md` Section 2 and `PRD.md` Section 4.2 & 5.2.
> Implement the Offerwall & Survey integration engine inside `backend/app/Domain/Offerwalls`:
>
> 1. Create database tables:
>
>    * `offerwall_providers` (`id`, `slug` unique: `offerwall_gg`, `offerwall_com`, `lootably`, `bitlabs`, `cpx_research`, `name`, `logo_url`, `payout_margin_percent` default 70, `bonus_multiplier` default 1.00, `allowed_ips` JSON, `secret_key`, `api_key`, `app_id`, `is_active` boolean).
>
>    * `offers` (`id`, `provider_slug`, `external_offer_id`, `title`, `description`, `requirements` JSON, `icon_url`, `network_payout_usd` decimal(10,4), `user_payout_coins` `BIGINT UNSIGNED`, `device_targets` JSON, `countries` JSON, `categories` JSON, `click_url_template`, `epc_score`, `is_featured` boolean, `is_active` boolean, unique `(provider_slug, external_offer_id)`).
>
>    * `offer_clicks` (`id`, `click_uuid` unique, `user_id`, `offer_id` nullable, `provider_slug`, `ip_address`, `device_hash`, `status` enum `clicked|pending|completed|reversed`, `created_at`).
>
>    * `postback_logs` (`id`, `provider_slug`, `external_transaction_id` nullable, `ip_address`, `headers` JSON, `payload` JSON, `status` enum `credited|held|duplicate|rejected_ip|rejected_sig|reversed|error`, `message`, `created_at`).
>
> 2. Create `OfferwallAdapterInterface` and concrete adapters for **Offerwall.gg**, **Offerwall.com**, **Lootably**, **BitLabs**, and **CPX Research** supporting:
>
>    * Direct campaign feed syncing (`php artisan offers:sync` scheduled hourly + Redis caching by country & OS).
>
>    * Live targeted survey fetching for BitLabs & CPX Research.
>
>    * Signature verification (`verifySignature(Request $request)`).
>
> 3. Build authenticated API endpoints:
>
>    * `GET /api/v1/offers` (paginated, filterable by `device`, `category`, `provider`, `sort`, cached in Redis).
>
>    * `GET /api/v1/surveys` (live matched surveys from BitLabs & CPX Research).
>
>    * `GET /api/v1/offer-walls` (active provider cards with bonus badges).
>
>    * `GET /api/v1/my-offers` (user's `offer_clicks` grouped by `all`, `clicked`, `pending`, `completed`).
>
>    * `POST /api/v1/offers/{offer}/click` (runs `FraudGuardService`, generates `click_uuid`, injects `subId`, returns signed redirect URL).
>
> 4. Build the S2S webhook route `ANY /api/v1/postbacks/{provider}` protected by `VerifyProviderIpMiddleware`, adapter cryptographic signature verification, and database idempotency (`UNIQUE(provider_slug, external_transaction_id)`). Route payouts $\ge$ `hold_threshold_coins` to `pending_coins`, trigger `ReferralService`, and add a scheduled command `php artisan ledger:release-holds` to release expired holds.
>
> 5. Write thorough PHPUnit tests simulating valid postbacks, invalid IPs (403), invalid signatures (401), duplicate postbacks (200 idempotent), high-value holds, and chargeback reversals.

## Phase 5: Manual Crypto Payout System & Filament v3 Admin Panel

**Prompt to copy into Antigravity:**

> Read `RULES.md` Section 4 and `PRD.md` Section 4.4 & Section 6.
> Implement the Manual Crypto Payout domain (`backend/app/Domain/Payouts`) and complete Filament v3 backoffice (`backend/app/Filament`):
>
> 1. Create tables:
>
>    * `crypto_payout_methods` (`id`, `symbol` e.g. `LTC`, `USDT_TRC20`, `USDT_BEP20`, `BTC`, `SOL`, `name`, `network`, `icon_url`, `min_coins` `BIGINT UNSIGNED`, `fee_coins` `BIGINT UNSIGNED`, `address_regex`, `is_active` boolean).
>
>    * `withdrawal_requests` (`id`, `uuid` unique, `user_id` FK, `crypto_payout_method_id` FK, `coins_amount` `BIGINT UNSIGNED`, `usd_amount` decimal(10,2), `wallet_address`, `status` enum `pending|approved|rejected`, `tx_hash` nullable, `rejection_reason` nullable, `reviewed_by_admin_id` nullable, `reviewed_at` nullable, `timestamps`).
>
> 2. Implement `PayoutService`:
>
>    * `requestWithdrawal(User $user, int $methodId, int $coins, string $walletAddress)`: validates minimum threshold, wallet regex, and Redis rate limit (`3/hour`), immediately debits `available_coins` into `locked_withdrawal_coins` via `LedgerService`, and creates a `pending` withdrawal request.
>
>    * `approveWithdrawal(WithdrawalRequest $withdrawal, User $admin, string $txHash)`: requires non-empty `tx_hash`, debits `locked_withdrawal_coins` to `asset:crypto_treasury` via `LedgerService`, marks status `approved`.
>
>    * `rejectWithdrawal(WithdrawalRequest $withdrawal, User $admin, string $reason)`: posts compensating ledger reversal crediting `available_coins`, marks status `rejected`.
>
> 3. Build API endpoints: `GET /api/v1/shop/methods`, `POST /api/v1/shop/withdraw`, `GET /api/v1/shop/withdrawals`, `GET /api/v1/public/stats` (live metrics + recent earnings ticker feed), and `GET /api/v1/leaderboard`.
>
> 4. Build Filament v3 Admin Resources & Dashboard Widgets:
>
>    * Dashboard KPIs: Gross Network Revenue (USD), Coin Liability Issued, Net Profit Margin, Pending Withdrawals Count, Flagged Fraud Events.
>
>    * `WithdrawalRequestResource` with custom table actions **Approve & Mark Paid** (modal requiring `tx_hash`) and **Reject & Refund** (modal requiring `rejection_reason`).
>
>    * `UserResource` (view ledger entries, device/IP collisions, suspend/ban actions), `OfferwallProviderResource`, `OfferResource`, `LedgerTransactionResource` (read-only audit view), and `PostbackLogResource` (read-only webhook inspector).
>
> 5. Seed initial crypto methods (LTC, USDT TRC20, BTC, SOL) and an admin user (`admin@coinhunters.local`), then run `php artisan test`.

## Phase 6: Next.js Frontend — Coinhub Dark-Mode Shell & Public Landing Page

**Prompt to copy into Antigravity:**

> Read `PRD.md` Section 4.1, `AGENTS.md` Section 2.5, and `RULES.md` Section 5 (strictly zero unrequested gamification: no spin wheels, no daily streaks, no daily task checklists).
> **Visual Reference Directive:** Inspect the UI screenshots inside `/docs/ui-reference/` (`home.jpg`, `offer-walls.jpg`, `surveys.jpg`, `my-offers.jpg`, `shop.jpg`, `referrals.jpg`) to match the exact deep warm-charcoal dark theme (`#120E0E` / `#181313`), coral-orange gradient buttons, card border styles, top live earnings ticker pills, and left sidebar proportions. Strictly follow `PRD.md` and `RULES.md` for scope—do NOT build the 7-day daily streak box, spin wheel, or multi-tier affiliate cards shown in the screenshots.
> Build the Next.js frontend foundation in `/frontend/src`:
>
> 1. Configure Tailwind CSS with the dark-mode gamer/fintech palette extracted from `/docs/ui-reference/` (`#120E0E` deep warm-charcoal canvas, `#181313` elevated card surfaces, `#261D22` subtle borders, `#FF5A36` / `#F97316` coral-orange primary gradient CTAs, `#FACC15` gold Coin accents, `#10B981` emerald live status indicators).
>
> 2. Create a strongly typed API client (`src/lib/api.ts`) with Laravel Sanctum Bearer token handling, error normalization, and TypeScript interfaces (`src/types/api.ts`).
>
> 3. Implement the deterministic client device fingerprint collector (`src/lib/fingerprint.ts`) capturing Canvas/WebGL hash, screen metrics, timezone, and `navigator.webdriver` detection to attach as `X-Device-Hash` headers on auth and offer click requests.
>
> 4. Build the persistent App Shell (`src/components/layout/AppShell.tsx`):
>
>    * **Left Sidebar:** Coin Hunters logo, primary nav (`Home`, `Earn`, `Offer Walls`, `Offers`, `Surveys` with live count badge, `Leaderboard`, `Shop`), authenticated `ACCOUNT` section (`My Offers`, `Referrals`), and bottom Welcome Bonus callout card. Responsive mobile drawer.
>
>    * **Top Header Bar:** Live horizontally scrolling **Recent Earnings Ticker** pills (Avatar, Username, Offer/Activity, `+Coins`), live Coin balance pill (`1,000 Coins = $1.00` tooltip), and `Sign Up` / `Sign In` modal triggers or authenticated user dropdown.
>
>    * **Footer:** Clean multi-column footer linking to `How It Works`, `FAQ`, `Terms of Service`, `Privacy Policy`, and `Contact`.
>
> 5. Build the **Public Home Page (`/`)** featuring the Hero split layout (Headline + Top 3 Featured High-Payout Offers widget + inline Quick Registration card with Signup Bonus badge), 3-column Live Platform Metrics bar, 3-step "Ready to earn? Here's how" cards, Trusted Providers logo strip (`Offerwall.gg`, `Offerwall.com`, `BitLabs`, `CPX Research`, `Lootably`), and bottom conversion CTA banner.

## Phase 7: Authenticated Next.js Views (Earn, Offers, Surveys, Shop, Referrals, Legal)

**Prompt to copy into Antigravity:**

> Complete all remaining Next.js pages inside `/frontend/src/app` connected to the Laravel REST API, maintaining visual consistency with `/docs/ui-reference/` (`offer-walls.jpg`, `surveys.jpg`, `my-offers.jpg`, `shop.jpg`, `referrals.jpg`):
>
> 1. **`/earn` (Unified Earn Hub):** Featured Offers carousel, Top Mobile Games grid, Quick Surveys preview, and Partner Offerwalls strip.
>
> 2. **`/offers` (Direct Offer Catalog):** Filter bar (`All`, `Android`, `iOS`, `Desktop`, category pills, sort dropdown), offer cards with Coin reward + USD equivalent, and an **Offer Detail Modal** displaying step-by-step instructions, device badge, and `Start Offer` button calling `POST /api/v1/offers/{id}/click`.
>
> 3. **`/offer-walls` (Partner Hub):** Grid of the 5 integrated networks (`Offerwall.gg`, `Offerwall.com`, `Lootably`, `BitLabs`, `CPX Research`) with active bonus badges and launch modals.
>
> 4. **`/surveys` (Survey Wall):** Live matched surveys from BitLabs and CPX Research showing estimated minutes and Coin rewards, plus direct partner wall cards.
>
> 5. **`/my-offers` (Conversion Tracker):** Filter tabs (`All`, `Clicked`, `Pending`, `Completed`) with status badges and timestamps.
>
> 6. **`/shop` (Crypto Payout Store):** Header card displaying `1,000 = $1` rate, user's available & pending Coin balances, crypto method cards (`Litecoin`, `USDT TRC20`, `Bitcoin`, `Solana`), interactive **Withdraw Modal** with wallet address validation & Coin calculator, and a **Withdrawal History** table showing `Pending`, `Approved` (with clickable blockchain explorer `tx_hash` link), or `Rejected` (with reason).
>
> 7. **`/referrals` (Flat One-Time Affiliate Page):** Stat cards (`Total Referrals`, `Qualified Referrals`, `Lifetime Referral Coins`), one-click copy invite link box, clear explanation of the flat one-time referral qualification bonus, and Referred Users status table.
>
> 8. **`/leaderboard` & Legal Pages:** Top earners leaderboard (`24h` / `7d` / `30d`) and clean typography pages for `/how-it-works`, `/terms`, `/privacy`, and `/faq`.
>
> 9. Run `npm run lint` and `npm run build` in `/frontend` and verify zero TypeScript or build errors.