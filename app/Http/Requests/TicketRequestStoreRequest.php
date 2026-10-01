<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TicketRequestFieldType;
use App\Http\Requests\Concerns\NormalizesFollowerCounts;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\Rules\Phone;

class TicketRequestStoreRequest extends FormRequest
{
    use NormalizesFollowerCounts;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeFollowerCounts($this->followerKeys());
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
            'influencer_category_id' => [
                $event->require_influencer_category ? 'required' : 'nullable',
                Rule::in(array_merge(
                    ['other'],
                    $event->influencerCategories->pluck('id')->map(fn ($id) => (string) $id)->all(),
                )),
            ],
            'influencer_category_other' => ['nullable', 'required_if:influencer_category_id,other', 'string', 'max:255'],
            'accept_terms' => ['accepted'],
        ];

        foreach ($event->ticketRequestFields as $field) {
            $inputKey = 'field_'.$field->id;
            $requiredRule = $field->is_required ? 'required' : 'nullable';

            $rules += match ($field->type) {
                TicketRequestFieldType::Instagram => [
                    $inputKey => [$requiredRule, 'url', 'max:2048'],
                ],
                TicketRequestFieldType::Portfolio => [
                    $inputKey.'_mode' => [$requiredRule, 'in:url,pdf'],
                    $inputKey.'_url' => ['nullable', 'required_if:'.$inputKey.'_mode,url', 'url', 'max:2048'],
                    $inputKey.'_file' => ['nullable', 'required_if:'.$inputKey.'_mode,pdf', 'file', 'mimes:pdf', 'max:5120'],
                ],
                TicketRequestFieldType::Cv => [
                    $inputKey => [$requiredRule, 'file', 'mimes:pdf,doc,docx', 'max:5120'],
                ],
                TicketRequestFieldType::SocialLink => [
                    $inputKey => [$requiredRule, 'url', 'max:2048'],
                ],
            };

            if (in_array($field->type, [TicketRequestFieldType::Instagram, TicketRequestFieldType::SocialLink], true)) {
                $rules[$inputKey.'_followers'] = $this->followerCountRules();
            }
        }

        return $rules;
    }

    /**
     * Names the admin-defined fields by the label the visitor sees, so an error reads
     * "The Instagram field is required." rather than "The field 1 field is required."
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        /** @var Event $event */
        $event = $this->route('event');
        $attributes = [];

        foreach ($event->ticketRequestFields as $field) {
            $inputKey = 'field_'.$field->id;
            $label = app()->getLocale() === 'ar' ? $field->label_ar : $field->label_en;

            foreach (['', '_mode', '_url', '_file'] as $suffix) {
                $attributes[$inputKey.$suffix] = $label;
            }
            $attributes[$inputKey.'_followers'] = __('Follower count');
        }

        $attributes['accept_terms'] = __('agreement');

        return $attributes;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return $this->followerCountMessages($this->followerKeys()) + [
            'accept_terms.accepted' => __('Please agree to the Terms & Conditions and the Refund & Cancellation Policy.'),
        ];
    }

    /**
     * The follower-count inputs this event's form has: one per Instagram or social link field.
     *
     * @return list<string>
     */
    private function followerKeys(): array
    {
        /** @var Event $event */
        $event = $this->route('event');

        return $event->ticketRequestFields
            ->filter(fn ($field) => in_array($field->type, [TicketRequestFieldType::Instagram, TicketRequestFieldType::SocialLink], true))
            ->map(fn ($field) => 'field_'.$field->id.'_followers')
            ->values()
            ->all();
    }
}
