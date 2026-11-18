<?php

namespace Database\Seeders;

use App\Models\Generation;
use App\Models\User;
use App\Services\Billing\CreditService;
use App\Services\Generations\GenerationFinalizer;
use App\Services\Pixelforge\PricingCalculator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private array $images = [
        'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=1200&q=80',
        'https://images.unsplash.com/photo-1634017839464-5c339ebe3cb4?auto=format&fit=crop&w=1200&q=80',
        'https://images.unsplash.com/photo-1620641788421-7a1c342ea42e?auto=format&fit=crop&w=1200&q=80',
        'https://images.unsplash.com/photo-1635776062127-d379bfcba9f8?auto=format&fit=crop&w=1200&q=80',
        'https://images.unsplash.com/photo-1614850715649-1d0106293bd1?auto=format&fit=crop&w=1200&q=80',
        'https://images.unsplash.com/photo-1614850523459-c2f4c699c52e?auto=format&fit=crop&w=1200&q=80',
        'https://images.unsplash.com/photo-1550859492-d5da9d8e45f3?auto=format&fit=crop&w=1200&q=80',
        'https://images.unsplash.com/photo-1541701494587-cb58502866ab?auto=format&fit=crop&w=1200&q=80',
        'https://images.unsplash.com/photo-1579546929518-9e396f3cc809?auto=format&fit=crop&w=1200&q=80',
        'https://images.unsplash.com/photo-1557682250-33bd709cbe85?auto=format&fit=crop&w=1200&q=80',
        'https://images.unsplash.com/photo-1557672172-298e090bd0f1?auto=format&fit=crop&w=1200&q=80',
        'https://images.unsplash.com/photo-1561214115-f2f134cc4912?auto=format&fit=crop&w=1200&q=80',
    ];

    private array $prompts = [
        'A bioluminescent cathedral floating above a midnight ocean, volumetric light, cinematic composition',
        'Portrait of a desert nomad wearing woven chrome fabric, soft rim lighting, editorial photography',
        'An electric violet koi swimming through a nebula, ultra detailed, dreamlike atmosphere',
        'Brutalist observatory on Europa, cyan aurora, photorealistic architectural render',
        'A botanical greenhouse on Mars, glass domes, warm sunlight, retro-futurism',
        'Abstract liquid mercury sculpture with lavender highlights, studio lighting, 8k',
        'A lone astronaut tending a garden of glowing flowers, soft pastel palette, painterly',
        'Sleek concept car made of smoked glass, wet asphalt, neon reflections, night photography',
        'Iridescent hummingbird built from stained glass, macro shot, dark background',
        'A floating library among clouds, indigo dusk, cinematic wide shot, intricate detail',
        'Ceramic sculpture of a sleeping fox glazed in deep violet, gallery lighting',
        'Underwater city with luminous coral towers, turquoise and cyan, hyperreal',
        'A couture gown woven from fiber optics, runway show, dramatic spotlight',
        'Minimal desert dunes under two moons, long shadows, film grain',
        'Crystal mountain range with a glowing river, aerial perspective, twilight',
        'A vintage radio dissolving into butterflies, surreal product photography',
    ];

    public function run(): void
    {
        $credits = app(CreditService::class);
        $finalizer = app(GenerationFinalizer::class);
        $pricing = app(PricingCalculator::class);

        $demo = User::query()->updateOrCreate(
            ['email' => 'demo@lumora.app'],
            [
                'name' => 'Maya Chen',
                'password' => Hash::make('password'),
                'is_admin' => false,
                'email_verified_at' => now(),
            ],
        );

        $this->fund($credits, $demo, 'studio', 'seed-demo-purchase');

        $createdAt = Carbon::now()->subDays(12)->setTime(10, 15);

        for ($i = 0; $i < 9; $i++) {
            $status = $i === 4 ? Generation::STATUS_FAILED : Generation::STATUS_SUCCEEDED;
            $this->makeGeneration(
                $demo,
                $this->prompts[$i],
                $this->images[$i],
                $createdAt->copy()->addHours($i * 9),
                $status,
                $finalizer,
                $pricing,
            );
        }

        $this->makeGeneration(
            $demo,
            'A kinetic sculpture of light ribbons suspended in a black gallery, long exposure',
            $this->images[11],
            now()->subMinutes(2),
            Generation::STATUS_PROCESSING,
            $finalizer,
            $pricing,
        );

        $creators = [
            ['name' => 'Noah Alder', 'email' => 'noah@example.com'],
            ['name' => 'Iris Vale', 'email' => 'iris@example.com'],
            ['name' => 'Theo Marchetti', 'email' => 'theo@example.com'],
            ['name' => 'Sana Okafor', 'email' => 'sana@example.com'],
        ];

        foreach ($creators as $index => $creator) {
            $user = User::query()->updateOrCreate(
                ['email' => $creator['email']],
                [
                    'name' => $creator['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $this->fund($credits, $user, $index % 2 === 0 ? 'starter' : 'studio', 'seed-purchase-'.$user->id);

            for ($j = 0; $j < 3; $j++) {
                $promptIndex = ($index * 3 + $j + 9) % count($this->prompts);

                $this->makeGeneration(
                    $user,
                    $this->prompts[$promptIndex],
                    $this->images[($index * 3 + $j) % count($this->images)],
                    now()->subDays(9 - $index)->setTime(14, 30)->addHours($j),
                    Generation::STATUS_SUCCEEDED,
                    $finalizer,
                    $pricing,
                );
            }
        }
    }

    private function fund(CreditService $credits, User $user, string $package, string $reference): void
    {
        if ($user->creditTransactions()->where('reference', $reference)->exists()) {
            return;
        }

        $credits->purchase($user->fresh(), $package, $reference);
    }

    private function makeGeneration(
        User $user,
        string $prompt,
        string $image,
        Carbon $createdAt,
        string $status,
        GenerationFinalizer $finalizer,
        PricingCalculator $pricing,
    ): Generation {
        $sizes = collect(config('lumora.generation.size_options', []))->pluck('value')->all();
        $size = $sizes[array_rand($sizes)];
        $qualities = collect(config('lumora.generation.quality_options', []))->pluck('value')->all();
        $quality = $qualities[array_rand($qualities)];
        $estimate = $pricing->estimate($size, $quality);

        [$width, $height] = array_map('intval', explode('x', $size));
        $uuid = (string) Str::uuid();
        $providerId = 'pf_'.Str::lower(Str::random(18));

        $generation = Generation::query()->create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'prompt' => $prompt,
            'size' => $size,
            'width' => $width,
            'height' => $height,
            'quality' => $quality,
            'status' => Generation::STATUS_PENDING,
            'provider' => 'pixelforge',
            'provider_generation_id' => $status === Generation::STATUS_PROCESSING ? null : $providerId,
            'estimated_provider_cost_micros' => $estimate->providerMicros,
            'estimated_user_charge_micros' => $estimate->totalMicros,
            'markup_basis_points' => $estimate->markupBasisPoints,
            'meta' => ['generation_uuid' => $uuid, 'seeded' => true],
        ]);

        $wallet = $user->fresh();
        app(CreditService::class)->holdForGeneration($wallet, $generation);

        if ($status === Generation::STATUS_SUCCEEDED) {
            $finalizer->succeed($generation, [
                'status' => 'succeeded',
                'id' => $providerId,
                'images' => [['url' => $image]],
                'usage' => [
                    'input_text_tokens' => 180,
                    'output_tokens' => 120,
                    'input_image_tokens' => 0,
                    'reasoning_tokens' => 350,
                ],
            ], 'seed', $createdAt->copy()->addSeconds(18));
        } elseif ($status === Generation::STATUS_FAILED) {
            $finalizer->fail(
                $generation,
                'prompt_rejected',
                'The provider could not render this prompt. Your credits were returned.',
                ['status' => 'failed', 'id' => $providerId],
                'seed',
                $createdAt->copy()->addSeconds(9),
            );
        }

        $generation->refresh();
        $generation->timestamps = false;
        $generation->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $status === Generation::STATUS_PROCESSING ? now() : $createdAt->copy()->addSeconds(20),
        ])->saveQuietly();

        return $generation;
    }
}
