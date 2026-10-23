{{-- resources/views/awards/partials/vote-window.blade.php --}}
{{-- The four-step vote window, driven by resources/js/award-voting.js: confirm the choice,
     email + security check, the emailed code, done. --}}
@php
    $voteWindowStrings = [
        'confirm' => __('You are voting for :nominee in :award.'),
        'genericError' => __('Something went wrong. Please try again.'),
        'tooManyAttempts' => __('Too many attempts. Please wait a minute and try again.'),
        'codeSentTo' => __('We sent a six-digit code to :email.'),
    ];
@endphp
<script type="application/json" data-award-voting-strings>@json($voteWindowStrings)</script>

<div class="fixed inset-0 z-100" data-vote-window hidden>
    <div class="fixed inset-0 bg-black/70" data-vote-close></div>

    <div class="fixed inset-0 overflow-y-auto p-4" data-vote-backdrop>
        <div class="flex min-h-full items-center justify-center" data-vote-backdrop>
            <div
                class="relative w-full max-w-md rounded-2xl border border-white/10 bg-ccs-black p-6 md:p-8 my-8"
                role="dialog"
                aria-modal="true"
                aria-labelledby="vote-window-title"
                data-vote-dialog
            >
                <div class="mb-6 flex items-center justify-between gap-4">
                    <h2 id="vote-window-title" class="font-display text-2xl font-extrabold">{{ __('Cast your vote') }}</h2>
                    <button type="button" data-vote-close class="text-2xl leading-none text-gray-400 transition-colors hover:text-white" aria-label="{{ __('Close') }}">&times;</button>
                </div>

                <p class="mb-4 text-sm font-bold text-red-400" role="alert" data-vote-feedback hidden></p>

                {{-- Step 1: confirm the choice --}}
                <div data-vote-step="confirm">
                    <p class="text-gray-300 leading-relaxed" data-vote-summary></p>
                    <p class="mt-3 text-sm text-gray-400">{{ __('We will email you a code to confirm it. One vote per email in each award.') }}</p>
                    <button type="button" class="ccs-form-submit mt-6" data-vote-continue>{{ __('Continue') }}</button>
                </div>

                {{-- Step 2: email + Cloudflare check --}}
                <form class="ccs-form" data-vote-step="email" novalidate hidden>
                    <div>
                        <label for="vote-email" class="ccs-form-label">{{ __('Email') }} <span class="ccs-form-required" aria-hidden="true">*</span></label>
                        <input id="vote-email" type="email" name="email" placeholder="{{ __('name@example.com') }}" autocomplete="email" dir="ltr" aria-required="true" aria-describedby="vote-error-email" class="ccs-form-input">
                        <p id="vote-error-email" class="ccs-form-error hidden" data-error-for="email"></p>
                    </div>

                    <div>
                        <div class="min-h-[65px]" data-turnstile-slot></div>
                        <p class="ccs-form-error hidden" data-error-for="captcha"></p>
                    </div>

                    <button type="submit" class="ccs-form-submit">{{ __('Send code') }}</button>
                </form>

                {{-- Step 3: the emailed code --}}
                <form class="ccs-form" data-vote-step="code" novalidate hidden>
                    <p class="text-sm text-gray-300 leading-relaxed" data-vote-code-sent></p>

                    <div>
                        <label for="vote-code" class="ccs-form-label">{{ __('Six-digit code') }} <span class="ccs-form-required" aria-hidden="true">*</span></label>
                        <input id="vote-code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]*" dir="ltr" aria-required="true" aria-describedby="vote-error-code" class="ccs-form-input text-center font-display text-2xl tracking-[0.5em]">
                        <p id="vote-error-code" class="ccs-form-error hidden" data-error-for="code"></p>
                    </div>

                    <button type="submit" class="ccs-form-submit">{{ __('Confirm vote') }}</button>

                    <button type="button" class="self-center text-sm font-semibold text-gray-400 underline underline-offset-4 hover:text-white" data-vote-resend>{{ __('Resend code') }}</button>
                </form>

                {{-- Step 4: done --}}
                <div class="text-center" data-vote-step="done" hidden>
                    <span aria-hidden="true" class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full border border-ccs-gold text-2xl text-ccs-gold">&#10003;</span>
                    <p class="font-display text-xl font-extrabold" data-vote-done-message tabindex="-1"></p>
                    <button type="button" class="ccs-form-submit mt-6" data-vote-close>{{ __('Done') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>
