<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Propaganistas\LaravelPhone\Rules\Phone;

class SpeakerRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'title_en' => ['required', 'string', 'max:255'],
            'bio_ar' => ['required', 'string', 'max:2000'],
            'bio_en' => ['required', 'string', 'max:2000'],
            'photo' => ['required', 'image', 'max:4096'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', (new Phone)->international()],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return ['photo' => __('Photo')];
    }
}
