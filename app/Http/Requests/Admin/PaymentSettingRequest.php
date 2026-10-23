<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMode;
use App\Models\PaymentSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The keys are never shown back, so a blank key field means "keep the saved one" — but
     * only once one has been saved; a key that was never entered is required.
     */
    public function rules(): array
    {
        $saved = PaymentSetting::current();

        return [
            'mode' => ['required', Rule::enum(PaymentMode::class)],
            'merchant_id' => ['required', 'string', 'max:100', 'regex:/^MID-[A-Za-z0-9-]+$/'],
            'api_key' => [filled($saved->api_key) ? 'nullable' : 'required', 'string', 'max:255'],
            'secret_key' => [filled($saved->secret_key) ? 'nullable' : 'required', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'mode' => __('Mode'),
            'merchant_id' => __('Merchant ID'),
            'api_key' => __('Payment API key'),
            'secret_key' => __('Secret key'),
        ];
    }

    public function messages(): array
    {
        return [
            'merchant_id.regex' => __('The merchant ID looks like MID-12345-678.'),
        ];
    }

    /**
     * What to save: the mode and merchant ID always, each key only when a new one was typed.
     *
     * @return array<string, string>
     */
    public function settingAttributes(): array
    {
        $attributes = [
            'mode' => (string) $this->validated('mode'),
            'merchant_id' => trim((string) $this->validated('merchant_id')),
        ];

        foreach (['api_key', 'secret_key'] as $key) {
            if (filled($this->validated($key))) {
                $attributes[$key] = trim((string) $this->validated($key));
            }
        }

        return $attributes;
    }
}
