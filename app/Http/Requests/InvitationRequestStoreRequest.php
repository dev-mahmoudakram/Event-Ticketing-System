<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\Rules\Phone;

class InvitationRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');

        return [
            'name' => ['required', 'string', 'max:255', 'regex:/^[\pL\pM\s\'\-\.]+$/u'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', (new Phone)->international()],
            'influencer_category_id' => [
                $event->require_influencer_category ? 'required' : 'nullable',
                Rule::in(array_merge(['other'], $event->influencerCategories->pluck('id')->map(fn ($id) => (string) $id)->all())),
            ],
            'influencer_category_other' => ['nullable', 'required_if:influencer_category_id,other', 'string', 'max:255'],
            'instagram_url' => ['nullable', 'url:http,https', 'max:2048'],
            'instagram_followers' => ['nullable', 'integer', 'between:0,4294967295'],
            'facebook_url' => ['nullable', 'url:http,https', 'max:2048'],
            'facebook_followers' => ['nullable', 'integer', 'between:0,4294967295'],
            'tiktok_url' => ['nullable', 'url:http,https', 'max:2048'],
            'tiktok_followers' => ['nullable', 'integer', 'between:0,4294967295'],
        ];
    }
}
