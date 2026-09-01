<p>{{ __('Hi') }} {{ $ticket->name }},</p>
<p>
    {{ __('Your ticket request for') }}
    {{ app()->getLocale() === 'ar' ? $ticket->event->name_ar : $ticket->event->name_en }}
    {{ __('has been approved.') }}
</p>
<p>{{ __('Reference') }}: {{ $ticket->ticket_number }}</p>
<p>{{ __('Click the link below to confirm your payment and receive your ticket:') }}</p>
<p><a href="{{ $paymentUrl }}">{{ __('Confirm payment and get my ticket') }}</a></p>