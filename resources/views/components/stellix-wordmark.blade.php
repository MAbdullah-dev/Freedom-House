{{-- Provisional typography until official Stellix Soft artwork is supplied. This is not a company logo. --}}
@props(['href' => null])

<a href="{{ $href ?? route('home') }}" {{ $attributes->merge(['class' => 'stellix-wordmark']) }} aria-label="Stellix Soft LMS">
    <span class="stellix-wordmark__letters" aria-hidden="true">S</span>
    <span class="stellix-wordmark__copy">
        <span class="stellix-wordmark__name">Stellix Soft</span>
        <span class="stellix-wordmark__product">LMS</span>
    </span>
</a>
