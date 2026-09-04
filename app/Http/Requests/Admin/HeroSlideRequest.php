<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\HeroSlide;
use App\Support\UploadLimit;
use Illuminate\Foundation\Http\FormRequest;

class HeroSlideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $slide = $this->route('heroSlide');
        $hasImage = $slide instanceof HeroSlide && $slide->exists && $slide->image_path !== null;

        return [
            // A slide with no image is just an empty panel, so require one when creating.
            'image' => [$hasImage ? 'nullable' : 'required', 'image', 'max:'.UploadLimit::effectiveKilobytes((int) config('media.max_image_kb'))],
            'headline_ar' => ['nullable', 'string', 'max:255'],
            'headline_en' => ['nullable', 'string', 'max:255'],
            'body_ar' => ['nullable', 'string', 'max:1000'],
            'body_en' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return ['image' => __('Image')];
    }
}
