<ul class="nav navbar-nav navbar-right">
    <li class="dropdown cursor-pointer">
        <a>
            {!! Auth::user()->getPublicDecoratedName() !!}
        </a>

        <ul class="dropdown-menu orange" role="menu">
            <li role="presentation">
                <a href="#" class="no-padding">
                @component('layouts.components.component-modal-toggle', ['id' => 'logout', 'class' => 'modal-logout-link'])
                    Выйти
                @endcomponent
                </a>
            </li>
        </ul>
    </li>
</ul>
