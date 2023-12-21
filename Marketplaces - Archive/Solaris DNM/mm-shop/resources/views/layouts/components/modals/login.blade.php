<!-- layouts/components/modals/login -->
@component('layouts.components.component-modal', ['id' => 'login'])
    @slot('title', 'Вход в аккаунт')

    <form class="form-horizontal" role="form" method="POST" action="{{ url('/auth/login') }}">
        {{ csrf_field() }}
        <input type="hidden" name="catalog_login" value="true">
        <input type="hidden" name="redirect_to" value="/auth/login" />
        <input type="hidden" name="redirect_after_login" value="{{ request()->getRequestUri() }}" />
        <div class="text-center">
            <span class="text-muted">Общий аккаунт Solaris позволяет синхронизировать покупки из разных магазинов в одном месте и входить в любой магазин без регистрации.</span>
            <br /><br />
            <input type="submit" class="btn btn-orange" value="Войти используя аккаунт Solaris">
        </div>
        <hr />
    </form>

    <form class="form-horizontal" role="form" action="{{ url('/auth/login') }}" method="post">
        {{ csrf_field() }}
        <input type="hidden" name="redirect_to" value="/auth/login" />
        <input type="hidden" name="redirect_after_login" value="{{ request()->getRequestUri() }}" />
        <div class="form-group">
            <div class="col-xs-24">
                <input id="username" type="username" class="form-control" name="username" placeholder="Имя пользователя" required>
            </div>
        </div>
        <div class="form-group">
            <div class="col-md-24">
                <input id="password" type="password" class="form-control" name="password" placeholder="Пароль" required>
            </div>
        </div>
        @slot('footer_btn_before')
            <button type="submit" class="btn btn-orange">Вход</button>
        @endslot
        @slot('footer_btn', 'Закрыть')
@endcomponent
    </form>
<!-- / layouts/components/modals/login -->