<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Sponsor;
use App\Models\SponsorTier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array{ar: string, en: string, sort_order: int}>
     */
    private const LEGACY_TIER_LABELS = [
        'platinum' => ['ar' => 'بلاتينيوم', 'en' => 'Platinum', 'sort_order' => 0],
        'gold' => ['ar' => 'ذهبي', 'en' => 'Gold', 'sort_order' => 1],
        'silver' => ['ar' => 'فضي', 'en' => 'Silver', 'sort_order' => 2],
        'bronze' => ['ar' => 'برونزي', 'en' => 'Bronze', 'sort_order' => 3],
        'community' => ['ar' => 'شركاء المجتمع', 'en' => 'Community Partners', 'sort_order' => 4],
    ];

    public function up(): void
    {
        Schema::table('sponsors', function (Blueprint $table) {
            $table->foreignId('sponsor_tier_id')->nullable()->after('event_id')->constrained()->nullOnDelete();
        });

        Event::query()->each(function (Event $event) {
            $tiersByLegacyKey = [];

            Sponsor::withoutGlobalScopes()->where('event_id', $event->id)
                ->whereNull('sponsor_tier_id')
                ->get()
                ->groupBy('tier')
                ->each(function ($sponsors, $legacyTier) use ($event, &$tiersByLegacyKey) {
                    $labels = self::LEGACY_TIER_LABELS[$legacyTier] ?? ['ar' => ucfirst($legacyTier), 'en' => ucfirst($legacyTier), 'sort_order' => 99];

                    $tier = SponsorTier::create([
                        'event_id' => $event->id,
                        'name_ar' => $labels['ar'],
                        'name_en' => $labels['en'],
                        'sort_order' => $labels['sort_order'],
                    ]);

                    $tiersByLegacyKey[$legacyTier] = $tier->id;

                    Sponsor::whereIn('id', $sponsors->pluck('id'))->update(['sponsor_tier_id' => $tier->id]);
                });
        });

        Schema::table('sponsors', function (Blueprint $table) {
            $table->dropColumn('tier');
        });
    }

    public function down(): void
    {
        Schema::table('sponsors', function (Blueprint $table) {
            $table->string('tier')->default('bronze')->after('event_id');
        });

        DB::table('sponsors')
            ->join('sponsor_tiers', 'sponsors.sponsor_tier_id', '=', 'sponsor_tiers.id')
            ->update(['sponsors.tier' => DB::raw('LOWER(sponsor_tiers.name_en)')]);

        Schema::table('sponsors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sponsor_tier_id');
        });
    }
};
