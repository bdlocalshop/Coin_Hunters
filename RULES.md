# RULES.md — Coin Hunters Non-Negotiable Engineering & Security Guardrails

These rules are mandatory for every agent, developer, and automated task operating within the **Coin Hunters** monorepo (`/backend` Laravel + Filament v3 and `/frontend` Next.js). Any code violating these rules must be rejected and refactored immediately.

---

## 1. Financial & Double-Entry Ledger Rules

1. **Integer-Only Coin Representation:**
   * All internal Coin values MUST be stored as `BIGINT UNSIGNED` integers (`1,000 Coins = $1.00 USD`).
   * **NEVER** use `FLOAT`, `DOUBLE`, or unscaled decimals for Coin balances or ledger amounts.
   * USD conversions (`coins / 1000`) are strictly for display formatting or external network payout calculations using `bcmath` / `Brick\Math`.

2. **Single Source of Truth (`LedgerService`):**
   * User balance columns (`available_coins`, `pending_coins`, `locked_withdrawal_coins`, `lifetime_earned_coins`) MUST **NEVER** be mutated directly via Eloquent (`$user->increment(...)`, `$user->update(...)`) or raw SQL outside `App\Domain\Ledger\Services\LedgerService`.
   * Every balance mutation must execute inside a `DB::transaction()` block using pessimistic row-level locking (`User::where('id', $userId)->lockForUpdate()->first()`).

3. **Zero-Sum Double-Entry Enforcement:**
   * Every `LedgerTransaction` MUST contain at least two `LedgerEntry` rows where:
     $$\sum \text{DEBIT amounts} == \sum \text{CREDIT amounts}$$
   * `LedgerService::post()` must throw an `UnbalancedLedgerException` and roll back the transaction if debits do not strictly equal credits or if any entry amount is $\le 0$.

4. **Strict Immutability:**
   * `ledger_transactions` and `ledger_entries` are strictly **append-only**.
   * Model lifecycle hooks (`updating`, `deleting`) on `LedgerTransaction` and `LedgerEntry` MUST throw an `ImmutableRecordException`.
   * Chargebacks, postback reversals, and rejected withdrawals MUST be recorded as a new compensating `LedgerTransaction` referencing `parent_transaction_id`.

---

## 2. S2S Postback & Offerwall Integration Rules

1. **Three-Gate Webhook Pipeline:**
   Every incoming S2S postback (`/api/v1/postbacks/{provider}`) MUST pass three sequential checks before crediting any user:
   * **Gate 1 — IP/CIDR Whitelist:** Verify the originating IP against the provider's allowlist stored in `offerwall_providers.allowed_ips`. Reject unauthorized IPs with HTTP `403`.
   * **Gate 2 — Cryptographic Signature / Secret Verification:** Validate the provider's HMAC-SHA256 / MD5 hash or secret token using constant-time comparison (`hash_equals()`). Reject invalid signatures with HTTP `401`.
   * **Gate 3 — Idempotency Lock:** Enforce a database `UNIQUE` constraint on `(provider, external_transaction_id)`. If a duplicate transaction arrives, log it in `postback_logs` and return HTTP `200 OK` immediately without crediting Coins again.

2. **Raw Audit Logging:**
   * Every inbound postback request (valid, invalid, duplicate, or error) MUST be logged to `postback_logs` with headers, IP address, query/body payload, verification status, and processing outcome.

3. **High-Value Conversion Hold:**
   * Conversions exceeding the admin-configured hold threshold (default: `>= 5,000 Coins` / `$5.00`) MUST credit `liability:user_pending_balance` (`pending_coins`) with a `release_at` timestamp rather than immediate `available_coins`.

---

## 3. Anti-Fraud & Security Rules

1. **Mandatory Proxy, VPN & Emulator Screening:**
   * Registration, login, and outbound offer click generation (`POST /api/v1/offers/{offer}/click`) MUST invoke the `FraudGuardService` (MaxMind + ProxyCheck.io + client fingerprint inspection).
   * Block requests flagged as VPN, Tor, datacenter/hosting ASN, known Android emulator, or headless browser (`webdriver = true`).

2. **One Account Per Person / Device Policy:**
   * Store deterministic client device fingerprints (`device_hash`) and IP history on `user_devices`.
   * Automatically flag or block new registrations that collide with an existing banned device hash or exceed the household IP account limit (default: `1 account per device`, `max 2 per residential IP`).

3. **Server-Side Click Attribution:**
   * Never expose raw unauthenticated offerwall tracking URLs to the client.
   * When a user clicks an offer, the backend generates a unique `click_uuid` (`offer_clicks` table), embeds `click_uuid` into the provider's `subId` parameter, and returns the redirect URL. Postbacks must reconcile against `click_uuid` or verified `user_id`.

4. **Rate Limiting & Secret Hygiene:**
   * Apply Redis rate limits to authentication (`5/min`), offer clicks (`20/min`), and withdrawal submissions (`3/hour`).
   * Never commit `.env`, API keys, MaxMind license keys, or postback secrets to Git. Keep `backend/.env.example` and `frontend/.env.example` updated with placeholder keys.

---

## 4. Manual Crypto Payout Rules

1. **Immediate Escrow Locking:**
   * When a user submits a crypto withdrawal request (LTC, USDT, BTC, SOL), `LedgerService` immediately debits `available_coins` and credits `locked_withdrawal_coins`. A user cannot spend locked Coins on duplicate withdrawal requests.

2. **Wallet Address Validation:**
   * Validate destination wallet addresses against strict network-specific regex/checksum rules before creating the withdrawal record.

3. **Two-State Admin Resolution (Filament v3):**
   * **Approve & Mark Paid:** Admin must supply a non-empty blockchain transaction hash (`tx_hash`). The ledger debits `locked_withdrawal_coins` and credits `asset:crypto_treasury`.
   * **Reject & Refund:** Admin must supply a rejection reason. The ledger posts a reversal debiting `locked_withdrawal_coins` and crediting `available_coins`.

---

## 5. Scope & Product Discipline Rules

1. **Strict Scope Adherence (No Unrequested Gamification):**
   * **DO NOT** build daily login streaks, spin-the-wheel modals, daily quest checklists, XP levels, loot boxes, or multi-tier referral commissions.
   * **ONLY** build the approved retention features:
     * One-time **Signup Bonus** (credited upon email verification + passing fraud check).
     * **Flat One-Time Referral Bonus** (credited to referrer once the referred user passes fraud screening and earns the configured Coin threshold from valid offers).

2. **Monorepo & Code Quality Standards:**
   * **Backend (`/backend`):** PHP 8.3+, Laravel 11+, strict typing (`declare(strict_types=1);` in all new PHP files), FormRequest validation on every API endpoint, and API Resources for JSON responses.
   * **Frontend (`/frontend`):** Next.js App Router, TypeScript (`strict: true`, zero `any` types), Tailwind CSS dark-mode theme matching the Coinhub reference aesthetic, and reusable typed API client wrappers.
   * **Windows 11 Compatibility:** All composer, artisan, and npm scripts must execute cleanly in PowerShell without relying on Bash-only syntax.