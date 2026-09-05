{{-- resources/views/emails/layout.blade.php --}}
@php
    use App\Support\MailImage;
    use App\Support\SiteText;

    $isArabic = app()->getLocale() === 'ar';
    $event = $event ?? ($ticket->event ?? null);
    $eventName = $event ? ($isArabic ? $event->name_ar : $event->name_en) : null;

    // The event's mark heads the message it belongs to; the platform signs the bottom of it.
    $eventLogo = MailImage::embed($message ?? null, $event?->logoUrl());
    $hubLogo = MailImage::embed($message ?? null, SiteText::image('branding', 'nav_logo') ?? asset('images/creators-hub/mark.png'));

    $contactEmail = $event?->contact_email ?? SiteText::stored('contact_details', 'email');
    $contactPhone = $event?->contact_phone ?? SiteText::stored('contact_details', 'phone');

    // Mail clients drop stylesheets, so every rule is inline and the layout is tables.
    $font = $isArabic ? "'Cairo', Tahoma, sans-serif" : "'Manrope', Arial, sans-serif";
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $eventName ?? 'Creators Hub')</title>
</head>
<body style="margin:0;padding:0;background:#f1f0fa;">
    {{-- Shown in the inbox list beside the subject, then hidden in the message itself. --}}
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">@yield('preview')</div>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%;background:#f1f0fa;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" dir="{{ $isArabic ? 'rtl' : 'ltr' }}" style="width:100%;max-width:620px;border-collapse:separate;border-spacing:0;background:#ffffff;border-radius:18px;overflow:hidden;font-family:{{ $font }};color:#171f22;">
                    <tr>
                        <td style="background:#171f22;padding:20px 26px;">
                            @if($eventLogo)
                                <img src="{{ $eventLogo }}" alt="{{ $eventName }}" height="34" style="height:34px;width:auto;max-width:220px;display:block;">
                            @elseif($eventName)
                                <span style="font-size:19px;font-weight:800;color:#ffffff;">{{ $eventName }}</span>
                            @else
                                <img src="{{ $hubLogo }}" alt="Creators Hub" height="30" style="height:30px;width:auto;max-width:200px;display:block;">
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px 26px 8px;">
                            @yield('content')
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 26px 26px;">
                            @if($contactEmail || $contactPhone)
                                <p style="margin:0 0 16px;font-size:13px;line-height:1.8;color:#6b6b6b;">
                                    {{ __('Any questions? Reply to this email or reach us at:') }}
                                    @if($contactEmail)
                                        <br><a href="mailto:{{ $contactEmail }}" style="color:#3c3489;text-decoration:none;">{{ $contactEmail }}</a>
                                    @endif
                                    @if($contactPhone)
                                        <br><span dir="ltr">{{ $contactPhone }}</span>
                                    @endif
                                </p>
                            @endif

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-top:1px solid #e6e4f2;">
                                <tr>
                                    <td style="padding-top:16px;vertical-align:middle;font-size:11px;color:#9a9a9a;">
                                        &copy; {{ now()->year }} {{ $eventName ?? 'Creators Hub' }}
                                    </td>
                                    <td style="padding-top:16px;vertical-align:middle;text-align:{{ $isArabic ? 'left' : 'right' }};white-space:nowrap;">
                                        <span style="font-size:11px;color:#9a9a9a;vertical-align:middle;">{{ __('Powered by') }}</span>
                                        <img src="{{ $hubLogo }}" alt="Creators Hub" height="18" style="height:18px;width:auto;max-width:110px;vertical-align:middle;margin-{{ $isArabic ? 'right' : 'left' }}:8px;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
