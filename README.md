# Lumora

**Your imagination, beautifully rendered.**

Lumora is a production-shaped AI image generation studio built with Laravel, Vue 3 and Inertia. It combines a prompt-to-image workspace with a prepaid credit wallet, a searchable gallery, a billing ledger and an admin dashboard for revenue, provider cost and profit.

---

## Highlights

- **Studio** — prompt, Pixelforge canvas sizes, standard/HD quality, reference image URLs, and a live price estimate showing provider cost, the 25% markup and the exact customer charge before a credit is held.
- **Asynchronous rendering** — a queued submit job, HMAC-verified webhooks, polling fallback, stale-generation expiry and rate limiting. Failed renders are refunded automatically.
- **Integer money only** — all amounts are stored as micro-dollars (1 USD = 1,000,000 micros). One credit is $0.01. Estimated and actual costs are stored separately.
- **Exactly-once wallet** — every movement is an append-only ledger row. A unique `(generation_id, type)` index plus row locking guarantees a generation can never be charged or refunded twice.
- **Gallery** — responsive grid, status filters, prompt search, pagination and a detail modal with the full cost breakdown.
- **Billing** — balance, purchased/bonus/consumed totals, idempotent demo purchases and a complete transaction ledger.
- **Admin** — revenue vs provider cost charts, profit, success/failure health, top spenders and provider usage reconciliation.
- **Themes & SEO** — light/dark/system theme with persistence, server-rendered meta/canonical/OpenGraph/JSON-LD, sitemap, robots and `noindex` on private pages.

## Stack

| Layer | Choice |
| --- | --- |
| Backend | Laravel 13, PHP 8.3+ |
| Frontend | Vue 3 Composition API, Inertia.js, Tailwind CSS v4 |
| Database | MySQL (SQLite is used for tests) |
| Queue / cache / sessions | Database drivers by default |
| Icons | `lucide-vue-next` |
| Fonts | Shantell Sans, Outfit, Quicksand |

## Documentation

- **User guide** — `/guide`, linked in the site footer after Terms. A plain-language walkthrough of rendering, prompts, canvas sizes, credits, the gallery, settings and troubleshooting.
- **Developer guide** — `/developer-guide`. Architecture, project map, local setup, the `LUMORA_*`/`PIXELFORGE_*` configuration, wiring real API keys and webhooks, the generation lifecycle, money model, key classes, testing and deployment.

Both guides are Inertia pages rendered from `resources/js/features/docs/`, use the same theme system as the app, and are included in the sitemap.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate

# Configure the database in .env, then:
php artisan migrate --seed
npm install
npm run build

php artisan serve
```

In a second terminal, run the queue worker:

```bash
php artisan queue:work
```

The scheduler drives polling, reconciliation and expiry:

```bash
php artisan schedule:work
```

For frontend development use `npm run dev` instead of `npm run build`.

### Demo accounts

Seeded by `php artisan migrate:fresh --seed`:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@lumora.app` | `password` |
| Creator | `demo@lumora.app` | `password` |

The demo data includes 20 succeeded generations, one failed generation and a pending render, plus ledger entries that reconcile to the wallet balances. Showcase imagery uses Unsplash URLs so the landing page is populated without provider credentials.

## Pixelforge integration

The integration is configurable because the final Pixelforge specification and credentials are supplied per environment. Every path, header name and signature format lives in `config/lumora.php` and can be overridden from `.env`:

```dotenv
PIXELFORGE_BASE_URL=https://api.pixelforge.dev
PIXELFORGE_API_KEY=
PIXELFORGE_WEBHOOK_SECRET=
PIXELFORGE_TIMEOUT=30
PIXELFORGE_CONNECT_TIMEOUT=10
PIXELFORGE_REQUESTS_PER_MINUTE=60
PIXELFORGE_GENERATIONS_PATH=/v1/generations
PIXELFORGE_USAGE_PATH=/v1/usage
PIXELFORGE_SIGNATURE_HEADER=X-Pixelforge-Signature
PIXELFORGE_TIMESTAMP_HEADER=X-Pixelforge-Timestamp
PIXELFORGE_SIGNATURE_TOLERANCE=300
PIXELFORGE_SIGNATURE_SCHEME=timestamped
```

### Request flow

1. `POST /v1/generations` with the prompt, size, quality, `n = 1`, reference images and `metadata.generation_uuid`.
2. An `Idempotency-Key` of `lumora-generation-{uuid}` is sent so retries are safe.
3. Webhooks are received at `POST /webhooks/pixelforge`. The signature is checked against the raw body:
   - `timestamped` (default): `HMAC-SHA256("{timestamp}.{rawBody}")`, accepting a plain hex digest or a Stripe-style `t=...,v1=...` header.
   - `raw`: `HMAC-SHA256(rawBody)`.
4. Event IDs are deduplicated in `webhook_events`. Events older than the last applied transition are ignored, unmatched events are stored and replayed once the generation can be linked.
5. If webhooks are delayed, `lumora:poll-generations` polls `GET /v1/generations/{id}` on a schedule. Polling also expires stale jobs and refunds them.
6. `GET /v1/usage` is read by the admin “Reconcile usage” action and hourly by `lumora:reconcile-usage`.

### Event shapes

