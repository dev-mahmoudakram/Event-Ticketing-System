{{-- resources/views/components/payment-logos.blade.php --}}
{{-- The card schemes the payment gateway accepts, shown where buyers and gateway reviewers
     expect them. Simple marks drawn inline — no image files or icon packages. --}}
<div data-payment-logos class="flex items-center gap-2" aria-label="{{ __('We accept Visa, Mastercard and Meeza') }}" role="img">
    <svg width="46" height="28" viewBox="0 0 46 28" aria-hidden="true"><rect width="46" height="28" rx="5" fill="#ffffff"/><text x="23" y="19" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" font-weight="700" font-style="italic" fill="#1a1f71">VISA</text></svg>
    <svg width="46" height="28" viewBox="0 0 46 28" aria-hidden="true"><rect width="46" height="28" rx="5" fill="#ffffff"/><circle cx="19" cy="14" r="8" fill="#eb001b"/><circle cx="27" cy="14" r="8" fill="#f79e1b" fill-opacity="0.9"/></svg>
    <svg width="46" height="28" viewBox="0 0 46 28" aria-hidden="true"><rect width="46" height="28" rx="5" fill="#ffffff"/><text x="23" y="18" text-anchor="middle" font-family="Arial, sans-serif" font-size="10" font-weight="700" fill="#0b6e4f">meeza</text></svg>
</div>
