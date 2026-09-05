{{-- resources/views/tickets/partials/ticket.blade.php --}}
@php
    use App\Support\MailImage;
    use App\Support\SiteText;

    $event = $ticket->event;
    $isArabic = app()->getLocale() === 'ar';
    $eventName = $isArabic ? $event->name_ar : $event->name_en;
    $venue = $isArabic ? $event->venue_name_ar : $event->venue_name_en;
    $ticketTypeName = $ticket->ticketType
        ? ($isArabic ? $ticket->ticketType->name_ar : $ticket->ticketType->name_en)
        : null;

    // The event's own mark heads the ticket; the platform signs the bottom of it.
    // In an email these become attachments, so the marks survive image blocking.
    $eventLogo = MailImage::embed($message ?? null, $event->logoUrl());
    $hubLogo = MailImage::embed($message ?? null, SiteText::image('branding', 'nav_logo') ?? asset('images/creators-hub/mark.png'));

    $dates = $event->start_date->translatedFormat('j M').' – '.$event->end_date->translatedFormat('j M Y');

    // Inline styles throughout: this same ticket is sent as an email, where stylesheets are
    // unreliable, and printed from the browser, where backgrounds are often dropped.
    $label = 'margin:0 0 4px;font-size:11px;letter-spacing:0.08em;color:#8b8b8b;';
    $value = 'margin:0 0 18px;font-size:16px;font-weight:700;color:#171f22;';
@endphp

<table role="presentation" cellpadding="0" cellspacing="0" border="0" dir="{{ $isArabic ? 'rtl' : 'ltr' }}" style="width:100%;max-width:620px;margin:0 auto;border-collapse:separate;border-spacing:0;background:#ffffff;border:1px solid #e6e4f2;border-radius:18px;overflow:hidden;font-family:{{ $isArabic ? "'Cairo', Tahoma, sans-serif" : "'Manrope', Arial, sans-serif" }};">
    <tr>
        <td style="background:#171f22;padding:22px 26px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%;">
                <tr>
                    <td style="vertical-align:middle;">
                        @if($eventLogo)
                            <img src="{{ $eventLogo }}" alt="{{ $eventName }}" height="38" style="height:38px;width:auto;max-width:210px;display:block;">
                        @else
                            <span style="font-size:20px;font-weight:800;color:#ffffff;">{{ $eventName }}</span>
                        @endif
                    </td>
                    <td style="vertical-align:middle;text-align:{{ $isArabic ? 'left' : 'right' }};">
                        <span style="font-size:11px;letter-spacing:0.16em;color:#7ccbcf;">{{ __('Admission Ticket') }}</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td style="padding:0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%;">
                <tr>
                    <td style="padding:26px;vertical-align:top;">
                        <p style="{{ $label }}">{{ __('Attendee') }}</p>
                        <p style="{{ $value }}">{{ $ticket->name }}</p>

                        @if($ticketTypeName)
                            <p style="{{ $label }}">{{ __('Ticket Type') }}</p>
                            <p style="{{ $value }}">{{ $ticketTypeName }}</p>
                        @endif

                        <p style="{{ $label }}">{{ __('Event') }}</p>
                        <p style="{{ $value }}">
                            {{ $eventName }}<br>
                            <span style="font-weight:400;font-size:14px;color:#5c5c5c;">
                                {{ $dates }}@if($venue) · {{ $venue }}@endif
                            </span>
                        </p>

                        <p style="{{ $label }}">{{ __('Reference') }}</p>
                        <p style="margin:0;font-size:16px;font-weight:700;color:#3c3489;letter-spacing:0.04em;"><bdi>{{ $ticket->ticket_number }}</bdi></p>

                        @if($ticket->workshop_booking_key)
                            <p style="{{ $label }}margin-top:18px;">{{ __('Workshop Booking Key') }}</p>
                            <p style="margin:0;font-size:16px;font-weight:700;color:#3c3489;letter-spacing:0.04em;"><bdi>{{ $ticket->workshop_booking_key }}</bdi></p>
                        @endif
                    </td>

                    {{-- The stub. Dashed rule stands in for the perforation. --}}
                    <td width="200" style="width:200px;padding:26px 22px;text-align:center;vertical-align:middle;border-{{ $isArabic ? 'right' : 'left' }}:2px dashed #e6e4f2;background:#faf9ff;">
                        @if($qrSrc)
                            <img src="{{ $qrSrc }}" alt="{{ __('Ticket QR code') }}" width="150" style="width:150px;height:150px;display:block;margin:0 auto 12px;">
                        @endif
                        <p style="margin:0;font-size:12px;line-height:1.6;color:#5c5c5c;">{{ __('Show this at the entrance to check in.') }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td style="padding:16px 26px;border-top:1px solid #e6e4f2;background:#ffffff;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%;">
                <tr>
                    <td style="vertical-align:middle;font-size:11px;color:#8b8b8b;">
                        {{ __('This ticket admits one person. Keep it safe.') }}
                    </td>
                    <td style="vertical-align:middle;text-align:{{ $isArabic ? 'left' : 'right' }};white-space:nowrap;">
                        <span style="font-size:11px;color:#8b8b8b;vertical-align:middle;">{{ __('Powered by') }}</span>
                        <img src="{{ $hubLogo }}" alt="Creators Hub" height="20" style="height:20px;width:auto;max-width:120px;vertical-align:middle;margin-{{ $isArabic ? 'right' : 'left' }}:8px;">
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
