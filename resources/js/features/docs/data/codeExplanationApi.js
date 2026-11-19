/**
 * Generated public-surface map for the unlisted /code-explanation reference.
 * Method signatures are kept in sync with app/ and config/ source files.
 */

export const publicApi = {
    "app/Services/Pixelforge/PricingCalculator.php": [
        [
            "function markupBasisPoints(): int",
            "Markup Basis Points"
        ],
        [
            "function estimate( string $size, string $quality, int $images = 1, string $prompt = '', int $references = 0, ): PriceEstimate",
            "Estimate"
        ],
        [
            "function actualProviderCostMicros( ?array $payload, string $size = '1024x1024', string $quality = 'medium', int $images = 1, ): int",
            "Actual Provider Cost Micros"
        ],
        [
            "function chargeForProviderCost(int $providerMicros): int",
            "Charge For Provider Cost"
        ],
        [
            "function usageFrom(array $payload): array",
            "Usage From"
        ]
    ],
    "app/Http/Controllers/GenerationController.php": [
        [
            "function create(Request $request): Response",
            "Create"
        ],
        [
            "function store(StoreGenerationRequest $request, GenerationService $generations): RedirectResponse",
            "Store"
        ],
        [
            "function index(Request $request): Response",
            "Index"
        ],
        [
            "function status(Request $request, Generation $generation): JsonResponse",
            "Status"
        ],
        [
            "function destroy(Request $request, Generation $generation): RedirectResponse",
            "Destroy"
        ]
    ],
    "app/Jobs/SubmitGenerationJob.php": [
        [
            "function uniqueId(): string",
            "Unique Id"
        ],
        [
            "function backoff(): array",
            "Backoff"
        ],
        [
            "function handle(PixelforgeClient $client, GenerationFinalizer $finalizer): void",
            "Handle"
        ],
        [
            "function failed(?Throwable $exception): void",
            "Failed"
        ]
    ],
    "app/Services/Generations/GenerationFinalizer.php": [
        [
            "function succeed(Generation $generation, array $payload, string $source, ?CarbonInterface $occurredAt = null): bool",
            "Succeed"
        ],
        [
            "function fail( Generation $generation, string $code, ?string $message, array $payload = [], string $source = 'system', ?CarbonInterface $occurredAt = null, ): bool",
            "Fail"
        ],
        [
            "function markProcessing(Generation $generation, array $payload, string $source): void",
            "Mark Processing"
        ],
        [
            "function acceptEvent(Generation $generation, ?CarbonInterface $occurredAt): bool",
            "Accept Event"
        ],
        [
            "function imageUrl(array $payload): ?string",
            "Image Url"
        ],
        [
            "function providerGenerationId(array $payload): ?string",
            "Provider Generation Id"
        ],
        [
            "function providerStatus(array $payload): ?string",
            "Provider Status"
        ]
    ],
    "app/Support/Seo.php": [
        [
            "static function forPath(string $path, array $overrides = []): array",
            "For Path"
        ]
    ],
    "app/Support/Money.php": [
        [
            "static function usdToMicros(int|string|BigDecimal $amount): int",
            "Usd To Micros"
        ],
        [
            "static function microsToUsd(int $micros, int $scale = 6): string",
            "Micros To Usd"
        ],
        [
            "static function format(int $micros, int $maximumFractionDigits = 4): string",
            "Format"
        ],
        [
            "static function applyMarkup(int $providerMicros, int $basisPoints = 2_500): int",
            "Apply Markup"
        ],
        [
            "static function withMarkup(int $providerMicros, int $basisPoints = 2_500): int",
            "With Markup"
        ],
        [
            "static function creditsToMicros(int|string $credits): int",
            "Credits To Micros"
        ],
        [
            "static function microsToCredits(int $micros, int $scale = 2): string",
            "Micros To Credits"
        ],
        [
            "static function clampPositive(int $value): int",
            "Clamp Positive"
        ]
    ],
    "app/Http/Controllers/LandingController.php": [
        [
            "function __invoke(Request $request, GenerationImageStore $images): Response",
            "Invoke"
        ]
    ],
    "app/Http/Controllers/DashboardController.php": [
        [
            "function __invoke(Request $request): Response",
            "Invoke"
        ]
    ],
    "app/Http/Controllers/BillingController.php": [
        [
            "function index(Request $request, CreditService $credits): Response",
            "Index"
        ],
        [
            "function purchase(PurchaseCreditsRequest $request, CreditService $credits): RedirectResponse",
            "Purchase"
        ]
    ],
    "app/Http/Controllers/SettingsController.php": [
        [
            "function edit(Request $request): Response",
            "Edit"
        ],
        [
            "function updateProfile(UpdateProfileRequest $request): RedirectResponse",
            "Update Profile"
        ],
        [
            "function updatePassword(UpdatePasswordRequest $request): RedirectResponse",
            "Update Password"
        ],
        [
            "function destroy(Request $request): RedirectResponse",
            "Destroy"
        ]
    ],
    "app/Http/Controllers/Admin/AdminDashboardController.php": [
        [
            "function index(Request $request, AdminMetrics $metrics): Response",
            "Index"
        ],
        [
            "function reconcile(UsageReconciler $reconciler): RedirectResponse",
            "Reconcile"
        ]
    ],
    "app/Http/Controllers/Admin/AdminReportController.php": [
        [
            "function index(Request $request, AdminReportService $reports): InertiaResponse",
            "Index"
        ],
        [
            "function sync( Request $request, AdminReportService $reports, UsageReconciler $reconciler, ): RedirectResponse",
            "Sync"
        ],
        [
            "function export( Request $request, string $format, AdminReportService $reports, ReportPdf $pdf, ReportXlsx $xlsx, ReportPng $png, ): Response",
            "Export"
        ]
    ],
    "app/Http/Controllers/Admin/AdminImageController.php": [
        [
            "function index(GenerationImageStore $store): Response",
            "Index"
        ],
        [
            "function store(Request $request, GenerationImageStore $store): RedirectResponse",
            "Store"
        ],
        [
            "function refetch(Generation $generation, GenerationImageStore $store): RedirectResponse",
            "Refetch"
        ]
    ],
    "app/Http/Controllers/GenerationMediaController.php": [
        [
            "function __invoke(Request $request, Generation $generation): Response",
            "Invoke"
        ]
    ],
    "app/Http/Controllers/DocumentationController.php": [
        [
            "function userGuide(): Response",
            "User Guide"
        ],
        [
            "function developerGuide(): Response",
            "Developer Guide"
        ]
    ],
    "app/Http/Controllers/CodeExplanationController.php": [
        [
            "function __invoke(): Response",
            "Invoke"
        ]
    ],
    "app/Http/Controllers/SitemapController.php": [
        [
            "function __invoke(): Response",
            "Invoke"
        ]
    ],
    "app/Http/Controllers/Webhooks/PixelforgeWebhookController.php": [
        [
            "function __invoke(Request $request, WebhookProcessor $processor): JsonResponse",
            "Invoke"
        ]
    ],
    "app/Http/Controllers/Auth/AuthenticatedSessionController.php": [
        [
            "function create(): Response",
            "Create"
        ],
        [
            "function store(Request $request): RedirectResponse",
            "Store"
        ],
        [
            "function destroy(Request $request): RedirectResponse",
            "Destroy"
        ]
    ],
    "app/Http/Controllers/Auth/RegisteredUserController.php": [
        [
            "function create(): Response",
            "Create"
        ],
        [
            "function store(Request $request, CreditService $credits): RedirectResponse",
            "Store"
        ]
    ],
    "app/Http/Controllers/Auth/PasswordResetLinkController.php": [
        [
            "function create(): Response",
            "Create"
        ],
        [
            "function store(Request $request): RedirectResponse",
            "Store"
        ]
    ],
    "app/Http/Controllers/Auth/NewPasswordController.php": [
        [
            "function create(Request $request): Response",
            "Create"
        ],
        [
            "function store(Request $request): RedirectResponse",
            "Store"
        ]
    ],
    "app/Http/Requests/StoreGenerationRequest.php": [
        [
            "function authorize(): bool",
            "Authorize"
        ],
        [
            "function rules(): array",
            "Rules"
        ],
        [
            "function messages(): array",
            "Messages"
        ],
        [
            "function attributes(): array",
            "Attributes"
        ]
    ],
    "app/Http/Requests/PurchaseCreditsRequest.php": [
        [
            "function authorize(): bool",
            "Authorize"
        ],
        [
            "function rules(): array",
            "Rules"
        ]
    ],
    "app/Http/Requests/UpdateProfileRequest.php": [
        [
            "function authorize(): bool",
            "Authorize"
        ],
        [
            "function rules(): array",
            "Rules"
        ]
    ],
    "app/Http/Requests/UpdatePasswordRequest.php": [
        [
            "function authorize(): bool",
            "Authorize"
        ],
        [
            "function rules(): array",
            "Rules"
        ]
    ],
    "app/Http/Resources/GenerationResource.php": [
        [
            "function toArray(Request $request): array",
            "To Array"
        ]
    ],
    "app/Http/Resources/CreditTransactionResource.php": [
        [
            "function toArray(Request $request): array",
            "To Array"
        ]
    ],
    "app/Http/Middleware/HandleInertiaRequests.php": [
        [
            "function version(Request $request): ?string",
            "Version"
        ],
        [
            "function share(Request $request): array",
            "Share"
        ]
    ],
    "app/Http/Middleware/EnsureUserIsAdmin.php": [
        [
            "function handle(Request $request, Closure $next): Response",
            "Handle"
        ]
    ],
    "app/Http/Middleware/PreventSearchIndexing.php": [
        [
            "function handle(Request $request, Closure $next): Response",
            "Handle"
        ]
    ],
    "app/Services/Generations/GenerationService.php": [
        [
            "function create(User $user, array $data): Generation",
            "Create"
        ],
        [
            "function dispatch(Generation $generation): void",
            "Dispatch"
        ],
        [
            "function statusPayload(Generation $generation): array",
            "Status Payload"
        ],
        [
            "function expireStale(): int",
            "Expire Stale"
        ]
    ],
    "app/Services/Generations/WebhookProcessor.php": [
        [
            "function process(array $payload, string $rawBody, array $headers = []): array",
            "Process"
        ],
        [
            "function replayPending(): int",
            "Replay Pending"
        ]
    ],
    "app/Services/Generations/ImageCaptionStripper.php": [
        [
            "function strip(string $contents): string",
            "Strip"
        ]
    ],
    "app/Services/Images/GenerationImageStore.php": [
        [
            "function disk(): string",
            "Disk"
        ],
        [
            "function driver(): string",
            "Driver"
        ],
        [
            "function storesInDatabase(): bool",
            "Stores In Database"
        ],
        [
            "function maxBytes(): int",
            "Max Bytes"
        ],
        [
            "function storedPath(Generation $generation): ?string",
            "Stored Path"
        ],
        [
            "function usesStoredImage(Generation $generation): bool",
            "Uses Stored Image"
        ],
        [
            "function databaseImage(Generation $generation): ?GenerationImage",
            "Database Image"
        ],
        [
            "function exists(Generation $generation): bool",
            "Exists"
        ],
        [
            "function missing(?int $limit = null): Collection",
            "Missing"
        ],
        [
            "function generationForFilename(string $filename): ?Generation",
            "Generation For Filename"
        ],
        [
            "function store(Generation $generation, string $contents, string $extension, array $meta = []): string",
            "Store"
        ],
        [
            "function restoreFile(Generation $generation, string $file, ?string $mime = null): string",
            "Restore File"
        ],
        [
            "function restoreFromProvider(Generation $generation): string",
            "Restore From Provider"
        ],
        [
            "function contents(Generation $generation): ?string",
            "Contents"
        ],
        [
            "function extension(?string $mime, ?string $name, string $contents = ''): string",
            "Extension"
        ]
    ],
    "app/Services/Pixelforge/PixelforgeClient.php": [
        [
            "function createGeneration(array $payload, string $idempotencyKey): array",
            "Create Generation"
        ],
        [
            "function getGeneration(string $providerGenerationId): array",
            "Get Generation"
        ],
        [
            "function usage(string $from, string $to, ?string $cursor = null): array",
            "Usage"
        ],
        [
            "function isConfigured(): bool",
            "Is Configured"
        ]
    ],
    "app/Services/Pixelforge/PixelforgeException.php": [
        [
            "function isRetryable(): bool",
            "Is Retryable"
        ],
        [
            "function isClientError(): bool",
            "Is Client Error"
        ],
        [
            "function isNotFound(): bool",
            "Is Not Found"
        ],
        [
            "function retryDelay(int $default = 10): int",
            "Retry Delay"
        ]
    ],
    "app/Services/Pixelforge/PriceEstimate.php": [
        [
            "function toArray(): array",
            "To Array"
        ]
    ],
    "app/Services/Pixelforge/WebhookSignature.php": [
        [
            "static function verify( string $rawBody, ?string $signatureHeader, ?string $timestampHeader, ?string $secret, string $scheme = 'timestamped', int $toleranceSeconds = 300, ?int $now = null, ): bool",
            "Verify"
        ],
        [
            "static function candidates(array $headers, string $configuredHeader): array",
            "Candidates"
        ]
    ],
    "app/Services/Pixelforge/UsageReconciler.php": [
        [
            "function sync(CarbonInterface $from, CarbonInterface $to): array",
            "Sync"
        ],
        [
            "function recordsForRange(CarbonInterface $from, CarbonInterface $to): Collection",
            "Records For Range"
        ]
    ],
    "app/Services/Billing/CreditService.php": [
        [
            "function lockUser(int $userId): User",
            "Lock User"
        ],
        [
            "function holdForGeneration(User $user, Generation $generation): CreditTransaction",
            "Hold For Generation"
        ],
        [
            "function settleGeneration(User $user, Generation $generation, int $actualChargeMicros): array",
            "Settle Generation"
        ],
        [
            "function refundGeneration(User $user, Generation $generation): void",
            "Refund Generation"
        ],
        [
            "function purchase(User $user, string $packageKey, ?string $reference = null): CreditTransaction",
            "Purchase"
        ],
        [
            "function grant(User $user, int $amountMicros, string $type, string $description, ?string $reference = null, array $meta = []): CreditTransaction",
            "Grant"
        ],
        [
            "function holdFor(Generation $generation): ?CreditTransaction",
            "Hold For"
        ],
        [
            "function summary(User $user): array",
            "Summary"
        ]
    ],
    "app/Services/Admin/AdminMetrics.php": [
        [
            "function overview(int $days = 14): array",
            "Overview"
        ]
    ],
    "app/Services/Admin/AdminReportService.php": [
        [
            "function build(int $days = 14): array",
            "Build"
        ],
        [
            "function normaliseDays(int $days): int",
            "Normalise Days"
        ]
    ],
    "app/Services/Admin/Reports/ReportPdf.php": [
        [
            "function render(array $report): string",
            "Render"
        ]
    ],
    "app/Services/Admin/Reports/ReportXlsx.php": [
        [
            "function render(array $report): string",
            "Render"
        ]
    ],
    "app/Services/Admin/Reports/ReportPng.php": [
        [
            "function render(array $report): string",
            "Render"
        ]
    ],
    "app/Jobs/PollGenerationJob.php": [
        [
            "function handle(PixelforgeClient $client, GenerationFinalizer $finalizer): void",
            "Handle"
        ]
    ],
    "app/Jobs/MirrorGenerationImage.php": [
        [
            "function handle(ImageCaptionStripper $stripper, GenerationImageStore $store): void",
            "Handle"
        ],
        [
            "function failed(?Throwable $exception): void",
            "Failed"
        ]
    ],
    "app/Console/Commands/PollGenerationsCommand.php": [
        [
            "function handle(): int",
            "Handle"
        ]
    ],
    "app/Console/Commands/ExpireGenerationsCommand.php": [
        [
            "function handle(GenerationService $generations): int",
            "Handle"
        ]
    ],
    "app/Console/Commands/ReconcileUsageCommand.php": [
        [
            "function handle(UsageReconciler $reconciler, WebhookProcessor $webhooks): int",
            "Handle"
        ]
    ],
    "app/Console/Commands/StripImageCaptionsCommand.php": [
        [
            "function handle(ImageCaptionStripper $stripper, GenerationImageStore $store): int",
            "Handle"
        ]
    ],
    "app/Console/Commands/VerifyGenerationImagesCommand.php": [
        [
            "function handle(GenerationImageStore $store): int",
            "Handle"
        ]
    ],
    "app/Console/Commands/RestoreGenerationImagesCommand.php": [
        [
            "function handle(GenerationImageStore $store): int",
            "Handle"
        ]
    ],
    "app/Models/User.php": [
        [
            "function generations(): HasMany",
            "Generations"
        ],
        [
            "function creditTransactions(): HasMany",
            "Credit Transactions"
        ],
        [
            "function isAdmin(): bool",
            "Is Admin"
        ],
        [
            "function hasCreditFor(int $micros): bool",
            "Has Credit For"
        ],
        [
            "function initials(): string",
            "Initials"
        ],
        [
            "function toInertiaArray(): array",
            "To Inertia Array"
        ]
    ],
    "app/Models/Generation.php": [
        [
            "function getRouteKeyName(): string",
            "Get Route Key Name"
        ],
        [
            "function user(): BelongsTo",
            "User"
        ],
        [
            "function settlement(): HasOne",
            "Settlement"
        ],
        [
            "function image(): HasOne",
            "Image"
        ],
        [
            "function scopeOwnedBy(Builder $query, User $user): Builder",
            "Scope Owned By"
        ],
        [
            "function scopeProcessing(Builder $query): Builder",
            "Scope Processing"
        ],
        [
            "function scopeSettled(Builder $query): Builder",
            "Scope Settled"
        ],
        [
            "function isTerminal(): bool",
            "Is Terminal"
        ],
        [
            "function isSucceeded(): bool",
            "Is Succeeded"
        ],
        [
            "function isFailed(): bool",
            "Is Failed"
        ],
        [
            "function isInProgress(): bool",
            "Is In Progress"
        ],
        [
            "function isSettled(): bool",
            "Is Settled"
        ],
        [
            "function markupMicros(): int",
            "Markup Micros"
        ]
    ],
    "app/Models/CreditTransaction.php": [
        [
            "function user(): BelongsTo",
            "User"
        ],
        [
            "function generation(): BelongsTo",
            "Generation"
        ],
        [
            "function isCredit(): bool",
            "Is Credit"
        ]
    ],
    "app/Models/WebhookEvent.php": [
        [
            "function generation(): BelongsTo",
            "Generation"
        ],
        [
            "function payloadArray(): array",
            "Payload Array"
        ]
    ],
    "app/Models/ProviderUsageRecord.php": [
        [
            "function generation(): BelongsTo",
            "Generation"
        ]
    ],
    "app/Models/GenerationImage.php": [
        [
            "function generation(): BelongsTo",
            "Generation"
        ]
    ],
    "app/Policies/GenerationPolicy.php": [
        [
            "function view(User $user, Generation $generation): bool",
            "View"
        ],
        [
            "function delete(User $user, Generation $generation): bool",
            "Delete"
        ]
    ],
    "app/Providers/AppServiceProvider.php": [
        [
            "function register(): void",
            "Register"
        ],
        [
            "function boot(): void",
            "Boot"
        ]
    ],
    "database/migrations/0001_01_01_000000_create_users_table.php": [
        [
            "function up(): void",
            "Up"
        ],
        [
            "function down(): void",
            "Down"
        ]
    ],
    "database/migrations/0001_01_01_000001_create_cache_table.php": [
        [
            "function up(): void",
            "Up"
        ],
        [
            "function down(): void",
            "Down"
        ]
    ],
    "database/migrations/0001_01_01_000002_create_jobs_table.php": [
        [
            "function up(): void",
            "Up"
        ],
        [
            "function down(): void",
            "Down"
        ]
    ],
    "database/migrations/2026_11_18_000001_add_lumora_columns_to_users_table.php": [
        [
            "function up(): void",
            "Up"
        ],
        [
            "function down(): void",
            "Down"
        ]
    ],
    "database/migrations/2026_11_18_000002_create_generations_table.php": [
        [
            "function up(): void",
            "Up"
        ],
        [
            "function down(): void",
            "Down"
        ]
    ],
    "database/migrations/2026_11_18_000003_create_credit_transactions_table.php": [
        [
            "function up(): void",
            "Up"
        ],
        [
            "function down(): void",
            "Down"
        ]
    ],
    "database/migrations/2026_11_18_000004_create_webhook_events_table.php": [
        [
            "function up(): void",
            "Up"
        ],
        [
            "function down(): void",
            "Down"
        ]
    ],
    "database/migrations/2026_11_18_000005_create_provider_usage_records_table.php": [
        [
            "function up(): void",
            "Up"
        ],
        [
            "function down(): void",
            "Down"
        ]
    ],
    "database/migrations/2026_11_19_000001_create_generation_images_table.php": [
        [
            "function up(): void",
            "Up"
        ],
        [
            "function down(): void",
            "Down"
        ]
    ],
    "database/factories/UserFactory.php": [
        [
            "function definition(): array",
            "Definition"
        ],
        [
            "function unverified(): static",
            "Unverified"
        ]
    ],
    "database/factories/GenerationFactory.php": [
        [
            "function definition(): array",
            "Definition"
        ],
        [
            "function succeeded(): static",
            "Succeeded"
        ],
        [
            "function failed(): static",
            "Failed"
        ]
    ],
    "database/seeders/DatabaseSeeder.php": [
        [
            "function run(): void",
            "Run"
        ]
    ],
    "database/seeders/AdminUserSeeder.php": [
        [
            "function run(): void",
            "Run"
        ]
    ],
    "database/seeders/DemoDataSeeder.php": [
        [
            "function run(): void",
            "Run"
        ]
    ],
    "tests/Unit/MoneyTest.php": [
        [
            "function it_rounds_markup_up_so_the_platform_never_undercharges(): void",
            "It rounds markup up so the platform never undercharges"
        ],
        [
            "function it_respects_a_custom_markup(): void",
            "It respects a custom markup"
        ],
        [
            "function it_converts_between_usd_credits_and_micros(): void",
            "It converts between usd credits and micros"
        ],
        [
            "function it_formats_micros_for_display(): void",
            "It formats micros for display"
        ],
        [
            "function it_rejects_negative_provider_costs(): void",
            "It rejects negative provider costs"
        ]
    ],
    "tests/Unit/WebhookSignatureTest.php": [
        [
            "function it_verifies_a_timestamped_signature(): void",
            "It verifies a timestamped signature"
        ],
        [
            "function it_verifies_a_stripe_style_signature_header(): void",
            "It verifies a stripe style signature header"
        ],
        [
            "function it_verifies_a_raw_body_signature(): void",
            "It verifies a raw body signature"
        ],
        [
            "function it_rejects_tampered_or_missing_signatures(): void",
            "It rejects tampered or missing signatures"
        ],
        [
            "function it_rejects_stale_timestamps(): void",
            "It rejects stale timestamps"
        ]
    ],
    "tests/Feature/GenerationFlowTest.php": [
        [
            "function creating_a_generation_holds_the_estimated_charge_and_dispatches_the_job(): void",
            "Creating a generation holds the estimated charge and dispatches the job"
        ],
        [
            "function the_caption_preference_is_stored_with_the_generation(): void",
            "The caption preference is stored with the generation"
        ],
        [
            "function prompts_used_by_provider_test_modes_are_stored_verbatim(): void",
            "Prompts used by provider test modes are stored verbatim"
        ],
        [
            "function users_without_enough_credits_cannot_generate(): void",
            "Users without enough credits cannot generate"
        ],
        [
            "function users_cannot_exceed_the_active_generation_limit(): void",
            "Users cannot exceed the active generation limit"
        ],
        [
            "function a_failed_generation_is_refunded_in_full(): void",
            "A failed generation is refunded in full"
        ],
        [
            "function a_successful_generation_refunds_the_unused_estimate(): void",
            "A successful generation refunds the unused estimate"
        ],
        [
            "function a_successful_generation_can_charge_more_than_the_estimate(): void",
            "A successful generation can charge more than the estimate"
        ],
        [
            "function settlement_is_idempotent_and_never_double_charges(): void",
            "Settlement is idempotent and never double charges"
        ],
        [
            "function older_provider_events_are_ignored_for_unsettled_generations(): void",
            "Older provider events are ignored for unsettled generations"
        ],
        [
            "function stale_generations_expire_and_are_refunded(): void",
            "Stale generations expire and are refunded"
        ],
        [
            "function the_wallet_ledger_always_matches_the_balance(): void",
            "The wallet ledger always matches the balance"
        ],
        [
            "function the_credit_service_refuses_to_hold_more_than_the_balance(): void",
            "The credit service refuses to hold more than the balance"
        ]
    ],
    "tests/Feature/SubmitGenerationTest.php": [
        [
            "function a_not_found_response_to_a_submit_is_retried_without_refunding(): void",
            "A not found response to a submit is retried without refunding"
        ],
        [
            "function a_rejected_submit_records_the_provider_error_and_refunds(): void",
            "A rejected submit records the provider error and refunds"
        ],
        [
            "function a_submit_that_runs_out_of_retries_is_refunded(): void",
            "A submit that runs out of retries is refunded"
        ]
    ],
    "tests/Feature/WebhookTest.php": [
        [
            "function a_signed_success_webhook_settles_the_generation(): void",
            "A signed success webhook settles the generation"
        ],
        [
            "function duplicate_webhooks_are_idempotent(): void",
            "Duplicate webhooks are idempotent"
        ],
        [
            "function a_failure_webhook_refunds_the_held_credits(): void",
            "A failure webhook refunds the held credits"
        ],
        [
            "function late_out_of_order_events_do_not_change_a_settled_generation(): void",
            "Late out of order events do not change a settled generation"
        ],
        [
            "function an_invalid_signature_is_rejected_and_nothing_is_settled(): void",
            "An invalid signature is rejected and nothing is settled"
        ],
        [
            "function a_stale_timestamp_is_rejected(): void",
            "A stale timestamp is rejected"
        ],
        [
            "function a_webhook_without_a_configured_secret_is_rejected(): void",
            "A webhook without a configured secret is rejected"
        ],
        [
            "function events_for_unknown_generations_are_stored_for_replay(): void",
            "Events for unknown generations are stored for replay"
        ],
        [
            "function provider_ping_events_are_acknowledged_without_replay_noise(): void",
            "Provider ping events are acknowledged without replay noise"
        ],
        [
            "function duplicate_prompts_are_kept_separate_but_only_settle_once_per_event(): void",
            "Duplicate prompts are kept separate but only settle once per event"
        ],
        [
            "function a_signature_in_a_differently_named_header_settles_the_generation(): void",
            "A signature in a differently named header settles the generation"
        ],
        [
            "function a_raw_body_signature_without_a_timestamp_settles_the_generation(): void",
            "A raw body signature without a timestamp settles the generation"
        ]
    ],
    "tests/Feature/PricingTest.php": [
        [
            "function the_estimate_matches_the_documented_high_quality_example(): void",
            "The estimate matches the documented high quality example"
        ],
        [
            "function actual_cost_uses_the_reported_usage_and_rates(): void",
            "Actual cost uses the reported usage and rates"
        ],
        [
            "function the_submit_request_only_sends_fields_the_provider_accepts(): void",
            "The submit request only sends fields the provider accepts"
        ]
    ],
    "tests/Feature/ImageMirrorTest.php": [
        [
            "function a_succeeded_render_is_copied_off_the_expiring_provider_url(): void",
            "A succeeded render is copied off the expiring provider url"
        ],
        [
            "function provider_captions_are_painted_out_when_a_render_is_mirrored(): void",
            "Provider captions are painted out when a render is mirrored"
        ],
        [
            "function provider_captions_are_kept_by_default(): void",
            "Provider captions are kept by default"
        ],
        [
            "function a_per_render_preference_can_remove_captions_even_when_the_global_default_is_off(): void",
            "A per render preference can remove captions even when the global default is off"
        ],
        [
            "function a_per_render_preference_can_keep_captions_even_when_the_global_default_is_on(): void",
            "A per render preference can keep captions even when the global default is on"
        ],
        [
            "function an_image_that_is_already_stored_is_left_alone(): void",
            "An image that is already stored is left alone"
        ]
    ],
    "tests/Feature/BillingTest.php": [
        [
            "function the_billing_page_lists_packages_and_the_ledger(): void",
            "The billing page lists packages and the ledger"
        ],
        [
            "function buying_a_package_credits_the_wallet(): void",
            "Buying a package credits the wallet"
        ],
        [
            "function the_same_purchase_token_never_credits_twice(): void",
            "The same purchase token never credits twice"
        ],
        [
            "function invalid_packages_and_tokens_are_rejected(): void",
            "Invalid packages and tokens are rejected"
        ]
    ],
    "tests/Feature/AdminTest.php": [
        [
            "function guests_are_redirected_and_non_admins_are_forbidden(): void",
            "Guests are redirected and non admins are forbidden"
        ],
        [
            "function admins_see_aggregate_metrics(): void",
            "Admins see aggregate metrics"
        ],
        [
            "function an_invalid_range_falls_back_to_fourteen_days(): void",
            "An invalid range falls back to fourteen days"
        ],
        [
            "function reconciling_pulls_provider_usage_and_links_generations(): void",
            "Reconciling pulls provider usage and links generations"
        ],
        [
            "function reconciliation_reports_provider_failures_without_crashing(): void",
            "Reconciliation reports provider failures without crashing"
        ]
    ],
    "tests/Feature/AuthorizationTest.php": [
        [
            "function guests_cannot_reach_authenticated_pages(): void",
            "Guests cannot reach authenticated pages"
        ],
        [
            "function users_cannot_view_or_delete_another_users_generation(): void",
            "Users cannot view or delete another users generation"
        ],
        [
            "function owners_can_poll_their_generation_status(): void",
            "Owners can poll their generation status"
        ],
        [
            "function in_progress_generations_cannot_be_deleted(): void",
            "In progress generations cannot be deleted"
        ],
        [
            "function finished_generations_can_be_soft_deleted_by_their_owner(): void",
            "Finished generations can be soft deleted by their owner"
        ],
        [
            "function creating_a_generation_dispatches_a_unique_provider_job(): void",
            "Creating a generation dispatches a unique provider job"
        ]
    ],
    "tests/Feature/RegistrationTest.php": [
        [
            "function registering_creates_a_wallet_with_the_welcome_bonus(): void",
            "Registering creates a wallet with the welcome bonus"
        ],
        [
            "function registration_requires_accepting_the_terms(): void",
            "Registration requires accepting the terms"
        ],
        [
            "function registration_rejects_duplicate_emails(): void",
            "Registration rejects duplicate emails"
        ],
        [
            "function users_can_sign_in_and_out(): void",
            "Users can sign in and out"
        ]
    ],
    "tests/Feature/SettingsTest.php": [
        [
            "function the_settings_page_shows_the_profile_and_stats(): void",
            "The settings page shows the profile and stats"
        ],
        [
            "function users_can_update_their_profile(): void",
            "Users can update their profile"
        ],
        [
            "function users_can_change_their_password_with_the_current_one(): void",
            "Users can change their password with the current one"
        ],
        [
            "function changing_the_password_requires_the_current_password(): void",
            "Changing the password requires the current password"
        ],
        [
            "function deleting_an_account_requires_the_password_and_signs_the_user_out(): void",
            "Deleting an account requires the password and signs the user out"
        ]
    ],
    "tests/Feature/DocumentationTest.php": [
        [
            "function guests_can_read_the_user_guide(): void",
            "Guests can read the user guide"
        ],
        [
            "function guests_can_read_the_developer_guide(): void",
            "Guests can read the developer guide"
        ],
        [
            "function the_spaced_developer_guide_url_redirects_to_the_canonical_page(): void",
            "The spaced developer guide url redirects to the canonical page"
        ],
        [
            "function documentation_pages_are_listed_in_the_sitemap(): void",
            "Documentation pages are listed in the sitemap"
        ]
    ]
};
