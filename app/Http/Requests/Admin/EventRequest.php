<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Support\UploadLimit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $eventId = $this->route('event')?->id;

        return [
            'slug' => ['required', 'string', 'max:255', Rule::unique('events', 'slug')->ignore($eventId)],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'tagline_ar' => ['nullable', 'string', 'max:255'],
            'tagline_en' => ['nullable', 'string', 'max:255'],
            'cover_image' => ['nullable', 'image', 'max:'.UploadLimit::effectiveKilobytes((int) config('media.max_image_kb'))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'venue_name_ar' => ['nullable', 'string', 'max:255'],
            'venue_name_en' => ['nullable', 'string', 'max:255'],
            'venue_address_ar' => ['nullable', 'string', 'max:255'],
            'venue_address_en' => ['nullable', 'string', 'max:255'],
            'map_embed_url' => ['nullable', 'url'],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ];
    }

    public function attributes(): array
    {
        return ['cover_image' => __('Cover Image')];
    }
}
