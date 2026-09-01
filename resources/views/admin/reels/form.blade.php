{{-- resources/views/admin/reels/form.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$reel->exists ? __('Edit Reel') : __('New Reel')" />

    <form method="POST" action="{{ $reel->exists ? route('admin.events.reels.update', [$event, $reel]) : route('admin.events.reels.store', $event) }}" enctype="multipart/form-data">
        @csrf
        @if($reel->exists) @method('PUT') @endif

        <x-admin.media-upload
            name="video"
            kind="video"
            accept="video/mp4,video/webm,video/quicktime"
            :label="__('Video')"
            :current="$reel->videoUrl()"
            :required="!$reel->exists"
            :hint="__('Vertical clip (9:16 works best). This server accepts up to :limit.', ['limit' => $uploadLimit])"
        />

        <x-admin.media-upload
            name="poster"
            :label="__('Poster Image')"
            :current="$reel->posterUrl()"
            :hint="__('Optional still shown before the clip plays.')"
        />

        <x-admin.bilingual-field name="caption" label="{{ __('Caption') }}" :value-ar="old('caption_ar', $reel->caption_ar)" :value-en="old('caption_en', $reel->caption_en)" />
        <x-admin.field type="number" name="sort_order" label="{{ __('Sort Order') }}" :value="old('sort_order', $reel->sort_order ?? 0)" />

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
