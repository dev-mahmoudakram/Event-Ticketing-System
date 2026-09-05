<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TicketRequestFieldType;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\Rules\Phone;

class TicketRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');

        $rules = [
            'ticket_type_id' => [
                'required',
                'integer',
                Rule::exists('ticket_types', 'id')->where('event_id', $event->id)->where('is_active', true),
            ],
            'name' => ['required', 'string', 'max:255', 'regex:/^[\pL\pM\s\'\-\.]+$/u'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', (new Phone)->international()],
            // An unknown or expired code is not a validation failure: the request goes through
            // at full price, and the form says the code was not applied.
            'coupon_code' => ['nullable', 'string', 'max:40'],
        ];

        foreach ($event->ticketRequestFields as $field) {
            $inputKey = 'field_'.$field->id;
            $requiredRule = $field->is_required ? 'required' : 'nullable';

            $rules += match ($field->type) {
                TicketRequestFieldType::Instagram => [
                    $inputKey => [$requiredRule, 'regex:/^@?[A-Za-z0-9_.]{1,30}$/'],
                ],
                TicketRequestFieldType::Portfolio => [
                    $inputKey.'_mode' => [$requiredRule, 'in:url,pdf'],
                    $inputKey.'_url' => ['nullable', 'required_if:'.$inputKey.'_mode,url', 'url', 'max:2048'],
                    $inputKey.'_file' => ['nullable', 'required_if:'.$inputKey.'_mode,pdf', 'file', 'mimes:pdf', 'max:5120'],
                ],
                TicketRequestFieldType::Cv => [
                    $inputKey => [$requiredRule, 'file', 'mimes:pdf,doc,docx', 'max:5120'],
                ],
            };
        }

        return $rules;
    }
}
