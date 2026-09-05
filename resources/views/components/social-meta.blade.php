{{-- resources/views/components/social-meta.blade.php --}}
@props([
    'title',
    'description' => null,
    'image' => null,
    'type' => 'website',
])

@php
    use App\Support\SocialMeta;

    $url = url()->current();
    $locale = app()->getLocale();
    $imageUrl = SocialMeta::absoluteUrl($image);
    $imageDetails = SocialMeta::imageDetails($image);
@endphp

@if($description)
    <meta name="description" content="{{ $description }}">
@endif
<link rel="canonical" href="{{ $url }}">

{{-- The same page in the other language is a query away, so crawlers are pointed at both. --}}
<link rel="alternate" hreflang="ar" href="{{ $url }}?lang=ar">
<link rel="alternate" hreflang="en" href="{{ $url }}?lang=en">
<link rel="alternate" hreflang="x-default" href="{{ $url }}">

<meta property="og:site_name" content="Creators Hub">
<meta property="og:type" content="{{ $type }}">
<meta property="og:url" content="{{ $url }}">
<meta property="og:title" content="{{ $title }}">
@if($description)
    <meta property="og:description" content="{{ $description }}">
@endif
<meta property="og:locale" content="{{ $locale === 'ar' ? 'ar_EG' : 'en_US' }}">
<meta property="og:locale:alternate" content="{{ $locale === 'ar' ? 'en_US' : 'ar_EG' }}">
@if($imageUrl)
    <meta property="og:image" content="{{ $imageUrl }}">
    @if(str_starts_with($imageUrl, 'https://'))
        <meta property="og:image:secure_url" content="{{ $imageUrl }}">
    @endif
    <meta property="og:image:alt" content="{{ $title }}">
    @if($imageDetails)
        <meta property="og:image:type" content="{{ $imageDetails['mime'] }}">
        <meta property="og:image:width" content="{{ $imageDetails['width'] }}">
        <meta property="og:image:height" content="{{ $imageDetails['height'] }}">
    @endif
@endif

<meta name="twitter:card" content="{{ $imageUrl ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $title }}">
@if($description)
    <meta name="twitter:description" content="{{ $description }}">
@endif
@if($imageUrl)
    <meta name="twitter:image" content="{{ $imageUrl }}">
    <meta name="twitter:image:alt" content="{{ $title }}">
@endif