The processor accepts the common variants rather than assuming one exact payload: generation IDs under `id`, `generation_id`, `data.id` or `data.generation_id`; status under `status`, `data.status` or `generation.status`; image URLs under `data.images[].url`, `data.image_url`, `data.output[].url`, `output[].url`, `image_url` or `url`; usage under `usage` or `data.usage`; explicit costs under `cost_usd`, `cost`, `provider_cost_usd` and their `data.*` equivalents.

Actual provider cost resolution order:

1. Explicit reported cost (if present).
2. Token usage priced with the configured per-million rates.
3. The size/quality rate card.

The customer charge is always provider cost plus the configured markup (default `2500` basis points = 25%), rounded up.

> **Before going live:** compare `PIXELFORGE_*` values against the official Pixelforge documentation and credentials you receive. If the provider uses a different path, header or signature scheme, change the environment values only — no code changes are required.

### Provider test keywords

Pixelforge-style sandbox prompts such as `[fail]`, `[slow]` and `[duplicate]` are passed through verbatim. The test suite verifies that:

- `[fail]` renders that fail through a webhook are refunded in full.
- `[slow]`-style processing stays non-terminal and is handled by polling/expiry.
- `[duplicate]` webhook deliveries settle a generation exactly once.

If your Pixelforge account exposes these test words only in a specific test environment, enable that environment and use the same prompts from the studio.

## Money model

- `users.credit_balance_micros` — spendable balance.
- `credit_transactions` — append-only ledger with `amount_micros` and `balance_after_micros`.
- On creation the **estimated** charge is debited as a `hold`.
- On success the hold is replaced by the **actual** charge:
  - actual < held → `settlement` refund of the difference;
  - actual > held → capped `settlement` debit (wallets never go negative; any shortfall is recorded in `meta`);
  - equal → zero-amount `settlement` for auditability.
- On failure the full hold is returned as a `refund` and the customer charge is `0`.
- Purchases and welcome bonuses are idempotent through a unique `reference`.

## Testing

```bash
php artisan test          # 55 unit + feature tests
vendor/bin/pint --test    # code style
npm run build             # frontend production build
```

Tests run against in-memory SQLite (see `phpunit.xml`) and cover pricing and markup, wallet holds/settlements/refunds, idempotency, out-of-order events, HMAC verification, validation, authorization, admin metrics, billing purchases, profile/password changes and account deletion.

## Production deployment

Lumora needs a standard PHP host with a persistent database and a queue worker. The app itself does not require a separate Node server — `npm run build` produces static assets in `public/build`.

1. **Provision** PHP 8.3+, a MySQL database, and a writable `storage/` + `bootstrap/cache/`.
2. **Configure `.env`** with production values:
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain.com`, generated `APP_KEY`.
   - Database credentials.
   - `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`.
   - A real mailer for password resets.
   - Pixelforge credentials and webhook secret.
3. **Build and migrate**:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   npm ci && npm run build
   ```
4. **Run workers**:
   ```bash
   php artisan queue:work --tries=5 --timeout=120
   php artisan schedule:work
   ```
   Use Supervisor (or your platform's worker feature) for the queue and a cron entry for `php artisan schedule:run` if you prefer cron over `schedule:work`.
5. **Point Pixelforge** at `https://your-domain.com/webhooks/pixelforge` and set `PIXELFORGE_WEBHOOK_SECRET` to the same secret.
6. **Search engines**:
   - Confirm `https://your-domain.com/robots.txt` and `/sitemap.xml` resolve over HTTPS.
   - Add the domain to Google Search Console and submit the sitemap.
   - Private pages (`/dashboard`, `/generate`, `/creations`, `/billing`, `/settings`, `/admin`) are disallowed in `robots.txt` and served with `noindex`.
7. **Smoke test**: register, confirm the welcome bonus, buy a package, run a render, verify the webhook settles it, check the admin dashboard and reconcile against provider usage.

### Deployment status

A **public deployment is not included** in this repository because it requires hosting, a domain, database and Pixelforge credentials owned by you. Once those are available, the checklist above is the complete deployment path. The app is already build-clean and test-clean locally.

## Project structure

```
app/
  Http/Controllers/{Auth,Admin,Webhooks}/
  Http/Requests/            # generation, purchase, profile, password validation
  Jobs/                     # SubmitGenerationJob, PollGenerationJob
  Models/                   # User, Generation, CreditTransaction, WebhookEvent, ProviderUsageRecord
  Policies/GenerationPolicy.php
  Services/
    Billing/CreditService.php
    Generations/{GenerationService,GenerationFinalizer,WebhookProcessor}.php
    Pixelforge/{PixelforgeClient,PricingCalculator,UsageReconciler,WebhookSignature}.php
    Admin/AdminMetrics.php
  Support/{Money,Seo}.php
resources/js/
  components/{ui,brand,layout,charts}/
  composables/              # theme, toast, seo, polling, sidebar, clipboard, click-outside
  features/<feature>/pages/ # Inertia pages
  layouts/                  # AppLayout, AuthLayout
config/lumora.php           # brand, SEO, packages, sizes, pricing, provider
```

## Security notes

- Webhooks are CSRF-exempt by design but require a valid HMAC signature and a fresh timestamp.
- Provider secrets and API keys are server-only; they are never shared with Inertia props.
- Authorization is enforced by `GenerationPolicy` and the `admin` middleware.
- Passwords use Laravel's hashed cast; account deletion requires the current password.
- Money and provider cost fields are integers, never floats.

## License

Lumora is an original application built for this project. Third-party dependencies retain their own licenses.
