<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesFollowerCounts;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\Rules\Phone;

class InvitationRequestStoreRequest extends FormRequest
{
    use NormalizesFollowerCounts;

    private const FOLLOWER_KEYS = ['instagram_followers', 'facebook_followers', 'tiktok_followers'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeFollowerCounts(self::FOLLOWER_KEYS);
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
            'instagram_followers' => $this->followerCountRules(),
            'facebook_url' => ['nullable', 'url:http,https', 'max:2048'],
            'facebook_followers' => $this->followerCountRules(),
            'tiktok_url' => ['nullable', 'url:http,https', 'max:2048'],
            'tiktok_followers' => $this->followerCountRules(),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return $this->followerCountMessages(self::FOLLOWER_KEYS);
    }
}
