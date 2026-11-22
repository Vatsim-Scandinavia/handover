{{-- Says who runs this Handover and credits the software. Pass :version="true" to show the version, on management pages only. --}}
@props(['version' => false])
<footer {{ $attributes->merge(['class' => 'site-footer']) }}>
    Run by {{ config('app.owner') }}
    &middot;
    <a href="https://github.com/Vatsim-Scandinavia/handover" target="_blank" rel="noopener noreferrer">Handover{{ $version ? ' '.config('app.version') : '' }}</a> by <a href="https://vatsim-scandinavia.org" target="_blank" rel="noopener noreferrer">VATSIM Scandinavia</a>
</footer>
