{{-- resources/views/admin/faqs/form.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$faq->exists ? __('Edit FAQ') : __('New FAQ')" />

    <form method="POST" action="{{ $faq->exists ? route('admin.events.faqs.update', [$event, $faq]) : route('admin.events.faqs.store', $event) }}">
        @csrf
        @if($faq->exists) @method('PUT') @endif

        <x-admin.bilingual-field name="question" label="{{ __('Question') }}" :value-ar="old('question_ar', $faq->question_ar)" :value-en="old('question_en', $faq->question_en)" />
        <x-admin.bilingual-field type="richtext" name="answer" label="{{ __('Answer') }}" :value-ar="old('answer_ar', $faq->answer_ar)" :value-en="old('answer_en', $faq->answer_en)" />
        <x-admin.field type="number" name="sort_order" label="{{ __('Sort Order') }}" :value="old('sort_order', $faq->sort_order ?? 0)" />

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
