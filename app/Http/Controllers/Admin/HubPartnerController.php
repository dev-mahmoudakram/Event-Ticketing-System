<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HubPartnerRequest;
use App\Models\HubPartner;
use App\Support\UploadLimit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HubPartnerController extends Controller
{
    use HandlesMediaUploads;

    public function index(): View
    {
        return view('admin.hub-partners.index', [
            'partners' => HubPartner::orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.hub-partners.form', [
            'partner' => new HubPartner,
            'uploadLimit' => $this->uploadLimitLabel(),
        ]);
    }

    public function store(HubPartnerRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['logo']);
        $data = $this->withUploadedMedia($data, $request, 'logo', 'logo_path', 'hub-partners');

        HubPartner::create($data);

        return redirect()->route('admin.hub-partners.index');
    }

    public function edit(HubPartner $hubPartner): View
    {
        return view('admin.hub-partners.form', [
            'partner' => $hubPartner,
            'uploadLimit' => $this->uploadLimitLabel(),
        ]);
    }

    public function update(HubPartnerRequest $request, HubPartner $hubPartner): RedirectResponse
    {
        $data = $request->safe()->except(['logo']);
        $data = $this->withUploadedMedia($data, $request, 'logo', 'logo_path', 'hub-partners', $hubPartner->logo_path);

        $hubPartner->update($data);

        return redirect()->route('admin.hub-partners.index');
    }

    public function destroy(HubPartner $hubPartner): RedirectResponse
    {
        $this->deleteStoredMedia($hubPartner->logo_path);
        $hubPartner->delete();

        return redirect()->route('admin.hub-partners.index');
    }

    private function uploadLimitLabel(): string
    {
        return UploadLimit::label(UploadLimit::effectiveKilobytes((int) config('media.max_image_kb')));
    }
}
