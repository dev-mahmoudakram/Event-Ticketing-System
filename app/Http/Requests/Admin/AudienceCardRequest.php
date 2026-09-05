<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Support\UploadLimit;
use Illuminate\Foundation\Http\FormRequest;

class AudienceCardRequest extends FormRequest
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
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'body_en' => ['nullable', 'string', 'max:2000'],
            'body_ar' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:'.UploadLimit::effectiveKilobytes((int) config('media.max_image_kb'))],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['image' => __('Image')];
    }
}
