<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\DiscountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DiscountCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Codes are typed by hand on a phone; case and stray spaces should not decide a match.
        if ($this->filled('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
    }

    public function rules(): array
    {
        $eventId = $this->route('event')?->id;
        $couponId = $this->route('discountCoupon')?->id;

        return [
            'code' => [
                'required', 'string', 'max:40', 'regex:/^[A-Z0-9-]+$/',
                Rule::unique('discount_coupons', 'code')->where('event_id', $eventId)->ignore($couponId),
            ],
            'type' => ['required', Rule::enum(DiscountType::class)],
            // A percentage cannot exceed 100; a fixed amount is bounded by the ticket price at
            // redemption, so it only has to be a sane number here.
            'value' => ['required', 'integer', 'min:1', $this->input('type') === 'percentage' ? 'max:100' : 'max:1000000'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => __('Use letters, numbers and dashes only.'),
            'code.unique' => __('This event already has a coupon with that code.'),
        ];
    }
}
