<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Event;
use App\Models\EventPage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');
        /** @var EventPage|null $page */
        $page = $this->route('page');

        return [
            'title_ar' => ['required', 'string', 'max:150'],
            'title_en' => ['required', 'string', 'max:150'],
            // Required pages keep their address; pageAttributes() ignores a posted slug for them.
            'slug' => $page?->isRequired() ? ['nullable'] : [
                'required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('event_pages', 'slug')->where('event_id', $event->id)->ignore($page?->id),
            ],
            'body_ar' => ['nullable', 'string'],
            'body_en' => ['nullable', 'string'],
            'show_in_footer' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['slug.regex' => __('Use lowercase English letters, numbers and single dashes only, e.g. ccs-2026.')];
    }

    /**
     * The attributes to save. Required pages keep their slug and stay published.
     *
     * @return array<string, mixed>
     */
    public function pageAttributes(?EventPage $page): array
    {
        $attributes = [
            'title_ar' => $this->validated('title_ar'),
            'title_en' => $this->validated('title_en'),
            'body_ar' => $this->validated('body_ar'),
            'body_en' => $this->validated('body_en'),
            'show_in_footer' => $this->boolean('show_in_footer'),
        ];

        if (! $page?->isRequired()) {
            $attributes['slug'] = $this->validated('slug');
            $attributes['is_published'] = $this->boolean('is_published');
        }

        return $attributes;
    }
}
