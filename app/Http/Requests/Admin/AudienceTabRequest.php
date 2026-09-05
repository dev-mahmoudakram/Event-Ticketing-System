<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AudienceTabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            // The label names the button in the switch, so a tab cannot exist without one.
            'label_en' => ['required', 'string', 'max:255'],
            'label_ar' => ['nullable', 'string', 'max:255'],
            'lede_en' => ['nullable', 'string', 'max:500'],
            'lede_ar' => ['nullable', 'string', 'max:500'],
            'cta_en' => ['nullable', 'string', 'max:255'],
            'cta_ar' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
