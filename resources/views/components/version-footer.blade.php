{{-- App version footer: links the app name and version to the repository. --}}
<div {{ $attributes->merge(['class' => 'text-white opacity-50 mt-4']) }} style="font-size: 11px;">
    <a href="https://github.com/Vatsim-Scandinavia/handover" class="text-white text-decoration-none" target="_blank" rel="noopener noreferrer">Handover v{{ config('app.version') }}</a>
</div>
