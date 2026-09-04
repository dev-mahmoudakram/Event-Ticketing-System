<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\SiteFaq;
use App\Support\SiteText;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function show(): View
    {
        SiteText::flush();

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
            'whyEgypt' => $this->whyEgypt(),
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
        return collect(['one', 'two', 'three'])
            ->map(fn (string $slot) => [
                'figure' => SiteText::stored('stats', 'figure_'.$slot) ?? '',
                'label' => SiteText::stored('stats', 'label_'.$slot) ?? '',
            ])
            ->filter(fn (array $stat) => $stat['figure'] !== '' && $stat['label'] !== '')
            ->values()
            ->all();
    }

    /**
     * Reasons to build here. Entirely admin-supplied — this app makes no claims about the
     * region on its own, so the section stays absent until someone writes them.
     *
     * @return array{heading: ?string, body: ?string, points: list<string>, image: ?string}
     */
    private function whyEgypt(): array
    {
        return [
            'heading' => SiteText::stored('why_egypt', 'heading'),
            'body' => SiteText::stored('why_egypt', 'body'),
            'points' => collect(['point_one', 'point_two', 'point_three', 'point_four', 'point_five', 'point_six'])
                ->map(fn (string $key) => SiteText::stored('why_egypt', $key))
                ->filter()
                ->values()
                ->all(),
            'image' => SiteText::image('why_egypt', 'image'),
        ];
    }
}
