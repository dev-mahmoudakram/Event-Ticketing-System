<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgendaItemRequest extends FormRequest
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
            'session_type_id' => ['required', 'integer', Rule::exists('session_types', 'id')->where('event_id', $event->id)],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('event_id', $event->id)],
            'day_date' => ['required', 'date', 'after_or_equal:'.$event->start_date->toDateString(), 'before_or_equal:'.$event->end_date->toDateString()],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'title_ar' => ['required', 'string', 'max:255'],
            'title_en' => ['required', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'speaker_ids' => ['nullable', 'array'],
            'speaker_ids.*' => ['integer', 'distinct', Rule::exists('speakers', 'id')->where('event_id', $event->id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'session_type_id' => __('Type'),
            'location_id' => __('Location'),
            'speaker_ids.*' => __('Speaker'),
        ];
    }
}
