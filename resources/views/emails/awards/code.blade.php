{{-- resources/views/emails/awards/code.blade.php --}}
@extends('emails.layout')

@section('preview', __('Your voting code: :code', ['code' => $code]))

@section('content')
    <h1 style="margin:0 0 16px;font-size:24px;font-weight:800;line-height:1.35;color:#171f22;">{{ __('Confirm your vote') }}</h1>

    <p style="margin:0 0 24px;font-size:15px;line-height:1.8;color:#4a4a4a;">
        {{ __('Enter this code on the voting page to confirm your vote for :nominee in :award.', ['nominee' => $nominee->name(), 'award' => $award->name()]) }}
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:0 0 24px;background:#faf9ff;border:1px solid #e6e4f2;border-radius:12px;">
        <tr>
            <td align="center" style="padding:20px;">
                <p style="margin:0 0 6px;font-size:11px;letter-spacing:0.08em;color:#8b8b8b;">{{ __('Voting code') }}</p>
                <p dir="ltr" style="margin:0;font-size:32px;font-weight:800;color:#3c3489;letter-spacing:0.3em;"><bdi>{{ $code }}</bdi></p>
            </td>
        </tr>
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:0 0 20px;">
        <tr>
            <td style="padding:0 0 8px;">
                <p style="margin:0 0 2px;font-size:11px;letter-spacing:0.08em;color:#8b8b8b;">{{ __('Award') }}</p>
                <p style="margin:0;font-size:15px;font-weight:700;color:#171f22;">{{ $award->name() }}</p>
            </td>
        </tr>
        <tr>
            <td>
                <p style="margin:0 0 2px;font-size:11px;letter-spacing:0.08em;color:#8b8b8b;">{{ __('Nominee') }}</p>
                <p style="margin:0;font-size:15px;font-weight:700;color:#171f22;">{{ $nominee->name() }}</p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px;font-size:13px;line-height:1.7;color:#6b6b6b;">
        {{ __('This code expires in 10 minutes and works once.') }}
    </p>
    <p style="margin:0;font-size:13px;line-height:1.7;color:#6b6b6b;">
        {{ __('If you did not ask to vote, you can ignore this email.') }}
    </p>
@endsection
