# Lumora

**Your imagination, beautifully rendered.**

Lumora is a production-shaped AI image generation studio built with Laravel, Vue 3 and Inertia. It combines a prompt-to-image workspace with a prepaid credit wallet, a searchable gallery, a billing ledger and an admin dashboard for revenue, provider cost and profit.

---

## Highlights

- **Studio** - prompt, Pixelforge canvas sizes, low/medium/high quality, reference image URLs, and a live price estimate showing provider cost, the 25% markup and the exact customer charge before a credit is held.
- **Asynchronous rendering** — a queued submit job, HMAC-verified webhooks, polling fallback, stale-generation expiry and rate limiting. Failed renders are refunded automatically.
- **Integer money only** — all amounts are stored as micro-dollars (1 USD = 1,000,000 micros). One credit is $0.01. Estimated and actual costs are stored separately.
- **Exactly-once wallet** — every movement is an append-only ledger row. A unique `(generation_id, type)` index plus row locking guarantees a generation can never be charged or refunded twice.
- **Gallery** — responsive grid, status filters, prompt search, pagination and a detail modal with the full cost breakdown.
- **Billing** — balance, purchased/bonus/consumed totals, idempotent demo purchases and a complete transaction ledger.
- **Admin** — revenue vs provider cost charts, profit, success/failure health, top spenders and provider usage reconciliation.
- **Operator report** — per-day and per-user credit spend, provider cost and profit for 7/14/30/90 days, reconciled against `GET /v1/usage` and downloadable as PDF, Excel or PNG. Because Pixelforge reports usage as daily totals, each account’s provider API cost is allocated from that day’s pool by its share of the day’s local provider cost, so the per-user column adds up to the API total.
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

After changing job code, restart it (`php artisan queue:restart`, then start it again if it is not supervised). A long-running worker keeps the old code in memory, so a newly added image step can appear to be ignored.

The scheduler drives polling, reconciliation and expiry:

```bash
php artisan schedule:work
```

For frontend development use `npm run dev` instead of `npm run build`.

### Accounts

Seeded by `php artisan migrate --seed`:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@lumora.com` | `password` |
| Creator | `demo@lumora.app` | `password` |

The admin account comes from `LUMORA_ADMIN_NAME`, `LUMORA_ADMIN_EMAIL` and `LUMORA_ADMIN_PASSWORD` (defaults shown above). It is the only account created when `APP_ENV=production`; the demo creator and sample generations are seeded outside production only. The operator starts with no credits and is never given the demo Pro pack, because reports do not need a wallet balance. Set a strong `LUMORA_ADMIN_PASSWORD` before seeding a public deployment.

The demo data includes 20 succeeded generations, one failed generation and a pending render, plus ledger entries that reconcile to the wallet balances. Showcase imagery uses Unsplash URLs so the landing page is populated without provider credentials.

## Pixelforge integration

The integration is configurable because the final Pixelforge specification and credentials are supplied per environment. Every path, header name and signature format lives in `config/lumora.php` and can be overridden from `.env`:

```dotenv
PIXELFORGE_BASE_URL=https://pixelforge.promptedit.dev
PIXELFORGE_API_KEY=
PIXELFORGE_WEBHOOK_SECRET=
PIXELFORGE_TIMEOUT=30
PIXELFORGE_CONNECT_TIMEOUT=10
PIXELFORGE_REQUESTS_PER_MINUTE=60
PIXELFORGE_GENERATIONS_PATH=/v1/generations
PIXELFORGE_USAGE_PATH=/v1/usage
PIXELFORGE_SIGNATURE_HEADER=Pixelforge-Signature
PIXELFORGE_TIMESTAMP_HEADER=X-Pixelforge-Timestamp
PIXELFORGE_SIGNATURE_TOLERANCE=300
PIXELFORGE_SIGNATURE_SCHEME=timestamped
```

### Request flow

1. `POST /v1/generations` with the prompt, size, quality, reference images and `metadata.generation_uuid`.
2. An `Idempotency-Key` of `lumora-generation-{uuid}` is sent so retries are safe.
3. Webhooks are received at `POST /webhooks/pixelforge`. The signature is checked against the raw body:
   - `timestamped` (default): `HMAC-SHA256("{timestamp}.{rawBody}")`, accepting a plain hex digest or a Stripe-style `t=...,v1=...` header.
   - `raw`: `HMAC-SHA256(rawBody)`.
4. Event IDs are deduplicated in `webhook_events`. Events older than the last applied transition are ignored, unmatched events are stored and replayed once the generation can be linked.
5. If webhooks are delayed, `lumora:poll-generations` polls `GET /v1/generations/{id}` on a schedule. Polling also expires stale jobs and refunds them.
6. `GET /v1/usage` is read by the admin “Reconcile usage” action, by the operator report’s “Sync provider usage” action for its selected range, and hourly by `lumora:reconcile-usage`. Daily provider aggregates are stored once per date, so repeated syncs update the same rows instead of duplicating them.

### Event shapes

Webhook deliveries use `{"id":"evt_...","type":"generation.succeeded","data":{"object":{"id":"gen_...","status":"succeeded","image_url":"...","usage":{...},"metadata":{"generation_uuid":"..."}}}}`. The processor reads the generation from `data.object` (a webhook's top-level `id` is the event id) and still accepts older variants such as `id` or `data.id` for the generation, `status`/`data.status` for the state, `images[].url`/`output[].url` for the image, and `usage`/`data.usage` for tokens.

Actual provider cost resolution order:

1. Explicit reported cost (if present).
2. Token usage priced with the `rates` returned alongside the generation.
3. Token usage priced with the configured per-million rates.
4. The documented token estimate for the requested size and quality.

The customer charge is always provider cost plus the configured markup (default `2500` basis points = 25%), rounded up.

Because signed provider image URLs stop working 24 hours after completion, `MirrorGenerationImage` copies each finished render into storage Lumora controls and keeps the original URL in `meta.provider_image_url`. In production the default driver keeps the bytes in the database (`/media/generations/{uuid}`); locally they are written to the `public` disk and served through `/storage/generations`, which needs `php artisan storage:link`.

Images are the one piece of state that used to live only on the filesystem. Container hosts such as Render replace that filesystem on every deploy (and on restart, on the free plan), so production stores renders in the database by default — see [Image storage](#image-storage). Otherwise the generation rows survive a redeploy while their image files disappear.

Pixelforge is a simulated provider, so its placeholders have the prompt, generation id and quality drawn onto the artwork. Mirrored renders keep them by default, exactly as the provider made them. The studio has a **Remove provider caption** checkbox for each render; enabling it paints the caption out while mirroring and keeps only the PIXELFORGE watermark. `LUMORA_STRIP_CAPTIONS=true` sets the default for generations created without a stored preference. If you change the default after images are already mirrored, run `php artisan lumora:strip-image-captions` to clean the existing files.

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
php artisan test          # full unit + feature suite
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
   - `LUMORA_ADMIN_EMAIL` and a strong `LUMORA_ADMIN_PASSWORD` for the administrator account.
   - Pixelforge credentials and webhook secret.
3. **Build and migrate**:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan db:seed --force
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   npm ci && npm run build
   ```
   In production the seeder creates the admin account only. To create just that account later, run `php artisan db:seed --class=AdminUserSeeder --force`.
