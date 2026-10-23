<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\VotingMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AwardVotingSettingsRequest extends FormRequest
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
        return [
            'voting_mode' => ['required', Rule::enum(VotingMode::class)],
            'voting_opens_at' => ['nullable', 'date'],
            'voting_closes_at' => array_filter([
                'nullable',
                'date',
                $this->filled('voting_opens_at') ? 'after:voting_opens_at' : null,
            ]),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['voting_closes_at.after' => __('Voting must close after it opens.')];
    }
}
