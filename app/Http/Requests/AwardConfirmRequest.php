<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * Confirming a vote with the emailed code. The nominee must be the one the code was sent
 * for; AwardVoting checks that, this only checks it belongs to the award.
 */
class AwardConfirmRequest extends AwardCodeRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nominee_id' => ['required', 'integer', $this->nomineeOfThisAward()],
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'string', 'max:20'],
        ];
    }
}
