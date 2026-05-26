{{-- Responsive card header: title content on the left, optional action buttons
     (via the `actions` slot) on the right. On narrow screens the two clusters
     stack vertically and the buttons wrap instead of overflowing. --}}
<div {{ $attributes->merge(['class' => 'card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2']) }}>
    <div>{{ $slot }}</div>
    @if(isset($actions) && ! $actions->isEmpty())
        <div class="d-flex flex-wrap gap-2">{{ $actions }}</div>
    @endif
</div>
