{{-- A small button that copies a value to the clipboard. The value itself
     only shows in the button's tooltip; the slot is the button's text. --}}
@props(['value', 'label', 'hint' => null])
<button type="button" {{ $attributes->class('copy-button') }} x-data="{ copied: false }"
    title="{{ $label }}&#10;{{ $value }}@if($hint)&#10;{{ $hint }}@endif" aria-label="Copy {{ $label }} {{ $value }}"
    x-on:click="navigator.clipboard.writeText(@js((string) $value)).then(() => { copied = true; setTimeout(() => copied = false, 1500) })">
    <i class="fa-regular fa-copy" x-show="!copied" aria-hidden="true"></i>
    <i class="fa-solid fa-check" x-show="copied" x-cloak aria-hidden="true"></i>
    {{ $slot }}
    <span class="visually-hidden" role="status" x-text="copied ? 'Copied' : ''"></span>
</button>