4. **Run workers**:
   ```bash
   php artisan queue:work --tries=5 --timeout=120
   php artisan schedule:work
   ```
   Use Supervisor (or your platform's worker feature) for the queue and a cron entry for `php artisan schedule:run` if you prefer cron over `schedule:work`. Restart workers on every deploy (`php artisan queue:restart`) so they load the new code.
5. **Point Pixelforge** at `https://your-domain.com/webhooks/pixelforge` and set `PIXELFORGE_WEBHOOK_SECRET` to the same secret.
6. **Search engines**:
   - Confirm `https://your-domain.com/robots.txt` and `/sitemap.xml` resolve over HTTPS.
   - Add the domain to Google Search Console and submit the sitemap.
   - Private pages (`/dashboard`, `/generate`, `/creations`, `/billing`, `/settings`, `/admin`) are disallowed in `robots.txt` and served with `noindex`.
7. **Smoke test**: register, confirm the welcome bonus, buy a package, run a render, verify the webhook settles it, check the admin dashboard, open the operator report, sync provider usage for the range and download each export.

### Image storage

A render has to outlive the host that produced it: provider URLs expire after 24 hours, and container filesystems are replaced on every deploy (Render's free plan also rebuilds on restart and spin-down). Lumora has two storage drivers, selected with `LUMORA_IMAGE_STORE`:

- **`database`** (default when `APP_ENV=production`): the bytes are kept in the `generation_images` table and served from `/media/generations/{uuid}`. Images survive redeploys and restarts with no extra infrastructure. Files are capped at `LUMORA_IMAGE_DATABASE_MAX_BYTES` (8 MB by default) to stay well inside database packet limits.
- **`disk`** (default locally): files go to `storage/app/public/generations` on `LUMORA_IMAGE_DISK`. Fast and cheap, but only durable when the filesystem is. Use it when you can mount a persistent disk, or point the disk at S3 (`LUMORA_IMAGE_DISK=s3` plus the usual `AWS_*` variables and `league/flysystem-aws-s3-v3`).

Run `php artisan storage:link` for the disk driver. With Render's free plan, keep the default database driver — the free plan cannot attach a disk, and the container is rebuilt on every deploy and after periods of inactivity.

Switching drivers only affects new writes; existing renders keep whatever URL they already have. Set the same driver on every instance so all workers agree where images belong.

Admins can audit and repair storage at **Admin → Image Storage** (`/admin/images`). It lists every completed render whose image is unavailable and can:

- upload recovered images named after the generation UUID (for example `258a328b-9697-452e-a08b-33b99a9f5ce4.png`),
- re-download a recent render from its stored Pixelforge URL (provider links expire after 24 hours).

The same operations are available from the CLI:

```bash
php artisan lumora:verify-images                       # list missing images (--json for scripts)
php artisan lumora:restore-images /path/to/recovered   # restore <uuid>.png files from a folder
```

If a redeploy already wiped the files, download the images you still have before they are overwritten again, restore them through either path above, and switch to the database driver (or a persistent disk) so it does not recur.

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
    Admin/AdminReportService.php
    Admin/Reports/{ReportPdf,ReportXlsx,ReportPng}.php
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
