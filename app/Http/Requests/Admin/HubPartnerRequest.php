<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\HubPartner;
use App\Support\UploadLimit;
use Illuminate\Foundation\Http\FormRequest;

class HubPartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $partner = $this->route('hubPartner');
        $hasLogo = $partner instanceof HubPartner && $partner->exists && $partner->logo_path !== null;

        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            // The row is a logo tile, so a logo is required unless one is already stored.
            'logo' => [$hasLogo ? 'nullable' : 'required', 'image', 'max:'.UploadLimit::effectiveKilobytes((int) config('media.max_image_kb'))],
            'website_url' => ['nullable', 'url', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return ['logo' => __('Logo')];
    }
}
