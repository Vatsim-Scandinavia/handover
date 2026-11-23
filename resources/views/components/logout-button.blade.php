{{-- Handover's logout button. It posts to the end session endpoint with the
     CSRF token, so the member logs out without a confirmation page. Other
     attributes go on the button. --}}
@props(['inline' => false])
<form method="POST" action="{{ route('oidc.logout') }}" @class(['d-inline' => $inline])>
    @csrf
    <button type="submit" {{ $attributes }}>Log out</button>
</form>
