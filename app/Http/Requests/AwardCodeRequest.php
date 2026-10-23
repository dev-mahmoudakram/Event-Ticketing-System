<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Award;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Asking for a voting code: which nominee, and the email to send it to. The Turnstile token
 * is checked by the controller, not here.
 */
class AwardCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nominee_id' => ['required', 'integer', $this->nomineeOfThisAward()],
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    protected function nomineeOfThisAward(): mixed
    {
        /** @var Award $award */
        $award = $this->route('award');

        return Rule::exists('award_nominees', 'id')->where('award_id', $award->id);
    }
}
