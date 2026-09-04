<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HeroSlideRequest;
use App\Models\HeroSlide;
use App\Support\UploadLimit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HeroSlideController extends Controller
{
    use HandlesMediaUploads;

    public function index(): View
    {
        return view('admin.hero-slides.index', [
            'slides' => HeroSlide::orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.hero-slides.form', [
            'slide' => new HeroSlide,
            'uploadLimit' => $this->uploadLimitLabel(),
        ]);
    }

    public function store(HeroSlideRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['image']);
        $data = $this->withUploadedMedia($data, $request, 'image', 'image_path', 'hero-slides');

        HeroSlide::create($data);

        return redirect()->route('admin.hero-slides.index');
    }

    public function edit(HeroSlide $heroSlide): View
    {
        return view('admin.hero-slides.form', [
            'slide' => $heroSlide,
            'uploadLimit' => $this->uploadLimitLabel(),
        ]);
    }

    public function update(HeroSlideRequest $request, HeroSlide $heroSlide): RedirectResponse
    {
        $data = $request->safe()->except(['image']);
        $data = $this->withUploadedMedia($data, $request, 'image', 'image_path', 'hero-slides', $heroSlide->image_path);

        $heroSlide->update($data);

        return redirect()->route('admin.hero-slides.index');
    }

    public function destroy(HeroSlide $heroSlide): RedirectResponse
    {
        $this->deleteStoredMedia($heroSlide->image_path);
        $heroSlide->delete();

        return redirect()->route('admin.hero-slides.index');
    }

    private function uploadLimitLabel(): string
    {
        return UploadLimit::label(UploadLimit::effectiveKilobytes((int) config('media.max_image_kb')));
    }
}
