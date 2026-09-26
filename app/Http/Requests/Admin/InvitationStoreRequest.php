<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvitationStoreRequest extends FormRequest
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
            'ticket_type_id' => [
                'required', 'integer',
                Rule::exists('ticket_types', 'id')->where('event_id', $event->id)->where('is_active', true),
            ],
        ];
    }
}
