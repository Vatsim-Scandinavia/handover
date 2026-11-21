{{-- Theme Toggle (cycles system -> light -> dark) --}}
<button type="button" class="nav-link d-inline {{ $class ?? 'text-white' }}" id="theme-toggle" title="Theme preference" data-theme-url="{{ route('user.settings.theme') }}">
    <i class="fas fa-fw fa-desktop" data-theme-icon></i>
    <span class="visually-hidden">Change theme</span>
</button>
