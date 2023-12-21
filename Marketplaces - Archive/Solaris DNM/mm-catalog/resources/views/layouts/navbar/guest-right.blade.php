<!-- layouts/navbar/guest-right -->
<ul class="nav navbar-nav navbar-right">
    <li><a href="/auth/register">{{ __('layout.Register') }}</a></li>
    <li>
        @if (request()->is('auth/*'))
            <a href="/auth/login">{{ __('layout.Log in') }}</a>
        @else
            <a href="#" class="no-padding">
            @component('layouts.components.component-modal-toggle', ['id' => 'login', 'class' => 'modal-login-link'])
                {{ __('layout.Log in') }}
            @endcomponent
            </a>
        @endif
    </li>
</ul>
<!-- / layouts/navbar/guest-right -->