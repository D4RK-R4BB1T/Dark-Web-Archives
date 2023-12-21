<!-- layouts/navbar/user-right -->
<ul class="nav navbar-nav navbar-right">
    @if ($unreadNotifications->count() > 0)
        <li style="margin-right: -10px;">
            <a>
                @component('layouts.components.component-modal-toggle', ['id' => 'notifications'])
                    <i class="glyphicon glyphicon-bell @if ($unreadNotifications->count() > 0) text-red @endif" style="top: 3px"></i>
                    <div class="badge red">{{ $unreadNotifications->count() }}</div>
                @endcomponent
            </a>
        </li>
    @endif
    <li class="dropdown">
        <a>
            {!! Auth::user()->getPublicDecoratedName() !!} <span class="glyphicon glyphicon-cog"></span>
        </a>
        <ul class="dropdown-menu orange" role="menu">
            <li role="presentation"><a role="menuitem" tabindex="-1" href="/settings">{{ __('layout.Settings') }}</a></li>
            <li role="presentation"><a role="menuitem" tabindex="0" href="{{ Auth::user()->isAdmin() ? '/admin' : '' }}/ticket">{{ __('feedback.Tickets') }}</a></li>
            <li role="separator" class="divider"></li>
            <li role="presentation">
                <a href="#" class="no-padding">
                @component('layouts.components.component-modal-toggle', ['id' => 'logout', 'class' => 'modal-logout-link'])
                    {{ __('layout.Log out') }}
                @endcomponent
                </a>
            </li>
        </ul>
    </li>
</ul>
<!-- / layouts/navbar/user-right -->