<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkshopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');
        $workshopId = $this->route('workshop')?->id;

        return [
            'slug' => ['required', 'string', 'max:255', Rule::unique('workshops', 'slug')->ignore($workshopId)],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'day_date' => ['required', 'date', 'after_or_equal:'.$event->start_date->toDateString(), 'before_or_equal:'.$event->end_date->toDateString()],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('event_id', $event->id)],
            'speaker_ids' => ['nullable', 'array'],
            'speaker_ids.*' => ['integer', 'distinct', Rule::exists('speakers', 'id')->where('event_id', $event->id)],
            'capacity' => ['required', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'location_id' => __('Location'),
            'speaker_ids.*' => __('Speaker'),
        ];
    }
}
