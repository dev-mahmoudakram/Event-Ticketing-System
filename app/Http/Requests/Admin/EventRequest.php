<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Rules\SafeSvg;
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
        $imageLimit = UploadLimit::effectiveKilobytes((int) config('media.max_image_kb'));

        return [
            'slug' => ['required', 'string', 'max:255', Rule::unique('events', 'slug')->ignore($eventId)],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'tagline_ar' => ['nullable', 'string', 'max:255'],
            'tagline_en' => ['nullable', 'string', 'max:255'],
            'cover_image' => ['nullable', 'image', 'max:'.$imageLimit],
            'logo' => ['nullable', 'image:allow_svg', new SafeSvg, 'max:'.$imageLimit],
            'footer_logo' => ['nullable', 'image:allow_svg', new SafeSvg, 'max:'.$imageLimit],
            'favicon' => ['nullable', 'image:allow_svg', new SafeSvg, 'max:'.$imageLimit],
            'apple_touch_icon' => ['nullable', 'image', 'max:'.$imageLimit],
            'share_image' => ['nullable', 'image', 'max:'.$imageLimit],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'url', 'max:2048'],
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
        return [
            'cover_image' => __('Cover Image'),
            'logo' => __('Logo'),
            'footer_logo' => __('Footer Logo'),
            'favicon' => __('Favicon'),
            'apple_touch_icon' => __('Apple Touch Icon'),
            'share_image' => __('Link Preview Image'),
        ];
    }
}
