{{-- The header every management page opens with: a breadcrumb trail, the
     page's h1 with any badges (the slot), and actions on the right. The meta
     slot sits under the title; the lead slot says what the page is for.
     Crumbs are [label, href] pairs, from the top level down. --}}
@props(['title', 'crumbs' => []])
<header {{ $attributes->class('page-header') }}>
    @if($crumbs)
    <nav aria-label="Breadcrumb">
        <ol class="page-crumbs">
            @foreach($crumbs as [$label, $href])
            <li><a href="{{ $href }}">{{ $label }}</a></li>
            @endforeach
        </ol>
    </nav>
    @endif
    <div class="page-header-row">
        <div class="page-title">
            <h1>{{ $title }}</h1>
            {{ $slot }}
        </div>
        @if(isset($actions) && ! $actions->isEmpty())
        <div class="page-actions">{{ $actions }}</div>
        @endif
    </div>
    @isset($meta)
    <div class="page-meta">{{ $meta }}</div>
    @endisset
    @isset($lead)
    <p class="page-lead">{{ $lead }}</p>
    @endisset
</header>
