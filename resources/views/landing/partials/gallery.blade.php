{{-- resources/views/landing/partials/gallery.blade.php --}}
@php
    $galleryEyebrow = $event->contentFor(\App\Enums\LandingPageSection::Gallery, 'eyebrow');
    $galleryHeading = $event->contentFor(\App\Enums\LandingPageSection::Gallery, 'heading');
    $eyebrowText = ($galleryEyebrow ? (app()->getLocale() === 'ar' ? $galleryEyebrow->value_ar : $galleryEyebrow->value_en) : null) ?: __('Gallery');
    $headingText = ($galleryHeading ? (app()->getLocale() === 'ar' ? $galleryHeading->value_ar : $galleryHeading->value_en) : null) ?: __('Last year, in frames.');
@endphp
@if($event->galleryPhotos->isNotEmpty() && $event->isSectionVisible('gallery'))
    <section id="gallery" class="ccs-section scroll-mt-24">
        <div class="ccs-eyebrow text-ccs-coral" data-reveal>{{ $eyebrowText }}</div>
        <h2 class="font-display text-3xl md:text-5xl font-extrabold mb-10" data-reveal>{{ $headingText }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($event->galleryPhotos as $photo)
                <div class="aspect-square rounded-xl overflow-hidden border border-white/10 bg-white/5" data-reveal data-reveal-delay="{{ min($loop->iteration, 5) }}">
                    <img src="{{ $photo->imageUrl() }}" alt="{{ app()->getLocale() === 'ar' ? $photo->caption_ar : $photo->caption_en }}" class="w-full h-full object-cover transition-transform duration-500 ease-out hover:scale-110" loading="lazy">
                </div>
            @endforeach
        </div>
    </section>
@endif
