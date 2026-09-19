<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\TicketRequestFieldType;
use App\Support\SocialPlatforms;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketRequestFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_column(TicketRequestFieldType::cases(), 'value'))],
            'platform' => [
                Rule::requiredIf($this->input('type') === TicketRequestFieldType::SocialLink->value),
                'nullable',
                Rule::in(SocialPlatforms::keys()),
            ],
            'label_ar' => ['required', 'string', 'max:255'],
            'label_en' => ['required', 'string', 'max:255'],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_required' => $this->boolean('is_required'),
        ]);
    }
}
