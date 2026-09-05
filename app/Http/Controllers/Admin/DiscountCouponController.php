<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DiscountCouponRequest;
use App\Models\DiscountCoupon;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DiscountCouponController extends Controller
{
    public function index(Event $event): View
    {
        return view('admin.discount-coupons.index', [
            'event' => $event,
            'coupons' => $event->discountCoupons()->withCount('tickets')->get(),
        ]);
    }

    public function create(Event $event): View
    {
        return view('admin.discount-coupons.form', ['event' => $event, 'coupon' => new DiscountCoupon]);
    }

    public function store(DiscountCouponRequest $request, Event $event): RedirectResponse
    {
        $event->discountCoupons()->create($request->validated());

        return redirect()->route('admin.events.discount-coupons.index', $event);
    }

    public function edit(Event $event, DiscountCoupon $discountCoupon): View
    {
        $this->assertBelongsToEvent($event, $discountCoupon);

        return view('admin.discount-coupons.form', ['event' => $event, 'coupon' => $discountCoupon]);
    }

    public function update(DiscountCouponRequest $request, Event $event, DiscountCoupon $discountCoupon): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $discountCoupon);

        $discountCoupon->update($request->validated());

        return redirect()->route('admin.events.discount-coupons.index', $event);
    }

    public function destroy(Event $event, DiscountCoupon $discountCoupon): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $discountCoupon);

        // Tickets keep their price and discount; they simply lose the reference.
        $discountCoupon->delete();

        return redirect()->route('admin.events.discount-coupons.index', $event);
    }

    private function assertBelongsToEvent(Event $event, DiscountCoupon $coupon): void
    {
        if ($coupon->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }
}
