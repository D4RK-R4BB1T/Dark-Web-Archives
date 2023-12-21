<!-- layouts/navbar/guest-right -->
<ul class="nav navbar-nav navbar-right">
    <li><a href="{{ url("/auth/register") }}">Регистрация</a></li>
    <li>
        @if (request()->is('auth/*'))
            <a href="{{ url("/auth/login") }}">Войти</a>
        @else
            <a href="#" class="no-padding">
            @component('layouts.components.component-modal-toggle', ['id' => 'login', 'class' => 'modal-login-link'])
                Войти
            @endcomponent
            </a>
        @endif
    </li>
</ul>
<!-- / layouts/navbar/guest-right -->