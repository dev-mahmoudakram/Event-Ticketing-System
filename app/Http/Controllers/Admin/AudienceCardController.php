<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AudienceCardRequest;
use App\Models\AudienceCard;
use App\Models\AudienceTab;
use App\Support\UploadLimit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AudienceCardController extends Controller
{
    use HandlesMediaUploads;

    public function create(AudienceTab $audienceTab): View
    {
        return view('admin.audience-tabs.card-form', [
            'tab' => $audienceTab,
            'card' => new AudienceCard,
            'uploadLimit' => $this->uploadLimitLabel(),
        ]);
    }

    public function store(AudienceCardRequest $request, AudienceTab $audienceTab): RedirectResponse
    {
        $data = $request->safe()->except(['image']);
        $data = $this->withUploadedMedia($data, $request, 'image', 'image_path', 'audiences');

        // A new card joins the end of the tab rather than sharing position zero with the first.
        $data['sort_order'] ??= (int) $audienceTab->cards()->max('sort_order') + 1;

        $audienceTab->cards()->create($data);

        return redirect()->route('admin.audience-tabs.edit', $audienceTab)->with('status', __('Card added.'));
    }

    public function edit(AudienceTab $audienceTab, AudienceCard $card): View
    {
        $this->assertBelongsTo($audienceTab, $card);

        return view('admin.audience-tabs.card-form', [
            'tab' => $audienceTab,
            'card' => $card,
            'uploadLimit' => $this->uploadLimitLabel(),
        ]);
    }

    public function update(AudienceCardRequest $request, AudienceTab $audienceTab, AudienceCard $card): RedirectResponse
    {
        $this->assertBelongsTo($audienceTab, $card);

        $data = $request->safe()->except(['image']);
        $data = $this->withUploadedMedia($data, $request, 'image', 'image_path', 'audiences', $card->image_path);

        $card->update($data);

        return redirect()->route('admin.audience-tabs.edit', $audienceTab)->with('status', __('Card saved.'));
    }

    public function destroy(AudienceTab $audienceTab, AudienceCard $card): RedirectResponse
    {
        $this->assertBelongsTo($audienceTab, $card);

        $this->deleteStoredMedia($card->image_path);
        $card->delete();

        return redirect()->route('admin.audience-tabs.edit', $audienceTab)->with('status', __('Card deleted.'));
    }

    /**
     * Save an order dragged in the browser.
     */
    public function reorder(Request $request, AudienceTab $audienceTab): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach ($validated['ids'] as $position => $id) {
            // Scoped to this tab, so a posted id from another tab cannot be reordered here.
            $audienceTab->cards()->whereKey($id)->update(['sort_order' => $position]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * A card reached through the wrong tab is not this tab's card.
     */
    private function assertBelongsTo(AudienceTab $tab, AudienceCard $card): void
    {
        if ($card->audience_tab_id !== $tab->id) {
            throw new NotFoundHttpException;
        }
    }

    private function uploadLimitLabel(): string
    {
        return UploadLimit::label(UploadLimit::effectiveKilobytes((int) config('media.max_image_kb')));
    }
}
