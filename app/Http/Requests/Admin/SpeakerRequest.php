<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Event;
use App\Models\Speaker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SpeakerRequest extends FormRequest
{
    /** Public-facing cap on how many speakers can be featured on the landing page at once. */
    private const MAX_FEATURED = 4;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');
        /** @var Speaker|null $speaker */
        $speaker = $this->route('speaker');

        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'bio_ar' => ['nullable', 'string'],
            'bio_en' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => [
                'nullable',
                'boolean',
                Rule::when(
                    $this->boolean('is_featured'),
                    [function (string $attribute, mixed $value, \Closure $fail) use ($event, $speaker): void {
                        $featuredCount = $event->speakers()
                            ->where('is_featured', true)
                            ->when($speaker, fn ($query) => $query->whereKeyNot($speaker->id))
                            ->count();

                        if ($featuredCount >= self::MAX_FEATURED) {
                            $fail(__('Only :max speakers can be featured at once.', ['max' => self::MAX_FEATURED]));
                        }
                    }],
                ),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_featured' => $this->boolean('is_featured')]);
    }
}
