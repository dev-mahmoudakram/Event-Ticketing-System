<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AudienceTabRequest;
use App\Models\AudienceTab;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AudienceTabController extends Controller
{
    use HandlesMediaUploads;

    public function index(): View
    {
        return view('admin.audience-tabs.index', [
            'tabs' => AudienceTab::with('cards')->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.audience-tabs.form', ['tab' => new AudienceTab]);
    }

    public function store(AudienceTabRequest $request): RedirectResponse
    {
        $tab = AudienceTab::create($request->validated());

        return redirect()->route('admin.audience-tabs.edit', $tab)
            ->with('status', __('Tab saved. Add its cards below.'));
    }

    public function edit(AudienceTab $audienceTab): View
    {
        return view('admin.audience-tabs.form', [
            'tab' => $audienceTab->load('cards'),
        ]);
    }

    public function update(AudienceTabRequest $request, AudienceTab $audienceTab): RedirectResponse
    {
        $audienceTab->update($request->validated());

        return redirect()->route('admin.audience-tabs.index')->with('status', __('Tab saved.'));
    }

    public function destroy(AudienceTab $audienceTab): RedirectResponse
    {
        // Cards go with the tab, so their images would otherwise be orphaned on disk.
        foreach ($audienceTab->cards as $card) {
            $this->deleteStoredMedia($card->image_path);
        }

        $audienceTab->delete();

        return redirect()->route('admin.audience-tabs.index')->with('status', __('Tab deleted.'));
    }

    /**
     * Save an order dragged in the browser.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:audience_tabs,id'],
        ]);

        foreach ($validated['ids'] as $position => $id) {
            AudienceTab::whereKey($id)->update(['sort_order' => $position]);
        }

        return response()->json(['status' => 'ok']);
    }
}
