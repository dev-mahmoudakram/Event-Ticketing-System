<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\SiteSection;
use App\Models\Event;
use App\Models\SiteContent;
use App\Models\SiteFaq;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function show(): View
    {
        $events = Event::query()
            ->where('status', EventStatus::Published)
            ->withCount(['speakers', 'workshops'])
            ->with('reels')
            ->orderBy('start_date')
            ->get();

        return view('home.show', [
            'featuredEvent' => $events->first(),
            'otherEvents' => $events->slice(1),
            'stats' => $this->stats(),
            'whyEgypt' => SiteContent::valuesFor(SiteSection::WhyEgypt),
            'faqs' => SiteFaq::orderBy('sort_order')->get(),
        ]);
    }

    /**
     * The three headline figures, dropping any pair that is not fully filled in so the band
     * never shows a number without its label, or the other way round.
     *
     * @return list<array{figure: string, label: string}>
     */
    private function stats(): array
    {
        $values = SiteContent::valuesFor(SiteSection::Stats);

        return collect(['one', 'two', 'three'])
            ->map(fn (string $slot) => [
                'figure' => $values->get('figure_'.$slot, ''),
                'label' => $values->get('label_'.$slot, ''),
            ])
            ->filter(fn (array $stat) => $stat['figure'] !== '' && $stat['label'] !== '')
            ->values()
            ->all();
    }
}
