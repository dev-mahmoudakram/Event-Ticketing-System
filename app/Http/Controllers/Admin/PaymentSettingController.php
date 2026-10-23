<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentSettingRequest;
use App\Models\PaymentSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The Kashier account the platform takes payments with. Admin only (AdminPermissions::ADMIN_ONLY).
 */
class PaymentSettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.payment-settings.edit', ['settings' => PaymentSetting::current()]);
    }

    public function update(PaymentSettingRequest $request): RedirectResponse
    {
        $settings = PaymentSetting::current();
        $settings->fill($request->settingAttributes())->save();

        return redirect()->route('admin.payment-settings.edit')->with('success', __('Payment settings saved.'));
    }
}
