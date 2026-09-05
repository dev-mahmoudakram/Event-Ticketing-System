{{-- resources/views/home/partials/faq-illustration.blade.php --}}
{{-- Decorative: a small stack of answered and unanswered questions, drawn from the same
     shapes the accordion beside it uses. Hidden from assistive tech, which reads the real
     questions instead. --}}
<svg viewBox="0 0 420 400" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full max-w-sm mx-auto text-hub-purple" role="presentation" aria-hidden="true">
    <rect x="18" y="18" width="300" height="80" rx="26" fill="currentColor" fill-opacity="0.06" stroke="currentColor" stroke-opacity="0.2" stroke-width="2"/>
    <rect x="48" y="50" width="150" height="8" rx="4" fill="currentColor" fill-opacity="0.35"/>
    <circle cx="278" cy="58" r="15" stroke="currentColor" stroke-opacity="0.3" stroke-width="2"/>
    <path d="M271 58h14M278 51v14" stroke="currentColor" stroke-opacity="0.55" stroke-width="2" stroke-linecap="round"/>

    {{-- The open one: a question with its answer showing. --}}
    <rect x="18" y="118" width="300" height="150" rx="26" fill="currentColor" fill-opacity="0.1" stroke="currentColor" stroke-opacity="0.45" stroke-width="2.5"/>
    <rect x="48" y="150" width="185" height="8" rx="4" fill="currentColor" fill-opacity="0.55"/>
    <circle cx="278" cy="158" r="15" stroke="currentColor" stroke-opacity="0.45" stroke-width="2"/>
    <path d="M271 158h14" stroke="currentColor" stroke-opacity="0.7" stroke-width="2" stroke-linecap="round"/>
    <rect x="48" y="192" width="230" height="6" rx="3" fill="currentColor" fill-opacity="0.22"/>
    <rect x="48" y="212" width="200" height="6" rx="3" fill="currentColor" fill-opacity="0.22"/>
    <rect x="48" y="232" width="140" height="6" rx="3" fill="currentColor" fill-opacity="0.22"/>

    <rect x="18" y="288" width="300" height="80" rx="26" fill="currentColor" fill-opacity="0.06" stroke="currentColor" stroke-opacity="0.2" stroke-width="2"/>
    <rect x="48" y="320" width="120" height="8" rx="4" fill="currentColor" fill-opacity="0.35"/>
    <circle cx="278" cy="328" r="15" stroke="currentColor" stroke-opacity="0.3" stroke-width="2"/>
    <path d="M271 328h14M278 321v14" stroke="currentColor" stroke-opacity="0.55" stroke-width="2" stroke-linecap="round"/>

    {{-- The badge that lifts the whole thing off the stack. --}}
    <circle cx="340" cy="112" r="46" fill="currentColor"/>
    <path d="M330 100c0-6 5-11 11-11s11 4 11 10c0 7-8 8-10 13v3" stroke="#fff" stroke-width="5" stroke-linecap="round" fill="none"/>
    <circle cx="341" cy="128" r="3.5" fill="#fff"/>
</svg>
