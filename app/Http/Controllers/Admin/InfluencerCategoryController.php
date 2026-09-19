<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InfluencerCategoryRequest;
use App\Models\Event;
use App\Models\InfluencerCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class InfluencerCategoryController extends Controller
{
    public function index(Event $event): View
    {
        return view('admin.influencer-categories.index', ['event' => $event, 'influencerCategories' => $event->influencerCategories]);
    }

    public function create(Event $event): View
    {
        return view('admin.influencer-categories.form', ['event' => $event, 'influencerCategory' => new InfluencerCategory]);
    }

    public function store(InfluencerCategoryRequest $request, Event $event): RedirectResponse
    {
        $event->influencerCategories()->create($request->validated());

        return redirect()->route('admin.events.influencer-categories.index', $event);
    }

    public function edit(Event $event, InfluencerCategory $influencerCategory): View
    {
        $this->assertBelongsToEvent($event, $influencerCategory);

        return view('admin.influencer-categories.form', ['event' => $event, 'influencerCategory' => $influencerCategory]);
    }

    public function update(InfluencerCategoryRequest $request, Event $event, InfluencerCategory $influencerCategory): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $influencerCategory);
        $influencerCategory->update($request->validated());

        return redirect()->route('admin.events.influencer-categories.index', $event);
    }

    public function destroy(Event $event, InfluencerCategory $influencerCategory): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $influencerCategory);
        $influencerCategory->delete();

        return redirect()->route('admin.events.influencer-categories.index', $event);
    }

    public function updateSettings(Request $request, Event $event): RedirectResponse
    {
        $event->update([
            'require_influencer_category' => $request->boolean('require_influencer_category'),
        ]);

        return redirect()->route('admin.events.influencer-categories.index', $event);
    }

    private function assertBelongsToEvent(Event $event, InfluencerCategory $influencerCategory): void
    {
        if ($influencerCategory->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }
}
