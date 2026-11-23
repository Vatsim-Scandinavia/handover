{{-- A small × that sends DELETE to $action. $label says what it removes, for
     the tooltip and screen readers. $confirm, if set, asks first. --}}
@props(['action', 'label', 'confirm' => null])
<form method="POST" action="{{ $action }}" class="d-inline"@if($confirm) onsubmit="return confirm(@js($confirm))"@endif>
    @csrf @method('DELETE')
    <button {{ $attributes->class('remove-button') }} title="{{ $label }}" aria-label="{{ $label }}">
        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
    </button>
</form>
